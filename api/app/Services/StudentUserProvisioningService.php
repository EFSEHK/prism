<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use App\Support\LoginIdentifier;
use Illuminate\Support\Facades\DB;

class StudentUserProvisioningService
{
    /**
     * @return array{student: ?array, parents: list<array>}
     */
    public function provisionForStudent(Student $student): array
    {
        return DB::transaction(function () use ($student) {
            $result = ['student' => null, 'parents' => []];

            if ($student->admission_no) {
                $result['student'] = $this->provisionStudentUser($student);
            }

            foreach ($this->parentCandidates($student) as $parent) {
                $provisioned = $this->provisionParentUser($student, $parent['cnic'], $parent['name']);
                if ($provisioned) {
                    $result['parents'][] = $provisioned;
                }
            }

            return $result;
        });
    }

    /**
     * @return array{email: string, created: bool, user_id: int}
     */
    private function provisionStudentUser(Student $student): array
    {
        $local = LoginIdentifier::normalizeLocalPart($student->admission_no);
        $email = LoginIdentifier::emailFromLocalPart($local);
        $name = trim("{$student->first_name} {$student->last_name}");

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name !== '' ? $name : $local,
                'password' => $local,
            ]
        );

        if (! $user->wasRecentlyCreated && $user->name !== $name && $name !== '') {
            $user->update(['name' => $name]);
        }

        if (! $user->hasRole('student')) {
            $user->assignRole('student');
        }

        if ($student->user_id !== $user->id) {
            $student->update(['user_id' => $user->id]);
        }

        return [
            'email' => $email,
            'created' => $user->wasRecentlyCreated,
            'user_id' => $user->id,
        ];
    }

    /**
     * @return list<array{cnic: string, name: string}>
     */
    private function parentCandidates(Student $student): array
    {
        $candidates = [];

        if ($student->father_cnic) {
            $candidates[] = [
                'cnic' => $student->father_cnic,
                'name' => $student->father_name ?: 'Parent',
            ];
        }

        if ($student->guardian_cnic) {
            $guardianLocal = LoginIdentifier::normalizeLocalPart($student->guardian_cnic);
            $fatherLocal = $student->father_cnic
                ? LoginIdentifier::normalizeLocalPart($student->father_cnic)
                : null;

            if ($guardianLocal !== $fatherLocal) {
                $candidates[] = [
                    'cnic' => $student->guardian_cnic,
                    'name' => $student->guardian_name ?: 'Guardian',
                ];
            }
        }

        return $candidates;
    }

    /**
     * @return ?array{email: string, created: bool, user_id: int, name: string}
     */
    private function provisionParentUser(Student $student, string $cnic, string $name): ?array
    {
        $local = LoginIdentifier::normalizeLocalPart($cnic);
        if ($local === '') {
            return null;
        }

        $email = LoginIdentifier::emailFromLocalPart($local);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $local,
            ]
        );

        if (! $user->hasRole('parent')) {
            $user->assignRole('parent');
        }

        $student->parents()->syncWithoutDetaching([$user->id]);

        return [
            'email' => $email,
            'created' => $user->wasRecentlyCreated,
            'user_id' => $user->id,
            'name' => $name,
        ];
    }
}
