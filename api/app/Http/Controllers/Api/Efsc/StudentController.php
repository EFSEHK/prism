<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\StudentUserProvisioningService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function search(Request $request)
    {
        abort_unless($request->user()->can('publish_user_broadcasts'), 403);

        $data = $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $term = $data['q'];

        $students = Student::query()
            ->with(['section.schoolClass.area:id,name'])
            ->where(function ($qq) use ($term) {
                $qq->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('father_name', 'like', "%{$term}%")
                    ->orWhere('admission_no', 'like', "%{$term}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', COALESCE(last_name, '')) LIKE ?", ["%{$term}%"]);
            })
            ->orderBy('first_name')
            ->limit(30)
            ->get(['id', 'first_name', 'last_name', 'father_name', 'admission_no', 'section_id', 'study_group_id']);

        return response()->json($students);
    }

    public function index(Request $request)
    {
        abort_unless(
            $request->user()->can('manage_student_roster')
            || $request->user()->can('mark_attendance')
            || $request->user()->can('enter_marks')
            || $request->user()->can('publish_user_broadcasts'),
            403
        );

        $data = $request->validate([
            'study_group_id' => 'required_without_all:section_id,school_class_id|nullable|exists:study_groups,id',
            'school_class_id' => 'nullable|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
        ]);

        $students = Student::query()
            ->with(['studyGroup:id,name', 'section.schoolClass:id,name', 'section.schoolClass.area:id,name'])
            ->when(
                ! empty($data['study_group_id']),
                fn ($q) => $q->where('study_group_id', $data['study_group_id'])
            )
            ->when(
                ! empty($data['section_id']),
                fn ($q) => $q->where('section_id', $data['section_id'])
            )
            ->when(
                empty($data['section_id']) && ! empty($data['school_class_id']),
                fn ($q) => $q->whereHas(
                    'section',
                    fn ($sq) => $sq->where('school_class_id', $data['school_class_id'])
                )
            )
            ->orderBy('first_name')
            ->get();

        return response()->json($students);
    }

    public function store(Request $request, StudentUserProvisioningService $provisioning)
    {
        abort_unless($request->user()->can('manage_student_roster'), 403);

        $data = $request->validate([
            'study_group_id' => 'required|exists:study_groups,id',
            'name' => 'required_without:first_name|string|max:255',
            'first_name' => 'required_without:name|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'admission_no' => 'required|string|max:64|unique:students,admission_no',
            'section_id' => 'nullable|exists:sections,id',
            'roll_no' => 'nullable|string|max:32',
            'cnic' => 'nullable|string|max:20',
            'father_name' => 'nullable|string|max:255',
            'father_cnic' => 'nullable|string|max:20',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_cnic' => 'nullable|string|max:20',
            'father_is_guardian' => 'boolean',
            'user_id' => 'nullable|exists:users,id|unique:students,user_id',
        ]);

        if (! empty($data['name'])) {
            $parts = preg_split('/\s+/', trim($data['name']), 2);
            $data['first_name'] = $parts[0];
            $data['last_name'] = $parts[1] ?? '';
            unset($data['name']);
        }

        if (! empty($data['father_is_guardian'])) {
            $data['guardian_name'] = $data['father_name'] ?? null;
            $data['guardian_cnic'] = $data['father_cnic'] ?? null;
        }

        unset($data['user_id']);

        $student = Student::create($data)->load(['studyGroup:id,name', 'section.schoolClass:id,name']);

        $accounts = $provisioning->provisionForStudent($student);
        $student->load(['user:id,name,email', 'parents:id,name,email']);

        return response()->json([
            'student' => $student,
            'accounts' => $accounts,
        ], 201);
    }
}
