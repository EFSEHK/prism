<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    use SoftDeletes;

    protected $table = 'school_classes';

    protected $fillable = ['area_id', 'name', 'sequence'];

    protected $casts = [
        'sequence' => 'integer',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('sequence')->orderBy('name');
    }
}
