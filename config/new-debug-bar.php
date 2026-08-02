<?php

return [
    'enabled' => env('NEW_DEBUG_BAR_ENABLED', true),

    'environments' => ['local'],

    'theme' => 'system',

    'slow_query_ms' => 100,

    'slow_request_ms' => 1_000,

    'findings' => [
        'minimum_cache_operations' => 5,
        'high_cache_miss_rate' => 0.8,
        'max_findings' => 50,
    ],

    'storage' => [
        'path' => null,
        'max_profiles' => 20,
        'max_age_minutes' => 60,
    ],

    'collection' => [
        'application_path' => null,
        'max_items_per_section' => 100,
        'max_depth' => 5,
        'max_string_length' => 2_000,
        'query_bindings' => env('NEW_DEBUG_BAR_QUERY_BINDINGS', 'safe'),
        'query_call_sites' => true,
        'query_call_site_frames' => 5,
        'query_call_site_scan_limit' => 40,
    ],
];
