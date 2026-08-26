<?php

use NewDebugBar\Analysis\LogAnalyzer;

it('renders structured log details without repeating the raw record', function () {
    $analysis = app(LogAnalyzer::class)->analyze([
        [
            'level' => 'error',
            'message' => "Reservation refresh failed.\nThe cached itinerary remains available.",
            'channel' => 'morrow-audit',
            'context' => ['trip_id' => 41, 'actor' => ['type' => 'planner', 'id' => 7]],
            'callsite' => ['file' => 'app/Actions/RefreshTrip.php', 'line' => 48],
            'stack' => [['file' => 'app/Actions/RefreshTrip.php', 'line' => 48, 'function' => 'handle']],
            'related_exception' => [
                'class' => RuntimeException::class,
                'message' => 'Partner rejected reservation KYO-441.',
                'file' => 'app/Partners/RailPartner.php',
                'line' => 91,
            ],
            'at_ms' => 18.432,
            'occurred_at' => '2026-08-24T16:32:10.123+02:00',
        ],
    ]);
    $section = [
        'summary' => ['count' => 1, ...$analysis['summary']],
        'payload' => ['items' => $analysis['items'], 'groups' => $analysis['groups']],
    ];

    $html = view('newdebugbar::livewire.sections.logs', compact('section'))->render();

    expect($html)
        ->toContain(
            'data-ndb-log-controls',
            'data-ndb-log-entry',
            'data-ndb-log-level="error"',
            'data-ndb-log-channel="morrow-audit"',
            'data-ndb-log-level-select',
            'data-ndb-log-detail',
            'Choose a log entry to inspect its evidence.',
            'selectLogEntry(1)',
            'data-ndb-log-related-exception',
            'data-ndb-log-source',
            'data-ndb-inspector-stack',
            'x-for="(frame, index) in JSON.parse(',
            'Review in Exceptions',
        )
        ->not->toContain(
            'data-ndb-log-empty',
            'data-ndb-log-context-preview',
            'data-ndb-log-actions',
            'data-ndb-log-attention-label',
            'data-ndb-copy-log-',
            'data-ndb-log-filter=',
            'data-ndb-log-raw',
            'Raw evidence',
            '<details data-ndb-log-entry',
            'data-ndb-log-details-popover',
            'data-ndb-popover-surface',
            'View details',
        );
});

it('renders a truthful empty state when no log records were captured', function () {
    $section = [
        'summary' => [
            'count' => 0,
            'attention_count' => 0,
            'group_count' => 0,
            'repeated_count' => 0,
            'levels' => [],
            'channels' => [],
        ],
        'payload' => ['items' => [], 'groups' => []],
    ];

    $html = view('newdebugbar::livewire.sections.logs', compact('section'))->render();

    expect($html)
        ->toContain('data-ndb-log-empty', 'No log records were captured for this request.')
        ->not->toContain('data-ndb-log-controls', 'data-ndb-log-entry');
});
