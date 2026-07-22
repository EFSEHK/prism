<?php

namespace App\Services\Aims;

use App\Models\AcademicYear;
use App\Services\Roster\AcademicStructureImportService;
use App\Services\Roster\StudentCsvImportService;
use App\Services\StudentUserProvisioningService;
use Illuminate\Support\Facades\DB;

class AimsStudentImporter
{
    public function __construct(
        private AimsCsvReader $reader,
        private AcademicStructureImportService $academic,
        private StudentUserProvisioningService $provisioning,
    ) {}

    /**
     * @return array{processed: int, succeeded: int, skipped: int, failed: int, errors: list<string>}
     */
    public function import(string $path): array
    {
        $stats = ['processed' => 0, 'succeeded' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];
        $parsed = $this->reader->read($path);
        $year = $this->academic->ensureCurrentYear();

        $labels = [];
        foreach ($parsed['rows'] as $row) {
            $label = trim($row['class_label'] ?? '');
            if ($label !== '') {
                $labels[$label] = true;
            }
        }
        foreach (array_keys($labels) as $label) {
            try {
                $this->academic->resolveClass($label, $year);
            } catch (\Throwable $e) {
                $stats['errors'][] = "Class label {$label}: ".$e->getMessage();
            }
        }

        foreach ($parsed['rows'] as $row) {
            $stats['processed']++;
            try {
                $result = $this->importRow($row, $year);
                if ($result === null) {
                    $stats['skipped']++;
                    continue;
                }
                $stats['succeeded']++;
            } catch (\Throwable $e) {
                $stats['failed']++;
                $uid = $row['admission_no'] ?? '';
                $stats['errors'][] = ($uid !== '' ? $uid.': ' : '').$e->getMessage();
            }
        }

        return $stats;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function importRow(array $row, AcademicYear $year): ?bool
    {
        $admissionNo = trim($row['admission_no'] ?? '');
        $cnic = $this->normalizeCnic($row['cnic'] ?? '');
        $fullName = trim($row['full_name'] ?? '');
        $classLabel = trim($row['class_label'] ?? '');
        $rollNo = trim($row['roll_no'] ?? '');
        $status = strtoupper(trim($row['status'] ?? ''));

        if ($admissionNo === '' || $fullName === '' || $classLabel === '') {
            return null;
        }

        if ($status !== '' && $status !== 'ADMITTED') {
            return null;
        }

        $resolved = $this->academic->resolveClass($classLabel, $year);
        $parts = preg_split('/\s+/', $fullName, 2);
        $firstName = $parts[0];
        $lastName = $parts[1] ?? '';

        $student = DB::transaction(function () use ($admissionNo, $cnic, $firstName, $lastName, $rollNo, $resolved) {
            return \App\Models\Student::query()->updateOrCreate(
                ['admission_no' => $admissionNo],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'cnic' => $cnic ?: null,
                    'roll_no' => $rollNo !== '' ? $rollNo : null,
                    'study_group_id' => $resolved['study_group']->id,
                    'section_id' => $resolved['section']->id,
                ]
            );
        });

        $this->provisioning->provisionForStudent($student);

        return true;
    }

    private function normalizeCnic(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $digits = preg_replace('/\D/', '', $value) ?? '';

        return $digits !== '' ? $digits : $value;
    }
}
