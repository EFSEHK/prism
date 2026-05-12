<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view_dashboard',
            'manage_users',
            'manage_roles',
            'manage_permissions',
            'manage_academic',
            'manage_attendance',
            'view_attendance_reports',
            'approve_notification_dispatches',
            'manage_marks',
            'view_marks',
            'manage_timetable',
            'manage_homework',
            'manage_online_classes',
            'manage_fee_vouchers',
            'view_fee_accounting',
            'manage_feed',
            'manage_leave_requests',
            'view_parent_dashboard',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $roleNames = [
            'superadmin',
            'admin',
            'principal',
            'vice_principal',
            'section_head',
            'class_incharge',
            'teacher',
            'parent',
            'computer_operator',
            'accountant',
            'developer',
        ];

        foreach ($roleNames as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        Role::findByName('superadmin', 'web')->syncPermissions(Permission::all());
        Role::findByName('developer', 'web')->syncPermissions(Permission::all());

        Role::findByName('admin', 'web')->syncPermissions([
            'view_dashboard', 'manage_users', 'manage_academic', 'manage_roles',
            'manage_attendance', 'view_attendance_reports', 'approve_notification_dispatches',
            'manage_marks', 'view_marks', 'manage_timetable', 'manage_homework',
            'manage_online_classes', 'manage_fee_vouchers', 'manage_feed', 'manage_leave_requests',
        ]);

        Role::findByName('principal', 'web')->syncPermissions([
            'view_dashboard', 'manage_academic', 'view_attendance_reports',
            'approve_notification_dispatches', 'view_marks', 'manage_leave_requests', 'manage_feed',
        ]);

        Role::findByName('vice_principal', 'web')->syncPermissions([
            'view_dashboard', 'view_attendance_reports', 'approve_notification_dispatches',
            'view_marks', 'manage_leave_requests', 'manage_feed',
        ]);

        Role::findByName('section_head', 'web')->syncPermissions([
            'view_dashboard', 'view_attendance_reports', 'approve_notification_dispatches',
            'view_marks', 'manage_marks', 'manage_leave_requests',
        ]);

        Role::findByName('class_incharge', 'web')->syncPermissions([
            'view_dashboard', 'view_attendance_reports', 'approve_notification_dispatches',
        ]);

        Role::findByName('teacher', 'web')->syncPermissions([
            'view_dashboard', 'manage_attendance', 'view_attendance_reports',
            'manage_marks', 'manage_homework', 'manage_online_classes', 'manage_leave_requests',
        ]);

        Role::findByName('parent', 'web')->syncPermissions([
            'view_parent_dashboard',
        ]);

        Role::findByName('computer_operator', 'web')->syncPermissions([
            'view_dashboard', 'manage_academic', 'manage_timetable', 'manage_fee_vouchers',
        ]);

        Role::findByName('accountant', 'web')->syncPermissions([
            'view_dashboard', 'view_fee_accounting', 'manage_fee_vouchers',
        ]);

        $map = [
            'superadmin@lask.com' => 'superadmin',
            'admin@lask.com' => 'admin',
            'developer@lask.com' => 'developer',
            'principal@school.test' => 'principal',
            'incharge@school.test' => 'class_incharge',
            'teacher@school.test' => 'teacher',
            'parent@school.test' => 'parent',
            'accountant@school.test' => 'accountant',
            'operator@school.test' => 'computer_operator',
        ];

        foreach ($map as $email => $role) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->syncRoles([$role]);
            }
        }
    }
}
