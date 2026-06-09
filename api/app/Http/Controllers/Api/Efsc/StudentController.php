<?php

namespace App\Http\Controllers\Api\Efsc;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            $request->user()->can('manage_student_roster') || $request->user()->can('mark_attendance') || $request->user()->can('enter_marks'),
            403
        );

        $data = $request->validate([
            'study_group_id' => 'required|exists:study_groups,id',
        ]);

        $students = Student::query()
            ->with(['studyGroup.schoolClass', 'section'])
            ->where('study_group_id', $data['study_group_id'])
            ->orderBy('first_name')
            ->get();

        return response()->json($students);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('manage_student_roster'), 403);

        $data = $request->validate([
            'study_group_id' => 'required|exists:study_groups,id',
            'name' => 'required_without:first_name|string|max:255',
            'first_name' => 'required_without:name|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'admission_no' => 'nullable|string|max:64|unique:students,admission_no',
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

        $student = Student::create($data)->load(['studyGroup.schoolClass', 'section']);

        return response()->json($student, 201);
    }
}
