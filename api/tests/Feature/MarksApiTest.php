<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\MarkSheet;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\NotificationCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MarksApiTest extends TestCase
{
    use RefreshDatabase;

    private StudyGroup $studyGroup;

    private Subject $subject;

    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'teacher', 'section_head', 'computer_operator', 'parent'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        foreach (['manage_assessments', 'enter_marks', 'verify_marks', 'view_marks_reports', 'view_own_marks'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findByName('computer_operator', 'web')->givePermissionTo(['manage_assessments']);
        Role::findByName('teacher', 'web')->givePermissionTo(['enter_marks']);
        Role::findByName('section_head', 'web')->givePermissionTo(['verify_marks', 'view_marks_reports']);
        Role::findByName('parent', 'web')->givePermissionTo(['view_own_marks']);
        Role::findByName('superadmin', 'web')->givePermissionTo(Permission::all());

        $this->seed(NotificationCatalogSeeder::class);

        $this->studyGroup = StudyGroup::query()->create(['name' => 'Group A']);
        $this->subject = Subject::query()->create(['name' => 'Math']);
        $this->assessment = Assessment::query()->create([
            'type' => 'test',
            'name' => 'Unit 1',
            'created_by_user_id' => User::factory()->create()->id,
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }

    public function test_operator_can_create_assessment(): void
    {
        Sanctum::actingAs($this->userWithRole('computer_operator'));

        $this->postJson('/api/efsc/assessments', [
            'type' => 'exam',
            'name' => 'Midterm',
            'number' => 1,
        ])->assertCreated()->assertJsonPath('name', 'Midterm');
    }

    public function test_teacher_can_create_sheet_enter_and_section_head_verifies(): void
    {
        $teacher = $this->userWithRole('teacher');
        Sanctum::actingAs($teacher);

        $sheetRes = $this->postJson('/api/efsc/mark-sheets', [
            'assessment_id' => $this->assessment->id,
            'study_group_id' => $this->studyGroup->id,
            'subject_id' => $this->subject->id,
        ]);
        $sheetRes->assertCreated();
        $sheetId = $sheetRes->json('id');

        $student = \App\Models\Student::query()->create([
            'first_name' => 'Sara',
            'last_name' => 'Ali',
            'study_group_id' => $this->studyGroup->id,
        ]);

        $this->postJson("/api/efsc/mark-sheets/{$sheetId}/entries", [
            'entries' => [
                [
                    'student_id' => $student->id,
                    'marks_obtained' => 35,
                    'max_marks' => 50,
                    'grade' => 'B',
                ],
            ],
        ])->assertOk()->assertJsonPath('status', 'submitted');

        Sanctum::actingAs($this->userWithRole('section_head'));
        $this->postJson("/api/efsc/mark-sheets/{$sheetId}/verify")
            ->assertOk()
            ->assertJsonPath('status', 'verified');

        $this->assertDatabaseHas('mark_sheets', [
            'id' => $sheetId,
            'status' => 'verified',
        ]);
    }

    public function test_parent_cannot_enter_marks(): void
    {
        Sanctum::actingAs($this->userWithRole('parent'));

        $this->postJson('/api/efsc/mark-sheets', [
            'assessment_id' => $this->assessment->id,
            'study_group_id' => $this->studyGroup->id,
            'subject_id' => $this->subject->id,
        ])->assertForbidden();
    }
}
