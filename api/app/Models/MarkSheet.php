<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarkSheet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'assessment_id',
        'study_group_id',
        'subject_id',
        'status',
        'submitted_by_user_id',
        'verified_by_user_id',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(MarkEntry::class);
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }
}
