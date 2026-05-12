<?php

namespace App\Services\Notifications;

use App\Models\NotificationApprovalPolicy;
use App\Models\NotificationFeature;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class NotificationPolicyResolver
{
    private const CACHE_TTL = 300;

    public function policiesForFeature(
        NotificationFeature $feature,
        ?int $schoolClassId = null,
        ?int $sectionId = null,
    ): Collection {
        $cacheKey = sprintf(
            'notif_policies:%d:%s:%s',
            $feature->id,
            $schoolClassId ?? 'any',
            $sectionId ?? 'any',
        );

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($feature, $schoolClassId, $sectionId) {
            return NotificationApprovalPolicy::query()
                ->where('notification_feature_id', $feature->id)
                ->where('is_active', true)
                ->where(function ($q) use ($schoolClassId) {
                    $q->whereNull('school_class_id')
                        ->when($schoolClassId, fn ($qq) => $qq->orWhere('school_class_id', $schoolClassId));
                })
                ->where(function ($q) use ($sectionId) {
                    $q->whereNull('section_id')
                        ->when($sectionId, fn ($qq) => $qq->orWhere('section_id', $sectionId));
                })
                ->orderBy('sequence')
                ->get();
        });
    }

    public static function forgetFeatureCache(NotificationFeature $feature): void
    {
        // Broad invalidation: flush tags if Redis; else forget known patterns — MVP simple flush
        Cache::flush();
    }

    public function featureByKey(string $featureKey): ?NotificationFeature
    {
        return Cache::remember('notif_feature:'.$featureKey, self::CACHE_TTL, function () use ($featureKey) {
            return NotificationFeature::query()
                ->where('feature_key', $featureKey)
                ->where('is_active', true)
                ->first();
        });
    }
}
