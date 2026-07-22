<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\DatesheetEntry;
use App\Models\Student;
use App\Models\TimetableSlot;
use App\Services\Notifications\NotificationDispatchService;
use App\Support\NotificationFeatureKeys;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    public function slotsIndex(Request $request)
    {
        abort_unless(
            $request->user()->can('manage_timetable') || $request->user()->can('view_parent_dashboard') || $request->user()->can('view_student_dashboard'),
            403
        );

        $q = TimetableSlot::query()->with(['subject:id,name', 'studyGroup:id,name']);

        if ($request->user()->hasAnyRole(['parent', 'student'])) {
            $groupIds = $this->learnerStudyGroupIds($request);
            $q->whereIn('study_group_id', $groupIds);
        } elseif ($request->filled('study_group_id')) {
            $q->where('study_group_id', $request->query('study_group_id'));
        }

        return response()->json($q->orderBy('day_of_week')->orderBy('start_time')->paginate(50));
    }

    public function slotsStore(Request $request)
    {
        abort_unless($request->user()->can('manage_timetable'), 403);

        $data = $request->validate([
            'study_group_id' => 'required|exists:study_groups,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'room' => 'nullable|string|max:64',
        ]);

        return response()->json(
            TimetableSlot::create($data)->load(['subject:id,name', 'studyGroup:id,name']),
            201
        );
    }

    public function datesheetIndex(Request $request)
    {
        abort_unless(
            $request->user()->can('manage_timetable') || $request->user()->can('view_parent_dashboard') || $request->user()->can('view_student_dashboard'),
            403
        );

        $q = DatesheetEntry::query()->with(['schoolClass:id,name', 'subject:id,name']);

        if ($request->filled('school_class_id')) {
            $q->where('school_class_id', $request->query('school_class_id'));
        }

        return response()->json($q->orderBy('exam_date')->paginate(50));
    }

    public function datesheetStore(Request $request, NotificationDispatchService $dispatchService)
    {
        abort_unless($request->user()->can('manage_timetable'), 403);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'exam_date' => 'required|date',
            'school_class_id' => 'nullable|exists:school_classes,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'notes' => 'nullable|string',
            'notify_parents' => 'sometimes|boolean',
        ]);

        $notify = (bool) ($data['notify_parents'] ?? false);
        unset($data['notify_parents']);

        $entry = DatesheetEntry::create($data);

        if ($notify) {
            $studentIds = $entry->school_class_id
                ? Student::query()
                    ->whereHas('section', fn ($q) => $q->where('school_class_id', $entry->school_class_id))
                    ->pluck('id')
                    ->all()
                : Student::query()->pluck('id')->all();

            if ($studentIds !== []) {
                $dispatchService->create(
                    NotificationFeatureKeys::TIMETABLE_DATESHEET,
                    'DatesheetEntry',
                    $entry->id,
                    'student',
                    ['student_ids' => $studentIds],
                    [
                        'title' => 'Exam datesheet: '.$entry->title,
                        'body' => 'Exam on '.$entry->exam_date->format('M j, Y').($entry->notes ? ' — '.$entry->notes : ''),
                        'data' => ['datesheet_entry_id' => $entry->id],
                    ],
                    schoolClassId: $entry->school_class_id ? (int) $entry->school_class_id : null,
                    createdByUserId: $request->user()->id,
                );
            }
        }

        return response()->json($entry->load(['schoolClass:id,name', 'subject:id,name']), 201);
    }

    private function learnerStudyGroupIds(Request $request)
    {
        if ($request->user()->hasRole('parent')) {
            return $request->user()->children()->pluck('study_group_id')->unique()->filter();
        }

        $sg = $request->user()->studentProfile?->study_group_id;

        return collect($sg ? [$sg] : []);
    }
}
