<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Super Admin', 'email' => 'superadmin@lask.com', 'password' => 'S.Admin.123'],
            ['name' => 'Admin', 'email' => 'admin@lask.com', 'password' => 'Admin.123'],
            ['name' => 'Developer', 'email' => 'developer@lask.com', 'password' => 'Developer.123'],
            ['name' => 'Principal (test)', 'email' => 'principal@efsc-ya.test', 'password' => 'Test.123'],
            ['name' => 'Vice Principal (test)', 'email' => 'viceprincipal@efsc-ya.test', 'password' => 'Test.123'],
            ['name' => 'Section Head (test)', 'email' => 'sectionhead@efsc-ya.test', 'password' => 'Test.123'],
            ['name' => 'Class Incharge (test)', 'email' => 'incharge@efsc-ya.test', 'password' => 'Test.123'],
            ['name' => 'Teacher (test)', 'email' => 'teacher@efsc-ya.test', 'password' => 'Test.123'],
            ['name' => 'Accountant (test)', 'email' => 'accountant@efsc-ya.test', 'password' => 'Test.123'],
            ['name' => 'Computer Operator (test)', 'email' => 'operator@efsc-ya.test', 'password' => 'Test.123'],
            ['name' => 'Parent (test)', 'email' => 'parent@efsc-ya.test', 'password' => 'Test.123'],
            ['name' => 'Student (test)', 'email' => 'student@efsc-ya.test', 'password' => 'Test.123'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make($u['password']),
                ]
            );
        }
    }
}
