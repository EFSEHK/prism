<?php

namespace App\Services\Aims;

use App\Models\Student;

class AimsStudentResolver
{
    public function resolve(?string $uid, ?string $cnic): ?Student
    {
        $uid = trim((string) $uid);
        if ($uid !== '') {
            $student = Student::query()->where('admission_no', $uid)->first();
            if ($student) {
                return $student;
            }
        }

        $cnic = $this->normalizeCnic((string) $cnic);
        if ($cnic !== '') {
            return Student::query()->where('cnic', $cnic)->first();
        }

        return null;
    }

    public function normalizeCnic(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $digits = preg_replace('/\D/', '', $value) ?? '';

        return $digits !== '' ? $digits : $value;
    }
}
