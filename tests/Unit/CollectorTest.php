<?php

use NewDebugBar\Collectors\CacheCollector;
use NewDebugBar\Collectors\LogCollector;
use NewDebugBar\Collectors\QueryCollector;
use NewDebugBar\Collectors\RedisCollector;
use NewDebugBar\Support\Redactor;

it('counts dropped collector items without retaining their payload', function () {
    $collector = new QueryCollector(new Redactor, maxItems: 1);

    $collector->record(['sql' => 'select 1', 'duration_ms' => 1.25]);
    $collector->record(['sql' => 'select 2', 'duration_ms' => 4.75]);

    expect($collector->summary())->toBe([
        'count' => 2,
        'retained_count' => 1,
        'dropped_count' => 1,
        'truncated' => true,
        'duration_ms' => 6.0,
        'transaction_count' => 0,
        'rollback_count' => 0,
    ])->and($collector->payload())->toBe([
        'items' => [[
            'sql' => 'select 1',
            'duration_ms' => 1.25,
            'source_preserved' => true,
            'runnable_available' => false,
        ]],
        'dropped' => 1,
        'retained' => 1,
        'total' => 2,
        'truncated' => true,
        'transactions' => [],
        'transaction_dropped' => 0,
        'transaction_total' => 0,
    ]);

    $collector->reset();

    expect($collector->summary())->toBe([
        'count' => 0,
        'retained_count' => 0,
        'dropped_count' => 0,
        'truncated' => false,
        'duration_ms' => 0.0,
        'transaction_count' => 0,
        'rollback_count' => 0,
    ])->and($collector->payload())->toBe([
        'items' => [],
        'dropped' => 0,
        'retained' => 0,
        'total' => 0,
        'truncated' => false,
        'transactions' => [],
        'transaction_dropped' => 0,
        'transaction_total' => 0,
    ]);
});

it('keeps transaction events separate from query counts', function () {
    $collector = new QueryCollector(new Redactor, maxItems: 2);
    $collector->record(['sql' => 'select 1', 'bindings' => [], 'duration_ms' => 1]);
    $collector->recordTransaction(['kind' => 'begin', 'connection' => 'testing', 'at_ms' => 1.2]);
    $collector->recordTransaction(['kind' => 'rollback', 'connection' => 'testing', 'at_ms' => 1.5]);

    expect($collector->summary())
        ->count->toBe(1)
        ->transaction_count->toBe(2)
        ->rollback_count->toBe(1)
        ->and($collector->payload()['transactions'])->toHaveCount(2);
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

it('removes literal values from captured sql', function () {
    $collector = new QueryCollector(new Redactor, maxItems: 1);

    $collector->record([
        'sql' => "select * from users where email = 'private@example.com'",
        'bindings' => [],
        'duration_ms' => 1,
    ]);

    expect($collector->payload()['items'][0]['sql'])
        ->toBe("select * from users where email = '[string]'");
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
        'retained_count' => 1,
        'dropped_count' => 1,
        'truncated' => true,
        'hits' => 1,
        'misses' => 1,
        'writes' => 0,
    ])->and($logs->summary())->toBe([
        'count' => 2,
        'retained_count' => 1,
        'dropped_count' => 1,
        'truncated' => true,
        'errors' => 1,
    ]);
});

it('removes a cache command even after the Redis item limit is reached', function () {
    $redis = new RedisCollector(new Redactor, maxItems: 1);
    $redis->record(['command' => 'GET', 'duration_ms' => 1.25, 'failed' => false]);
    $redis->record(['command' => 'SETEX', 'duration_ms' => 0.5, 'failed' => false]);

    $redis->excludeCacheOperation('write');

    expect($redis->summary())->toBe([
        'count' => 1,
        'retained_count' => 1,
        'dropped_count' => 0,
        'truncated' => false,
        'duration_ms' => 1.25,
        'failed_count' => 0,
    ])->and($redis->payload()['dropped'])->toBe(0);
});
