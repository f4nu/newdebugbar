<?php

use NewDebugBar\Support\LogChannelTracker;

it('matches a log event to the channel processor that saw its record', function () {
    $tracker = new LogChannelTracker;
    $record = (object) [
        'level' => 'WARNING',
        'message' => 'Partner unavailable',
        'context' => ['trip_id' => 1],
    ];

    $tracker->remember('operations', $record);
    $tracker->remember('nested-handler', $record);

    expect($tracker->take('warning', 'Partner unavailable', ['trip_id' => 1]))->toBe('operations')
        ->and($tracker->take('warning', 'Partner unavailable', ['trip_id' => 1]))->toBeNull();
});

it('prefers the matching context while retaining a bounded message fallback', function () {
    $tracker = new LogChannelTracker;
    $tracker->remember('first', (object) [
        'level' => 'info',
        'message' => 'Refresh complete',
        'context' => ['trip_id' => 1],
    ]);
    $tracker->remember('second', (object) [
        'level' => 'info',
        'message' => 'Refresh complete',
        'context' => ['trip_id' => 2],
    ]);

    expect($tracker->take('info', 'Refresh complete', ['trip_id' => 2]))->toBe('second')
        ->and($tracker->take('info', 'Refresh complete', ['trip_id' => 99]))->toBe('first');
});
