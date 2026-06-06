<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationDispatchRequest extends Model
{
    protected $fillable = [
        'notification_feature_id',
        'context_type',
        'context_id',
        'scope_type',
        'scope_ids',
        'payload_json',
        'status',
        'current_sequence',
        'area_id',
        'school_class_id',
        'section_id',
        'study_group_id',
        'scheduled_for',
        'sent_at',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'scope_ids' => 'array',
            'payload_json' => 'array',
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(NotificationFeature::class, 'notification_feature_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approvalActions(): HasMany
    {
        return $this->hasMany(NotificationApprovalAction::class);
    }
}
