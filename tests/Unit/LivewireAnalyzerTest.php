<?php

use NewDebugBar\Analysis\LivewireAnalyzer;

it('reports bounded observed slow server work with causal evidence IDs', function () {
    $findings = (new LivewireAnalyzer)->analyze([
        'payload' => [
            'exchange' => ['id' => 'exchange-1'],
            'components' => [[
                'id' => 'component-1',
                'class' => 'App\\Livewire\\ApplicationBoard',
            ]],
            'actions' => [[
                'id' => 'action-1',
                'name' => 'loadReviewOptions',
            ]],
            'server_spans' => [[
                'id' => 'span-1',
                'component_id' => 'component-1',
                'action_id' => 'action-1',
                'phase' => 'call',
                'duration_ms' => 224.56,
            ]],
        ],
    ]);

    expect($findings)->toHaveCount(1)
        ->and($findings[0])
        ->rule_id->toBe('livewire.slow_server_work')
        ->severity->toBe('warning')
        ->section->toBe('livewire')
        ->summary->toBe('Application Board spent 224.6 ms in Load Review Options.')
        ->why->toBe('The Livewire response waited for this server work to finish.')
        ->origin->toBe('Observed Load Review Options work on Application Board.')
        ->next->toContain('lazy loading or an island')
        ->and($findings[0]['evidence'])
        ->exchange_id->toBe('exchange-1')
        ->span_id->toBe('span-1')
        ->component_id->toBe('component-1')
        ->action_id->toBe('action-1')
        ->duration_ms->toBe(224.6)
        ->threshold_ms->toBe(200.0);
});

it('falls back to observed total server time when internal phase timing is unavailable', function () {
    $findings = (new LivewireAnalyzer)->analyze([
        'payload' => [
            'exchange' => ['id' => 'exchange-1', 'duration_ms' => 231.75],
            'components' => [[
                'id' => 'component-1',
                'class' => 'App\\Livewire\\ApplicationBoard',
            ]],
            'server_spans' => [],
        ],
    ]);

    expect($findings)->toHaveCount(1)
        ->and($findings[0])
        ->rule_id->toBe('livewire.slow_request')
        ->summary->toBe('Application Board request took 231.8 ms.')
        ->origin->toContain('Internal phase timing was unavailable')
        ->next->toContain('Laravel debug mode on locally')
        ->and($findings[0]['evidence'])
        ->duration_ms->toBe(231.8)
        ->threshold_ms->toBe(200.0)
        ->server_phase_timing->toBe('unavailable');
});

it('does not turn normal Livewire behavior or a large batch into findings', function (string $result) {
    $messages = array_fill(0, 17, ['id' => 'message', 'result' => $result]);

    expect((new LivewireAnalyzer)->analyze([
        'payload' => [
            'messages' => $messages,
            'server_spans' => [['phase' => 'render', 'duration_ms' => 199.9]],
        ],
    ]))->toBe([]);
})->with([
    'rendered',
    'renderless',
    'skipped',
    'validation_failed',
]);
