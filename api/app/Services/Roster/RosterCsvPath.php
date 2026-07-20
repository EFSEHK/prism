<?php

namespace App\Services\Roster;

use RuntimeException;

class RosterCsvPath
{
    public static function resolve(string $filename): string
    {
        $candidates = [
            dirname(base_path()).DIRECTORY_SEPARATOR.$filename,
            database_path('data'.DIRECTORY_SEPARATOR.$filename),
        ];

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        throw new RuntimeException("CSV not found: {$filename}. Checked: ".implode(', ', $candidates));
    }
}
