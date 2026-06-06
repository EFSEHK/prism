<?php

namespace App\Models;

use App\Casts\AppDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnlineClassLink extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'study_group_id',
        'subject_id',
        'label',
        'url',
        'scheduled_date',
        'start_time',
        'end_time',
        'status',
        'created_by_user_id',
        'approved_by_user_id',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => AppDate::class,
            'reminder_sent_at' => 'datetime',
        ];
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
