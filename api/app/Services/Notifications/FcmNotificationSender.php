<?php

namespace App\Services\Notifications;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * MVP: logs payloads. Replace with FCM HTTP v1 using google service account (config/services.php).
 */
class FcmNotificationSender
{
    public function sendToUsers(array $userIds, string $title, string $body, array $data = []): int
    {
        if ($userIds === []) {
            return 0;
        }

        $tokens = DeviceToken::query()
            ->whereIn('user_id', $userIds)
            ->whereNull('revoked_at')
            ->pluck('token')
            ->unique()
            ->values()
            ->all();

        if ($tokens === []) {
            Log::info('FCM (stub): no device tokens', ['user_ids' => $userIds, 'title' => $title]);

            return 0;
        }

        Log::info('FCM (stub): would send', [
            'token_count' => count($tokens),
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        return count($tokens);
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): int
    {
        return $this->sendToUsers([$user->id], $title, $body, $data);
    }
}
