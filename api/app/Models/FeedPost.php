<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'title',
        'body',
        'scope',
        'scope_school_class_id',
        'scope_section_id',
        'scope_student_id',
        'author_user_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function scopeSchoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'scope_school_class_id');
    }
}
