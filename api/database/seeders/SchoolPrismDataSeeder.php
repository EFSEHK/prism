<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\HomeworkPost;
use App\Models\MarkEntry;
use App\Models\MarkSheet;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\StaffClassAssignment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class SchoolPrismDataSeeder extends Seeder
{
    public function run(): void
    {
        $year = AcademicYear::firstOrCreate(
            ['name' => '2025-2026'],
            ['starts_on' => '2025-04-01', 'ends_on' => '2026-03-31', 'is_current' => true]
        );

        $class = SchoolClass::firstOrCreate(['name' => 'Grade 10'], ['grade_level' => '10']);
        $section = Section::firstOrCreate(
            ['school_class_id' => $class->id, 'name' => 'A'],
            []
        );

        $math = Subject::firstOrCreate(['code' => 'MATH'], ['name' => 'Mathematics']);
        Subject::firstOrCreate(['code' => 'ENG'], ['name' => 'English']);

        $student = Student::firstOrCreate(
            ['admission_no' => 'STU-001'],
            [
                'first_name' => 'Ali',
                'last_name' => 'Student',
                'school_class_id' => $class->id,
                'section_id' => $section->id,
            ]
        );

        $parent = User::where('email', 'parent@school.test')->first();
        if ($parent) {
            $parent->children()->syncWithoutDetaching([$student->id]);
        }

        $teacher = User::where('email', 'teacher@school.test')->first();
        $incharge = User::where('email', 'incharge@school.test')->first();

        if ($teacher) {
            StaffClassAssignment::firstOrCreate(
                [
                    'user_id' => $teacher->id,
                    'school_class_id' => $class->id,
                    'section_id' => $section->id,
                    'role_in_class' => 'teacher',
                ]
            );
        }

        if ($incharge) {
            StaffClassAssignment::firstOrCreate(
                [
                    'user_id' => $incharge->id,
                    'school_class_id' => $class->id,
                    'section_id' => $section->id,
                    'role_in_class' => 'incharge',
                ]
            );
        }

        $assessment = Assessment::firstOrCreate(
            [
                'academic_year_id' => $year->id,
                'type' => 'test',
                'number' => 1,
                'name' => 'Test 1',
            ],
            ['held_on' => now()->toDateString()]
        );

        $sheet = MarkSheet::firstOrCreate(
            [
                'assessment_id' => $assessment->id,
                'school_class_id' => $class->id,
                'section_id' => $section->id,
                'subject_id' => $math->id,
            ],
            ['submitted_by_user_id' => $teacher?->id]
        );

        MarkEntry::firstOrCreate(
            ['mark_sheet_id' => $sheet->id, 'student_id' => $student->id],
            ['marks_obtained' => 45, 'max_marks' => 50, 'grade' => 'A']
        );

        if ($teacher) {
            HomeworkPost::firstOrCreate(
                [
                    'school_class_id' => $class->id,
                    'section_id' => $section->id,
                    'subject_id' => $math->id,
                    'title' => 'Algebra exercises',
                ],
                [
                    'body' => 'Complete chapter 3',
                    'due_date' => now()->addDays(3)->toDateString(),
                    'created_by_user_id' => $teacher->id,
                ]
            );
        }
    }
}
