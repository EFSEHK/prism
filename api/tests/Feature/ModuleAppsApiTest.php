<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ModuleAppsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'superadmin',
            'developer',
            'admin',
            'teacher',
            'parent',
            'student',
            'computer_operator',
            'section_head',
            'class_incharge',
            'principal',
            'vice_principal',
            'accountant',
        ] as $role) {
            Role::findOrCreate($role, 'web');
        }

        Permission::findOrCreate('manage_academic_structure', 'web');
        Permission::findOrCreate('manage_student_roster', 'web');
        Role::findByName('superadmin', 'web')->givePermissionTo(Permission::all());
    }

    public function test_superadmin_can_list_and_update_apps(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['superadmin']);
        Sanctum::actingAs($user);

        $list = $this->getJson('/api/efsc/apps');
        $list->assertOk();
        $list->assertJsonStructure(['data', 'roles']);
        $this->assertNotEmpty($list->json('data'));

        $payload = [
            'modules' => [
                [
                    'id' => 'attendance',
                    'status' => 'live',
                    'visible_roles' => ['teacher', 'parent'],
                ],
            ],
        ];

        $save = $this->putJson('/api/efsc/apps', $payload);
        $save->assertOk();
        $save->assertJsonPath('message', 'App visibility saved.');

        $attendance = collect($save->json('data'))->firstWhere('id', 'attendance');
        $this->assertSame('live', $attendance['status']);
        $this->assertEqualsCanonicalizing(['teacher', 'parent'], $attendance['visible_roles']);
    }

    public function test_developer_can_list_apps(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['developer']);
        Sanctum::actingAs($user);

        $this->getJson('/api/efsc/apps')->assertOk();
    }

    public function test_teacher_cannot_manage_apps(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['teacher']);
        Sanctum::actingAs($user);

        $this->getJson('/api/efsc/apps')->assertForbidden();
        $this->putJson('/api/efsc/apps', [
            'modules' => [
                ['id' => 'attendance', 'status' => 'live', 'visible_roles' => ['teacher']],
            ],
        ])->assertForbidden();
    }
}
