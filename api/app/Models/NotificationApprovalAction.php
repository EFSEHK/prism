<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationApprovalAction extends Model
{
    protected $fillable = [
        'notification_dispatch_request_id',
        'sequence',
        'approver_role_name',
        'decision',
        'decided_by_user_id',
        'decided_at',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function dispatchRequest(): BelongsTo
    {
        return $this->belongsTo(NotificationDispatchRequest::class, 'notification_dispatch_request_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }
}
