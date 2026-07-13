<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleSetting extends Model
{
    protected $fillable = [
        'module_id',
        'status',
        'visible_roles',
    ];

    protected function casts(): array
    {
        return [
            'visible_roles' => 'array',
        ];
    }
}
