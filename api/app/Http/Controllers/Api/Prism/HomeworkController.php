<?php

namespace App\Http\Controllers\Api\Prism;

use App\Http\Controllers\Controller;
use App\Models\HomeworkPost;
use App\Services\Notifications\NotificationDispatchService;
use App\Support\NotificationFeatureKeys;
use Illuminate\Http\Request;

class HomeworkController extends Controller
{
    public function index(Request $request)
    {
        $q = HomeworkPost::query()->with(['subject:id,name', 'schoolClass:id,name', 'section:id,name']);

        if ($request->user()->hasRole('parent')) {
            $classIds = $request->user()->children()->pluck('school_class_id')->unique();
            $secIds = $request->user()->children()->pluck('section_id')->unique();
            $q->whereIn('school_class_id', $classIds)->whereIn('section_id', $secIds);
        } elseif ($request->filled('school_class_id')) {
            $q->where('school_class_id', $request->query('school_class_id'));
            if ($request->filled('section_id')) {
                $q->where('section_id', $request->query('section_id'));
            }
        }

        return response()->json($q->orderByDesc('created_at')->paginate(min((int) $request->query('per_page', 20), 50)));
    }

    public function store(Request $request, NotificationDispatchService $dispatchService)
    {
        abort_unless($request->user()->can('manage_homework'), 403);

        $data = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'due_date' => 'nullable|date',
            'attachment_path' => 'nullable|string|max:2048',
        ]);

        $post = HomeworkPost::create([
            ...$data,
            'created_by_user_id' => $request->user()->id,
        ]);

        $dispatchService->create(
            NotificationFeatureKeys::HOMEWORK_NEW,
            'HomeworkPost',
            $post->id,
            'class',
            null,
            [
                'title' => 'New homework',
                'body' => $post->title,
                'data' => ['type' => 'homework', 'homework_post_id' => $post->id],
            ],
            $post->school_class_id,
            $post->section_id,
            $request->user()->id,
        );

        return response()->json($post->load(['subject', 'schoolClass', 'section']), 201);
    }
}
