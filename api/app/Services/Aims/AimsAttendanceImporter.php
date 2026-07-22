<?php

namespace App\Services\Aims;

use App\Models\AttendanceBatch;
use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;

class AimsAttendanceImporter
{
    public function __construct(
        private AimsCsvReader $reader,
        private AimsStudentResolver $students,
    ) {}

    /**
     * @return array{processed: int, succeeded: int, skipped: int, failed: int, errors: list<string>}
     */
    public function import(string $path, int $userId): array
    {
        $stats = ['processed' => 0, 'succeeded' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];
        $parsed = $this->reader->read($path);
        $groups = [];

        foreach ($parsed['rows'] as $row) {
            $stats['processed']++;

            if ((int) ($row['status'] ?? 1) === 0) {
                $stats['skipped']++;

                continue;
            }

            $student = $this->students->resolve($row['student_uid'] ?? '', $row['student_cnic'] ?? '');
            if (! $student) {
                $stats['skipped']++;
                $stats['errors'][] = 'Unmatched student: '.($row['student_uid'] ?? 'unknown');

                continue;
            }

            if (! $student->section_id) {
                $stats['skipped']++;
                $stats['errors'][] = ($row['student_uid'] ?? 'unknown').': student has no section';

                continue;
            }

            $date = $row['attendance_date'] ?? '';
            if ($date === '') {
                $stats['skipped']++;

                continue;
            }

            $status = $this->mapStatus((int) ($row['attendance_status'] ?? 3));
            $key = $student->section_id.'|'.$date;
            $groups[$key]['section_id'] = $student->section_id;
            $groups[$key]['date'] = $date;
            $groups[$key]['records'][$student->id] = $status;
        }

        DB::transaction(function () use ($groups, $userId, &$stats) {
            foreach ($groups as $group) {
                $existing = AttendanceBatch::query()
                    ->where('section_id', $group['section_id'])
                    ->whereDate('date', $group['date'])
                    ->first();

                if ($existing && $existing->status === 'verified') {
                    $stats['skipped'] += count($group['records']);
                    $stats['errors'][] = "Skipped verified batch: section {$group['section_id']} on {$group['date']}";

                    continue;
                }

                $batch = AttendanceBatch::query()->updateOrCreate(
                    [
                        'section_id' => $group['section_id'],
                        'date' => $group['date'],
                    ],
                    [
                        'status' => 'submitted',
                        'submitted_by_user_id' => $userId,
                        'verified_by_user_id' => null,
                        'verified_at' => null,
                    ]
                );

                AttendanceRecord::query()->where('attendance_batch_id', $batch->id)->delete();

                foreach ($group['records'] as $studentId => $status) {
                    AttendanceRecord::query()->create([
                        'attendance_batch_id' => $batch->id,
                        'student_id' => $studentId,
                        'status' => $status,
                    ]);
                    $stats['succeeded']++;
                }
            }
        });

        return $stats;
    }

    private function mapStatus(int $aimsStatus): string
    {
        return match ($aimsStatus) {
            1, 2 => 'present',
            4 => 'leave',
            default => 'absent',
        };
    }
}
