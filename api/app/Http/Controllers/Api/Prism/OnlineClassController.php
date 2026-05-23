<?php

namespace App\Http\Controllers\Api\Prism;

use App\Http\Controllers\Controller;
use App\Models\OnlineClassLink;
use App\Models\Student;
use App\Services\Notifications\NotificationDispatchService;
use App\Support\NotificationFeatureKeys;
use Illuminate\Http\Request;

class OnlineClassController extends Controller
{
    public function index(Request $request)
    {
        $q = OnlineClassLink::query()->with(['subject:id,name', 'schoolClass:id,name', 'section:id,name']);

        if ($request->user()->hasRole('parent')) {
            $classIds = $request->user()->children()->pluck('school_class_id')->unique();
            $secIds = $request->user()->children()->pluck('section_id')->unique();
            $q->whereIn('school_class_id', $classIds)
                ->where(function ($qq) use ($secIds) {
                    $qq->whereNull('section_id')->orWhereIn('section_id', $secIds);
                });
        } elseif ($request->filled('school_class_id')) {
            $q->where('school_class_id', $request->query('school_class_id'));
        }

        return response()->json($q->orderBy('label')->paginate(min((int) $request->query('per_page', 30), 100)));
    }

    public function store(Request $request, NotificationDispatchService $dispatchService)
    {
        abort_unless($request->user()->can('manage_online_classes'), 403);

        $data = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'label' => 'required|string|max:255',
            'url' => 'required|url|max:2048',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'start_time' => 'nullable|date_format:H:i',
            'minutes_before' => 'nullable|integer|min:0|max:180',
            'schedule_reminder' => 'nullable|boolean',
        ]);

        $link = OnlineClassLink::create(collect($data)->except('schedule_reminder')->all());

        if ($request->boolean('schedule_reminder') && $data['start_time'] ?? null) {
            $minutesBefore = (int) ($data['minutes_before'] ?? 30);
            $scheduledFor = now()->setTimeFromTimeString($data['start_time'])->subMinutes($minutesBefore);
            if ($scheduledFor->isPast()) {
                $scheduledFor = $scheduledFor->addDay();
            }

            $studentIds = Student::query()
                ->where('school_class_id', $link->school_class_id)
                ->when($link->section_id, fn ($q) => $q->where('section_id', $link->section_id))
                ->pluck('id')
                ->all();

            $dispatchService->create(
                NotificationFeatureKeys::ONLINE_CLASS_REMINDER,
                'OnlineClassLink',
                $link->id,
                'class',
                ['student_ids' => $studentIds],
                [
                    'title' => 'Online class reminder',
                    'body' => $link->label.' starts soon. Join: '.$link->url,
                    'data' => ['type' => 'online_class', 'online_class_link_id' => $link->id],
                ],
                (int) $link->school_class_id,
                $link->section_id ? (int) $link->section_id : null,
                $request->user()->id,
                $scheduledFor,
            );
        }

        return response()->json($link->load(['subject', 'schoolClass', 'section']), 201);
    }
}
