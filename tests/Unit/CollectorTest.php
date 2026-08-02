<?php

use NewDebugBar\Collectors\CacheCollector;
use NewDebugBar\Collectors\LogCollector;
use NewDebugBar\Collectors\QueryCollector;
use NewDebugBar\Support\Redactor;

it('counts dropped collector items without retaining their payload', function () {
    $collector = new QueryCollector(new Redactor, maxItems: 1);

    $collector->record(['sql' => 'select 1', 'duration_ms' => 1.25]);
    $collector->record(['sql' => 'select 2', 'duration_ms' => 4.75]);

    expect($collector->summary())->toBe([
        'count' => 2,
        'duration_ms' => 6.0,
    ])->and($collector->payload())->toBe([
        'items' => [['sql' => 'select 1', 'duration_ms' => 1.25]],
        'dropped' => 1,
    ]);

    $collector->reset();

    expect($collector->summary())->toBe([
        'count' => 0,
        'duration_ms' => 0.0,
    ])->and($collector->payload())->toBe([
        'items' => [],
        'dropped' => 0,
    ]);
});

it('masks unnamed string query bindings by default', function () {
    $collector = new QueryCollector(new Redactor, maxItems: 2);

    $collector->record([
        'sql' => 'select * from users where email = ? and id = ?',
        'bindings' => ['patient@example.com', 42],
        'duration_ms' => 1.5,
    ]);

    expect($collector->payload()['items'][0]['bindings'])->toBe(['[string]', 42]);
});

it('includes dropped items in cache and log summaries', function () {
    $cache = new CacheCollector(new Redactor, maxItems: 1);
    $logs = new LogCollector(new Redactor, maxItems: 1);

    $cache->record(['operation' => 'hit']);
    $cache->record(['operation' => 'miss']);
    $logs->record(['level' => 'info']);
    $logs->record(['level' => 'error']);

    expect($cache->summary())->toBe([
        'count' => 2,
        'hits' => 1,
        'misses' => 1,
        'writes' => 0,
    ])->and($logs->summary())->toBe([
        'count' => 2,
        'errors' => 1,
    ]);
});
