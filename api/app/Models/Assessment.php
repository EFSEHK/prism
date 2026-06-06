<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'type',
        'name',
        'number',
        'held_on',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'held_on' => 'date',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
