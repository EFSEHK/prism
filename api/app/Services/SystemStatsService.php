<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class SystemStatsService
{
    public function getDashboardCards(): array
    {
        return [
            $this->databaseCard(),
            $this->databaseSizeCard(),
            $this->tableSizesCard(),
            $this->activeConnectionsCard(),
            $this->cacheCard(),
            $this->environmentCard(),
            $this->debugModeCard(),
            $this->optimizationCard(),
        ];
    }

    public function databaseCard(): array
    {
        try {
            DB::connection()->getPdo();

            return $this->card(
                'MySQL Database',
                'Ok',
                sprintf('%s on %s', $this->databaseName(), $this->databaseHost()),
            );
        } catch (Throwable $exception) {
            return $this->card(
                'MySQL Database',
                'Unavailable',
                $this->trimMessage($exception->getMessage()),
                'failed',
            );
        }
    }

    public function databaseSizeCard(): array
    {
        if (! $this->isMysql()) {
            return $this->card(
                'MySQL Database Size',
                'Unsupported',
                'Database size is only available for MySQL connections.',
                'warning',
            );
        }

        try {
            $size = DB::selectOne(
                'SELECT SUM(data_length + index_length) AS size_in_bytes
                 FROM information_schema.tables
                 WHERE table_schema = ?',
                [$this->databaseName()],
            );

            $bytes = (int) ($size->size_in_bytes ?? 0);

            return $this->card(
                'MySQL Database Size',
                $this->formatBytes($bytes),
                sprintf('Calculated from %s.', $this->databaseName()),
            );
        } catch (Throwable $exception) {
            return $this->card(
                'MySQL Database Size',
                'Unavailable',
                $this->trimMessage($exception->getMessage()),
                'failed',
            );
        }
    }

    public function tableSizesCard(): array
    {
        if (! $this->isMysql()) {
            return $this->card(
                'MySQL Table Sizes',
                'Unsupported',
                'Table size checks are only available for MySQL connections.',
                'warning',
            );
        }

        try {
            $tables = collect(DB::select(
                'SELECT table_name AS table_name,
                        ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_in_mb
                 FROM information_schema.tables
                 WHERE table_schema = ?
                 ORDER BY (data_length + index_length) DESC',
                [$this->databaseName()],
            ));

            if ($tables->isEmpty()) {
                return $this->card(
                    'MySQL Table Sizes',
                    'No tables found',
                    'Create tables to start tracking their sizes.',
                    'warning',
                );
            }

            $largestTable = $tables->first();
            $tableCount = $tables->count();

            return $this->card(
                'MySQL Table Sizes',
                sprintf('%s tracked', $this->pluralize($tableCount, 'table')),
                sprintf(
                    'Largest table: %s (%s MB)',
                    $largestTable->table_name,
                    number_format((float) $largestTable->size_in_mb, 2),
                ),
            );
        } catch (Throwable $exception) {
            return $this->card(
                'MySQL Table Sizes',
                'Unavailable',
                $this->trimMessage($exception->getMessage()),
                'failed',
            );
        }
    }

    public function activeConnectionsCard(): array
    {
        try {
            $connections = $this->activeConnectionsCount();

            return $this->card(
                'Currently Active Connections',
                $this->pluralize($connections, 'connection'),
                sprintf('Live sessions connected to %s.', $this->databaseName()),
            );
        } catch (Throwable $exception) {
            return $this->card(
                'Currently Active Connections',
                'Unavailable',
                $this->trimMessage($exception->getMessage()),
                'failed',
            );
        }
    }

    public function cacheCard(): array
    {
        try {
            $key = 'lask-health-check-' . md5((string) microtime(true));

            Cache::put($key, 'ok', now()->addMinutes(1));
            $cached = Cache::get($key) === 'ok';
            Cache::forget($key);

            if (! $cached) {
                return $this->card(
                    'Laravel Cache',
                    'Unavailable',
                    'The configured cache store did not return the probe value.',
                    'failed',
                );
            }

            return $this->card(
                'Laravel Cache',
                'Ok',
                sprintf('Using the %s cache store.', config('cache.default')),
            );
        } catch (Throwable $exception) {
            return $this->card(
                'Laravel Cache',
                'Unavailable',
                $this->trimMessage($exception->getMessage()),
                'failed',
            );
        }
    }

    public function environmentCard(): array
    {
        return $this->card(
            'Laravel Environment',
            app()->environment(),
            sprintf('Application: %s', config('app.name')),
        );
    }

    public function debugModeCard(): array
    {
        return $this->card(
            'Laravel Debug Mode',
            config('app.debug') ? 'true' : 'false',
            config('app.debug')
                ? 'Detailed errors are enabled for development.'
                : 'Detailed errors are hidden.',
        );
    }

    public function optimizationCard(): array
    {
        $missing = [];

        if (! app()->configurationIsCached()) {
            $missing[] = 'config';
        }

        if (! app()->routesAreCached()) {
            $missing[] = 'routes';
        }

        if (! app()->eventsAreCached()) {
            $missing[] = 'events';
        }

        if ($missing === []) {
            return $this->card(
                'Laravel Optimization',
                'Ready',
                'Config, routes, and events are cached.',
            );
        }

        return $this->card(
            'Laravel Optimization',
            'Not cached',
            'Missing: ' . implode(', ', $missing),
            'failed',
        );
    }

    public function activeConnectionsCount(): int
    {
        if ($this->isMysql()) {
            $processList = DB::selectOne(
                'SELECT COUNT(*) AS aggregate
                 FROM information_schema.processlist
                 WHERE DB = ?',
                [$this->databaseName()],
            );

            return (int) ($processList->aggregate ?? 0);
        }

        $status = DB::selectOne("SHOW STATUS LIKE 'Threads_connected'");

        return (int) ($status->Value ?? 0);
    }

    protected function isMysql(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    protected function databaseName(): string
    {
        return (string) DB::connection()->getDatabaseName();
    }

    protected function databaseHost(): string
    {
        return (string) config('database.connections.' . config('database.default') . '.host', 'localhost');
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return number_format($bytes / (1024 ** $power), $power > 0 ? 2 : 0) . ' ' . $units[$power];
    }

    protected function pluralize(int $count, string $word): string
    {
        return $count . ' ' . ($count === 1 ? $word : $word . 's');
    }

    protected function trimMessage(string $message): string
    {
        return mb_strimwidth($message, 0, 120, '...');
    }

    protected function card(string $title, string $value, string $detail, string $status = 'ok'): array
    {
        return [
            'title' => $title,
            'value' => $value,
            'detail' => $detail,
            'status' => $status,
        ];
    }
}
