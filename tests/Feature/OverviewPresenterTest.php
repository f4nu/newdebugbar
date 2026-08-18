<?php

use NewDebugBar\Presentation\OverviewPresenter;

it('builds a focused overview without standalone counts', function () {
    $profile = [
        'sections' => [
            'overview' => [
                'payload' => [
                    'runtime' => [
                        'environment' => 'testing',
                        'php' => '8.4.0',
                        'debug' => true,
                    ],
                    'drivers' => ['database' => 'sqlite'],
                    'cache_state' => ['configuration' => true],
                    'ecosystem' => [
                        ['label' => 'Scout', 'version' => 'v10.0.0'],
                    ],
                ],
            ],
            'queries' => [
                'summary' => ['total_time_ms' => 1.25],
                'payload' => ['repeated_groups' => [['count' => 3]]],
            ],
            'timeline' => ['payload' => []],
            'http_client' => ['payload' => []],
            'logs' => ['payload' => ['items' => [['level' => 'info']]]],
            'events' => ['payload' => []],
            'cache' => ['payload' => []],
        ],
    ];
    $links = [
        ['key' => 'overview', 'label' => 'Overview', 'count' => null],
        ['key' => 'cache', 'label' => 'Cache', 'count' => 3, 'active' => true, 'attention' => false],
        ['key' => 'events', 'label' => 'Events', 'count' => 4, 'active' => true, 'attention' => false],
        ['key' => 'logs', 'label' => 'Logs', 'count' => 9, 'active' => true, 'attention' => false],
        ['key' => 'http_client', 'label' => 'HTTP Client', 'count' => 1, 'active' => true, 'attention' => false],
        ['key' => 'timeline', 'label' => 'Timeline', 'count' => 6, 'active' => true, 'attention' => false],
        ['key' => 'queries', 'label' => 'Queries', 'count' => 9, 'active' => true, 'attention' => true],
    ];

    $overview = app(OverviewPresenter::class)->present($profile, $links);

    expect(array_column($overview['activity'], 'key'))
        ->toBe(['queries', 'timeline', 'http_client', 'logs', 'events'])
        ->and($overview['activity'][0])
        ->toMatchArray([
            'description' => '9 queries, including one pattern repeated 3 times',
            'attention' => true,
        ])
        ->not->toHaveKey('count')
        ->and($overview['activity'][3]['description'])->toBe('9 messages, no errors')
        ->and($overview['runtime']['runtime'])
        ->not->toHaveKey('count')
        ->and($overview['runtime']['runtime']['items'])->toContain([
            'name' => 'PHP',
            'value' => '8.4.0',
        ])
        ->and($overview['runtime']['ecosystem']['items'])->toBe([
            ['name' => 'Scout', 'value' => 'v10.0.0'],
        ]);
});
