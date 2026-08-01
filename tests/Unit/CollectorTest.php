<?php

use NewDebugBar\Collectors\QueryCollector;
use NewDebugBar\Support\Redactor;

it('counts dropped collector items without retaining their payload', function () {
    $collector = new QueryCollector(new Redactor, maxItems: 1);

    $collector->record(['sql' => 'select 1', 'duration_ms' => 1.25]);
    $collector->record(['sql' => 'select 2', 'duration_ms' => 4.75]);

    expect($collector->summary())->toBe([
        'count' => 2,
        'duration_ms' => 1.25,
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
