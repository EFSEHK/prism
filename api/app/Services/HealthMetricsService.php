<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Health\ResultStores\CacheHealthResultStore;

class HealthMetricsService
{
    protected const HISTORY_CACHE_KEY = 'health:active-metrics-history:v1';

    protected const BASE_SAMPLE_INTERVAL_SECONDS = 5;

    protected const HISTORY_RETENTION_SECONDS = 7200;

    /**
     * @var array<string, array{label: string, interval_seconds: int, nodes: int}>
     */
    protected const RANGES = [
        '1m' => [
            'label' => '1 minute',
            'interval_seconds' => 5,
            'nodes' => 12,
        ],
        '10m' => [
            'label' => '10 minutes',
            'interval_seconds' => 60,
            'nodes' => 10,
        ],
        '30m' => [
            'label' => '30 minutes',
            'interval_seconds' => 300,
            'nodes' => 6,
        ],
        '1h' => [
            'label' => '1 hour',
            'interval_seconds' => 300,
            'nodes' => 12,
        ],
    ];

    public function __construct(
        protected SystemStatsService $systemStats,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function sampleAndBuild(string $rangeKey): array
    {
        $range = self::RANGES[$rangeKey] ?? null;

        if ($range === null) {
            throw new InvalidArgumentException("Unsupported range [{$rangeKey}].");
        }

        $now = CarbonImmutable::now();
        $sample = $this->storeBaseSample($now);
        $history = $this->history();
        $currentBucketEpoch = $this->snapEpoch($now->timestamp, $range['interval_seconds']);
        $points = [];

        for ($index = $range['nodes'] - 1; $index >= 0; $index--) {
            $bucketEpoch = $currentBucketEpoch - ($index * $range['interval_seconds']);
            $point = $this->buildPoint($bucketEpoch, $range['interval_seconds'], $history);

            if ($sample['bucket_epoch'] >= $bucketEpoch && $sample['bucket_epoch'] < ($bucketEpoch + $range['interval_seconds'])) {
                $point['connections'] = $sample['connections'];
                $point['active_users'] = $sample['active_users'];
                $point['sampled_at'] = $sample['sampled_at'];
            }

            $points[] = $point;
        }

        return [
            'range' => $rangeKey,
            'ranges' => collect(self::RANGES)
                ->map(fn (array $definition) => [
                    'label' => $definition['label'],
                    'interval_seconds' => $definition['interval_seconds'],
                    'nodes' => $definition['nodes'],
                ])
                ->all(),
            'current' => [
                'connections' => $sample['connections'],
                'active_users' => $sample['active_users'],
                'sampled_at' => $sample['sampled_at'],
            ],
            'points' => $points,
        ];
    }

    /**
     * @return array<int, array{bucket_epoch: int, sampled_at: string, connections: int, active_users: int}>
     */
    protected function history(): array
    {
        /** @var array<int|string, array{bucket_epoch: int, sampled_at: string, connections: int, active_users: int}> $history */
        $history = $this->cache()->get(self::HISTORY_CACHE_KEY, []);

        return collect($history)
            ->mapWithKeys(fn (array $sample, int|string $epoch) => [(int) $epoch => $sample])
            ->sortKeys()
            ->all();
    }

    /**
     * @return array{bucket_epoch: int, sampled_at: string, connections: int, active_users: int}
     */
    protected function storeBaseSample(CarbonImmutable $now): array
    {
        $bucketEpoch = $this->snapEpoch($now->timestamp, self::BASE_SAMPLE_INTERVAL_SECONDS);
        $sample = [
            'bucket_epoch' => $bucketEpoch,
            'sampled_at' => $now->toIso8601String(),
            'connections' => $this->systemStats->activeConnectionsCount(),
            'active_users' => $this->activeUsersCount($now),
        ];

        $history = $this->history();
        $minimumEpoch = $bucketEpoch - self::HISTORY_RETENTION_SECONDS;

        $history[$bucketEpoch] = $sample;
        $history = collect($history)
            ->filter(fn (array $item, int $epoch) => $epoch >= $minimumEpoch)
            ->sortKeys()
            ->all();

        $this->cache()->forever(self::HISTORY_CACHE_KEY, $history);

        return $sample;
    }

    /**
     * @param  array<int, array{bucket_epoch: int, sampled_at: string, connections: int, active_users: int}>  $history
     * @return array{bucket_started_at: string, bucket_label: string, sampled_at: ?string, connections: ?int, active_users: ?int}
     */
    protected function buildPoint(int $bucketEpoch, int $intervalSeconds, array $history): array
    {
        $matchingSample = collect($history)
            ->filter(fn (array $sample, int $epoch) => $epoch >= $bucketEpoch && $epoch < ($bucketEpoch + $intervalSeconds))
            ->sortKeys()
            ->last();

        $bucketStartedAt = CarbonImmutable::createFromTimestamp($bucketEpoch, config('app.timezone'));

        return [
            'bucket_started_at' => $bucketStartedAt->toIso8601String(),
            'bucket_label' => $bucketStartedAt->format($intervalSeconds === self::BASE_SAMPLE_INTERVAL_SECONDS ? 'H:i:s' : 'H:i'),
            'sampled_at' => $matchingSample['sampled_at'] ?? null,
            'connections' => $matchingSample['connections'] ?? null,
            'active_users' => $matchingSample['active_users'] ?? null,
        ];
    }

    protected function activeUsersCount(CarbonImmutable $now): int
    {
        $webUserIds = DB::table(config('session.table', 'sessions'))
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $now->subMinutes((int) config('session.lifetime', 120))->timestamp)
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($userId) => (int) $userId)
            ->all();

        $tokenQuery = DB::table('personal_access_tokens')
            ->where('tokenable_type', (new User)->getMorphClass())
            ->whereNotNull('tokenable_id')
            ->whereNotNull('last_activity_at')
            ->where('last_activity_at', '>=', $now->subMinutes((int) config('sanctum.inactivity_timeout', 30)))
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            });

        $tokenExpiration = config('sanctum.expiration');

        if (is_numeric($tokenExpiration)) {
            $tokenQuery->where('created_at', '>=', $now->subMinutes((int) $tokenExpiration));
        }

        $tokenUserIds = $tokenQuery
            ->distinct()
            ->pluck('tokenable_id')
            ->map(fn ($userId) => (int) $userId)
            ->all();

        return count(array_unique([...$webUserIds, ...$tokenUserIds]));
    }

    protected function snapEpoch(int $timestamp, int $intervalSeconds): int
    {
        return intdiv($timestamp, $intervalSeconds) * $intervalSeconds;
    }

    protected function cache(): CacheRepository
    {
        $store = config('health.result_stores.' . CacheHealthResultStore::class . '.store');

        return $store
            ? Cache::store($store)
            : Cache::store();
    }
}
