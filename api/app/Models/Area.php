<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use SoftDeletes;

    protected $fillable = ['academic_year_id', 'name', 'section_head_user_id'];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function sectionHead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'section_head_user_id');
    }

    public function schoolClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class)->orderBy('sequence')->orderBy('name');
    }
}
