<?php

use NewDebugBar\Analysis\ProfileComparator;
use NewDebugBar\Presentation\ProfileSummaryPresenter;
use NewDebugBar\Support\Redactor;

it('compares stable profile metrics and explains directional changes', function () {
    $comparator = new ProfileComparator(new ProfileSummaryPresenter(new Redactor));
    $profile = fn (string $id, float $duration, int $queries, int $hits, int $misses): array => [
        'id' => $id,
        'metrics' => ['duration_ms' => $duration, 'peak_memory_mb' => 8],
        'findings' => [],
        'sections' => [
            'request' => ['summary' => ['method' => 'GET', 'status' => 200], 'payload' => ['path' => '/clinics']],
            'queries' => ['summary' => [
                'total_count' => $queries,
                'total_time_ms' => $queries * 2,
                'repeated_pattern_count' => 1,
                'slow_count' => 0,
            ]],
            'cache' => ['summary' => ['hits' => $hits, 'misses' => $misses]],
            'exceptions' => ['summary' => ['count' => 0]],
        ],
    ];

    $comparison = $comparator->compare(
        $profile('before', 100, 5, 1, 1),
        $profile('after', 80, 3, 3, 1),
    );

    expect($comparison['path'])->toBe('/clinics')
        ->and($comparison['baseline']['id'])->toBe('before')
        ->and($comparison['current']['id'])->toBe('after')
        ->and(collect($comparison['metrics'])->keyBy('key')->get('duration_ms'))->toMatchArray([
            'baseline' => 100.0,
            'current' => 80.0,
            'delta' => -20.0,
            'tone' => 'improved',
        ])
        ->and(collect($comparison['metrics'])->keyBy('key')->get('cache_hit_rate'))->toMatchArray([
            'baseline' => 50.0,
            'current' => 75.0,
            'delta' => 25.0,
            'tone' => 'improved',
        ])
        ->and(collect($comparison['metrics'])->keyBy('key')->get('query_count'))->toMatchArray([
            'delta' => -2.0,
            'tone' => 'neutral',
        ]);
});
