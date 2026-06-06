<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HomeworkPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'study_group_id',
        'section_id',
        'subject_id',
        'title',
        'body',
        'due_date',
        'attachment_path',
        'status',
        'created_by_user_id',
        'approved_by_user_id',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
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
