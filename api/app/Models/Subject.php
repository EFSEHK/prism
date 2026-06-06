<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code'];

    public function studyGroups(): BelongsToMany
    {
        return $this->belongsToMany(StudyGroup::class, 'study_group_subject')
            ->withTimestamps();
    }
}
