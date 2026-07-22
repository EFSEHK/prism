<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataImportLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'source',
        'data_type',
        'filename',
        'stats',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'stats' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
