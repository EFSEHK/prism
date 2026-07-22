<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\AttendanceBatch;
use App\Models\HomeworkPost;
use App\Models\LeaveRequest;
use App\Models\MarkSheet;
use App\Models\NotificationDispatchRequest;
use App\Models\UserBroadcast;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $request)
    {
        abort_unless($request->user()->can('view_dashboard')
            || $request->user()->can('view_parent_dashboard')
            || $request->user()->can('view_student_dashboard'), 403);

        $user = $request->user();
        $roles = $user->getRoleNames()->toArray();

        $payload = [
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'roles' => $roles,
            'widgets' => [],
        ];

        if ($user->can('view_parent_dashboard') || $user->can('view_student_dashboard')) {
            return response()->json(array_merge($payload, [
                'redirect' => 'learner',
            ]));
        }

        if ($user->can('approve_notification_dispatches')) {
            $payload['widgets']['pending_approvals'] = NotificationDispatchRequest::query()
                ->where('status', 'pending_approval')
                ->count();
        }

        if ($user->can('verify_attendance')) {
            $payload['widgets']['attendance_pending_verify'] = AttendanceBatch::query()
                ->where('status', 'submitted')
                ->count();
        }

        if ($user->can('approve_homework')) {
            $payload['widgets']['homework_pending_approve'] = HomeworkPost::query()
                ->where('status', 'pending_approval')
                ->count();
        }

        if ($user->can('verify_marks')) {
            $payload['widgets']['marks_pending_verify'] = MarkSheet::query()
                ->where('status', 'submitted')
                ->count();
        }

        if ($user->can('manage_leave_requests')) {
            $payload['widgets']['leave_pending'] = LeaveRequest::query()
                ->where('status', 'pending')
                ->count();
        }

        if ($user->can('publish_user_broadcasts')) {
            $payload['widgets']['recent_broadcasts'] = UserBroadcast::query()
                ->whereNotNull('published_at')
                ->orderByDesc('published_at')
                ->limit(5)
                ->get(['id', 'title', 'audience_type', 'published_at']);
        }

        return response()->json($payload);
    }
}
