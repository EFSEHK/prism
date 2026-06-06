<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage_assessments') || $request->user()->can('enter_marks') || $request->user()->can('view_marks_reports'), 403);

        return response()->json(
            Assessment::query()->orderByDesc('held_on')->paginate(min((int) $request->query('per_page', 20), 50))
        );
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('manage_assessments'), 403);

        $data = $request->validate([
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'type' => 'required|in:test,exam',
            'name' => 'required|string|max:255',
            'number' => 'nullable|integer|min:1',
            'held_on' => 'nullable|date',
        ]);

        $data['created_by_user_id'] = $request->user()->id;

        return response()->json(Assessment::create($data), 201);
    }
}
