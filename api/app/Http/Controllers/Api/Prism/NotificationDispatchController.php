<?php

namespace App\Http\Controllers\Api\Prism;

use App\Http\Controllers\Controller;
use App\Models\NotificationDispatchRequest;
use App\Services\Notifications\NotificationApprovalService;
use Illuminate\Http\Request;

class NotificationDispatchController extends Controller
{
    public function pending(Request $request, NotificationApprovalService $approvalService)
    {
        abort_unless($request->user()->can('approve_notification_dispatches'), 403);

        $items = NotificationDispatchRequest::query()
            ->with(['feature', 'approvalActions', 'schoolClass:id,name', 'section:id,name', 'createdBy:id,name'])
            ->where('status', 'pending_approval')
            ->orderByDesc('created_at')
            ->limit(80)
            ->get();

        $filtered = $items->filter(fn ($d) => $approvalService->userCanApprove($request->user(), $d))->values();

        return response()->json(['data' => $filtered]);
    }

    public function approve(Request $request, NotificationDispatchRequest $notificationDispatchRequest, NotificationApprovalService $approvalService)
    {
        abort_unless($request->user()->can('approve_notification_dispatches'), 403);

        $data = $request->validate(['comment' => 'nullable|string|max:500']);

        $dispatch = $approvalService->approve(
            $notificationDispatchRequest,
            $request->user(),
            $data['comment'] ?? null
        );

        return response()->json($dispatch);
    }

    public function reject(Request $request, NotificationDispatchRequest $notificationDispatchRequest, NotificationApprovalService $approvalService)
    {
        abort_unless($request->user()->can('approve_notification_dispatches'), 403);

        $data = $request->validate(['comment' => 'nullable|string|max:500']);

        $dispatch = $approvalService->reject(
            $notificationDispatchRequest,
            $request->user(),
            $data['comment'] ?? null
        );

        return response()->json($dispatch);
    }
}
