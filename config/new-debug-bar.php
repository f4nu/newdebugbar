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

    'mcp' => [
        'max_items' => 50,
        'max_bytes' => 100_000,
    ],

    'collection' => [
        'application_path' => null,
        'max_items_per_collector' => 500,
        'max_items_per_array' => 100,
        'max_depth' => 5,
        'max_string_length' => 2_000,
        'query_bindings' => env('NEW_DEBUG_BAR_QUERY_BINDINGS', 'safe'),
        'call_sites' => true,
        'call_site_frames' => 5,
        'call_site_scan_limit' => 40,
        'exception_application_frames' => 12,
        'exception_vendor_frames' => 12,
        'exception_source_context_lines' => 9,
    ],
];
