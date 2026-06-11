<?php

namespace App\Services\Notifications;

use App\Models\Area;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Models\UserBroadcast;

class UserBroadcastApprovalService
{
    private const ADMIN_ROLES = ['admin', 'superadmin', 'developer'];

    private const SCOPED_AUTO_APPROVE_ROLES = ['admin', 'section_head', 'superadmin', 'developer'];

    public function needsApproval(User $user, string $audienceType): bool
    {
        if ($audienceType === 'general') {
            return ! $user->hasAnyRole(self::ADMIN_ROLES);
        }

        return ! $user->hasAnyRole(self::SCOPED_AUTO_APPROVE_ROLES);
    }

    public function userCanApprove(User $user, UserBroadcast $broadcast): bool
    {
        if ($broadcast->approval_status !== 'pending_approval') {
            return false;
        }

        if ($broadcast->audience_type === 'general') {
            return $user->hasAnyRole(self::ADMIN_ROLES);
        }

        if ($user->hasAnyRole(self::ADMIN_ROLES)) {
            return true;
        }

        if ($user->hasRole('section_head')) {
            return $this->sectionHeadInScope($user, $broadcast);
        }

        return false;
    }

    public function approve(UserBroadcast $broadcast, User $actor): UserBroadcast
    {
        if (! $this->userCanApprove($actor, $broadcast)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Not authorized to approve this broadcast.');
        }

        $broadcast->update([
            'approval_status' => 'approved',
            'approved_by_user_id' => $actor->id,
            'approved_at' => now(),
            'published_at' => now(),
            'rejection_comment' => null,
        ]);

        return $broadcast->fresh();
    }

    public function reject(UserBroadcast $broadcast, User $actor, ?string $comment = null): UserBroadcast
    {
        if (! $this->userCanApprove($actor, $broadcast)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Not authorized to reject this broadcast.');
        }

        $broadcast->update([
            'approval_status' => 'rejected',
            'approved_by_user_id' => $actor->id,
            'approved_at' => now(),
            'rejection_comment' => $comment,
        ]);

        return $broadcast->fresh();
    }

    private function sectionHeadInScope(User $user, UserBroadcast $broadcast): bool
    {
        $areaIds = Area::query()
            ->where('section_head_user_id', $user->id)
            ->pluck('id');

        if ($areaIds->isEmpty()) {
            return false;
        }

        if ($broadcast->area_id && $areaIds->contains($broadcast->area_id)) {
            return true;
        }

        if ($broadcast->school_class_id) {
            $class = SchoolClass::find($broadcast->school_class_id);
            if ($class && $areaIds->contains($class->area_id)) {
                return true;
            }
        }

        if ($broadcast->section_id) {
            $section = Section::with('schoolClass:id,area_id')->find($broadcast->section_id);
            if ($section?->schoolClass && $areaIds->contains($section->schoolClass->area_id)) {
                return true;
            }
        }

        if ($broadcast->student_id) {
            $student = Student::with('section.schoolClass:id,area_id')->find($broadcast->student_id);
            if ($student?->section?->schoolClass && $areaIds->contains($student->section->schoolClass->area_id)) {
                return true;
            }
        }

        if ($broadcast->study_group_id) {
            return Student::query()
                ->where('study_group_id', $broadcast->study_group_id)
                ->whereHas(
                    'section.schoolClass',
                    fn ($q) => $q->whereIn('area_id', $areaIds)
                )
                ->exists();
        }

        return false;
    }
}
