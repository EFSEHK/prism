<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    protected $fillable = [
        'user_id',
        'notification_dispatch_request_id',
        'title',
        'body',
        'data_json',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data_json' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dispatchRequest(): BelongsTo
    {
        return $this->belongsTo(NotificationDispatchRequest::class, 'notification_dispatch_request_id');
    }
}
