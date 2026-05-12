<?php

namespace App\Http\Controllers\Api\Prism;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\Notifications\NotificationDispatchService;
use App\Support\NotificationFeatureKeys;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $q = LeaveRequest::query()->with(['student:id,first_name,last_name', 'parent:id,name']);

        if ($request->user()->hasRole('parent')) {
            $q->where('parent_user_id', $request->user()->id);
        } elseif ($request->user()->can('manage_leave_requests')) {
            if ($request->filled('status')) {
                $q->where('status', $request->query('status'));
            }
        } else {
            abort(403);
        }

        return response()->json($q->orderByDesc('created_at')->paginate(min((int) $request->query('per_page', 20), 50)));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('parent'), 403);

        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        abort_unless($request->user()->children()->where('students.id', $data['student_id'])->exists(), 403);

        $leave = LeaveRequest::create([
            'student_id' => $data['student_id'],
            'parent_user_id' => $request->user()->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json($leave->load('student'), 201);
    }

    public function decide(Request $request, LeaveRequest $leaveRequest, NotificationDispatchService $dispatchService)
    {
        abort_unless($request->user()->can('manage_leave_requests'), 403);

        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $leaveRequest->update([
            'status' => $data['status'],
            'decided_by_user_id' => $request->user()->id,
            'decided_at' => now(),
        ]);

        $parentId = $leaveRequest->parent_user_id;
        $dispatchService->create(
            NotificationFeatureKeys::LEAVE_DECISION_PARENT,
            'LeaveRequest',
            $leaveRequest->id,
            'student',
            ['student_ids' => [$leaveRequest->student_id]],
            [
                'title' => 'Leave request '.$data['status'],
                'body' => 'Your leave request has been '.$data['status'].'.',
                'data' => ['type' => 'leave', 'leave_request_id' => $leaveRequest->id],
                'parent_user_ids' => [$parentId],
            ],
            null,
            null,
            $request->user()->id,
        );

        return response()->json($leaveRequest);
    }
}
