<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\DatesheetEntry;
use App\Models\TimetableSlot;
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

        if ($request->filled('study_group_id')) {
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

        return response()->json(TimetableSlot::create($data), 201);
    }

    public function datesheetIndex(Request $request)
    {
        abort_unless(
            $request->user()->can('manage_timetable') || $request->user()->can('view_parent_dashboard') || $request->user()->can('view_student_dashboard'),
            403
        );

        return response()->json(
            DatesheetEntry::query()->orderBy('exam_date')->paginate(50)
        );
    }

    public function datesheetStore(Request $request)
    {
        abort_unless($request->user()->can('manage_timetable'), 403);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'exam_date' => 'required|date',
            'school_class_id' => 'nullable|exists:school_classes,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'notes' => 'nullable|string',
        ]);

        return response()->json(DatesheetEntry::create($data), 201);
    }
}
