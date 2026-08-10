<?php

use NewDebugBar\Presentation\LivewirePresenter;

it('builds three focused Livewire views from safe correlated evidence', function () {
    $section = (new LivewirePresenter)->present([
        'schema_version' => 1,
        'profile_revision' => 2,
        'label' => 'Livewire',
        'summary' => [
            'message_count' => 1,
            'result' => 'rendered',
            'trace_status' => 'complete',
        ],
        'payload' => [
            'exchange' => ['kind' => 'update', 'result' => 'rendered'],
            'messages' => [[
                'id' => 'message-1',
                'component_id' => 'component-12345678',
                'result' => 'rendered',
            ]],
            'actions' => [[
                'id' => 'action-1',
                'message_id' => 'message-1',
                'component_id' => 'component-12345678',
                'kind' => 'property_update',
                'name' => '$set',
                'property_paths' => ['search'],
            ]],
            'components' => [[
                'id' => 'component-12345678',
                'name' => 'pages::diagnostics-fixture',
                'class' => 'App\\Livewire\\DiagnosticsFixture',
                'parent_id' => null,
                'depth' => 0,
                'source' => ['file' => 'app/Livewire/DiagnosticsFixture.php', 'line' => 8],
                'view' => 'resources/views/livewire/diagnostics-fixture.blade.php',
                'rendered' => 'yes',
            ]],
            'state_changes' => [
                [
                    'id' => 'change-1',
                    'action_id' => 'action-1',
                    'component_id' => 'component-12345678',
                    'path' => 'search',
                    'before' => '',
                    'submitted' => 'north',
                    'server' => 'northline',
                    'browser' => ['status' => 'observed', 'matches_server' => true],
                    'redacted' => false,
                ],
                [
                    'id' => 'change-2',
                    'action_id' => 'action-1',
                    'component_id' => 'component-12345678',
                    'path' => 'password',
                    'before' => '[redacted]',
                    'submitted' => '[redacted]',
                    'server' => '[redacted]',
                    'browser' => ['status' => 'unknown'],
                    'redacted' => true,
                ],
            ],
            'events' => [],
            'server_spans' => [[
                'id' => 'server-span-1',
                'component_id' => 'component-12345678',
                'action_id' => 'action-1',
                'phase' => 'render',
                'start_ms' => 2,
                'duration_ms' => 8,
            ]],
            'browser_trace' => [
                'status' => 'complete',
                'actions' => [[
                    'action_id' => 'action-1',
                    'source' => [
                        'status' => 'observed',
                        'directive' => 'wire:model.live',
                        'element' => 'input',
                    ],
                ]],
            ],
            'completeness' => [
                'components' => 'affected_only',
                'server_spans' => 'observed',
                'browser_trace' => 'complete',
                'truncated' => false,
            ],
        ],
    ]);

    $view = $section['payload']['presentation'];

    expect($section['summary']['count'])->toBe(1)
        ->and(array_column($view['tabs'], 'key'))->toBe(['overview', 'components', 'events'])
        ->and($view)->not->toHaveKeys(['facts', 'lanes', 'messages', 'actions'])
        ->and($view['activity'])
        ->title->toBe('Search changed')
        ->detail->toBe('Diagnostics Fixture handled the property change.')
        ->and($view['outcome']['title'])->toBe('Rendered')
        ->and($view['state_changes'][0])
        ->before_display->toBe('empty')
        ->after_display->toBe('northline')
        ->browser_matches_server->toBeTrue()
        ->and($view['state_changes'][1])
        ->before_display->toBe('Changed, value hidden')
        ->after_display->toBe('Changed, value hidden')
        ->and($view['components'][0])
        ->display_name->toBe('Diagnostics Fixture')
        ->raw_name->toBe('pages::diagnostics-fixture')
        ->trigger_label->toBe('Search changed')
        ->rendered_label->toBe('Rendered')
        ->source_label->toBe('app/Livewire/DiagnosticsFixture.php:8')
        ->and($view['components'][0]['copy_details'])
        ->toContain('search: empty -> northline')
        ->toContain('password: Changed, value hidden -> Changed, value hidden')
        ->not->toContain('$set')
        ->and($view['server_work'][0])
        ->label->toBe('Rendered')
        ->component_name->toBe('Diagnostics Fixture')
        ->and($view['notices'])->toBe([])
        ->and($view['affected_hierarchy_only'])->toBeTrue();
});

it('keeps missing and malformed evidence visible instead of inventing facts', function () {
    $section = (new LivewirePresenter)->present([
        'summary' => 'broken',
        'payload' => [
            'exchange' => ['result' => 'unknown'],
            'messages' => 'broken',
            'components' => [['name' => null, 'rendered' => 'maybe']],
            'browser_trace' => ['status' => 'missing'],
            'completeness' => [
                'components' => 'affected_only',
                'server_spans' => 'unknown',
                'unknown_reasons' => ['Livewire server timing evidence was unavailable.'],
                'truncated' => true,
            ],
        ],
    ]);

    $view = $section['payload']['presentation'];

    expect($view['activity'])
        ->title->toBe('Livewire request')
        ->detail->toBe('The exact trigger was not observed.')
        ->and($view['outcome']['title'])->toBe('Result not observed')
        ->and($view['components'][0])
        ->display_name->toBe('Unknown component')
        ->rendered_label->toBe('Render not observed')
        ->and(array_column($view['notices'], 'title'))->toBe([
            'Browser evidence is missing',
            'Server timing is unavailable',
            'Some evidence was truncated',
        ])
        ->and($view['events'])->toBe([]);
});

it('presents polling and renderless work as normal outcomes', function () {
    $section = (new LivewirePresenter)->present([
        'summary' => ['result' => 'renderless'],
        'payload' => [
            'exchange' => ['kind' => 'update', 'result' => 'renderless'],
            'messages' => [[
                'id' => 'message-poll',
                'component_id' => 'component-poll',
                'result' => 'renderless',
            ]],
            'actions' => [[
                'id' => 'action-poll',
                'component_id' => 'component-poll',
                'kind' => 'refresh',
                'name' => '$refresh',
            ]],
            'components' => [[
                'id' => 'component-poll',
                'name' => 'status-panel',
                'rendered' => 'no',
            ]],
            'browser_trace' => [
                'status' => 'complete',
                'actions' => [[
                    'action_id' => 'action-poll',
                    'source' => [
                        'status' => 'observed',
                        'directive' => 'wire:poll.5s',
                        'element' => 'div',
                    ],
                ]],
            ],
        ],
    ]);

    $view = $section['payload']['presentation'];

    expect($view['activity'])
        ->title->toBe('Polled')
        ->detail->toBe('Status Panel handled the scheduled check.')
        ->and($view['outcome'])
        ->title->toBe('Finished without rendering')
        ->detail->toBe('The action finished without a render.')
        ->and($view['findings'])->toBe([]);
});

it('uses the familiar property label when framework work accompanies one trigger', function () {
    $section = (new LivewirePresenter)->present([
        'payload' => [
            'exchange' => ['kind' => 'update', 'result' => 'rendered'],
            'actions' => [
                [
                    'id' => 'action-search-1',
                    'component_id' => 'component-search',
                    'kind' => 'property_update',
                    'property_paths' => ['search'],
                ],
                [
                    'id' => 'action-framework',
                    'component_id' => 'component-search',
                    'kind' => 'action',
                    'name' => '$commit',
                ],
            ],
            'components' => [[
                'id' => 'component-search',
                'class' => 'App\\Livewire\\ApplicationBoard',
            ]],
        ],
    ]);

    expect($section['payload']['presentation']['activity'])
        ->title->toBe('Search changed')
        ->detail->toBe('Application Board handled the property change.');
});

it('keeps declared event targets separate from observed recipients', function () {
    $section = (new LivewirePresenter)->present([
        'payload' => [
            'components' => [
                ['id' => 'component-1', 'name' => 'application-board', 'class' => 'App\\Livewire\\ApplicationBoard'],
                ['id' => 'component-2', 'name' => 'application-board-child', 'class' => 'App\\Livewire\\ApplicationBoard'],
            ],
            'events' => [
                [
                    'id' => 'event-1',
                    'name' => 'review-requested',
                    'mode' => 'targeted',
                    'source_component_id' => 'component-1',
                    'declared_target' => 'App\\Livewire\\ApplicationBoard',
                    'observed_recipient_ids' => [],
                    'recipient_status' => 'unknown',
                    'parameters' => ['email' => '[redacted]', 'count' => 2],
                ],
                [
                    'id' => 'event-2',
                    'name' => 'review-requested',
                    'mode' => 'received',
                    'source_component_id' => null,
                    'declared_target' => null,
                    'observed_recipient_ids' => ['component-2'],
                    'recipient_status' => 'observed',
                    'parameters' => [],
                ],
            ],
            'browser_trace' => ['status' => 'complete'],
        ],
    ]);

    $view = $section['payload']['presentation'];

    expect(array_column($view['components'], 'list_label'))->toBe([
        'Application Board (component-1)',
        'Application Board (component-2)',
    ])
        ->and($view['events'][0]['sequence'])->toBe(1)
        ->and($view['events'][0])
        ->display_name->toBe('Review Requested')
        ->mode_label->toBe('Named target')
        ->source_name->toBe('Application Board')
        ->declared_target_label->toBe('Application Board')
        ->recipient_label->toBe('Not observed')
        ->and($view['events'][0]['copy_details'])
        ->toContain('Observed recipients: Not observed')
        ->toContain('"email": "[redacted]"')
        ->and($view['events'][1]['sequence'])->toBe(2)
        ->and($view['events'][1])
        ->mode_label->toBe('Received')
        ->source_name->toBeNull()
        ->recipient_names->toBe(['Application Board']);
});
