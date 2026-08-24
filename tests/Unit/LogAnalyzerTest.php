<?php

use NewDebugBar\Analysis\LogAnalyzer;

it('structures levels channels context and exception evidence for log reading', function () {
    $analysis = (new LogAnalyzer)->analyze([
        [
            'level' => 'NOTICE',
            'message' => "Inventory refresh started\nWaiting for the partner response.",
            'channel' => 'operations',
            'context' => [
                'trip_id' => 41,
                'options' => ['quiet' => true, 'limit' => 3],
            ],
            'callsite' => ['file' => 'app/Jobs/RefreshInventory.php', 'line' => 27],
            'stack' => [['file' => 'app/Jobs/RefreshInventory.php', 'line' => 27, 'function' => 'handle']],
            'related_exception' => [
                'class' => RuntimeException::class,
                'message' => 'Partner response was late.',
                'file' => 'app/Partners/RailPartner.php',
                'line' => 88,
            ],
            'at_ms' => 12.3456,
            'occurred_at' => '2026-08-24T14:30:10.123+02:00',
        ],
    ]);

    expect($analysis['items'][0])->toMatchArray([
        'sequence' => 1,
        'level' => 'notice',
        'level_label' => 'Notice',
        'attention' => false,
        'channel_label' => 'operations',
        'callsite_short_label' => 'RefreshInventory.php:27',
        'at_ms' => 12.346,
    ])
        ->and($analysis['items'][0]['context_fields'])
        ->toMatchArray([
            ['key' => 'trip_id', 'value' => 41, 'preview' => '41', 'structured' => false],
            ['key' => 'options', 'value' => ['quiet' => true, 'limit' => 3], 'preview' => '2 values', 'structured' => true],
        ])
        ->and($analysis['items'][0]['search'])
        ->toContain('operations', 'refreshinventory.php:27', 'partner response was late', 'trip_id')
        ->and($analysis['summary'])
        ->toMatchArray([
            'attention_count' => 0,
            'group_count' => 1,
            'repeated_count' => 0,
            'levels' => ['notice' => 1],
            'channels' => ['operations' => 1],
        ]);
});

it('groups only consecutive identical records and keeps every occurrence', function () {
    $warning = [
        'level' => 'warning',
        'message' => 'Rail partner is slow.',
        'channel' => 'stack',
        'context' => ['trip_id' => 1],
        'callsite' => ['file' => 'app/Jobs/SyncRail.php', 'line' => 18],
        'stack' => [],
    ];
    $analysis = (new LogAnalyzer)->analyze([
        [...$warning, 'at_ms' => 10],
        [...$warning, 'at_ms' => 12],
        ['level' => 'info', 'message' => 'Retrying.', 'at_ms' => 14],
        [...$warning, 'at_ms' => 16],
    ]);

    expect($analysis['groups'])->toHaveCount(3)
        ->and($analysis['groups'][0])
        ->repeat_count->toBe(2)
        ->first_sequence->toBe(1)
        ->last_sequence->toBe(2)
        ->first_at_ms->toBe(10.0)
        ->last_at_ms->toBe(12.0)
        ->and($analysis['groups'][0]['occurrences'])->toBe([
            ['sequence' => 1, 'at_ms' => 10.0, 'occurred_at' => null],
            ['sequence' => 2, 'at_ms' => 12.0, 'occurred_at' => null],
        ])
        ->and($analysis['groups'][2]['repeat_count'])->toBe(1)
        ->and($analysis['summary'])
        ->attention_count->toBe(3)
        ->group_count->toBe(3)
        ->repeated_count->toBe(1);
});

it('keeps malformed or unavailable optional log facts explicit', function () {
    $entry = (new LogAnalyzer)->analyze([[
        'level' => '',
        'message' => 'No metadata',
        'context' => 'invalid',
        'at_ms' => 'invalid',
    ]])['items'][0];

    expect($entry)
        ->level->toBe('log')
        ->channel->toBeNull()
        ->channel_label->toBe('—')
        ->callsite->toBeNull()
        ->callsite_label->toBe('—')
        ->context->toBe([])
        ->at_ms->toBeNull();
});
