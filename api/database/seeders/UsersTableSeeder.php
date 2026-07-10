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
            ['name' => 'Super Admin', 'email' => 'superadmin@efsc-ya.com', 'password' => 'S.Admin.123'],
            ['name' => 'Admin', 'email' => 'admin@efsc-ya.com', 'password' => 'Admin.123'],
            ['name' => 'Developer', 'email' => 'developer@efsc-ya.com', 'password' => 'Developer.123'],
            ['name' => 'Principal (test)', 'email' => 'principal@efsc-ya.com', 'password' => 'Test.123'],
            ['name' => 'Vice Principal (test)', 'email' => 'viceprincipal@efsc-ya.com', 'password' => 'Test.123'],
            ['name' => 'Section Head (test)', 'email' => 'sectionhead@efsc-ya.com', 'password' => 'Test.123'],
            ['name' => 'Class Incharge (test)', 'email' => 'incharge@efsc-ya.com', 'password' => 'Test.123'],
            ['name' => 'Teacher (test)', 'email' => 'teacher@efsc-ya.com', 'password' => 'Test.123'],
            ['name' => 'Accountant (test)', 'email' => 'accountant@efsc-ya.com', 'password' => 'Test.123'],
            ['name' => 'Computer Operator (test)', 'email' => 'operator@efsc-ya.com', 'password' => 'Test.123'],
            ['name' => 'Parent (test)', 'email' => 'parent@efsc-ya.com', 'password' => 'Test.123'],
            ['name' => 'Student (test)', 'email' => 'student@efsc-ya.com', 'password' => 'Test.123'],
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
