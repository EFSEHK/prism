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
        ?int $areaId = null,
        ?int $schoolClassId = null,
        ?int $sectionId = null,
        ?int $studyGroupId = null,
    ): Collection {
        $cacheKey = sprintf(
            'notif_policies:%d:%s:%s:%s:%s',
            $feature->id,
            $areaId ?? 'any',
            $schoolClassId ?? 'any',
            $sectionId ?? 'any',
            $studyGroupId ?? 'any',
        );

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($feature, $areaId, $schoolClassId, $sectionId, $studyGroupId) {
            return NotificationApprovalPolicy::query()
                ->where('notification_feature_id', $feature->id)
                ->where('is_active', true)
                ->where(function ($q) use ($areaId) {
                    $q->whereNull('area_id')
                        ->when($areaId, fn ($qq) => $qq->orWhere('area_id', $areaId));
                })
                ->where(function ($q) use ($schoolClassId) {
                    $q->whereNull('school_class_id')
                        ->when($schoolClassId, fn ($qq) => $qq->orWhere('school_class_id', $schoolClassId));
                })
                ->where(function ($q) use ($sectionId) {
                    $q->whereNull('section_id')
                        ->when($sectionId, fn ($qq) => $qq->orWhere('section_id', $sectionId));
                })
                ->where(function ($q) use ($studyGroupId) {
                    $q->whereNull('study_group_id')
                        ->when($studyGroupId, fn ($qq) => $qq->orWhere('study_group_id', $studyGroupId));
                })
                ->orderBy('sequence')
                ->get();
        });
    }

    public static function forgetFeatureCache(NotificationFeature $feature): void
    {
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
