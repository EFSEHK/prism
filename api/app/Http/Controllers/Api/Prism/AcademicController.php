<?php

namespace App\Http\Controllers\Api\Prism;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\Request;

class AcademicController extends Controller
{
    public function classesIndex(Request $request)
    {
        abort_unless($request->user()->can('manage_attendance')
            || $request->user()->can('manage_marks')
            || $request->user()->can('manage_timetable')
            || $request->user()->can('manage_homework')
            || $request->user()->can('manage_feed')
            || $request->user()->can('manage_fee_vouchers')
            || $request->user()->can('manage_online_classes')
            || $request->user()->can('view_parent_dashboard'), 403);

        $classes = SchoolClass::query()
            ->with(['sections:id,name,school_class_id'])
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level']);

        return response()->json($classes);
    }

    public function subjectsIndex(Request $request)
    {
        abort_unless($request->user()->can('manage_homework')
            || $request->user()->can('manage_marks')
            || $request->user()->can('manage_timetable')
            || $request->user()->can('manage_online_classes'), 403);

        return response()->json(Subject::query()->orderBy('name')->get(['id', 'name', 'code']));
    }
}
