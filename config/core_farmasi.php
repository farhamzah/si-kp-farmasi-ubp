<?php

return [
    'enabled' => (bool) env('KP_CORE_HTTP_ENABLED', false),
    'base_url' => env('KP_CORE_BASE_URL'),
    'profile_url' => env('KP_CORE_PROFILE_URL')
        ?: (env('KP_CORE_BASE_URL') ? rtrim((string) env('KP_CORE_BASE_URL'), '/') . '/profile' : null),
    'app_code' => env('KP_CORE_APP_CODE', 'kp-farmasi'),
    'client_id' => env('KP_CORE_CLIENT_ID'),
    'client_secret' => env('KP_CORE_CLIENT_SECRET'),
    'timeout' => (int) env('KP_CORE_TIMEOUT', 5),
    'connect_timeout' => (int) env('KP_CORE_CONNECT_TIMEOUT', 3),
    'verify_ssl' => (bool) env('KP_CORE_VERIFY_SSL', true),
    'read_mode' => env('KP_CORE_READ_MODE', 'legacy'),
    'fail_silently' => (bool) env('KP_CORE_FAIL_SILENTLY', true),
];
