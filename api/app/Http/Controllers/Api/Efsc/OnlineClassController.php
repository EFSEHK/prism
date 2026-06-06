<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\OnlineClassLink;
use App\Services\Notifications\NotificationDispatchService;
use App\Support\NotificationFeatureKeys;
use Illuminate\Http\Request;

class OnlineClassController extends Controller
{
    public function index(Request $request)
    {
        $q = OnlineClassLink::query()->with(['subject:id,name', 'studyGroup:id,name']);

        if ($request->user()->hasAnyRole(['parent', 'student'])) {
            $groupIds = $request->user()->hasRole('parent')
                ? $request->user()->children()->pluck('study_group_id')->unique()
                : collect([$request->user()->studentProfile?->study_group_id])->filter();
            $q->where('status', 'approved')->whereIn('study_group_id', $groupIds);
        } elseif ($request->filled('study_group_id')) {
            $q->where('study_group_id', $request->query('study_group_id'));
        }

        return response()->json($q->orderBy('scheduled_date')->paginate(min((int) $request->query('per_page', 20), 50)));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('manage_online_classes'), 403);

        $data = $request->validate([
            'study_group_id' => 'required|exists:study_groups,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'label' => 'required|string|max:255',
            'url' => 'required|url|max:2048',
            'scheduled_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
        ]);

        $link = OnlineClassLink::create([
            ...$data,
            'status' => 'pending_approval',
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json($link, 201);
    }

    public function approve(Request $request, OnlineClassLink $onlineClassLink, NotificationDispatchService $dispatchService)
    {
        abort_unless($request->user()->can('approve_online_classes'), 403);

        $onlineClassLink->update([
            'status' => 'approved',
            'approved_by_user_id' => $request->user()->id,
        ]);

        $dispatchService->create(
            NotificationFeatureKeys::ONLINE_CLASS_APPROVED,
            'OnlineClassLink',
            $onlineClassLink->id,
            'study_group',
            null,
            [
                'title' => 'Online class scheduled',
                'body' => $onlineClassLink->label.' on '.$onlineClassLink->scheduled_date->format('M j, Y'),
                'data' => ['online_class_link_id' => $onlineClassLink->id],
            ],
            studyGroupId: (int) $onlineClassLink->study_group_id,
            createdByUserId: $request->user()->id,
        );

        return response()->json($onlineClassLink);
    }
}
