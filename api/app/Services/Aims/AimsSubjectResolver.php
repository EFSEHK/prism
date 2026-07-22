<?php

namespace App\Services\Aims;

use App\Models\Student;
use App\Models\Subject;
use App\Models\StudyGroup;

class AimsSubjectResolver
{
    public function resolve(string $name, ?Student $student = null): Subject
    {
        $name = trim($name);
        $subject = Subject::query()->firstOrCreate(
            ['name' => $name],
            ['code' => strtoupper(substr(preg_replace('/\s+/', '', $name) ?? $name, 0, 8))]
        );

        if ($student?->study_group_id) {
            $studyGroup = StudyGroup::query()->find($student->study_group_id);
            if ($studyGroup && ! $studyGroup->subjects()->where('subjects.id', $subject->id)->exists()) {
                $studyGroup->subjects()->attach($subject->id);
            }
        }

        return $subject;
    }
}
