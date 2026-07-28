<?php

namespace Tests\Unit;

use App\Models\ModuleSetting;
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
            'apps',
            'aims-import',
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

    public function test_developer_receives_apps_module(): void
    {
        $user = $this->userWithRoles(['developer']);

        $ids = collect($this->catalog->forUser($user))->pluck('id')->all();

        $this->assertContains('dashboard', $ids);
        $this->assertContains('apps', $ids);
        $this->assertContains('aims-import', $ids);
        $this->assertContains('configuration', $ids);
        $this->assertNotContains('permissions', $ids);
        $this->assertNotContains('attendance', $ids);
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
        $this->assertNotContains('apps', $ids);
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
        $this->assertNotContains('apps', collect($mobile)->pluck('id')->all());
    }

    public function test_feature_modules_are_live_by_default(): void
    {
        $user = $this->userWithRoles(['superadmin']);

        $byId = collect($this->catalog->forUser($user))->keyBy('id');

        foreach ([
            'dashboard', 'users', 'configuration', 'permissions', 'apps', 'aims-import', 'approvals',
            'attendance', 'marks', 'homework', 'timetable', 'online', 'fees', 'notifications', 'leave',
        ] as $id) {
            $this->assertFalse($byId->get($id)['coming_soon'], $id);
            $this->assertSame('live', $byId->get($id)['status'], $id);
        }
    }

    public function test_accountant_receives_aims_import_module(): void
    {
        $user = $this->userWithRoles(['accountant']);

        $ids = collect($this->catalog->forUser($user))->pluck('id')->all();

        $this->assertContains('aims-import', $ids);
        $this->assertNotContains('users', $ids);
    }

    public function test_gate_modules_respect_visible_roles_over_permissions(): void
    {
        ModuleSetting::query()->create([
            'module_id' => 'aims-import',
            'status' => 'live',
            'visible_roles' => ['superadmin', 'developer', 'admin', 'computer_operator', 'accountant'],
        ]);
        ModuleSetting::query()->create([
            'module_id' => 'configuration',
            'status' => 'live',
            'visible_roles' => ['superadmin', 'admin', 'developer', 'computer_operator', 'section_head'],
        ]);

        $this->catalog = new ModuleCatalogService;

        $vicePrincipal = $this->userWithRoles(['vice_principal']);
        $vicePrincipal->givePermissionTo('import_aims_data');

        $classIncharge = $this->userWithRoles(['class_incharge']);
        $classIncharge->givePermissionTo('manage_student_roster');

        $vpIds = collect($this->catalog->forUser($vicePrincipal))->pluck('id')->all();
        $ciIds = collect($this->catalog->forUser($classIncharge))->pluck('id')->all();

        $this->assertNotContains('aims-import', $vpIds);
        $this->assertNotContains('configuration', $ciIds);
    }

    public function test_persisted_settings_override_status_and_roles(): void
    {
        ModuleSetting::query()->create([
            'module_id' => 'attendance',
            'status' => 'coming_soon',
            'visible_roles' => ['teacher'],
        ]);

        $this->catalog = new ModuleCatalogService;

        $teacher = $this->userWithRoles(['teacher']);
        $admin = $this->userWithRoles(['admin']);

        $teacherById = collect($this->catalog->forUser($teacher))->keyBy('id');
        $adminIds = collect($this->catalog->forUser($admin))->pluck('id')->all();

        $this->assertSame('coming_soon', $teacherById->get('attendance')['status']);
        $this->assertTrue($teacherById->get('attendance')['coming_soon']);
        $this->assertNotContains('attendance', $adminIds);
    }

    public function test_disabled_module_is_hidden_from_catalog(): void
    {
        ModuleSetting::query()->create([
            'module_id' => 'marks',
            'status' => 'disabled',
            'visible_roles' => ['teacher', 'parent'],
        ]);

        $this->catalog = new ModuleCatalogService;
        $teacher = $this->userWithRoles(['teacher']);

        $this->assertNotContains('marks', collect($this->catalog->forUser($teacher))->pluck('id')->all());
    }

    public function test_sync_settings_updates_admin_catalog(): void
    {
        $updated = $this->catalog->syncSettings([
            [
                'id' => 'homework',
                'status' => 'live',
                'visible_roles' => ['teacher', 'parent'],
            ],
        ]);

        $homework = collect($updated)->firstWhere('id', 'homework');
        $this->assertSame('live', $homework['status']);
        $this->assertSame(['teacher', 'parent'], $homework['visible_roles']);
        $this->assertDatabaseHas('module_settings', [
            'module_id' => 'homework',
            'status' => 'live',
        ]);
    }

    private function seedPermissions(): void
    {
        foreach ([
            'manage_academic_structure',
            'manage_student_roster',
            'mark_attendance',
            'view_dashboard',
            'import_aims_data',
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
