<?php

use NewDebugBar\Analysis\ProfileAnalyzer;
use NewDebugBar\Analysis\QueryAnalyzer;

it('produces stable bounded findings with supporting evidence', function () {
    $analyzer = new ProfileAnalyzer(
        queries: new QueryAnalyzer(slowQueryMs: 50),
        slowRequestMs: 100,
        minimumCacheOperations: 5,
        highCacheMissRate: 0.8,
        maxFindings: 20,
    );

    $profile = [
        'metrics' => ['duration_ms' => 150],
        'sections' => [
            'request' => ['summary' => ['status' => 500]],
            'queries' => [
                'label' => 'Queries',
                'summary' => ['count' => 5, 'retained_count' => 3],
                'payload' => [
                    'items' => [
                        ['sql' => 'select ?', 'bindings' => [1], 'duration_ms' => 60, 'callsite' => ['file' => 'app/A.php', 'line' => 1]],
                        ['sql' => 'select ?', 'bindings' => [2], 'duration_ms' => 10, 'callsite' => ['file' => 'app/A.php', 'line' => 1]],
                        ['sql' => 'select ?', 'bindings' => [3], 'duration_ms' => 10, 'callsite' => ['file' => 'app/A.php', 'line' => 1]],
                    ],
                    'dropped' => 2,
                ],
            ],
            'exceptions' => ['summary' => ['count' => 1], 'payload' => ['items' => []]],
            'cache' => ['summary' => ['hits' => 1, 'misses' => 4], 'payload' => ['items' => []]],
        ],
    ];

    $findings = $analyzer->analyze($profile);
    $ruleIds = array_column($findings, 'rule_id');

    expect($ruleIds)->toBe([
        'request.error',
        'request.slow',
        'query.slow',
        'query.repeated',
        'query.n_plus_one',
        'collector.truncated',
        'cache.high_miss_rate',
    ])->and($findings[3])->toMatchArray([
        'severity' => 'warning',
        'section' => 'queries',
    ])->and($findings[3]['evidence']['count'])->toBe(3)
        ->and($findings[3]['evidence']['extra_executions'])->toBe(2)
        ->and($findings[4]['evidence']['shared_callsite'])->toBe([
            'file' => 'app/A.php',
            'line' => 1,
        ])
        ->and($findings[5])->toMatchArray([
            'summary' => 'Showing 3 of 5 queries.',
            'evidence' => ['collector' => 'queries', 'retained' => 3, 'total' => 5, 'dropped' => 2],
        ]);
});

it('limits the number of findings', function () {
    $analyzer = new ProfileAnalyzer(new QueryAnalyzer, maxFindings: 1);

    $findings = $analyzer->analyze([
        'metrics' => ['duration_ms' => 2_000],
        'sections' => [
            'request' => ['summary' => ['status' => 500]],
            'exceptions' => ['summary' => ['count' => 1]],
        ],
    ]);

    expect($findings)->toHaveCount(1)
        ->and($findings[0]['rule_id'])->toBe('request.error');
});
