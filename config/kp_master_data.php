<?php

return [
    'read_mode' => env('KP_MASTER_DATA_READ_MODE', 'legacy'),

    'allowed_modes' => [
        'legacy',
        'core_preferred',
        'core_only',
    ],

    'fallback_enabled' => env('KP_MASTER_DATA_READ_MODE', 'legacy') === 'core_preferred',

    'app_code' => 'kp-farmasi',
];
