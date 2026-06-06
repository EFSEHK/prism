<?php

namespace App\Models;

use App\Casts\AppDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DatesheetEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'exam_date',
        'school_class_id',
        'subject_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => AppDate::class,
        ];
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
