<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationFeature extends Model
{
    protected $fillable = [
        'module_code',
        'feature_key',
        'name',
        'description',
        'default_payload_schema',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_payload_schema' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function policies(): HasMany
    {
        return $this->hasMany(NotificationApprovalPolicy::class);
    }

    public function dispatchRequests(): HasMany
    {
        return $this->hasMany(NotificationDispatchRequest::class);
    }
}
