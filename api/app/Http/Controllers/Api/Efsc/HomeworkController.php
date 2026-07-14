<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\HomeworkPost;
use App\Services\Notifications\NotificationDispatchService;
use App\Support\NotificationFeatureKeys;
use Illuminate\Http\Request;

class HomeworkController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isLearner = $user->hasAnyRole(['parent', 'student']);

        if ($isLearner) {
            abort_unless($user->can('view_own_homework'), 403);
        } else {
            abort_unless(
                $user->can('post_homework') || $user->can('approve_homework'),
                403
            );
        }

        $q = HomeworkPost::query()->with([
            'subject:id,name',
            'studyGroup:id,name',
            'section:id,name',
            'createdBy:id,name',
        ]);

        if ($isLearner) {
            $students = $user->hasRole('parent')
                ? $user->children()->get(['students.id', 'students.study_group_id', 'students.section_id'])
                : collect([$user->studentProfile])->filter();

            $groupIds = $students->pluck('study_group_id')->unique()->filter()->values();
            $sectionIds = $students->pluck('section_id')->unique()->filter()->values();

            $q->where('status', 'approved')->where(function ($qq) use ($groupIds, $sectionIds) {
                if ($groupIds->isNotEmpty()) {
                    $qq->whereIn('study_group_id', $groupIds);
                }
                if ($sectionIds->isNotEmpty()) {
                    $qq->orWhereIn('section_id', $sectionIds);
                }
                if ($groupIds->isEmpty() && $sectionIds->isEmpty()) {
                    $qq->whereRaw('1 = 0');
                }
            });
        } else {
            if ($request->filled('study_group_id')) {
                $q->where('study_group_id', $request->query('study_group_id'));
            }
            if ($request->filled('status')) {
                $q->where('status', $request->query('status'));
            }
        }

        return response()->json(
            $q->orderByDesc('created_at')->paginate(min((int) $request->query('per_page', 20), 50))
        );
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('post_homework'), 403);

        $data = $request->validate([
            'study_group_id' => 'nullable|exists:study_groups,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        abort_unless($data['study_group_id'] || $data['section_id'], 422, 'study_group_id or section_id required.');

        $post = HomeworkPost::create([
            ...$data,
            'status' => 'pending_approval',
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json($post->load(['subject:id,name', 'studyGroup:id,name', 'section:id,name', 'createdBy:id,name']), 201);
    }

    public function approve(Request $request, HomeworkPost $homeworkPost, NotificationDispatchService $dispatchService)
    {
        abort_unless($request->user()->can('approve_homework'), 403);
        abort_unless($homeworkPost->status === 'pending_approval', 422, 'Only pending homework can be approved.');

        $homeworkPost->update([
            'status' => 'approved',
            'approved_by_user_id' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $due = $homeworkPost->due_date
            ? ' Due '.$homeworkPost->due_date->format('M j, Y').'.'
            : '';

        $scopeType = $homeworkPost->study_group_id ? 'study_group' : 'section';

        $dispatchService->create(
            NotificationFeatureKeys::HOMEWORK_NEW,
            'HomeworkPost',
            $homeworkPost->id,
            $scopeType,
            null,
            [
                'title' => 'New homework: '.$homeworkPost->title,
                'body' => trim(($homeworkPost->body ?: $homeworkPost->title).$due),
                'data' => ['homework_post_id' => $homeworkPost->id],
            ],
            sectionId: $homeworkPost->section_id ? (int) $homeworkPost->section_id : null,
            studyGroupId: $homeworkPost->study_group_id ? (int) $homeworkPost->study_group_id : null,
            createdByUserId: $request->user()->id,
        );

        return response()->json($homeworkPost->fresh()->load([
            'subject:id,name',
            'studyGroup:id,name',
            'section:id,name',
            'createdBy:id,name',
        ]));
    }

    public function reject(Request $request, HomeworkPost $homeworkPost)
    {
        abort_unless($request->user()->can('approve_homework'), 403);
        abort_unless($homeworkPost->status === 'pending_approval', 422, 'Only pending homework can be rejected.');

        $homeworkPost->update([
            'status' => 'rejected',
            'approved_by_user_id' => null,
            'approved_at' => null,
        ]);

        return response()->json($homeworkPost->fresh()->load([
            'subject:id,name',
            'studyGroup:id,name',
            'section:id,name',
            'createdBy:id,name',
        ]));
    }
}
