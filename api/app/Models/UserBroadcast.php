<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserBroadcast extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'audience_type',
        'area_id',
        'school_class_id',
        'section_id',
        'study_group_id',
        'student_id',
        'visible_to_student',
        'title',
        'body',
        'author_user_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'visible_to_student' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
