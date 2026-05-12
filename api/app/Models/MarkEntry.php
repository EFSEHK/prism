<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarkEntry extends Model
{
    protected $fillable = [
        'mark_sheet_id',
        'student_id',
        'marks_obtained',
        'max_marks',
        'grade',
    ];

    protected function casts(): array
    {
        return [
            'marks_obtained' => 'decimal:2',
            'max_marks' => 'decimal:2',
        ];
    }

    public function markSheet(): BelongsTo
    {
        return $this->belongsTo(MarkSheet::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
