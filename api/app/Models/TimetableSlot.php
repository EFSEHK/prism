<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimetableSlot extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'study_group_id',
        'subject_id',
        'day_of_week',
        'start_time',
        'end_time',
        'room',
    ];

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
