<?php

namespace App\Http\Controllers\Api\Prism;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            $request->user()->can('manage_attendance') || $request->user()->can('manage_marks'),
            403
        );

        $data = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'section_id' => 'required|exists:sections,id',
        ]);

        $students = Student::query()
            ->where('school_class_id', $data['school_class_id'])
            ->where('section_id', $data['section_id'])
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'admission_no', 'school_class_id', 'section_id']);

        return response()->json($students);
    }
}
