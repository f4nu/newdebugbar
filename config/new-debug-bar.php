<?php

return [
    'enabled' => env('NEW_DEBUG_BAR_ENABLED', true),

    'environments' => ['local'],

    'storage' => [
        'path' => null,
        'max_profiles' => 20,
        'max_age_minutes' => 60,
    ],

    'collection' => [
        'max_items_per_section' => 100,
        'max_depth' => 5,
        'max_string_length' => 2_000,
    ],
];
