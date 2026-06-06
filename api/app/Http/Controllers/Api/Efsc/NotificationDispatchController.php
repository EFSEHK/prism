<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\NotificationDispatchRequest;
use App\Services\Notifications\NotificationApprovalService;
use Illuminate\Http\Request;

class NotificationDispatchController extends Controller
{
    public function pending(Request $request)
    {
        abort_unless($request->user()->can('approve_notification_dispatches'), 403);

        $items = NotificationDispatchRequest::query()
            ->where('status', 'pending_approval')
            ->with(['feature:id,feature_key,name', 'approvalActions'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($items);
    }

    public function approve(Request $request, NotificationDispatchRequest $notificationDispatchRequest, NotificationApprovalService $approvalService)
    {
        abort_unless($request->user()->can('approve_notification_dispatches'), 403);
        abort_unless($approvalService->userCanApprove($request->user(), $notificationDispatchRequest), 403);

        $data = $request->validate(['comment' => 'nullable|string']);

        return response()->json(
            $approvalService->approve($notificationDispatchRequest, $request->user(), $data['comment'] ?? null)
        );
    }

    public function reject(Request $request, NotificationDispatchRequest $notificationDispatchRequest, NotificationApprovalService $approvalService)
    {
        abort_unless($request->user()->can('approve_notification_dispatches'), 403);

        $data = $request->validate(['comment' => 'nullable|string']);

        return response()->json(
            $approvalService->reject($notificationDispatchRequest, $request->user(), $data['comment'] ?? null)
        );
    }
}
