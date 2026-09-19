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
        $rows = array_map(fn (array $row) => $this->normalizeRow($row), $parsed['rows']);

        // #region agent log
        $skipReasons = ['missing_required' => 0, 'status_not_admitted' => 0];
        $statusCounts = [];
        $sampleRows = [];
        $debugLog = function (string $hypothesisId, string $location, string $message, array $data) {
            $payload = [
                'sessionId' => 'e8d189',
                'runId' => 'post-fix',
                'hypothesisId' => $hypothesisId,
                'location' => $location,
                'message' => $message,
                'data' => $data,
                'timestamp' => (int) (microtime(true) * 1000),
            ];
            @file_put_contents(
                base_path('../debug-e8d189.log'),
                json_encode($payload, JSON_UNESCAPED_UNICODE)."\n",
                FILE_APPEND
            );
        };
        $headers = $parsed['headers'] ?? [];
        $first = $rows[0] ?? [];
        $debugLog('B', 'AimsStudentImporter.php:import', 'csv_headers_and_keys', [
            'headers' => $headers,
            'row_count' => count($rows),
            'has_admission_no' => ($first['admission_no'] ?? '') !== '',
            'has_full_name' => ($first['full_name'] ?? '') !== '',
            'has_class_label' => ($first['class_label'] ?? '') !== '',
            'has_status' => array_key_exists('status', $first),
            'first_row_keys' => array_keys($first),
            'first_admission_no_len' => strlen(trim((string) ($first['admission_no'] ?? ''))),
            'first_full_name_len' => strlen(trim((string) ($first['full_name'] ?? ''))),
            'first_class_label_len' => strlen(trim((string) ($first['class_label'] ?? ''))),
            'first_status_raw' => (string) ($first['status'] ?? ''),
            'first_status_upper' => strtoupper(trim((string) ($first['status'] ?? ''))),
            'first_col_count' => count($first),
        ]);
        // #endregion

        $labels = [];
        foreach ($rows as $row) {
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

        foreach ($rows as $row) {
            $stats['processed']++;
            try {
                $result = $this->importRow($row, $year, $skipReasons, $statusCounts, $sampleRows);
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

        // #region agent log
        $debugLog('A', 'AimsStudentImporter.php:import', 'skip_summary', [
            'processed' => $stats['processed'],
            'succeeded' => $stats['succeeded'],
            'skipped' => $stats['skipped'],
            'failed' => $stats['failed'],
            'skip_reasons' => $skipReasons,
            'status_counts' => $statusCounts,
            'sample_skips' => array_slice($sampleRows, 0, 8),
        ]);
        // #endregion

        return $stats;
    }

    /**
     * Map AIMS SAP export headers onto the canonical student CSV fields.
     *
     * @param  array<string, string>  $row
     * @return array<string, string>
     */
    private function normalizeRow(array $row): array
    {
        $aliases = [
            'admission_no' => ['admission_no', 'uid', 'student_uid'],
            'full_name' => ['full_name', 'student name', 'student_name', 'name'],
            'class_label' => ['class_label', 'class'],
            'roll_no' => ['roll_no', 'roll no', 'roll'],
            'cnic' => ['cnic', 'student_cnic'],
            'status' => ['status'],
        ];

        $normalized = $row;
        foreach ($aliases as $canonical => $keys) {
            if (trim((string) ($normalized[$canonical] ?? '')) !== '') {
                continue;
            }
            foreach ($keys as $key) {
                if (trim((string) ($row[$key] ?? '')) !== '') {
                    $normalized[$canonical] = $row[$key];
                    break;
                }
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<string, int>  $skipReasons
     * @param  array<string, int>  $statusCounts
     * @param  list<array<string, mixed>>  $sampleRows
     */
    private function importRow(array $row, AcademicYear $year, array &$skipReasons = [], array &$statusCounts = [], array &$sampleRows = []): ?bool
    {
        $admissionNo = trim($row['admission_no'] ?? '');
        $cnic = $this->normalizeCnic($row['cnic'] ?? '');
        $fullName = trim($row['full_name'] ?? '');
        $classLabel = trim($row['class_label'] ?? '');
        $rollNo = trim($row['roll_no'] ?? '');
        $statusRaw = trim($row['status'] ?? '');
        $status = strtoupper($statusRaw);

        // #region agent log
        $statusKey = $status === '' ? '(empty)' : $status;
        $statusCounts[$statusKey] = ($statusCounts[$statusKey] ?? 0) + 1;
        // #endregion

        if ($admissionNo === '' || $fullName === '' || $classLabel === '') {
            // #region agent log
            $skipReasons['missing_required'] = ($skipReasons['missing_required'] ?? 0) + 1;
            if (count($sampleRows) < 8) {
                $sampleRows[] = [
                    'reason' => 'missing_required',
                    'admission_empty' => $admissionNo === '',
                    'full_name_empty' => $fullName === '',
                    'class_label_empty' => $classLabel === '',
                    'status' => $statusKey,
                    'row_keys' => array_keys($row),
                ];
            }
            // #endregion
            return null;
        }

        if ($status !== '' && $status !== 'ADMITTED') {
            // #region agent log
            $skipReasons['status_not_admitted'] = ($skipReasons['status_not_admitted'] ?? 0) + 1;
            if (count($sampleRows) < 8) {
                $sampleRows[] = [
                    'reason' => 'status_not_admitted',
                    'status_raw' => $statusRaw,
                    'status_upper' => $status,
                    'status_len' => strlen($status),
                    'status_ord' => array_map('ord', str_split(substr($status, 0, 20))),
                ];
            }
            // #endregion
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
