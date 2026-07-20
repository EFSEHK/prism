<?php

namespace Database\Seeders;

use App\Services\Roster\FacultyCsvImportService;
use Illuminate\Database\Seeder;

class FacultyCsvSeeder extends Seeder
{
    public function run(): void
    {
        $stats = app(FacultyCsvImportService::class)->import();

        $this->command?->info(sprintf(
            'FacultyCsvSeeder: %d staff, %d new staff users, %d skipped, %d errors.',
            $stats['staff'],
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
