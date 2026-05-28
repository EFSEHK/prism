<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AttendanceBatch;
use App\Models\AttendanceRecord;
use App\Models\DatesheetEntry;
use App\Models\FeeVoucher;
use App\Models\FeedPost;
use App\Models\HomeworkPost;
use App\Models\MarkEntry;
use App\Models\MarkSheet;
use App\Models\OnlineClassLink;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\StaffClassAssignment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TimetableSlot;
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
                'last_name' => 'Khan',
                'school_class_id' => $class->id,
                'section_id' => $section->id,
            ]
        );

        $class9 = SchoolClass::firstOrCreate(['name' => 'Grade 9'], ['grade_level' => '9']);
        $section9 = Section::firstOrCreate(
            ['school_class_id' => $class9->id, 'name' => 'B'],
            []
        );

        $student2 = Student::firstOrCreate(
            ['admission_no' => 'STU-002'],
            [
                'first_name' => 'Sara',
                'last_name' => 'Khan',
                'school_class_id' => $class9->id,
                'section_id' => $section9->id,
            ]
        );

        $parent = User::where('email', 'parent@school.test')->first();
        if ($parent) {
            $parent->children()->syncWithoutDetaching([$student->id, $student2->id]);
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

        TimetableSlot::firstOrCreate(
            [
                'school_class_id' => $class->id,
                'section_id' => $section->id,
                'subject_id' => $math->id,
                'day_of_week' => now()->dayOfWeek,
                'start_time' => '09:00',
            ],
            ['end_time' => '09:45', 'room' => '101']
        );

        DatesheetEntry::firstOrCreate(
            ['title' => 'Mid-term Mathematics', 'exam_date' => now()->addWeeks(2)->toDateString()],
            ['school_class_id' => $class->id, 'subject_id' => $math->id, 'notes' => 'Bring calculator']
        );

        OnlineClassLink::firstOrCreate(
            ['school_class_id' => $class->id, 'section_id' => $section->id, 'label' => 'Google Classroom'],
            [
                'subject_id' => $math->id,
                'url' => 'https://classroom.google.com',
                'day_of_week' => now()->dayOfWeek,
                'start_time' => '10:00',
                'minutes_before' => 30,
            ]
        );

        FeeVoucher::firstOrCreate(
            ['student_id' => $student->id, 'title' => 'Term 2 fee voucher'],
            ['submission_status' => 'pending', 'updated_by_user_id' => $teacher?->id]
        );

        $principal = User::where('email', 'principal@school.test')->first();
        FeedPost::firstOrCreate(
            ['title' => 'Sports day announcement', 'type' => 'announcement'],
            [
                'body' => 'Annual sports day next month. All families are welcome.',
                'scope' => 'school',
                'author_user_id' => $principal?->id ?? $teacher?->id,
                'published_at' => now(),
            ]
        );

        FeedPost::firstOrCreate(
            ['title' => 'School holiday schedule', 'type' => 'announcement'],
            [
                'body' => 'The institute will be closed on the upcoming public holiday. Classes resume as per the calendar.',
                'scope' => 'school',
                'author_user_id' => $principal?->id ?? $teacher?->id,
                'published_at' => now()->subDay(),
            ]
        );

        $batch = AttendanceBatch::firstOrCreate(
            [
                'school_class_id' => $class->id,
                'section_id' => $section->id,
                'date' => now()->subDay()->toDateString(),
            ],
            ['submitted_by_user_id' => $teacher?->id]
        );
        AttendanceRecord::firstOrCreate(
            ['attendance_batch_id' => $batch->id, 'student_id' => $student->id],
            ['status' => 'present']
        );
    }
}
