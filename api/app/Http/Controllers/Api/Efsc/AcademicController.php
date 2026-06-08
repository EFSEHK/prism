<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Area;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\StudyGroup;
use App\Models\Subject;
use Illuminate\Http\Request;

class AcademicController extends Controller
{
    public function yearsIndex(Request $request)
    {
        abort_unless($request->user()->can('manage_academic_structure') || $request->user()->can('view_dashboard'), 403);

        return response()->json(AcademicYear::query()->orderByDesc('starts_on')->get());
    }

    public function areasIndex(Request $request)
    {
        abort_unless($this->canReadAcademic($request), 403);

        $q = Area::query()->with('academicYear:id,name');
        if ($request->filled('academic_year_id')) {
            $q->where('academic_year_id', $request->query('academic_year_id'));
        }

        return response()->json($q->orderBy('name')->get());
    }

    public function classesIndex(Request $request)
    {
        abort_unless($this->canReadAcademic($request), 403);

        $q = SchoolClass::query()->with(['area:id,name']);
        if ($request->filled('area_id')) {
            $q->where('area_id', $request->query('area_id'));
        }

        return response()->json($q->orderBy('name')->get());
    }

    public function sectionsIndex(Request $request)
    {
        abort_unless($this->canReadAcademic($request), 403);

        $q = Section::query()->with('schoolClass:id,name');
        if ($request->filled('school_class_id')) {
            $q->where('school_class_id', $request->query('school_class_id'));
        }

        return response()->json($q->orderBy('name')->get());
    }

    public function studyGroupsIndex(Request $request)
    {
        abort_unless($this->canReadAcademic($request), 403);

        $q = StudyGroup::query()->with(['section:id,name,school_class_id', 'subjects:id,name,code']);
        if ($request->filled('section_id')) {
            $q->where('section_id', $request->query('section_id'));
        }

        return response()->json($q->orderBy('name')->get());
    }

    public function subjectsIndex(Request $request)
    {
        abort_unless($this->canReadAcademic($request), 403);

        return response()->json(Subject::query()->orderBy('name')->get());
    }

    public function storeYear(Request $request)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after:starts_on',
            'is_current' => 'boolean',
        ]);

        return response()->json(AcademicYear::create($data), 201);
    }

    public function storeArea(Request $request)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:255',
        ]);

        return response()->json(Area::create($data), 201);
    }

    public function storeClass(Request $request)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $data = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'name' => 'required|string|max:255',
        ]);

        return response()->json(SchoolClass::create($data), 201);
    }

    public function storeSection(Request $request)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $data = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'name' => 'required|string|max:255',
        ]);

        return response()->json(Section::create($data), 201);
    }

    public function storeStudyGroup(Request $request)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $data = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'name' => 'required|string|max:255',
        ]);

        return response()->json(StudyGroup::create($data), 201);
    }

    public function storeSubject(Request $request)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:32',
        ]);

        return response()->json(Subject::create($data), 201);
    }

    public function syncStudyGroupSubjects(Request $request, StudyGroup $studyGroup)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $data = $request->validate([
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        $studyGroup->subjects()->sync($data['subject_ids']);

        return response()->json($studyGroup->load('subjects:id,name,code'));
    }

    private function canReadAcademic(Request $request): bool
    {
        return $request->user()->can('manage_academic_structure')
            || $request->user()->can('manage_student_roster')
            || $request->user()->can('mark_attendance')
            || $request->user()->can('enter_marks')
            || $request->user()->can('view_dashboard')
            || $request->user()->can('view_parent_dashboard')
            || $request->user()->can('view_student_dashboard');
    }
}
