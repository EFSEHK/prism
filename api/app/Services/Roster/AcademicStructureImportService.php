<?php

namespace App\Services\Roster;

use App\Models\AcademicYear;
use App\Models\Area;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\StudyGroup;
use Illuminate\Support\Facades\DB;

class AcademicStructureImportService
{
    /** @var array<string, array{area: Area, school_class: SchoolClass, section: Section, study_group: StudyGroup}> */
    private array $classMap = [];

    public function ensureCurrentYear(string $name = '2026-27'): AcademicYear
    {
        return DB::transaction(function () use ($name) {
            AcademicYear::query()->where('is_current', true)->update(['is_current' => false]);

            return AcademicYear::query()->updateOrCreate(
                ['name' => $name],
                [
                    'starts_on' => '2026-04-01',
                    'ends_on' => '2027-03-31',
                    'is_current' => true,
                ]
            );
        });
    }

    /**
     * @return array{area: Area, school_class: SchoolClass, section: Section, study_group: StudyGroup}
     */
    public function resolveClass(string $classLabel, AcademicYear $year): array
    {
        $classLabel = trim($classLabel);
        if ($classLabel === '') {
            throw new \InvalidArgumentException('Empty class label.');
        }

        if (isset($this->classMap[$classLabel])) {
            return $this->classMap[$classLabel];
        }

        if (! preg_match('/^(\d+(?:ST|ND|RD|TH))\s+(\S+)\s+(BOYS|GIRLS)$/i', $classLabel, $matches)) {
            throw new \InvalidArgumentException("Unrecognized class format: {$classLabel}");
        }

        $grade = strtoupper($matches[1]);
        $sectionName = strtoupper($matches[2]);
        $areaName = ucfirst(strtolower($matches[3]));

        $area = Area::query()->firstOrCreate(
            ['academic_year_id' => $year->id, 'name' => $areaName],
            ['section_head_user_id' => null]
        );

        $schoolClass = SchoolClass::query()->firstOrCreate(
            ['area_id' => $area->id, 'name' => $grade],
            ['grade_level' => $grade]
        );

        $section = Section::query()->firstOrCreate(
            ['school_class_id' => $schoolClass->id, 'name' => $sectionName]
        );

        $studyGroup = StudyGroup::query()->firstOrCreate(['name' => $classLabel]);

        return $this->classMap[$classLabel] = [
            'area' => $area,
            'school_class' => $schoolClass,
            'section' => $section,
            'study_group' => $studyGroup,
        ];
    }

    /**
     * @return list<string>
     */
    public function uniqueClassLabelsFromCsv(string $path): array
    {
        $labels = [];
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Cannot open {$path}");
        }

        fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $label = trim($row[4] ?? '');
            if ($label !== '') {
                $labels[$label] = true;
            }
        }
        fclose($handle);

        return array_keys($labels);
    }
}
