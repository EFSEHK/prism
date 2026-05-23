<?php

namespace App\Http\Controllers\Api\Prism;

use App\Http\Controllers\Controller;
use App\Models\DatesheetEntry;
use App\Models\TimetableSlot;
use App\Services\Notifications\NotificationDispatchService;
use App\Support\NotificationFeatureKeys;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    public function slotsIndex(Request $request)
    {
        abort_unless($request->user()->can('manage_timetable') || $request->user()->can('view_parent_dashboard'), 403);

        $q = TimetableSlot::query()->with(['subject:id,name', 'schoolClass:id,name', 'section:id,name']);

        if ($request->filled('school_class_id')) {
            $q->where('school_class_id', $request->query('school_class_id'));
        }
        if ($request->filled('section_id')) {
            $q->where('section_id', $request->query('section_id'));
        }

        if ($request->user()->hasRole('parent')) {
            $classIds = $request->user()->children()->pluck('school_class_id')->unique();
            $secIds = $request->user()->children()->pluck('section_id')->unique();
            $q->whereIn('school_class_id', $classIds)->whereIn('section_id', $secIds);
        }

        return response()->json($q->orderBy('day_of_week')->orderBy('start_time')->paginate(min((int) $request->query('per_page', 50), 100)));
    }

    public function slotsStore(Request $request)
    {
        abort_unless($request->user()->can('manage_timetable'), 403);

        $data = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'room' => 'nullable|string|max:64',
        ]);

        $slot = TimetableSlot::create($data);

        return response()->json($slot->load(['subject', 'schoolClass', 'section']), 201);
    }

    public function datesheetIndex(Request $request)
    {
        abort_unless(
            $request->user()->can('manage_timetable') || $request->user()->can('view_parent_dashboard'),
            403
        );

        $q = DatesheetEntry::query()->with(['schoolClass:id,name', 'subject:id,name']);

        if ($request->user()->hasRole('parent')) {
            $classIds = $request->user()->children()->pluck('school_class_id')->unique();
            $q->where(function ($qq) use ($classIds) {
                $qq->whereNull('school_class_id')->orWhereIn('school_class_id', $classIds);
            });
        }

        if ($request->filled('from')) {
            $q->whereDate('exam_date', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('exam_date', '<=', $request->query('to'));
        }

        return response()->json($q->orderBy('exam_date')->paginate(min((int) $request->query('per_page', 30), 100)));
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
            'notify_parents' => 'nullable|boolean',
        ]);

        $entry = DatesheetEntry::create(collect($data)->except('notify_parents')->all());

        if ($request->boolean('notify_parents')) {
            $dispatchService->create(
                NotificationFeatureKeys::TIMETABLE_DATESHEET,
                'DatesheetEntry',
                $entry->id,
                $entry->school_class_id ? 'class' : 'school',
                null,
                [
                    'title' => 'Datesheet published',
                    'body' => $entry->title.' — '.$entry->exam_date->format('M j, Y'),
                    'data' => ['type' => 'datesheet', 'datesheet_entry_id' => $entry->id],
                ],
                $entry->school_class_id,
                null,
                $request->user()->id,
            );
        }

        return response()->json($entry->load(['schoolClass', 'subject']), 201);
    }
}
