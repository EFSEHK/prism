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
            ['name' => 'Principal User', 'email' => 'principal@school.test', 'password' => 'Parent.123'],
            ['name' => 'Class Incharge', 'email' => 'incharge@school.test', 'password' => 'Parent.123'],
            ['name' => 'Teacher User', 'email' => 'teacher@school.test', 'password' => 'Parent.123'],
            ['name' => 'Parent User', 'email' => 'parent@school.test', 'password' => 'Parent.123'],
            ['name' => 'Accountant', 'email' => 'accountant@school.test', 'password' => 'Parent.123'],
            ['name' => 'Computer Operator', 'email' => 'operator@school.test', 'password' => 'Parent.123'],
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
