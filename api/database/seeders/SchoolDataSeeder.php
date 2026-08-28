<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Area;
use App\Models\SchoolClass;
use App\Models\Section;
use Illuminate\Database\Seeder;

class SchoolDataSeeder extends Seeder
{
    /**
     * Area → class → section names for the current academic year.
     *
     * @var array<string, array<string, list<string>>>
     */
    private const STRUCTURE = [
        'Middle Boys' => [
            '6th' => ['Green', 'Blue', 'Orange'],
            '7th' => ['White', 'Green', 'Blue', 'Orange'],
            '8th' => ['White', 'Green', 'Blue', 'Orange'],
        ],
        'Middle Girls' => [
            '6th' => ['Green', 'Blue'],
            '7th' => ['Green', 'Blue', 'Orange'],
            '8th' => ['Green', 'Blue', 'Orange'],
        ],
        'Matric Boys' => [
            '9th' => ['White', 'Green', 'Blue', 'Orange'],
            '10th' => ['White', 'Green', 'Orange', 'Blue'],
        ],
        'Matric Girls' => [
            '9th' => ['White', 'Green', 'Blue'],
            '10th' => ['Green', 'White', 'Blue'],
        ],
        'College Boys' => [
            '11th' => ['White', 'Green', 'Blue'],
            '12th' => ['White', 'Green', 'Blue'],
        ],
        'College Girls' => [
            '11th' => ['White', 'Green', 'Blue', 'Red'],
            '12th' => ['White', 'Green', 'Blue', 'Red'],
        ],
    ];

    public function run(): void
    {
        $year = AcademicYear::query()->firstOrCreate(
            ['name' => '2026-27'],
            [
                'starts_on' => '2026-04-01',
                'ends_on' => '2027-03-31',
                'is_current' => true,
            ]
        );

        if (! $year->is_current) {
            AcademicYear::query()->where('is_current', true)->update(['is_current' => false]);
            $year->update(['is_current' => true]);
        }

        $areaSequence = 0;
        foreach (self::STRUCTURE as $areaName => $classes) {
            $areaSequence++;
            $area = Area::query()->updateOrCreate(
                ['academic_year_id' => $year->id, 'name' => $areaName],
                ['sequence' => $areaSequence, 'section_head_user_id' => null]
            );

            $classSequence = 0;
            foreach ($classes as $className => $sections) {
                $classSequence++;
                $schoolClass = SchoolClass::query()->updateOrCreate(
                    ['area_id' => $area->id, 'name' => $className],
                    ['sequence' => $classSequence]
                );

                $sectionSequence = 0;
                foreach ($sections as $sectionName) {
                    $sectionSequence++;
                    Section::query()->updateOrCreate(
                        ['school_class_id' => $schoolClass->id, 'name' => $sectionName],
                        ['sequence' => $sectionSequence]
                    );
                }
            }
        }
    }
}
