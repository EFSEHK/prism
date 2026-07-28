<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Area;
use App\Models\DataImportLog;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\Aims\AimsCsvImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AimsImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('import_aims_data', 'web');
        Role::findOrCreate('student', 'web');
        Role::findOrCreate('parent', 'web');
        Role::findOrCreate('superadmin', 'web');
        Role::findOrCreate('accountant', 'web');
        Role::findOrCreate('vice_principal', 'web');
    }

    public function test_students_csv_import_via_api(): void
    {
        $year = AcademicYear::query()->create([
            'name' => '2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
        ]);
        $area = Area::query()->create(['academic_year_id' => $year->id, 'name' => 'Boys']);
        $schoolClass = SchoolClass::query()->create(['area_id' => $area->id, 'name' => '6TH', 'grade_level' => '6TH']);
        $section = Section::query()->create(['school_class_id' => $schoolClass->id, 'name' => 'GREEN']);
        StudyGroup::query()->create(['name' => '6TH GREEN BOYS']);

        $user = User::factory()->create();
        $user->assignRole('accountant');
        $user->givePermissionTo('import_aims_data');

        $csv = "admission_no,cnic,full_name,class_label,roll_no,status\n";
        $csv .= "10001,3520212345671,Ali Khan,6TH GREEN BOYS,12,ADMITTED\n";

        $file = UploadedFile::fake()->createWithContent('students_prism.csv', $csv);

        $response = $this->actingAs($user)->postJson('/api/efsc/import/aims/students', [
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertJsonPath('stats.succeeded', 1);

        $this->assertDatabaseHas('students', [
            'admission_no' => '10001',
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'section_id' => $section->id,
        ]);

        $this->assertSame(1, DataImportLog::query()->count());
    }

    public function test_import_requires_permission(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('students.csv', "admission_no\n");

        $this->actingAs($user)
            ->postJson('/api/efsc/import/aims/students', ['file' => $file])
            ->assertForbidden();
    }

    public function test_import_respects_apps_visible_roles(): void
    {
        \App\Models\ModuleSetting::query()->create([
            'module_id' => 'aims-import',
            'status' => 'live',
            'visible_roles' => ['superadmin', 'admin', 'accountant'],
        ]);

        $user = User::factory()->create();
        $user->assignRole('vice_principal');
        $user->givePermissionTo('import_aims_data');

        $file = UploadedFile::fake()->createWithContent('students.csv', "admission_no\n");

        $this->actingAs($user)
            ->postJson('/api/efsc/import/aims/students', ['file' => $file])
            ->assertForbidden();
    }

    public function test_attendance_status_mapping(): void
    {
        $service = app(AimsCsvImportService::class);
        $year = AcademicYear::query()->create([
            'name' => '2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
        ]);
        $area = Area::query()->create(['academic_year_id' => $year->id, 'name' => 'Boys']);
        $schoolClass = SchoolClass::query()->create(['area_id' => $area->id, 'name' => '6TH', 'grade_level' => '6TH']);
        $section = Section::query()->create(['school_class_id' => $schoolClass->id, 'name' => 'GREEN']);
        $group = StudyGroup::query()->create(['name' => '6TH GREEN BOYS']);
        Student::query()->create([
            'admission_no' => '20001',
            'first_name' => 'Sara',
            'last_name' => 'Test',
            'study_group_id' => $group->id,
            'section_id' => $section->id,
        ]);

        $path = storage_path('app/test_attendance.csv');
        $csv = "student_uid,student_r_uid,student_cnic,student_id,attendance_date,attendance_status,status\n";
        $csv .= "20001,,,,2026-07-01,2,1\n";
        file_put_contents($path, $csv);

        $user = User::factory()->create();
        $stats = $service->import('attendance', $path, $user->id);

        $this->assertSame(1, $stats['succeeded']);
        $this->assertDatabaseHas('attendance_records', ['status' => 'present']);

        @unlink($path);
    }

    public function test_superadmin_can_import_without_explicit_permission_in_token(): void
    {
        $year = AcademicYear::query()->create([
            'name' => '2026-27',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
        ]);
        $area = Area::query()->create(['academic_year_id' => $year->id, 'name' => 'Boys']);
        $schoolClass = SchoolClass::query()->create(['area_id' => $area->id, 'name' => '6TH', 'grade_level' => '6TH']);
        $section = Section::query()->create(['school_class_id' => $schoolClass->id, 'name' => 'GREEN']);
        StudyGroup::query()->create(['name' => '6TH GREEN BOYS']);

        $user = User::factory()->create();
        $user->assignRole('superadmin');

        $csv = "admission_no,cnic,full_name,class_label,roll_no,status\n";
        $csv .= "10002,3520212345672,Sara Khan,6TH GREEN BOYS,13,ADMITTED\n";

        $file = UploadedFile::fake()->createWithContent('students_efsc_ya.csv', $csv);

        $this->actingAs($user)
            ->postJson('/api/efsc/import/aims/students', ['file' => $file])
            ->assertOk()
            ->assertJsonPath('stats.succeeded', 1);
    }
}
