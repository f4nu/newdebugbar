<?php

use NewDebugBar\Analysis\LivewireAnalyzer;

it('reports only a bounded large batch with causal evidence IDs', function () {
    $messages = array_map(
        fn (int $index): array => ['id' => 'message-'.$index],
        range(1, 10),
    );
    $actions = array_map(
        fn (int $index): array => ['id' => 'action-'.$index],
        range(1, 10),
    );
    $findings = (new LivewireAnalyzer)->analyze([
        'payload' => [
            'exchange' => ['id' => 'exchange-1'],
            'messages' => $messages,
            'actions' => $actions,
        ],
    ]);

    expect($findings)->toHaveCount(1)
        ->and($findings[0])
        ->rule_id->toBe('livewire.large_batch')
        ->severity->toBe('info')
        ->section->toBe('livewire')
        ->summary->toBe('10 Livewire messages ran in one exchange.')
        ->and($findings[0]['evidence'])
        ->exchange_id->toBe('exchange-1')
        ->message_count->toBe(10)
        ->action_count->toBe(10)
        ->threshold->toBe(10)
        ->message_ids->toBe(array_column($messages, 'id'))
        ->action_ids->toBe(array_column($actions, 'id'));
});

it('does not turn ordinary Livewire results into findings', function (string $result) {
    $messages = array_fill(0, 9, ['id' => 'message', 'result' => $result]);

    expect((new LivewireAnalyzer)->analyze([
        'payload' => ['messages' => $messages],
    ]))->toBe([]);
})->with([
    'rendered',
    'renderless',
    'skipped',
    'validation_failed',
]);
