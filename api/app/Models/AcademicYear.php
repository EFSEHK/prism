<?php

namespace App\Models;

use App\Casts\AppDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    protected $fillable = ['name', 'starts_on', 'ends_on', 'is_current'];

    protected function casts(): array
    {
        return [
            'starts_on' => AppDate::class,
            'ends_on' => AppDate::class,
            'is_current' => 'boolean',
        ];
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }
}
