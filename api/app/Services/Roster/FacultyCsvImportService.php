<?php

namespace App\Services\Roster;

use App\Enums\StaffClassGroup;
use App\Models\Department;
use App\Models\Staff;
use App\Models\User;
use App\Support\LoginIdentifier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FacultyCsvImportService
{
    /**
     * @return array{staff: int, users: int, skipped: int, errors: list<string>}
     */
    public function import(?string $path = null): array
    {
        $path ??= RosterCsvPath::resolve('faculty.csv');
        $stats = ['staff' => 0, 'users' => 0, 'skipped' => 0, 'errors' => []];

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Cannot open {$path}");
        }

        // Skip title row and header row.
        fgetcsv($handle);
        fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            try {
                $parsed = $this->parseRow($row);
                if ($parsed === null) {
                    $stats['skipped']++;
                    continue;
                }

                $result = $this->importStaff($parsed);
                $stats['staff']++;
                if ($result['user_created']) {
                    $stats['users']++;
                }
            } catch (\Throwable $e) {
                $name = trim($row[1] ?? '');
                $stats['errors'][] = ($name !== '' ? $name.': ' : '').$e->getMessage();
            }
        }

        fclose($handle);

        return $stats;
    }

    /**
     * @return ?array<string, mixed>
     */
    private function parseRow(array $row): ?array
    {
        $name = trim($row[1] ?? '');
        $designation = trim($row[2] ?? '');

        if ($name === '' || $designation === '') {
            return null;
        }

        if (preg_match('/^(FEMALE|MALE|TOTAL)\s/i', $name)) {
            return null;
        }

        $bucket = strtoupper(trim($row[9] ?? ''));
        if ($bucket === '') {
            $bucket = strtoupper(trim($row[8] ?? ''));
        }

        $cnic = $this->normalizeCnic($this->extractCnic($row));
        $subject = trim($row[13] ?? '');
        if ($subject === '') {
            $subject = trim($row[7] ?? '');
        }

        return [
            'name' => $name,
            'designation' => $designation,
            'gender' => strtoupper(substr(trim($row[3] ?? ''), 0, 1)) ?: null,
            'contact_no' => trim($row[4] ?? '') ?: null,
            'date_of_joining' => $this->parseDate($row[5] ?? ''),
            'qualification' => $this->sanitizeText(trim($row[6] ?? '')) ?: null,
            'department_name' => trim($row[7] ?? '') ?: null,
            'classes' => $this->mapClassGroup($bucket, $designation),
            'subject' => $subject !== '' ? $subject : null,
            'cnic' => $cnic !== '' ? $cnic : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{user_created: bool}
     */
    private function importStaff(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $departmentId = null;
            if (! empty($data['department_name'])) {
                $department = Department::query()->firstOrCreate(['name' => $data['department_name']]);
                $departmentId = $department->id;
            }

            $match = [];
            if (! empty($data['cnic'])) {
                $match['cnic'] = $data['cnic'];
            } else {
                $match['name'] = $data['name'];
                $match['designation'] = $data['designation'];
            }

            $staff = Staff::query()->updateOrCreate(
                $match,
                [
                    'department_id' => $departmentId,
                    'name' => $data['name'],
                    'designation' => $data['designation'],
                    'gender' => $data['gender'],
                    'contact_no' => $data['contact_no'],
                    'date_of_joining' => $data['date_of_joining'],
                    'qualification' => $data['qualification'],
                    'classes' => $data['classes'],
                    'subject' => $data['subject'],
                    'cnic' => $data['cnic'],
                ]
            );

            $userCreated = false;
            $role = $this->mapRole($data['designation'], $data['classes']);

            if ($role !== null && ! empty($data['cnic']) && $data['classes'] !== StaffClassGroup::Supporting) {
                $local = LoginIdentifier::normalizeLocalPart($data['cnic']);
                $email = LoginIdentifier::emailFromLocalPart($local);

                $user = User::query()->firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $data['name'],
                        'password' => $local,
                    ]
                );

                if (! $user->wasRecentlyCreated && $user->name !== $data['name']) {
                    $user->update(['name' => $data['name']]);
                }

                if (! $user->hasRole($role)) {
                    $user->assignRole($role);
                }

                if ($staff->user_id !== $user->id) {
                    $staff->update(['user_id' => $user->id]);
                }

                $userCreated = $user->wasRecentlyCreated;
            }

            return ['user_created' => $userCreated];
        });
    }

    private function mapClassGroup(string $bucket, string $designation): ?StaffClassGroup
    {
        $bucket = strtoupper(trim($bucket));
        $designation = strtoupper(trim($designation));

        if ($bucket === 'SUPPORTING' || str_contains($designation, 'SECURITY') || str_contains($designation, 'COOK')
            || str_contains($designation, 'DRIVER') || str_contains($designation, 'AYA')
            || str_contains($designation, 'WATCHMAN') || str_contains($designation, 'OFFICE BOY')
            || str_contains($designation, 'SENITARY')) {
            return StaffClassGroup::Supporting;
        }

        return match (true) {
            in_array($bucket, ['MANAGEMENT', 'SECTION HEADS', 'ADMIN'], true) => StaffClassGroup::Management,
            in_array($bucket, ['CLG FACULTY', 'COLLEGE'], true) => StaffClassGroup::CollegeFaculty,
            in_array($bucket, ['SS FACULTY', 'JS FACULTY', 'SENIOR', 'JUNIOR'], true) => StaffClassGroup::SchoolFaculty,
            $bucket === 'VISITING' => StaffClassGroup::Visiting,
            $bucket === 'PTI' => StaffClassGroup::Pti,
            in_array($bucket, ['TA COLLEGE', 'TA SCHOOL'], true) => StaffClassGroup::TeachingAssistant,
            str_contains($designation, 'TA (') || str_contains($designation, 'T.A (') => StaffClassGroup::TeachingAssistant,
            default => StaffClassGroup::Management,
        };
    }

    private function mapRole(string $designation, ?StaffClassGroup $classes): ?string
    {
        $d = strtoupper(trim($designation));

        if ($classes === StaffClassGroup::Supporting) {
            return null;
        }

        return match (true) {
            str_contains($d, 'V.PRINCIPAL') || str_contains($d, 'VICE PRINCIPAL') => 'vice_principal',
            str_contains($d, 'PRINCIPAL') => 'principal',
            str_contains($d, 'SECTION HEAD') => 'section_head',
            str_contains($d, 'ACCOUNTANT') => 'accountant',
            str_contains($d, 'COMPUTER OPERATOR') => 'computer_operator',
            str_contains($d, 'CLASS INCHARGE') => 'class_incharge',
            default => 'teacher',
        };
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || strtoupper($value) === 'N/A') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractCnic(array $row): string
    {
        for ($i = count($row) - 1; $i >= 0; $i--) {
            $cell = trim($row[$i] ?? '');
            if ($cell === '') {
                continue;
            }
            if (preg_match('/^\d{5}[\-\s]?\d{7}[\-\s]?\d$/', $cell) || preg_match('/^\d{13}$/', preg_replace('/\D/', '', $cell))) {
                return $cell;
            }
        }

        return '';
    }

    private function normalizeCnic(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $digits = preg_replace('/\D/', '', $value) ?? '';

        return strlen($digits) === 13 ? $digits : $value;
    }

    private function sanitizeText(string $value): string
    {
        $value = str_replace("\xC2\xA0", ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');

        return trim($value);
    }
}
