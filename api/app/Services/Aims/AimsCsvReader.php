<?php

namespace App\Services\Aims;

class AimsCsvReader
{
    /**
     * @return array{headers: list<string>, rows: list<array<string, string>>}
     */
    public function read(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Cannot open {$path}");
        }

        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);

            return ['headers' => [], 'rows' => []];
        }

        if (isset($headerRow[0])) {
            $headerRow[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headerRow[0]) ?? $headerRow[0];
        }

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headerRow);
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === false) {
                continue;
            }
            $assoc = [];
            foreach ($headers as $i => $header) {
                $assoc[$header] = trim((string) ($row[$i] ?? ''));
            }
            if (implode('', $assoc) === '') {
                continue;
            }
            $rows[] = $assoc;
        }

        fclose($handle);

        return ['headers' => $headers, 'rows' => $rows];
    }
}
