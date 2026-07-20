<?php

namespace App\Services\Roster;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Services\StudentUserProvisioningService;
use Illuminate\Support\Facades\DB;

class StudentCsvImportService
{
    public function __construct(
        private AcademicStructureImportService $academic,
        private StudentUserProvisioningService $provisioning,
    ) {}

    /**
     * @return array{students: int, users: int, skipped: int, errors: list<string>}
     */
    public function import(?string $path = null): array
    {
        $path ??= RosterCsvPath::resolve('students.csv');
        $year = $this->academic->ensureCurrentYear();

        $labels = $this->academic->uniqueClassLabelsFromCsv($path);
        foreach ($labels as $label) {
            $this->academic->resolveClass($label, $year);
        }

        $stats = ['students' => 0, 'users' => 0, 'skipped' => 0, 'errors' => []];

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Cannot open {$path}");
        }

        fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            try {
                $result = $this->importRow($row, $year);
                if ($result === null) {
                    $stats['skipped']++;
                    continue;
                }
                $stats['students']++;
                if ($result['user_created']) {
                    $stats['users']++;
                }
            } catch (\Throwable $e) {
                $uid = trim($row[1] ?? '');
                $stats['errors'][] = ($uid !== '' ? $uid.': ' : '').$e->getMessage();
            }
        }
        fclose($handle);

        return $stats;
    }

    /**
     * @return ?array{user_created: bool}
     */
    private function importRow(array $row, AcademicYear $year): ?array
    {
        $admissionNo = trim($row[1] ?? '');
        $cnic = $this->normalizeCnic($row[2] ?? '');
        $fullName = trim($row[3] ?? '');
        $classLabel = trim($row[4] ?? '');
        $rollNo = trim($row[5] ?? '');
        $status = strtoupper(trim($row[6] ?? ''));

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
            return Student::query()->updateOrCreate(
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

        $accounts = $this->provisioning->provisionForStudent($student);

        return [
            'user_created' => (bool) ($accounts['student']['created'] ?? false),
        ];
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
