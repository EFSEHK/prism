<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\AttendanceBatch;
use App\Models\AttendanceRecord;
use App\Services\Notifications\NotificationDispatchService;
use App\Support\NotificationFeatureKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            $request->user()->can('mark_attendance') || $request->user()->can('view_attendance_reports') || $request->user()->can('verify_attendance'),
            403
        );

        $q = AttendanceBatch::query()
            ->with(['studyGroup:id,name,section_id', 'submittedBy:id,name'])
            ->withCount('records');

        if ($request->filled('study_group_id')) {
            $q->where('study_group_id', $request->query('study_group_id'));
        }
        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }
        if ($request->filled('date')) {
            $q->whereDate('date', $request->query('date'));
        }

        return response()->json($q->orderByDesc('date')->paginate(min((int) $request->query('per_page', 20), 50)));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('mark_attendance'), 403);

        $data = $request->validate([
            'study_group_id' => 'required|exists:study_groups,id',
            'date' => 'required|date',
            'records' => 'required|array|min:1',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.status' => 'required|in:present,absent,late,excused',
        ]);

        $batch = DB::transaction(function () use ($data, $request) {
            $batch = AttendanceBatch::updateOrCreate(
                [
                    'study_group_id' => $data['study_group_id'],
                    'date' => $data['date'],
                ],
                [
                    'submitted_by_user_id' => $request->user()->id,
                    'status' => 'submitted',
                    'verified_by_user_id' => null,
                    'verified_at' => null,
                ]
            );

            $batch->records()->delete();

            foreach ($data['records'] as $row) {
                AttendanceRecord::create([
                    'attendance_batch_id' => $batch->id,
                    'student_id' => $row['student_id'],
                    'status' => $row['status'],
                ]);
            }

            return $batch->load(['records.student:id,first_name,last_name']);
        });

        return response()->json($batch, 201);
    }

    public function verify(Request $request, AttendanceBatch $attendanceBatch, NotificationDispatchService $dispatchService)
    {
        abort_unless($request->user()->can('verify_attendance'), 403);
        abort_unless($attendanceBatch->status === 'submitted', 422, 'Batch is not awaiting verification.');

        $batch = DB::transaction(function () use ($attendanceBatch, $request, $dispatchService) {
            $attendanceBatch->update([
                'status' => 'verified',
                'verified_by_user_id' => $request->user()->id,
                'verified_at' => now(),
            ]);

            $absentIds = $attendanceBatch->records()
                ->where('status', 'absent')
                ->pluck('student_id')
                ->all();

            if ($absentIds !== []) {
                $dispatchService->create(
                    NotificationFeatureKeys::ATTENDANCE_ABSENT,
                    'AttendanceBatch',
                    $attendanceBatch->id,
                    'study_group',
                    ['student_ids' => array_values(array_unique($absentIds))],
                    [
                        'title' => 'Attendance notice',
                        'body' => 'One or more students were marked absent on '.$attendanceBatch->date->format('M j, Y').'.',
                        'data' => [
                            'type' => 'attendance_absent',
                            'attendance_batch_id' => $attendanceBatch->id,
                        ],
                    ],
                    studyGroupId: (int) $attendanceBatch->study_group_id,
                    createdByUserId: $request->user()->id,
                );
            }

            return $attendanceBatch->fresh(['records.student:id,first_name,last_name']);
        });

        return response()->json($batch);
    }

    public function show(Request $request, AttendanceBatch $attendanceBatch)
    {
        abort_unless(
            $request->user()->can('mark_attendance') || $request->user()->can('view_attendance_reports') || $request->user()->can('verify_attendance'),
            403
        );

        if ($request->user()->hasAnyRole(['parent', 'student'])) {
            abort_unless($attendanceBatch->isVerified(), 403);
        }

        $attendanceBatch->load(['records.student:id,first_name,last_name', 'studyGroup:id,name']);

        return response()->json($attendanceBatch);
    }

    public function reportMonthly(Request $request)
    {
        abort_unless($request->user()->can('view_attendance_reports') || $request->user()->can('view_own_attendance'), 403);

        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'month' => 'required|date_format:Y-m',
        ]);

        $this->assertCanViewStudentAttendance($request, (int) $data['student_id']);

        $start = \Carbon\Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $rows = AttendanceRecord::query()
            ->join('attendance_batches', 'attendance_records.attendance_batch_id', '=', 'attendance_batches.id')
            ->where('attendance_records.student_id', $data['student_id'])
            ->where('attendance_batches.status', 'verified')
            ->whereBetween('attendance_batches.date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('attendance_batches.date as date, attendance_records.status as status')
            ->orderBy('attendance_batches.date')
            ->get();

        return response()->json(['student_id' => (int) $data['student_id'], 'month' => $data['month'], 'days' => $rows]);
    }

    public function reportWeekly(Request $request)
    {
        abort_unless($request->user()->can('view_attendance_reports') || $request->user()->can('view_own_attendance'), 403);

        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'week_start' => 'required|date',
        ]);

        $this->assertCanViewStudentAttendance($request, (int) $data['student_id']);

        $start = \Carbon\Carbon::parse($data['week_start'])->startOfDay();
        $end = (clone $start)->addDays(6)->endOfDay();

        $rows = AttendanceRecord::query()
            ->join('attendance_batches', 'attendance_records.attendance_batch_id', '=', 'attendance_batches.id')
            ->where('attendance_records.student_id', $data['student_id'])
            ->where('attendance_batches.status', 'verified')
            ->whereBetween('attendance_batches.date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('attendance_batches.date as date, attendance_records.status as status')
            ->orderBy('attendance_batches.date')
            ->get();

        return response()->json(['student_id' => (int) $data['student_id'], 'days' => $rows]);
    }

    private function assertCanViewStudentAttendance(Request $request, int $studentId): void
    {
        $user = $request->user();
        if ($user->hasRole('parent')) {
            abort_unless($user->children()->where('students.id', $studentId)->exists(), 403);
        } elseif ($user->hasRole('student')) {
            abort_unless($user->studentProfile?->id === $studentId, 403);
        }
    }
}
