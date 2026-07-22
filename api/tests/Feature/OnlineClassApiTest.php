<?php

namespace Tests\Feature;

use App\Models\OnlineClassLink;
use App\Models\StudyGroup;
use App\Models\User;
use Database\Seeders\NotificationCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OnlineClassApiTest extends TestCase
{
    use RefreshDatabase;

    private StudyGroup $studyGroup;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['teacher', 'section_head', 'parent'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        foreach (['manage_online_classes', 'approve_online_classes', 'view_own_online_classes'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findByName('teacher', 'web')->givePermissionTo(['manage_online_classes']);
        Role::findByName('section_head', 'web')->givePermissionTo(['approve_online_classes']);
        Role::findByName('parent', 'web')->givePermissionTo(['view_own_online_classes']);

        $this->seed(NotificationCatalogSeeder::class);
        $this->studyGroup = StudyGroup::query()->create(['name' => 'Group A']);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }

    public function test_teacher_creates_pending_link(): void
    {
        Sanctum::actingAs($this->userWithRole('teacher'));

        $this->postJson('/api/efsc/online-classes', [
            'study_group_id' => $this->studyGroup->id,
            'label' => 'Meet link',
            'url' => 'https://meet.google.com/abc-defg-hij',
            'scheduled_date' => '2026-08-10',
            'start_time' => '10:00',
        ])->assertCreated()->assertJsonPath('status', 'pending_approval');
    }

    public function test_section_head_can_approve_and_reject(): void
    {
        $teacher = $this->userWithRole('teacher');
        $link = OnlineClassLink::query()->create([
            'study_group_id' => $this->studyGroup->id,
            'label' => 'Class',
            'url' => 'https://meet.google.com/xyz',
            'scheduled_date' => '2026-08-10',
            'start_time' => '10:00',
            'status' => 'pending_approval',
            'created_by_user_id' => $teacher->id,
        ]);

        Sanctum::actingAs($this->userWithRole('section_head'));
        $this->postJson("/api/efsc/online-classes/{$link->id}/approve")
            ->assertOk()
            ->assertJsonPath('status', 'approved');

        $link2 = OnlineClassLink::query()->create([
            'study_group_id' => $this->studyGroup->id,
            'label' => 'Class 2',
            'url' => 'https://meet.google.com/uvw',
            'scheduled_date' => '2026-08-11',
            'start_time' => '11:00',
            'status' => 'pending_approval',
            'created_by_user_id' => $teacher->id,
        ]);

        $this->postJson("/api/efsc/online-classes/{$link2->id}/reject")
            ->assertOk()
            ->assertJsonPath('status', 'rejected');
    }

    public function test_parent_sees_only_approved(): void
    {
        $teacher = $this->userWithRole('teacher');
        OnlineClassLink::query()->create([
            'study_group_id' => $this->studyGroup->id,
            'label' => 'Pending',
            'url' => 'https://meet.google.com/p',
            'scheduled_date' => '2026-08-10',
            'start_time' => '10:00',
            'status' => 'pending_approval',
            'created_by_user_id' => $teacher->id,
        ]);
        OnlineClassLink::query()->create([
            'study_group_id' => $this->studyGroup->id,
            'label' => 'Approved',
            'url' => 'https://meet.google.com/a',
            'scheduled_date' => '2026-08-10',
            'start_time' => '10:00',
            'status' => 'approved',
            'created_by_user_id' => $teacher->id,
        ]);

        $parent = $this->userWithRole('parent');
        $child = \App\Models\Student::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'study_group_id' => $this->studyGroup->id,
        ]);
        $parent->children()->attach($child->id);
        Sanctum::actingAs($parent);

        $response = $this->getJson('/api/efsc/online-classes');
        $response->assertOk();
        $labels = collect($response->json('data'))->pluck('label')->all();
        $this->assertSame(['Approved'], $labels);
    }
}
