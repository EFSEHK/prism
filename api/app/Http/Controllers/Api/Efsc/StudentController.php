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
            ->where('study_group_id', $data['study_group_id'])
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'admission_no', 'study_group_id', 'user_id']);

        return response()->json($students);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('manage_student_roster'), 403);

        $data = $request->validate([
            'study_group_id' => 'required|exists:study_groups,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'admission_no' => 'nullable|string|max:64|unique:students,admission_no',
            'user_id' => 'nullable|exists:users,id|unique:students,user_id',
        ]);

        $student = Student::create($data);

        return response()->json($student, 201);
    }
}
