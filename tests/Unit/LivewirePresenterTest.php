<?php

use NewDebugBar\Presentation\LivewirePresenter;

it('builds one truthful Livewire view model with separate timing lanes', function () {
    $section = (new LivewirePresenter)->present([
        'schema_version' => 1,
        'profile_revision' => 2,
        'label' => 'Livewire',
        'summary' => [
            'title' => 'Updated search',
            'message_count' => 1,
            'action_count' => 1,
            'component_count' => 1,
            'state_change_count' => 2,
            'result' => 'rendered',
            'trace_status' => 'complete',
        ],
        'payload' => [
            'exchange' => [
                'kind' => 'update',
                'title' => 'Updated search',
                'title_confidence' => 'inferred',
                'result' => 'rendered',
            ],
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
                'confidence' => 'observed',
            ]],
            'components' => [[
                'id' => 'component-12345678',
                'name' => 'diagnostics-fixture',
                'class' => 'App\\Livewire\\DiagnosticsFixture',
                'parent_id' => null,
                'depth' => 0,
                'source' => ['file' => 'app/Livewire/DiagnosticsFixture.php', 'line' => 8],
                'view' => 'resources/views/livewire/diagnostics-fixture.blade.php',
                'rendered' => 'yes',
                'render_reason' => ['kind' => 'property_update', 'confidence' => 'inferred'],
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
                    'browser' => ['status' => 'observed', 'matches_server' => true, 'type' => 'string'],
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
                'spans' => [[
                    'id' => 'browser-span-1',
                    'phase' => 'request_wait',
                    'start_ms' => 0,
                    'duration_ms' => 18,
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
        ->and($view['headline'])
        ->title->toBe('Updated search')
        ->kind->toBe('update')
        ->confidence->toBe('inferred')
        ->and($view['outcome']['title'])->toBe('Rendered successfully')
        ->and(array_column($view['tabs'], 'count'))->toBe([null, 1, 2, 0])
        ->and($view['actions'][0])
        ->component_name->toBe('diagnostics-fixture')
        ->source_label->toBe('wire:model.live on <input>')
        ->and($view['components'][0])
        ->source_label->toBe('app/Livewire/DiagnosticsFixture.php:8')
        ->render_reason_label->toBe('Property update')
        ->and($view['state_changes'][0])
        ->before_display->toBe('""')
        ->server_display->toBe('northline')
        ->submitted_material->toBeTrue()
        ->browser_matches_server->toBeTrue()
        ->and($view['state_changes'][1])
        ->before_display->toBe('Changed, value hidden')
        ->server_display->toBe('Changed, value hidden')
        ->and($view['lanes'][0])
        ->label->toBe('Server')
        ->duration_ms->toBe(10.0)
        ->and($view['lanes'][1])
        ->label->toBe('Browser')
        ->duration_ms->toBe(18.0)
        ->and($view['notices'])->toBe([])
        ->and($view['affected_hierarchy_only'])->toBeTrue();
});

it('keeps missing and malformed evidence visible instead of inventing facts', function () {
    $section = (new LivewirePresenter)->present([
        'summary' => ['trace_status' => 'unknown'],
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

    expect($view['headline'])
        ->title->toBe('Livewire exchange')
        ->detail->toBe('The trigger could not be derived from the available evidence.')
        ->confidence->toBe('unknown')
        ->and($view['outcome']['title'])->toBe('Result is not fully known')
        ->and($view['components'][0])
        ->name->toBe('Unknown component')
        ->rendered->toBe('unknown')
        ->and(array_column($view['notices'], 'title'))->toBe([
            'Browser trace is missing',
            'Server phase timing is unavailable',
            'Some evidence was truncated',
        ])
        ->and($view['events'])->toBe([]);
});
