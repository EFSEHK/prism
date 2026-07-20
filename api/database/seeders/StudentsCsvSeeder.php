<?php

namespace Database\Seeders;

use App\Services\Roster\StudentCsvImportService;
use Illuminate\Database\Seeder;

class StudentsCsvSeeder extends Seeder
{
    public function run(): void
    {
        $stats = app(StudentCsvImportService::class)->import();

        $this->command?->info(sprintf(
            'StudentsCsvSeeder: %d students, %d new student users, %d skipped, %d errors.',
            $stats['students'],
            $stats['users'],
            $stats['skipped'],
            count($stats['errors'])
        ));

        foreach (array_slice($stats['errors'], 0, 20) as $error) {
            $this->command?->warn($error);
        }

        if (count($stats['errors']) > 20) {
            $this->command?->warn('... and '.(count($stats['errors']) - 20).' more errors.');
        }
    }
}
