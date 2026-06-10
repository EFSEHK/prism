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
            'manage_academic_structure',
            'manage_student_roster',
            'mark_attendance',
            'verify_attendance',
            'view_attendance_reports',
            'view_own_attendance',
            'manage_assessments',
            'enter_marks',
            'verify_marks',
            'view_marks_reports',
            'view_own_marks',
            'post_homework',
            'approve_homework',
            'view_own_homework',
            'manage_timetable',
            'manage_online_classes',
            'approve_online_classes',
            'view_own_online_classes',
            'manage_fee_vouchers',
            'view_fee_accounting',
            'publish_user_broadcasts',
            'manage_leave_requests',
            'view_parent_dashboard',
            'view_student_dashboard',
            'approve_notification_dispatches',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $roleNames = [
            'superadmin', 'admin', 'developer', 'principal', 'vice_principal',
            'section_head', 'class_incharge', 'teacher', 'parent', 'student',
            'computer_operator', 'accountant',
        ];

        foreach ($roleNames as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        Role::findByName('superadmin', 'web')->syncPermissions(Permission::all());
        Role::findByName('developer', 'web')->syncPermissions(Permission::all());

        Role::findByName('admin', 'web')->syncPermissions([
            'view_dashboard', 'manage_users', 'manage_roles', 'manage_academic_structure',
            'manage_student_roster', 'view_attendance_reports', 'view_marks_reports',
            'publish_user_broadcasts', 'manage_leave_requests',
        ]);

        Role::findByName('principal', 'web')->syncPermissions([
            'view_dashboard', 'view_attendance_reports', 'view_marks_reports',
            'publish_user_broadcasts', 'approve_notification_dispatches',
        ]);

        Role::findByName('vice_principal', 'web')->syncPermissions([
            'view_dashboard', 'view_attendance_reports', 'view_marks_reports',
            'publish_user_broadcasts', 'approve_notification_dispatches',
        ]);

        Role::findByName('section_head', 'web')->syncPermissions([
            'view_dashboard', 'manage_student_roster', 'verify_attendance',
            'view_attendance_reports', 'verify_marks', 'view_marks_reports',
            'approve_homework', 'approve_online_classes', 'publish_user_broadcasts',
            'manage_leave_requests', 'approve_notification_dispatches',
        ]);

        Role::findByName('class_incharge', 'web')->syncPermissions([
            'view_dashboard', 'manage_student_roster', 'mark_attendance',
            'view_attendance_reports', 'post_homework', 'publish_user_broadcasts',
            'approve_notification_dispatches',
        ]);

        Role::findByName('teacher', 'web')->syncPermissions([
            'view_dashboard', 'mark_attendance', 'enter_marks', 'post_homework',
            'manage_online_classes', 'publish_user_broadcasts',
        ]);

        Role::findByName('parent', 'web')->syncPermissions([
            'view_parent_dashboard', 'view_own_marks', 'view_own_attendance',
            'view_own_homework', 'view_own_online_classes',
        ]);

        Role::findByName('student', 'web')->syncPermissions([
            'view_student_dashboard', 'view_own_marks', 'view_own_attendance',
            'view_own_homework', 'view_own_online_classes',
        ]);

        Role::findByName('computer_operator', 'web')->syncPermissions([
            'view_dashboard', 'manage_users', 'manage_academic_structure', 'manage_student_roster',
            'mark_attendance', 'view_attendance_reports',
            'manage_assessments', 'manage_timetable', 'manage_fee_vouchers', 'publish_user_broadcasts',
        ]);

        Role::findByName('accountant', 'web')->syncPermissions([
            'view_dashboard', 'view_fee_accounting', 'manage_fee_vouchers',
        ]);

        $map = [
            'superadmin@efsc-ya.test' => 'superadmin',
            'admin@efsc-ya.test' => 'admin',
            'developer@efsc-ya.test' => 'developer',
            'superadmin@lask.com' => 'superadmin',
            'admin@lask.com' => 'admin',
            'developer@lask.com' => 'developer',
            'principal@efsc-ya.test' => 'principal',
            'viceprincipal@efsc-ya.test' => 'vice_principal',
            'sectionhead@efsc-ya.test' => 'section_head',
            'incharge@efsc-ya.test' => 'class_incharge',
            'teacher@efsc-ya.test' => 'teacher',
            'parent@efsc-ya.test' => 'parent',
            'student@efsc-ya.test' => 'student',
            'accountant@efsc-ya.test' => 'accountant',
            'operator@efsc-ya.test' => 'computer_operator',
        ];

        foreach ($map as $email => $role) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->syncRoles([$role]);
            }
        }
    }
}
