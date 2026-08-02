<?php

use NewDebugBar\Analysis\TimelineBuilder;

it('keeps point events distinct and only estimates query spans', function () {
    $timeline = (new TimelineBuilder)->build([
        'metrics' => ['duration_ms' => 50],
        'sections' => [
            'request' => ['payload' => []],
            'queries' => ['payload' => ['items' => [[
                'normalized_sql' => 'select * from users',
                'duration_ms' => 4,
                'at_ms' => 10,
            ]]]],
            'cache' => ['payload' => ['items' => [[
                'operation' => 'hit',
                'key_hash' => 'abc',
                'at_ms' => 5,
            ]]]],
            'logs' => ['payload' => ['items' => [[
                'level' => 'info',
                'message' => str_repeat('a', 200),
                'at_ms' => 20,
            ]]]],
        ],
    ]);

    expect(array_column($timeline, 'id'))->toBe([
        'request-start',
        'cache-0',
        'queries-0',
        'logs-0',
        'request-end',
    ])->and($timeline[1])
        ->kind->toBe('point')
        ->start_ms->toBeNull()
        ->duration_ms->toBeNull()
        ->and($timeline[2])
        ->kind->toBe('span')
        ->start_ms->toBe(6.0)
        ->duration_ms->toBe(4.0)
        ->and(mb_strlen($timeline[3]['label']))->toBeLessThanOrEqual(140);
});
