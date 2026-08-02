<?php

use NewDebugBar\Analysis\QueryAnalyzer;

it('groups repeated queries by normalized sql and connection', function () {
    $analysis = (new QueryAnalyzer(slowQueryMs: 100))->analyze([
        [
            'sql' => 'select * from users where id = ?',
            'bindings' => [1],
            'duration_ms' => 120,
            'connection' => 'primary',
            'at_ms' => 140,
            'callsite' => ['file' => 'app/UserFinder.php', 'line' => 21],
        ],
        [
            'sql' => " select  *  from users\nwhere id = ? ",
            'bindings' => [2],
            'duration_ms' => 20,
            'connection' => 'primary',
            'at_ms' => 180,
            'callsite' => ['file' => 'app/UserFinder.php', 'line' => 21],
        ],
        [
            'sql' => 'select * from users where id = ?',
            'bindings' => [3],
            'duration_ms' => 10,
            'connection' => 'primary',
            'at_ms' => 210,
            'callsite' => ['file' => 'app/UserFinder.php', 'line' => 21],
        ],
        [
            'sql' => 'select * from users where id = ?',
            'bindings' => [4],
            'duration_ms' => 5,
            'connection' => 'replica',
            'at_ms' => 220,
        ],
        [
            'sql' => 'update users set active = 1',
            'bindings' => [],
            'duration_ms' => 5,
            'connection' => 'primary',
            'type' => 'write',
            'at_ms' => 230,
        ],
    ], requestDurationMs: 400);

    expect($analysis['summary'])->toBe([
        'total_count' => 5,
        'total_time_ms' => 160.0,
        'request_time_percent' => 40.0,
        'slow_count' => 1,
        'repeated_pattern_count' => 1,
        'extra_execution_count' => 2,
        'read_count' => 4,
        'write_count' => 1,
    ])->and($analysis['items'][0])
        ->execution->toBe(1)
        ->slow->toBeTrue()
        ->repeated_count->toBe(3)
        ->query_time_percent->toBe(75.0)
        ->request_time_percent->toBe(30.0)
        ->start_ms->toBe(20.0)
        ->and($analysis['repeated_groups'])->toHaveCount(1)
        ->and($analysis['repeated_groups'][0])
        ->count->toBe(3)
        ->extra_executions->toBe(2)
        ->bindings_vary->toBeTrue()
        ->likely_n_plus_one->toBeTrue()
        ->shared_callsite->toBe(['file' => 'app/UserFinder.php', 'line' => 21])
        ->executions->toHaveCount(3);
});

it('does not claim n plus one without complete evidence', function (array $queries) {
    $group = (new QueryAnalyzer)->analyze($queries)['repeated_groups'][0];

    expect($group['likely_n_plus_one'])->toBeFalse();
})->with([
    'only two executions' => [[
        ['sql' => 'select ?', 'bindings' => [1], 'callsite' => ['file' => 'app/A.php', 'line' => 1]],
        ['sql' => 'select ?', 'bindings' => [2], 'callsite' => ['file' => 'app/A.php', 'line' => 1]],
    ]],
    'same bindings' => [[
        ['sql' => 'select ?', 'bindings' => [1], 'callsite' => ['file' => 'app/A.php', 'line' => 1]],
        ['sql' => 'select ?', 'bindings' => [1], 'callsite' => ['file' => 'app/A.php', 'line' => 1]],
        ['sql' => 'select ?', 'bindings' => [1], 'callsite' => ['file' => 'app/A.php', 'line' => 1]],
    ]],
    'missing callsite' => [[
        ['sql' => 'select ?', 'bindings' => [1], 'callsite' => ['file' => 'app/A.php', 'line' => 1]],
        ['sql' => 'select ?', 'bindings' => [2]],
        ['sql' => 'select ?', 'bindings' => [3], 'callsite' => ['file' => 'app/A.php', 'line' => 1]],
    ]],
    'different callsites' => [[
        ['sql' => 'select ?', 'bindings' => [1], 'callsite' => ['file' => 'app/A.php', 'line' => 1]],
        ['sql' => 'select ?', 'bindings' => [2], 'callsite' => ['file' => 'app/B.php', 'line' => 2]],
        ['sql' => 'select ?', 'bindings' => [3], 'callsite' => ['file' => 'app/A.php', 'line' => 1]],
    ]],
]);

it('uses the connection as part of the query pattern', function () {
    $analysis = (new QueryAnalyzer)->analyze([
        ['sql' => 'select 1', 'connection' => 'one'],
        ['sql' => 'select 1', 'connection' => 'two'],
    ]);

    expect($analysis['summary']['repeated_pattern_count'])->toBe(0)
        ->and($analysis['items'][0]['fingerprint'])->not->toBe($analysis['items'][1]['fingerprint']);
});
