<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\HomeworkPost;
use Illuminate\Http\Request;

class HomeworkController extends Controller
{
    public function index(Request $request)
    {
        $q = HomeworkPost::query()->with(['subject:id,name', 'studyGroup:id,name', 'section:id,name']);

        if ($request->user()->hasAnyRole(['parent', 'student'])) {
            $groupIds = $request->user()->hasRole('parent')
                ? $request->user()->children()->pluck('study_group_id')->unique()
                : collect([$request->user()->studentProfile?->study_group_id])->filter();
            $q->where('status', 'approved')->whereIn('study_group_id', $groupIds);
        } elseif ($request->filled('study_group_id')) {
            $q->where('study_group_id', $request->query('study_group_id'));
        }

        return response()->json($q->orderByDesc('created_at')->paginate(min((int) $request->query('per_page', 20), 50)));
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

        return response()->json($post, 201);
    }

    public function approve(Request $request, HomeworkPost $homeworkPost)
    {
        abort_unless($request->user()->can('approve_homework'), 403);

        $homeworkPost->update([
            'status' => 'approved',
            'approved_by_user_id' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json($homeworkPost);
    }
}
