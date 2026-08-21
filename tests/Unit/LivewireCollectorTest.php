<?php

use NewDebugBar\Collectors\LivewireCollector;
use NewDebugBar\Support\Redactor;

it('keeps the latest descriptor for each Livewire component instance', function () {
    $collector = new LivewireCollector(new Redactor);

    $collector->record([
        'kind' => 'component',
        'component' => [
            'id' => 'counter-1',
            'name' => 'benchmark.counter',
            'properties' => [['path' => 'count', 'value' => 1]],
        ],
    ]);
    $collector->record([
        'kind' => 'component',
        'component' => [
            'id' => 'counter-1',
            'name' => 'benchmark.counter',
            'properties' => [['path' => 'count', 'value' => 2]],
        ],
    ]);

    expect($collector->summary())
        ->component_count->toBe(1)
        ->activity_count->toBe(0)
        ->truncated->toBeFalse()
        ->and($collector->payload()['components'])
        ->toHaveCount(1)
        ->{0}->properties->{0}->value->toBe(2);
});

it('retains Livewire activity timing and reports bounded drops', function () {
    $collector = new LivewireCollector(
        new Redactor,
        maxComponents: 1,
        maxActivity: 1,
    );

    foreach (['counter-1', 'counter-2'] as $id) {
        $collector->record([
            'kind' => 'component',
            'component' => ['id' => $id, 'name' => 'benchmark.counter'],
        ]);
    }

    $collector->record([
        'kind' => 'activity',
        'at_ms' => 1.25,
        'activity' => ['id' => 'activity-1', 'type' => 'action'],
    ]);
    $collector->record([
        'kind' => 'activity',
        'at_ms' => 2.5,
        'activity' => ['id' => 'activity-2', 'type' => 'render'],
    ]);

    expect($collector->summary())
        ->component_count->toBe(1)
        ->activity_count->toBe(1)
        ->dropped_component_count->toBe(1)
        ->dropped_activity_count->toBe(1)
        ->truncated->toBeTrue()
        ->and($collector->payload())
        ->items->{0}->at_ms->toBe(1.25)
        ->components->{0}->id->toBe('counter-1')
        ->dropped_counts->toBe(['components' => 1, 'activity' => 1]);

    $collector->reset();

    expect($collector->summary())
        ->component_count->toBe(0)
        ->activity_count->toBe(0)
        ->dropped_count->toBe(0);
});
