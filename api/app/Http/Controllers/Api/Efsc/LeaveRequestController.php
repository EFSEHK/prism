<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\Notifications\NotificationDispatchService;
use App\Support\NotificationFeatureKeys;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->hasRole('parent')) {
            $q = LeaveRequest::query()->where('parent_user_id', $request->user()->id);
        } elseif ($request->user()->can('manage_leave_requests')) {
            $q = LeaveRequest::query();
        } else {
            abort(403);
        }

        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }

        return response()->json(
            $q->with('student:id,first_name,last_name')->orderByDesc('created_at')->paginate(20)
        );
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
            ...$data,
            'parent_user_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        return response()->json($leave, 201);
    }

    public function decide(Request $request, LeaveRequest $leaveRequest, NotificationDispatchService $dispatchService)
    {
        abort_unless($request->user()->can('manage_leave_requests'), 403);
        abort_unless($leaveRequest->status === 'pending', 422, 'Only pending leave can be decided.');

        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $leaveRequest->update([
            'status' => $data['status'],
            'decided_by_user_id' => $request->user()->id,
            'decided_at' => now(),
        ]);

        $student = $leaveRequest->student;
        $name = trim(($student?->first_name ?? '').' '.($student?->last_name ?? ''));
        $decision = $data['status'] === 'approved' ? 'approved' : 'rejected';

        $dispatchService->create(
            NotificationFeatureKeys::LEAVE_DECISION_PARENT,
            'LeaveRequest',
            $leaveRequest->id,
            'student',
            ['student_ids' => [$leaveRequest->student_id]],
            [
                'title' => 'Leave request '.$decision,
                'body' => trim($name.' leave '.$decision.' for '.$leaveRequest->start_date->format('M j').'–'.$leaveRequest->end_date->format('M j, Y')),
                'data' => ['leave_request_id' => $leaveRequest->id, 'status' => $data['status']],
            ],
            createdByUserId: $request->user()->id,
        );

        return response()->json($leaveRequest->fresh(['student:id,first_name,last_name']));
    }
}
