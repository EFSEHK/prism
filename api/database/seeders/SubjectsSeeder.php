<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectsSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['name' => 'Biology', 'code' => 'bio'],
            ['name' => 'Chemistry', 'code' => 'chem'],
            ['name' => 'Computer Sciences', 'code' => 'comp'],
            ['name' => 'English', 'code' => 'eng'],
            ['name' => 'Islamiyat', 'code' => 'isl'],
            ['name' => 'Mathematics', 'code' => 'maths'],
            ['name' => 'Pakistan Studies', 'code' => 'pst'],
            ['name' => 'Physics', 'code' => 'phy'],
            ['name' => 'Social Studies', 'code' => 'sst'],
            ['name' => 'Urdu', 'code' => 'ur'],
        ];

        foreach ($subjects as $subject) {
            Subject::updateOrCreate(
                ['code' => $subject['code']],
                ['name' => $subject['name']]
            );
        }
    }
}
