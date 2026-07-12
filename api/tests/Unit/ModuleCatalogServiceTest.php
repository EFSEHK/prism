<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\ModuleCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ModuleCatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    private ModuleCatalogService $catalog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->catalog = new ModuleCatalogService;
        $this->seedPermissions();
    }

    public function test_superadmin_receives_full_staff_catalog(): void
    {
        $user = $this->userWithRoles(['superadmin']);

        $ids = collect($this->catalog->forUser($user))->pluck('id')->all();

        $this->assertSame([
            'dashboard',
            'users',
            'configuration',
            'permissions',
            'approvals',
            'attendance',
            'marks',
            'homework',
            'timetable',
            'online',
            'fees',
            'notifications',
            'leave',
        ], $ids);
    }

    public function test_teacher_does_not_receive_admin_or_fees_modules(): void
    {
        $user = $this->userWithRoles(['teacher']);

        $ids = collect($this->catalog->forUser($user))->pluck('id')->all();

        $this->assertContains('attendance', $ids);
        $this->assertContains('marks', $ids);
        $this->assertContains('timetable', $ids);
        $this->assertNotContains('users', $ids);
        $this->assertNotContains('permissions', $ids);
        $this->assertNotContains('fees', $ids);
        $this->assertNotContains('leave', $ids);
    }

    public function test_parent_receives_learner_modules_only(): void
    {
        $user = $this->userWithRoles(['parent']);

        $ids = collect($this->catalog->forUser($user))->pluck('id')->all();

        $this->assertSame([
            'dashboard',
            'homework',
            'marks',
            'attendance',
            'timetable',
            'notifications',
            'fees',
            'online',
            'leave',
        ], $ids);
    }

    public function test_platform_filter_excludes_missing_platforms(): void
    {
        $user = $this->userWithRoles(['superadmin']);

        $mobile = $this->catalog->forUser($user, 'mobile');

        $this->assertNotEmpty($mobile);
        foreach ($mobile as $module) {
            $this->assertContains('mobile', $module['platforms']);
            $this->assertTrue($module['enabled']);
        }
    }

    public function test_timetable_and_fees_are_marked_coming_soon(): void
    {
        $user = $this->userWithRoles(['superadmin']);

        $byId = collect($this->catalog->forUser($user))->keyBy('id');

        $this->assertTrue($byId->get('timetable')['coming_soon']);
        $this->assertTrue($byId->get('fees')['coming_soon']);
        $this->assertSame('coming_soon', $byId->get('timetable')['status']);
        $this->assertSame('coming_soon', $byId->get('fees')['status']);
        $this->assertFalse($byId->get('attendance')['coming_soon']);
        $this->assertSame('live', $byId->get('attendance')['status']);
        $this->assertFalse($byId->get('marks')['coming_soon']);
        $this->assertSame('live', $byId->get('marks')['status']);
    }

    private function seedPermissions(): void
    {
        foreach ([
            'manage_academic_structure',
            'manage_student_roster',
            'mark_attendance',
            'view_dashboard',
        ] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach ([
            'superadmin',
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
            'developer',
        ] as $role) {
            Role::findOrCreate($role, 'web');
        }

        Role::findByName('superadmin', 'web')->givePermissionTo(Permission::all());
    }

    /**
     * @param  list<string>  $roles
     */
    private function userWithRoles(array $roles): User
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->syncRoles($roles);

        return $user->fresh();
    }
}
