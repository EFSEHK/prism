<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\UserBroadcast;
use App\Services\Notifications\UserBroadcastApprovalService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserBroadcastController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $q = UserBroadcast::query()
            ->with([
                'author:id,name',
                'area:id,name',
                'schoolClass:id,name',
                'section:id,name',
                'studyGroup:id,name',
                'student:id,first_name,last_name,father_name',
            ])
            ->orderByDesc('created_at');

        if ($user->hasRole('parent')) {
            $childIds = $user->children()->pluck('students.id');
            $studyGroupIds = $user->children()->pluck('students.study_group_id')->filter();
            $sectionIds = $user->children()->pluck('students.section_id')->filter();

            $q->whereNotNull('published_at')
                ->where(function ($qq) use ($childIds, $studyGroupIds, $sectionIds) {
                    $qq->where('audience_type', 'general')
                        ->orWhere(function ($q2) use ($childIds) {
                            $q2->where('audience_type', 'individual')
                                ->whereIn('student_id', $childIds);
                        })
                        ->orWhere(function ($q2) use ($studyGroupIds, $sectionIds) {
                            $q2->where('audience_type', 'scoped')
                                ->where(function ($q3) use ($studyGroupIds, $sectionIds) {
                                    if ($studyGroupIds->isNotEmpty()) {
                                        $q3->orWhereIn('study_group_id', $studyGroupIds);
                                    }
                                    if ($sectionIds->isNotEmpty()) {
                                        $q3->orWhereIn('section_id', $sectionIds);
                                    }
                                });
                        });
                });
        } elseif ($user->hasRole('student')) {
            $student = $user->studentProfile;
            $studentId = $student?->id;
            $studyGroupId = $student?->study_group_id;
            $sectionId = $student?->section_id;

            $q->whereNotNull('published_at')
                ->where(function ($qq) use ($studentId, $studyGroupId, $sectionId) {
                    $qq->where('audience_type', 'general')
                        ->orWhere(function ($q2) use ($studentId) {
                            $q2->where('audience_type', 'individual')
                                ->where('student_id', $studentId)
                                ->where('visible_to_student', true);
                        })
                        ->orWhere(function ($q2) use ($studyGroupId, $sectionId) {
                            $q2->where('audience_type', 'scoped')
                                ->where(function ($q3) use ($studyGroupId, $sectionId) {
                                    if ($studyGroupId) {
                                        $q3->orWhere('study_group_id', $studyGroupId);
                                    }
                                    if ($sectionId) {
                                        $q3->orWhere('section_id', $sectionId);
                                    }
                                });
                        });
                });
        } elseif (! $user->can('publish_user_broadcasts')) {
            $q->whereNotNull('published_at');
        }

        return response()->json($q->limit(50)->get());
    }

    public function pending(Request $request, UserBroadcastApprovalService $approvalService)
    {
        abort_unless($request->user()->can('publish_user_broadcasts'), 403);

        $user = $request->user();
        $items = UserBroadcast::query()
            ->where('approval_status', 'pending_approval')
            ->with([
                'author:id,name',
                'area:id,name',
                'schoolClass:id,name',
                'section:id,name',
                'studyGroup:id,name',
                'student:id,first_name,last_name,father_name',
            ])
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn (UserBroadcast $b) => $approvalService->userCanApprove($user, $b))
            ->values();

        return response()->json($items);
    }

    public function store(Request $request, UserBroadcastApprovalService $approvalService)
    {
        abort_unless($request->user()->can('publish_user_broadcasts'), 403);

        $data = $request->validate([
            'audience_type' => 'required|in:general,scoped,individual',
            'area_id' => 'nullable|exists:areas,id',
            'school_class_id' => 'nullable|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'study_group_id' => 'nullable|exists:study_groups,id',
            'student_id' => 'nullable|exists:students,id',
            'visible_to_student' => 'boolean',
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'publish' => 'boolean',
        ]);

        if ($data['audience_type'] === 'scoped') {
            $hasScope = ! empty($data['area_id'])
                || ! empty($data['school_class_id'])
                || ! empty($data['section_id'])
                || ! empty($data['study_group_id']);
            if (! $hasScope) {
                throw ValidationException::withMessages([
                    'audience_type' => 'Select at least one scope (area, class, section, or study group).',
                ]);
            }
        }

        if ($data['audience_type'] === 'individual' && empty($data['student_id'])) {
            throw ValidationException::withMessages([
                'student_id' => 'Select a student for individual notifications.',
            ]);
        }

        $user = $request->user();
        $needsApproval = $approvalService->needsApproval($user, $data['audience_type']);
        $autoApprove = ! $needsApproval && ($data['publish'] ?? true);

        $broadcast = UserBroadcast::create([
            ...$data,
            'author_user_id' => $user->id,
            'approval_status' => $autoApprove ? 'approved' : 'pending_approval',
            'approved_by_user_id' => $autoApprove ? $user->id : null,
            'approved_at' => $autoApprove ? now() : null,
            'published_at' => $autoApprove ? now() : null,
            'visible_to_student' => $data['visible_to_student'] ?? false,
        ]);

        $broadcast->load([
            'author:id,name',
            'area:id,name',
            'schoolClass:id,name',
            'section:id,name',
            'studyGroup:id,name',
            'student:id,first_name,last_name,father_name',
        ]);

        return response()->json($broadcast, 201);
    }

    public function approve(
        Request $request,
        UserBroadcast $userBroadcast,
        UserBroadcastApprovalService $approvalService,
    ) {
        abort_unless($request->user()->can('publish_user_broadcasts'), 403);

        return response()->json(
            $approvalService->approve($userBroadcast, $request->user())
                ->load([
                    'author:id,name',
                    'area:id,name',
                    'schoolClass:id,name',
                    'section:id,name',
                    'studyGroup:id,name',
                    'student:id,first_name,last_name,father_name',
                ])
        );
    }

    public function reject(
        Request $request,
        UserBroadcast $userBroadcast,
        UserBroadcastApprovalService $approvalService,
    ) {
        abort_unless($request->user()->can('publish_user_broadcasts'), 403);

        $data = $request->validate(['comment' => 'nullable|string|max:500']);

        return response()->json(
            $approvalService->reject($userBroadcast, $request->user(), $data['comment'] ?? null)
                ->load([
                    'author:id,name',
                    'area:id,name',
                    'schoolClass:id,name',
                    'section:id,name',
                    'studyGroup:id,name',
                    'student:id,first_name,last_name,father_name',
                ])
        );
    }
}
