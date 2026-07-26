<?php

return [
    'integration' => [
        'enabled' => env('DOSEN_FARMASI_INTEGRATION_ENABLED', false),
        'base_url' => env('DOSEN_FARMASI_BASE_URL'),
        'token' => env('DOSEN_FARMASI_INTEGRATION_TOKEN'),
        'timeout_seconds' => (int) env('DOSEN_FARMASI_TIMEOUT_SECONDS', 10),
        'connect_timeout_seconds' => (int) env('DOSEN_FARMASI_CONNECT_TIMEOUT_SECONDS', 3),
        'verify_tls' => env('DOSEN_FARMASI_VERIFY_TLS', true),
        'max_attempts' => (int) env('DOSEN_FARMASI_MAX_ATTEMPTS', 5),
    ],
];
