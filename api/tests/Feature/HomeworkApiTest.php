<?php

namespace Tests\Feature;

use App\Models\HomeworkPost;
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

class HomeworkApiTest extends TestCase
{
    use RefreshDatabase;

    private StudyGroup $studyGroup;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'superadmin',
            'teacher',
            'section_head',
            'parent',
            'student',
            'admin',
        ] as $role) {
            Role::findOrCreate($role, 'web');
        }

        foreach ([
            'post_homework',
            'approve_homework',
            'view_own_homework',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findByName('teacher', 'web')->givePermissionTo(['post_homework']);
        Role::findByName('section_head', 'web')->givePermissionTo(['approve_homework']);
        Role::findByName('parent', 'web')->givePermissionTo(['view_own_homework']);
        Role::findByName('student', 'web')->givePermissionTo(['view_own_homework']);
        Role::findByName('superadmin', 'web')->givePermissionTo(Permission::all());

        $this->seed(NotificationCatalogSeeder::class);

        $this->studyGroup = StudyGroup::query()->create(['name' => 'Group A']);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }

    public function test_teacher_can_create_pending_homework(): void
    {
        $teacher = $this->userWithRole('teacher');
        Sanctum::actingAs($teacher);

        $response = $this->postJson('/api/efsc/homework', [
            'study_group_id' => $this->studyGroup->id,
            'title' => 'Math worksheet',
            'body' => 'Page 12',
            'due_date' => '2026-07-20',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'pending_approval')
            ->assertJsonPath('title', 'Math worksheet');

        $this->assertDatabaseHas('homework_posts', [
            'title' => 'Math worksheet',
            'status' => 'pending_approval',
            'created_by_user_id' => $teacher->id,
        ]);
    }

    public function test_section_head_can_approve_and_queues_notification(): void
    {
        $teacher = $this->userWithRole('teacher');
        $post = HomeworkPost::query()->create([
            'study_group_id' => $this->studyGroup->id,
            'title' => 'Science project',
            'body' => 'Build a model',
            'status' => 'pending_approval',
            'created_by_user_id' => $teacher->id,
        ]);

        $approver = $this->userWithRole('section_head');
        Sanctum::actingAs($approver);

        $response = $this->postJson("/api/efsc/homework/{$post->id}/approve");

        $response->assertOk()->assertJsonPath('status', 'approved');

        $feature = NotificationFeature::query()
            ->where('feature_key', NotificationFeatureKeys::HOMEWORK_NEW)
            ->first();
        $this->assertNotNull($feature);

        $this->assertDatabaseHas('notification_dispatch_requests', [
            'notification_feature_id' => $feature->id,
            'context_type' => 'HomeworkPost',
            'context_id' => $post->id,
        ]);

        $this->assertTrue(
            NotificationDispatchRequest::query()
                ->where('context_type', 'HomeworkPost')
                ->where('context_id', $post->id)
                ->exists()
        );
    }

    public function test_section_head_can_reject_pending_homework(): void
    {
        $teacher = $this->userWithRole('teacher');
        $post = HomeworkPost::query()->create([
            'study_group_id' => $this->studyGroup->id,
            'title' => 'Reject me',
            'status' => 'pending_approval',
            'created_by_user_id' => $teacher->id,
        ]);

        Sanctum::actingAs($this->userWithRole('section_head'));

        $this->postJson("/api/efsc/homework/{$post->id}/reject")
            ->assertOk()
            ->assertJsonPath('status', 'rejected');
    }

    public function test_reapprove_is_blocked(): void
    {
        $teacher = $this->userWithRole('teacher');
        $post = HomeworkPost::query()->create([
            'study_group_id' => $this->studyGroup->id,
            'title' => 'Already approved',
            'status' => 'approved',
            'created_by_user_id' => $teacher->id,
            'approved_at' => now(),
        ]);

        Sanctum::actingAs($this->userWithRole('section_head'));

        $this->postJson("/api/efsc/homework/{$post->id}/approve")
            ->assertStatus(422);
    }

    public function test_parent_sees_only_approved_homework_for_child_group(): void
    {
        $teacher = $this->userWithRole('teacher');
        HomeworkPost::query()->create([
            'study_group_id' => $this->studyGroup->id,
            'title' => 'Pending',
            'status' => 'pending_approval',
            'created_by_user_id' => $teacher->id,
        ]);
        HomeworkPost::query()->create([
            'study_group_id' => $this->studyGroup->id,
            'title' => 'Approved',
            'status' => 'approved',
            'created_by_user_id' => $teacher->id,
            'approved_at' => now(),
        ]);

        $parent = $this->userWithRole('parent');
        $child = Student::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'study_group_id' => $this->studyGroup->id,
        ]);
        $parent->children()->attach($child->id);

        Sanctum::actingAs($parent);

        $response = $this->getJson('/api/efsc/homework');
        $response->assertOk();
        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertContains('Approved', $titles);
        $this->assertNotContains('Pending', $titles);
    }

    public function test_unauthorized_post_and_approve_return_403(): void
    {
        $parent = $this->userWithRole('parent');
        Sanctum::actingAs($parent);

        $this->postJson('/api/efsc/homework', [
            'study_group_id' => $this->studyGroup->id,
            'title' => 'Nope',
        ])->assertForbidden();

        $teacher = $this->userWithRole('teacher');
        $post = HomeworkPost::query()->create([
            'study_group_id' => $this->studyGroup->id,
            'title' => 'Pending',
            'status' => 'pending_approval',
            'created_by_user_id' => $teacher->id,
        ]);

        $this->postJson("/api/efsc/homework/{$post->id}/approve")->assertForbidden();
    }

    public function test_staff_can_filter_by_status(): void
    {
        $teacher = $this->userWithRole('teacher');
        HomeworkPost::query()->create([
            'study_group_id' => $this->studyGroup->id,
            'title' => 'Pending one',
            'status' => 'pending_approval',
            'created_by_user_id' => $teacher->id,
        ]);
        HomeworkPost::query()->create([
            'study_group_id' => $this->studyGroup->id,
            'title' => 'Approved one',
            'status' => 'approved',
            'created_by_user_id' => $teacher->id,
            'approved_at' => now(),
        ]);

        Sanctum::actingAs($teacher);

        $response = $this->getJson('/api/efsc/homework?status=pending_approval');
        $response->assertOk();
        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertSame(['Pending one'], $titles);
    }

    public function test_admin_without_homework_perms_cannot_list(): void
    {
        Sanctum::actingAs($this->userWithRole('admin'));

        $this->getJson('/api/efsc/homework')->assertForbidden();
    }
}
