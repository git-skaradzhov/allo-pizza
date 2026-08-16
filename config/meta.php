<?php

return [
    'capi' => [
        'enabled' => (bool) env('META_CAPI_ENABLED', false),
        'access_token' => env('META_CAPI_ACCESS_TOKEN'),
        'api_version' => env('META_CAPI_API_VERSION', 'v26.0'),
        'test_event_code' => env('META_CAPI_TEST_EVENT_CODE'),
        'timeout' => (int) env('META_CAPI_TIMEOUT', 3),
        'retry_times' => 2,
        'retry_sleep_ms' => 150,
        'max_purchase_attempts' => 10,
    ],
    'pixel_id' => env('META_PIXEL_ID'),
    'currency' => 'EUR',
    'consent' => [
        'cookie' => 'marketing_consent',
        'lifetime_minutes' => 365 * 24 * 60,
    ],
    'event_token_ttl' => 1800,
    'event_dedupe_ttl' => 86400,
    'allowed_page_events' => [
        'PageView',
        'ViewContent',
        'InitiateCheckout',
    ],
];
