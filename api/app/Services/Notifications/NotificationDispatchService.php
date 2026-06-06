<?php

namespace App\Services\Notifications;

use App\Jobs\ProcessApprovedNotificationDispatchJob;
use App\Models\NotificationApprovalAction;
use App\Models\NotificationDispatchRequest;
use App\Models\NotificationFeature;
use Illuminate\Support\Facades\DB;

class NotificationDispatchService
{
    public function __construct(
        private NotificationPolicyResolver $policyResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $payloadJson
     * @param  array<string, mixed>|null  $scopeIds
     */
    public function create(
        string $featureKey,
        string $contextType,
        int $contextId,
        string $scopeType,
        ?array $scopeIds,
        array $payloadJson,
        ?int $areaId = null,
        ?int $schoolClassId = null,
        ?int $sectionId = null,
        ?int $studyGroupId = null,
        ?int $createdByUserId = null,
        ?\DateTimeInterface $scheduledFor = null,
    ): NotificationDispatchRequest {
        $feature = $this->policyResolver->featureByKey($featureKey);
        if (! $feature instanceof NotificationFeature) {
            throw new \InvalidArgumentException("Unknown notification feature: {$featureKey}");
        }

        return DB::transaction(function () use (
            $feature,
            $featureKey,
            $contextType,
            $contextId,
            $scopeType,
            $scopeIds,
            $payloadJson,
            $areaId,
            $schoolClassId,
            $sectionId,
            $studyGroupId,
            $createdByUserId,
            $scheduledFor,
        ) {
            $existing = NotificationDispatchRequest::query()
                ->where('notification_feature_id', $feature->id)
                ->where('context_type', $contextType)
                ->where('context_id', $contextId)
                ->whereIn('status', ['pending_approval', 'approved', 'draft'])
                ->first();

            if ($existing) {
                return $existing;
            }

            $policies = $this->policyResolver->policiesForFeature(
                $feature,
                $areaId,
                $schoolClassId,
                $sectionId,
                $studyGroupId,
            );

            $needsApproval = $policies->isNotEmpty() && $policies->contains(fn ($p) => $p->requires_approval);

            $dispatch = NotificationDispatchRequest::create([
                'notification_feature_id' => $feature->id,
                'context_type' => $contextType,
                'context_id' => $contextId,
                'scope_type' => $scopeType,
                'scope_ids' => $scopeIds,
                'payload_json' => $payloadJson,
                'status' => $needsApproval ? 'pending_approval' : 'approved',
                'current_sequence' => 1,
                'area_id' => $areaId,
                'school_class_id' => $schoolClassId,
                'section_id' => $sectionId,
                'study_group_id' => $studyGroupId,
                'scheduled_for' => $scheduledFor,
                'created_by_user_id' => $createdByUserId,
            ]);

            if ($needsApproval) {
                foreach ($policies->where('requires_approval', true) as $policy) {
                    NotificationApprovalAction::create([
                        'notification_dispatch_request_id' => $dispatch->id,
                        'sequence' => $policy->sequence,
                        'approver_role_name' => $policy->approver_role_name,
                        'decision' => 'pending',
                    ]);
                }
            }

            if (! $needsApproval) {
                if ($scheduledFor) {
                    ProcessApprovedNotificationDispatchJob::dispatch($dispatch->id)
                        ->delay($scheduledFor);
                } else {
                    ProcessApprovedNotificationDispatchJob::dispatch($dispatch->id);
                }
            }

            return $dispatch->fresh(['approvalActions', 'feature']);
        });
    }

    public function finalizeIfApproved(NotificationDispatchRequest $dispatch): void
    {
        if ($dispatch->status !== 'approved') {
            return;
        }

        if ($dispatch->scheduled_for && $dispatch->scheduled_for->isFuture()) {
            ProcessApprovedNotificationDispatchJob::dispatch($dispatch->id)
                ->delay($dispatch->scheduled_for);
        } else {
            ProcessApprovedNotificationDispatchJob::dispatch($dispatch->id);
        }
    }
}
