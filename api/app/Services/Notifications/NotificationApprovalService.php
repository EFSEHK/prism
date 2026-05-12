<?php

namespace App\Services\Notifications;

use App\Models\NotificationApprovalAction;
use App\Models\NotificationDispatchRequest;
use App\Models\StaffClassAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NotificationApprovalService
{
    public function __construct(
        private NotificationDispatchService $dispatchService,
    ) {}

    public function userCanApprove(User $user, NotificationDispatchRequest $dispatch): bool
    {
        $dispatch->loadMissing('approvalActions');
        $pending = $dispatch->approvalActions->where('decision', 'pending')->sortBy('sequence')->first();
        if (! $pending) {
            return false;
        }
        if (! $user->hasRole($pending->approver_role_name)) {
            return false;
        }

        return $this->passesScope($user, $pending->approver_role_name, $dispatch);
    }

    public function approve(
        NotificationDispatchRequest $dispatch,
        User $actor,
        ?string $comment = null,
    ): NotificationDispatchRequest {
        return DB::transaction(function () use ($dispatch, $actor, $comment) {
            $dispatch->load('approvalActions');

            $pending = $dispatch->approvalActions
                ->where('decision', 'pending')
                ->sortBy('sequence')
                ->first();

            if (! $pending) {
                return $dispatch;
            }

            if (! $actor->hasRole($pending->approver_role_name)) {
                throw new \Illuminate\Auth\Access\AuthorizationException('Not authorized for this approval step.');
            }

            if (! $this->passesScope($actor, $pending->approver_role_name, $dispatch)) {
                throw new \Illuminate\Auth\Access\AuthorizationException('Out of scope for this class/section.');
            }

            $pending->update([
                'decision' => 'approved',
                'decided_by_user_id' => $actor->id,
                'decided_at' => now(),
                'comment' => $comment,
            ]);

            $stillPending = $dispatch->approvalActions()->where('decision', 'pending')->exists();

            if (! $stillPending) {
                $dispatch->update(['status' => 'approved']);
                $this->dispatchService->finalizeIfApproved($dispatch->fresh());
            }

            return $dispatch->fresh(['approvalActions']);
        });
    }

    public function reject(
        NotificationDispatchRequest $dispatch,
        User $actor,
        ?string $comment = null,
    ): NotificationDispatchRequest {
        return DB::transaction(function () use ($dispatch, $actor, $comment) {
            $dispatch->load('approvalActions');
            $pending = $dispatch->approvalActions->where('decision', 'pending')->sortBy('sequence')->first();

            if (! $pending) {
                return $dispatch;
            }

            if (! $actor->hasRole($pending->approver_role_name)) {
                throw new \Illuminate\Auth\Access\AuthorizationException('Not authorized for this approval step.');
            }

            if (! $this->passesScope($actor, $pending->approver_role_name, $dispatch)) {
                throw new \Illuminate\Auth\Access\AuthorizationException('Out of scope for this class/section.');
            }

            $pending->update([
                'decision' => 'rejected',
                'decided_by_user_id' => $actor->id,
                'decided_at' => now(),
                'comment' => $comment,
            ]);

            $dispatch->update(['status' => 'rejected']);

            return $dispatch->fresh(['approvalActions']);
        });
    }

    public function passesScope(User $actor, string $roleName, NotificationDispatchRequest $dispatch): bool
    {
        if (in_array($roleName, [
            'principal', 'vice_principal', 'admin', 'superadmin', 'hod_section_head',
        ], true)) {
            return true;
        }

        if (! in_array($roleName, ['class_incharge', 'teacher', 'section_head'], true)) {
            return true;
        }

        if (! $dispatch->school_class_id) {
            return true;
        }

        $q = StaffClassAssignment::query()->where('user_id', $actor->id)
            ->where('school_class_id', $dispatch->school_class_id);

        if ($dispatch->section_id) {
            $q->where(function ($qq) use ($dispatch) {
                $qq->whereNull('section_id')->orWhere('section_id', $dispatch->section_id);
            });
        }

        return $q->exists();
    }
}
