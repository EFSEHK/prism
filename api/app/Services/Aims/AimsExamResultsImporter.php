<?php

namespace App\Services\Aims;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\MarkEntry;
use App\Models\MarkSheet;
use Illuminate\Support\Facades\DB;

class AimsExamResultsImporter
{
    public function __construct(
        private AimsCsvReader $reader,
        private AimsStudentResolver $students,
        private AimsSubjectResolver $subjects,
    ) {}

    /**
     * @return array{processed: int, succeeded: int, skipped: int, failed: int, errors: list<string>}
     */
    public function import(string $path, int $userId): array
    {
        $stats = ['processed' => 0, 'succeeded' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];
        $parsed = $this->reader->read($path);
        $year = AcademicYear::query()->where('is_current', true)->first();

        DB::transaction(function () use ($parsed, $userId, $year, &$stats) {
            foreach ($parsed['rows'] as $row) {
                $stats['processed']++;

                if (strtolower($row['status'] ?? 'active') === 'inactive') {
                    $stats['skipped']++;

                    continue;
                }

                $student = $this->students->resolve($row['student_uid'] ?? '', $row['student_cnic'] ?? '');
                if (! $student || ! $student->study_group_id) {
                    $stats['skipped']++;
                    $stats['errors'][] = 'Unmatched student: '.($row['student_uid'] ?? 'unknown');

                    continue;
                }

                $subjectName = trim($row['subject'] ?? '');
                $examName = trim($row['exam_name'] ?? '');
                if ($subjectName === '' || $examName === '') {
                    $stats['skipped']++;

                    continue;
                }

                $heldOn = ($row['exam_date'] ?? '') !== '' ? $row['exam_date'] : null;

                $assessment = Assessment::query()->firstOrCreate(
                    [
                        'type' => 'exam',
                        'name' => $examName,
                        'held_on' => $heldOn,
                    ],
                    [
                        'academic_year_id' => $year?->id,
                        'created_by_user_id' => $userId,
                    ]
                );

                $subject = $this->subjects->resolve($subjectName, $student);

                $sheet = MarkSheet::query()->firstOrCreate(
                    [
                        'assessment_id' => $assessment->id,
                        'study_group_id' => $student->study_group_id,
                        'subject_id' => $subject->id,
                    ],
                    [
                        'status' => 'submitted',
                        'submitted_by_user_id' => $userId,
                    ]
                );

                if ($sheet->status === 'draft') {
                    $sheet->update([
                        'status' => 'submitted',
                        'submitted_by_user_id' => $userId,
                    ]);
                }

                $maxMarks = (float) ($row['total_marks'] ?? 0);
                $obtained = (float) ($row['obtained_marks'] ?? 0);
                $isPass = $maxMarks > 0 ? ($obtained / $maxMarks) >= 0.4 : null;

                MarkEntry::query()->updateOrCreate(
                    [
                        'mark_sheet_id' => $sheet->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'marks_obtained' => $obtained,
                        'max_marks' => $maxMarks,
                        'is_pass' => $isPass,
                    ]
                );

                $stats['succeeded']++;
            }
        });

        return $stats;
    }
}
