<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationApprovalPolicy extends Model
{
    protected $fillable = [
        'notification_feature_id',
        'sequence',
        'approver_role_name',
        'area_id',
        'school_class_id',
        'section_id',
        'study_group_id',
        'requires_approval',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
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
}
