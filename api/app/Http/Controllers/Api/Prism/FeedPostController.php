<?php

namespace App\Http\Controllers\Api\Prism;

use App\Http\Controllers\Controller;
use App\Models\FeedPost;
use App\Services\Notifications\NotificationDispatchService;
use App\Support\NotificationFeatureKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FeedPostController extends Controller
{
    public function index(Request $request)
    {
        $q = FeedPost::query()->with('author:id,name')->whereNotNull('published_at');

        if ($request->user()->hasRole('parent')) {
            $studentIds = $request->user()->children()->pluck('students.id');
            $classIds = $request->user()->children()->pluck('school_class_id')->unique();
            $q->where(function ($qq) use ($studentIds, $classIds) {
                $qq->where('scope', 'school')
                    ->orWhere(function ($q2) use ($classIds) {
                        $q2->where('scope', 'class')->whereIn('scope_school_class_id', $classIds);
                    })
                    ->orWhere(function ($q3) use ($studentIds) {
                        $q3->where('scope', 'student')->whereIn('scope_student_id', $studentIds);
                    });
            });
        }

        return response()->json($q->orderByDesc('published_at')->paginate(min((int) $request->query('per_page', 15), 50)));
    }

    public function store(Request $request, NotificationDispatchService $dispatchService)
    {
        abort_unless($request->user()->can('manage_feed'), 403);

        $data = $request->validate([
            'type' => 'required|in:announcement,event,achievement',
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'scope' => 'required|in:school,class,student',
            'scope_school_class_id' => 'nullable|required_if:scope,class|exists:school_classes,id',
            'scope_section_id' => 'nullable|exists:sections,id',
            'scope_student_id' => 'nullable|required_if:scope,student|exists:students,id',
        ]);

        $publish = $request->boolean('publish');

        $post = FeedPost::create([
            'type' => $data['type'],
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'scope' => $data['scope'],
            'scope_school_class_id' => $data['scope_school_class_id'] ?? null,
            'scope_section_id' => $data['scope_section_id'] ?? null,
            'scope_student_id' => $data['scope_student_id'] ?? null,
            'author_user_id' => $request->user()->id,
            'published_at' => $publish ? now() : null,
        ]);

        if ($post->published_at) {
            $scopeType = $post->scope;
            $classId = $post->scope_school_class_id;
            $sectionId = $post->scope_section_id;
            $dispatchService->create(
                NotificationFeatureKeys::EVENTS_BROADCAST,
                'FeedPost',
                $post->id,
                $scopeType,
                $post->scope === 'student' ? ['student_ids' => [(int) $post->scope_student_id]] : null,
                [
                    'title' => $post->title,
                    'body' => Str::limit((string) $post->body, 200),
                    'data' => ['type' => 'feed', 'feed_post_id' => $post->id],
                ],
                $classId,
                $sectionId,
                $request->user()->id,
            );
        }

        return response()->json($post, 201);
    }
}
