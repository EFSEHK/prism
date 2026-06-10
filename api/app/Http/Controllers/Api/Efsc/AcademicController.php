<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Area;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        $q = Area::query()->with([
            'academicYear:id,name',
            'sectionHead:id,name,email',
        ]);
        if ($request->filled('academic_year_id')) {
            $q->where('academic_year_id', $request->query('academic_year_id'));
        }

        return response()->json($q->orderBy('name')->get());
    }

    public function sectionHeadsIndex(Request $request)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);

        return response()->json(
            User::role('section_head')->orderBy('name')->get(['id', 'name', 'email'])
        );
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

        $q = StudyGroup::query()->with(['subjects:id,name,code']);

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

    public function updateYear(Request $request, AcademicYear $academicYear)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after:starts_on',
            'is_current' => 'boolean',
        ]);
        $academicYear->update($data);

        return response()->json($academicYear);
    }

    public function destroyYear(Request $request, AcademicYear $academicYear)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $academicYear->delete();

        return response()->json(null, 204);
    }

    public function storeArea(Request $request)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $data = $request->validate(array_merge([
            'academic_year_id' => 'required|exists:academic_years,id',
        ], $this->areaPayloadRules()));

        $area = Area::create($data)->load(['academicYear:id,name', 'sectionHead:id,name,email']);

        return response()->json($area, 201);
    }

    public function updateArea(Request $request, Area $area)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $data = $request->validate($this->areaPayloadRules());
        $area->update($data);

        return response()->json($area->load(['academicYear:id,name', 'sectionHead:id,name,email']));
    }

    public function destroyArea(Request $request, Area $area)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $area->delete();

        return response()->json(null, 204);
    }

    public function storeClass(Request $request)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $data = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'name' => 'required|string|max:255',
        ]);

        return response()->json(SchoolClass::create($data)->load('area:id,name'), 201);
    }

    public function updateClass(Request $request, SchoolClass $schoolClass)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $schoolClass->update($data);

        return response()->json($schoolClass->load('area:id,name'));
    }

    public function destroyClass(Request $request, SchoolClass $schoolClass)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $schoolClass->delete();

        return response()->json(null, 204);
    }

    public function storeSection(Request $request)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $data = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'name' => 'required|string|max:255',
        ]);

        return response()->json(Section::create($data)->load('schoolClass:id,name'), 201);
    }

    public function updateSection(Request $request, Section $section)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $section->update($data);

        return response()->json($section->load('schoolClass:id,name'));
    }

    public function destroySection(Request $request, Section $section)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $section->delete();

        return response()->json(null, 204);
    }

    public function storeStudyGroup(Request $request)
    {
        abort_unless($request->user()->can('manage_academic_structure'), 403);
        $data = $request->validate([
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

    private function areaPayloadRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'section_head_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value && ! User::find($value)?->hasRole('section_head')) {
                        $fail('The selected user must have the section head role.');
                    }
                },
            ],
        ];
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
