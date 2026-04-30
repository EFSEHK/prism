<?php

return [
    'result_stores' => [
        Spatie\Health\ResultStores\CacheHealthResultStore::class => [
            'store' => env('HEALTH_CACHE_STORE', 'file'),
        ],
    ],

    'notifications' => [
        'enabled' => false,

        'notifications' => [
            Spatie\Health\Notifications\CheckFailedNotification::class => ['mail'],
        ],

        'notifiable' => Spatie\Health\Notifications\Notifiable::class,

        'throttle_notifications_for_minutes' => 60,
        'throttle_notifications_key' => 'health:latestNotificationSentAt:',
        'only_on_failure' => false,

        'mail' => [
            'to' => env('HEALTH_NOTIFICATION_EMAIL', 'your@example.com'),

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],

        'slack' => [
            'webhook_url' => env('HEALTH_SLACK_WEBHOOK_URL', ''),
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],
    ],

    'theme' => 'light',

    'json_results_failure_status' => 503,

    'secret_token' => env('HEALTH_SECRET_TOKEN'),
];
