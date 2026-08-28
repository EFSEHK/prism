<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Area;
use App\Models\SchoolClass;
use App\Models\Section;
use Database\Seeders\SchoolDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_areas_classes_and_sections(): void
    {
        $this->seed(SchoolDataSeeder::class);

        $this->assertDatabaseHas('academic_years', [
            'name' => '2026-27',
            'is_current' => true,
        ]);

        $this->assertSame(6, Area::query()->count());
        $this->assertSame(14, SchoolClass::query()->count());
        $this->assertSame(47, Section::query()->count());

        $year = AcademicYear::query()->where('name', '2026-27')->firstOrFail();

        $middleBoys = Area::query()
            ->where('academic_year_id', $year->id)
            ->where('name', 'Middle Boys')
            ->firstOrFail();

        $sixth = SchoolClass::query()
            ->where('area_id', $middleBoys->id)
            ->where('name', '6th')
            ->firstOrFail();

        $this->assertEquals(
            ['Green', 'Blue', 'Orange'],
            $sixth->sections()->orderBy('sequence')->pluck('name')->all()
        );

        $collegeGirls = Area::query()
            ->where('academic_year_id', $year->id)
            ->where('name', 'College Girls')
            ->firstOrFail();

        $eleventhGirls = SchoolClass::query()
            ->where('area_id', $collegeGirls->id)
            ->where('name', '11th')
            ->firstOrFail();

        $this->assertEquals(
            ['White', 'Green', 'Blue', 'Red'],
            $eleventhGirls->sections()->orderBy('sequence')->pluck('name')->all()
        );
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(SchoolDataSeeder::class);
        $this->seed(SchoolDataSeeder::class);

        $this->assertSame(6, Area::query()->count());
        $this->assertSame(14, SchoolClass::query()->count());
        $this->assertSame(47, Section::query()->count());
    }
}
