<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\NotificationDispatchRequest;
use App\Models\NotificationFeature;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\User;
use App\Support\NotificationFeatureKeys;
use Database\Seeders\NotificationCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeaveApiTest extends TestCase
{
    use RefreshDatabase;

    private StudyGroup $studyGroup;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'section_head', 'parent', 'admin'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        Permission::findOrCreate('manage_leave_requests', 'web');
        Role::findByName('section_head', 'web')->givePermissionTo(['manage_leave_requests']);
        Role::findByName('superadmin', 'web')->givePermissionTo(Permission::all());

        $this->seed(NotificationCatalogSeeder::class);

        $this->studyGroup = StudyGroup::query()->create(['name' => 'Group A']);
        $this->student = Student::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'study_group_id' => $this->studyGroup->id,
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }

    public function test_parent_can_submit_leave(): void
    {
        $parent = $this->userWithRole('parent');
        $parent->children()->attach($this->student->id);
        Sanctum::actingAs($parent);

        $response = $this->postJson('/api/efsc/leave-requests', [
            'student_id' => $this->student->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
            'reason' => 'Family trip',
        ]);

        $response->assertCreated()->assertJsonPath('status', 'pending');
        $this->assertDatabaseHas('leave_requests', [
            'student_id' => $this->student->id,
            'parent_user_id' => $parent->id,
            'status' => 'pending',
        ]);
    }

    public function test_staff_can_filter_by_status_and_decide_with_notification(): void
    {
        $parent = $this->userWithRole('parent');
        $parent->children()->attach($this->student->id);
        $leave = LeaveRequest::query()->create([
            'student_id' => $this->student->id,
            'parent_user_id' => $parent->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
            'status' => 'pending',
        ]);

        LeaveRequest::query()->create([
            'student_id' => $this->student->id,
            'parent_user_id' => $parent->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-01',
            'status' => 'approved',
        ]);

        $staff = $this->userWithRole('section_head');
        Sanctum::actingAs($staff);

        $list = $this->getJson('/api/efsc/leave-requests?status=pending');
        $list->assertOk();
        $this->assertCount(1, $list->json('data'));

        $this->postJson("/api/efsc/leave-requests/{$leave->id}/decide", ['status' => 'approved'])
            ->assertOk()
            ->assertJsonPath('status', 'approved');

        $feature = NotificationFeature::query()
            ->where('feature_key', NotificationFeatureKeys::LEAVE_DECISION_PARENT)
            ->first();
        $this->assertNotNull($feature);
        $this->assertDatabaseHas('notification_dispatch_requests', [
            'notification_feature_id' => $feature->id,
            'context_type' => 'LeaveRequest',
            'context_id' => $leave->id,
        ]);
        $this->assertTrue(
            NotificationDispatchRequest::query()
                ->where('context_type', 'LeaveRequest')
                ->where('context_id', $leave->id)
                ->exists()
        );
    }

    public function test_unauthorized_decide_is_forbidden(): void
    {
        $parent = $this->userWithRole('parent');
        $leave = LeaveRequest::query()->create([
            'student_id' => $this->student->id,
            'parent_user_id' => $parent->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($parent);
        $this->postJson("/api/efsc/leave-requests/{$leave->id}/decide", ['status' => 'approved'])
            ->assertForbidden();
    }
}
