<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view_dashboard',
            'manage_users',
            'manage_roles',
            'manage_permissions',
            'view_reports',
            'manage_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::create(['name' => 'superadmin']);
        $admin = Role::create(['name' => 'admin']);
        $developer = Role::create(['name' => 'developer']);

        // Super Admin gets all permissions
        $superAdmin->givePermissionTo(Permission::all());

        // Admin permissions
        $admin->givePermissionTo([
            'view_dashboard',
            'manage_users',
            'view_reports',
            'manage_settings',
        ]);

        // Developer permissions
        $developer->givePermissionTo(Permission::all());

        // Create a super admin user
        $superAdminUser = User::where('email', 'superadmin@lask.com')->first();
        $superAdminUser->assignRole('superadmin');

        // Create an admin user
        $adminUser = User::where('email', 'admin@lask.com')->first();
        $adminUser->assignRole('admin');

        // Create a user
        $developerUser = User::where('email', 'developer@lask.com')->first();
        $developerUser->assignRole('developer');
    }
} 