<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudyGroup extends Model
{
    use SoftDeletes;

    protected $fillable = ['section_id', 'name'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'study_group_subject')
            ->withTimestamps();
    }
}
