<?php

namespace App\Http\Controllers\Api\Prism;

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
            $request->user()->can('manage_attendance') || $request->user()->can('view_attendance_reports'),
            403
        );

        $q = AttendanceBatch::query()
            ->with(['schoolClass:id,name', 'section:id,name'])
            ->withCount('records');

        if ($request->filled('school_class_id')) {
            $q->where('school_class_id', $request->query('school_class_id'));
        }
        if ($request->filled('section_id')) {
            $q->where('section_id', $request->query('section_id'));
        }
        if ($request->filled('date')) {
            $q->whereDate('date', $request->query('date'));
        }

        return response()->json($q->orderByDesc('date')->paginate(min((int) $request->query('per_page', 20), 50)));
    }

    public function store(Request $request, NotificationDispatchService $dispatchService)
    {
        abort_unless($request->user()->can('manage_attendance'), 403);

        $data = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'required|exists:sections,id',
            'date' => 'required|date',
            'records' => 'required|array|min:1',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.status' => 'required|in:present,absent,late,excused',
        ]);

        $batch = DB::transaction(function () use ($data, $request, $dispatchService) {
            $batch = AttendanceBatch::updateOrCreate(
                [
                    'school_class_id' => $data['school_class_id'],
                    'section_id' => $data['section_id'],
                    'date' => $data['date'],
                ],
                ['submitted_by_user_id' => $request->user()->id]
            );

            $batch->records()->delete();

            $absentIds = [];
            foreach ($data['records'] as $row) {
                AttendanceRecord::create([
                    'attendance_batch_id' => $batch->id,
                    'student_id' => $row['student_id'],
                    'status' => $row['status'],
                ]);
                if ($row['status'] === 'absent') {
                    $absentIds[] = (int) $row['student_id'];
                }
            }

            if ($absentIds !== []) {
                $dispatchService->create(
                    NotificationFeatureKeys::ATTENDANCE_ABSENT,
                    'AttendanceBatch',
                    $batch->id,
                    'class',
                    ['student_ids' => array_values(array_unique($absentIds))],
                    [
                        'title' => 'Attendance notice',
                        'body' => 'One or more students were marked absent on '.$batch->date->format('M j, Y').'. Please check the app.',
                        'data' => [
                            'type' => 'attendance_absent',
                            'attendance_batch_id' => $batch->id,
                            'student_ids' => $absentIds,
                        ],
                    ],
                    (int) $batch->school_class_id,
                    (int) $batch->section_id,
                    $request->user()->id,
                );
            }

            return $batch->load(['records.student:id,first_name,last_name']);
        });

        return response()->json($batch, 201);
    }

    public function show(Request $request, AttendanceBatch $attendanceBatch)
    {
        abort_unless(
            $request->user()->can('manage_attendance') || $request->user()->can('view_attendance_reports'),
            403
        );

        $attendanceBatch->load(['records.student:id,first_name,last_name', 'schoolClass:id,name', 'section:id,name']);

        return response()->json($attendanceBatch);
    }

    public function reportMonthly(Request $request)
    {
        abort_unless($request->user()->can('view_attendance_reports'), 403);

        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'month' => 'required|date_format:Y-m',
        ]);

        if ($request->user()->hasRole('parent')) {
            abort_unless($request->user()->children()->where('students.id', $data['student_id'])->exists(), 403);
        }

        $start = \Carbon\Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $rows = AttendanceRecord::query()
            ->join('attendance_batches', 'attendance_records.attendance_batch_id', '=', 'attendance_batches.id')
            ->where('attendance_records.student_id', $data['student_id'])
            ->whereBetween('attendance_batches.date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('attendance_batches.date as date, attendance_records.status as status')
            ->orderBy('attendance_batches.date')
            ->get();

        return response()->json(['student_id' => (int) $data['student_id'], 'month' => $data['month'], 'days' => $rows]);
    }

    public function reportWeekly(Request $request)
    {
        abort_unless($request->user()->can('view_attendance_reports'), 403);

        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'week_start' => 'required|date',
        ]);

        if ($request->user()->hasRole('parent')) {
            abort_unless($request->user()->children()->where('students.id', $data['student_id'])->exists(), 403);
        }

        $start = \Carbon\Carbon::parse($data['week_start'])->startOfDay();
        $end = (clone $start)->addDays(6)->endOfDay();

        $rows = AttendanceRecord::query()
            ->join('attendance_batches', 'attendance_records.attendance_batch_id', '=', 'attendance_batches.id')
            ->where('attendance_records.student_id', $data['student_id'])
            ->whereBetween('attendance_batches.date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('attendance_batches.date as date, attendance_records.status as status')
            ->orderBy('attendance_batches.date')
            ->get();

        return response()->json(['student_id' => (int) $data['student_id'], 'days' => $rows]);
    }
}
