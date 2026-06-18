<?php

return [
    'default_web_app_url' => env(
        'DEFAULT_WEB_APP_URL',
        env('PRODUCTION_WEB_URL', 'https://sap.innovisiq.com')
    ),
    'dev_portal_path' => env('DEV_PORTAL_PATH', 'sys/portal-access'),
];
