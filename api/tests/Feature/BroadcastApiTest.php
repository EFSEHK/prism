<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BroadcastApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['teacher', 'section_head', 'parent', 'admin'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        Permission::findOrCreate('publish_user_broadcasts', 'web');
        Role::findByName('teacher', 'web')->givePermissionTo(['publish_user_broadcasts']);
        Role::findByName('section_head', 'web')->givePermissionTo(['publish_user_broadcasts']);
        Role::findByName('admin', 'web')->givePermissionTo(['publish_user_broadcasts']);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }

    public function test_staff_can_create_broadcast_pending_approval(): void
    {
        Sanctum::actingAs($this->userWithRole('teacher'));

        $response = $this->postJson('/api/efsc/broadcasts', [
            'audience_type' => 'general',
            'title' => 'Holiday notice',
            'body' => 'School closed Monday',
            'visible_to_student' => false,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('user_broadcasts', [
            'title' => 'Holiday notice',
            'approval_status' => 'pending_approval',
        ]);
    }

    public function test_parent_lists_published_broadcasts_only(): void
    {
        $author = $this->userWithRole('admin');
        UserBroadcast::query()->create([
            'audience_type' => 'general',
            'title' => 'Published',
            'body' => 'Hello',
            'author_user_id' => $author->id,
            'published_at' => now(),
            'approval_status' => 'approved',
        ]);
        UserBroadcast::query()->create([
            'audience_type' => 'general',
            'title' => 'Pending',
            'body' => 'Not yet',
            'author_user_id' => $author->id,
            'published_at' => null,
            'approval_status' => 'pending_approval',
        ]);

        Sanctum::actingAs($this->userWithRole('parent'));
        $response = $this->getJson('/api/efsc/broadcasts');
        $response->assertOk();
        $titles = collect($response->json('data') ?? [])->pluck('title')->all();
        if ($titles === []) {
            $titles = collect($response->json())->pluck('title')->filter()->all();
        }
        $this->assertContains('Published', $titles);
        $this->assertNotContains('Pending', $titles);
    }
}
