<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\UserBroadcast;
use Illuminate\Http\Request;

class UserBroadcastController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $q = UserBroadcast::query()->whereNotNull('published_at')->orderByDesc('published_at');

        if ($user->hasRole('parent')) {
            $childIds = $user->children()->pluck('students.id');
            $q->where(function ($qq) use ($childIds, $user) {
                $qq->where('audience_type', 'general')
                    ->orWhere(function ($q2) use ($childIds) {
                        $q2->where('audience_type', 'individual')
                            ->whereIn('student_id', $childIds);
                    });
            });
        } elseif ($user->hasRole('student')) {
            $studentId = $user->studentProfile?->id;
            $q->where(function ($qq) use ($studentId) {
                $qq->where('audience_type', 'general')
                    ->orWhere(function ($q2) use ($studentId) {
                        $q2->where('audience_type', 'individual')
                            ->where('student_id', $studentId)
                            ->where('visible_to_student', true);
                    });
            });
        }

        return response()->json($q->limit(50)->get());
    }

    public function store(Request $request)
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

        $broadcast = UserBroadcast::create([
            ...$data,
            'author_user_id' => $request->user()->id,
            'published_at' => ($data['publish'] ?? true) ? now() : null,
            'visible_to_student' => $data['visible_to_student'] ?? false,
        ]);

        return response()->json($broadcast, 201);
    }
}
