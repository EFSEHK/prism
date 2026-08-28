<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\AttendanceBatch;
use App\Models\AttendanceRecord;
use App\Models\Section;
use App\Models\Student;
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
            ->with([
                'section:id,name,school_class_id',
                'section.schoolClass:id,name,area_id',
                'section.schoolClass.area:id,name',
                'submittedBy:id,name',
            ])
            ->withCount('records');

        $this->applySectionScope($q, $request);

        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }
        if ($request->filled('status_in')) {
            $statuses = array_filter(explode(',', (string) $request->query('status_in')));
            if ($statuses !== []) {
                $q->whereIn('status', $statuses);
            }
        }
        if ($request->boolean('own_only')) {
            $q->where('submitted_by_user_id', $request->user()->id);
        }
        if ($request->filled('date')) {
            $q->whereDate('date', $request->query('date'));
        }

        return response()->json($q->orderByDesc('date')->paginate(min((int) $request->query('per_page', 50), 100)));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('mark_attendance'), 403);

        $data = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'date' => 'required|date',
            'records' => 'required|array|min:1',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.status' => 'required|in:present,absent,leave',
        ]);

        $this->assertStudentsInSection((int) $data['section_id'], collect($data['records'])->pluck('student_id')->all());

        $existing = AttendanceBatch::query()
            ->where('section_id', $data['section_id'])
            ->whereDate('date', $data['date'])
            ->first();

        if ($existing && in_array($existing->status, ['submitted', 'verified'], true)) {
            abort(422, 'This attendance has already been submitted or approved and cannot be changed.');
        }

        $batch = DB::transaction(function () use ($data, $request, $existing) {
            $batch = AttendanceBatch::updateOrCreate(
                [
                    'section_id' => $data['section_id'],
                    'date' => $data['date'],
                ],
                [
                    'submitted_by_user_id' => $request->user()->id,
                    'status' => 'draft',
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

            return $batch->load([
                'records.student:id,first_name,last_name,roll_no',
                'section:id,name,school_class_id',
                'section.schoolClass:id,name',
            ]);
        });

        return response()->json($batch, 201);
    }

    public function submit(Request $request, AttendanceBatch $attendanceBatch)
    {
        abort_unless($request->user()->can('mark_attendance'), 403);
        abort_unless($attendanceBatch->status === 'draft', 422, 'Only draft batches can be submitted for verification.');
        abort_unless(
            (int) $attendanceBatch->submitted_by_user_id === (int) $request->user()->id,
            403,
            'You can only submit attendance you marked.'
        );

        $attendanceBatch->update(['status' => 'submitted']);

        return response()->json($attendanceBatch->fresh([
            'records.student:id,first_name,last_name,roll_no',
            'section:id,name,school_class_id',
            'section.schoolClass:id,name',
        ]));
    }

    public function verify(Request $request, AttendanceBatch $attendanceBatch, NotificationDispatchService $dispatchService)
    {
        abort_unless($request->user()->can('verify_attendance'), 403);
        abort_unless($attendanceBatch->status === 'submitted', 422, 'Only submitted attendance can be approved.');

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
                $section = $attendanceBatch->section()->with('schoolClass')->first();
                $absentDate = $attendanceBatch->date->format('M j, Y');
                $dispatchService->create(
                    NotificationFeatureKeys::ATTENDANCE_ABSENT,
                    'AttendanceBatch',
                    $attendanceBatch->id,
                    'study_group',
                    ['student_ids' => array_values(array_unique($absentIds))],
                    [
                        'title' => 'Absent today',
                        'body' => 'Your child was marked absent on '.$absentDate.'.',
                        'student_ids' => array_values(array_unique($absentIds)),
                        'data' => [
                            'type' => 'attendance_absent',
                            'attendance_batch_id' => $attendanceBatch->id,
                            'date' => $absentDate,
                        ],
                    ],
                    areaId: $section?->schoolClass?->area_id,
                    schoolClassId: $section?->school_class_id,
                    sectionId: (int) $attendanceBatch->section_id,
                    createdByUserId: $request->user()->id,
                );
            }

            return $attendanceBatch->fresh([
                'records.student:id,first_name,last_name,roll_no',
                'section:id,name,school_class_id',
                'section.schoolClass:id,name',
            ]);
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

        $attendanceBatch->load([
            'records.student:id,first_name,last_name,roll_no',
            'section:id,name,school_class_id',
            'section.schoolClass:id,name,area_id',
            'section.schoolClass.area:id,name',
            'submittedBy:id,name',
            'verifiedBy:id,name',
        ]);

        return response()->json($attendanceBatch);
    }

    public function summary(Request $request)
    {
        abort_unless(
            $request->user()->can('view_attendance_reports') || $request->user()->can('mark_attendance') || $request->user()->can('verify_attendance'),
            403
        );

        $filters = $request->validate([
            'area_id' => 'nullable|exists:areas,id',
            'school_class_id' => 'nullable|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        if (! empty($filters['section_id'])) {
            return response()->json($this->summaryByStudents($filters));
        }

        if (empty($filters['area_id']) && empty($filters['school_class_id'])) {
            abort(422, 'Select an area or class for a cumulative summary, or select a section for per-student totals.');
        }

        return response()->json($this->summaryCumulative($filters));
    }

    private function summaryByStudents(array $filters): array
    {
        $students = Student::query()
            ->with(['section:id,name,school_class_id', 'section.schoolClass:id,name,area_id', 'section.schoolClass.area:id,name'])
            ->where('section_id', $filters['section_id'])
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'roll_no', 'section_id']);

        $studentIds = $students->pluck('id')->all();

        $counts = $this->summaryRecordsQuery($filters)
            ->whereIn('attendance_records.student_id', $studentIds)
            ->where('attendance_batches.section_id', $filters['section_id'])
            ->selectRaw('attendance_records.student_id, attendance_records.status, COUNT(*) as total')
            ->groupBy('attendance_records.student_id', 'attendance_records.status')
            ->get();

        $byStudent = [];
        foreach ($counts as $row) {
            $byStudent[$row->student_id][$row->status] = (int) $row->total;
        }

        $rows = $students->map(function (Student $student) use ($byStudent) {
            $tally = $byStudent[$student->id] ?? [];

            return [
                'student_id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'roll_no' => $student->roll_no,
                'section' => $student->section,
                'present' => $tally['present'] ?? 0,
                'absent' => $tally['absent'] ?? 0,
                'leave' => $tally['leave'] ?? 0,
            ];
        });

        return [
            'mode' => 'students',
            'students' => $rows,
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
        ];
    }

    private function summaryCumulative(array $filters): array
    {
        $base = $this->summaryRecordsQuery($filters);
        $this->applySummaryScope($base, $filters);

        $statusRows = (clone $base)
            ->selectRaw('attendance_records.status, COUNT(*) as total')
            ->groupBy('attendance_records.status')
            ->pluck('total', 'status');

        $meta = (clone $base)
            ->selectRaw('COUNT(DISTINCT attendance_records.student_id) as students')
            ->selectRaw('COUNT(DISTINCT attendance_batches.date) as school_days')
            ->selectRaw('COUNT(DISTINCT attendance_batches.section_id) as sections')
            ->first();

        $breakdownRows = (clone $base)
            ->join('sections', 'attendance_batches.section_id', '=', 'sections.id')
            ->join('school_classes', 'sections.school_class_id', '=', 'school_classes.id')
            ->selectRaw('sections.id as section_id, sections.name as section_name, school_classes.name as class_name, attendance_records.status, COUNT(*) as total')
            ->groupBy('sections.id', 'sections.name', 'school_classes.name', 'attendance_records.status')
            ->orderBy('school_classes.name')
            ->orderBy('sections.name')
            ->get();

        $bySection = [];
        foreach ($breakdownRows as $row) {
            $key = $row->section_id;
            if (! isset($bySection[$key])) {
                $bySection[$key] = ['present' => 0, 'absent' => 0, 'leave' => 0];
            }
            $bySection[$key][$row->status] = (int) $row->total;
        }

        $sectionsInScope = Section::query()
            ->with(['schoolClass:id,name,area_id', 'schoolClass.area:id,name'])
            ->when(
                ! empty($filters['school_class_id']),
                fn ($q) => $q->where('school_class_id', $filters['school_class_id'])
            )
            ->when(
                empty($filters['school_class_id']) && ! empty($filters['area_id']),
                fn ($q) => $q->whereHas(
                    'schoolClass',
                    fn ($sq) => $sq->where('area_id', $filters['area_id'])
                )
            )
            ->join('school_classes', 'sections.school_class_id', '=', 'school_classes.id')
            ->orderBy('school_classes.name')
            ->orderBy('sections.name')
            ->select('sections.id', 'sections.name', 'sections.school_class_id')
            ->get();

        $rows = $sectionsInScope->map(function (Section $section) use ($bySection) {
            $tally = $bySection[$section->id] ?? ['present' => 0, 'absent' => 0, 'leave' => 0];
            $present = (int) ($tally['present'] ?? 0);
            $absent = (int) ($tally['absent'] ?? 0);
            $leave = (int) ($tally['leave'] ?? 0);

            return [
                'section_id' => $section->id,
                'section_name' => $section->name,
                'class_name' => $section->schoolClass?->name ?? '—',
                'area_name' => $section->schoolClass?->area?->name ?? '—',
                'total' => $present + $absent + $leave,
                'present' => $present,
                'absent' => $absent,
                'leave' => $leave,
            ];
        })->values()->all();

        return [
            'mode' => 'cumulative',
            'totals' => [
                'present' => (int) ($statusRows['present'] ?? 0),
                'absent' => (int) ($statusRows['absent'] ?? 0),
                'leave' => (int) ($statusRows['leave'] ?? 0),
                'total' => (int) (($statusRows['present'] ?? 0) + ($statusRows['absent'] ?? 0) + ($statusRows['leave'] ?? 0)),
                'students' => (int) ($meta->students ?? 0),
                'school_days' => (int) ($meta->school_days ?? 0),
                'sections' => (int) ($meta->sections ?? 0),
            ],
            'by_section' => $rows,
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
        ];
    }

    private function summaryRecordsQuery(array $filters)
    {
        $q = AttendanceRecord::query()
            ->join('attendance_batches', 'attendance_records.attendance_batch_id', '=', 'attendance_batches.id')
            ->where('attendance_batches.status', 'verified');

        if (! empty($filters['from'])) {
            $q->whereDate('attendance_batches.date', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $q->whereDate('attendance_batches.date', '<=', $filters['to']);
        }

        return $q;
    }

    private function applySummaryScope($query, array $filters): void
    {
        if (! empty($filters['school_class_id'])) {
            $classId = (int) $filters['school_class_id'];
            $query->whereHas('batch.section', fn ($q) => $q->where('school_class_id', $classId));
        } elseif (! empty($filters['area_id'])) {
            $areaId = (int) $filters['area_id'];
            $query->whereHas('batch.section.schoolClass', fn ($q) => $q->where('area_id', $areaId));
        }
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

    private function applySectionScope($query, Request $request): void
    {
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->query('section_id'));
        } elseif ($request->filled('school_class_id')) {
            $classId = (int) $request->query('school_class_id');
            $query->whereHas('section', fn ($q) => $q->where('school_class_id', $classId));
        } elseif ($request->filled('area_id')) {
            $areaId = (int) $request->query('area_id');
            $query->whereHas('section.schoolClass', fn ($q) => $q->where('area_id', $areaId));
        }
    }

    private function assertStudentsInSection(int $sectionId, array $studentIds): void
    {
        $validCount = Student::query()
            ->where('section_id', $sectionId)
            ->whereIn('id', $studentIds)
            ->count();

        abort_unless($validCount === count(array_unique($studentIds)), 422, 'One or more students are not in the selected section.');
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
