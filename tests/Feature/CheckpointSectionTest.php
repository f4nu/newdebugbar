<?php

it('renders checkpoints with source timing and structured context', function () {
    $html = view('newdebugbar::livewire.sections.messages', [
        'section' => [
            'payload' => [
                'items' => [[
                    'label' => 'Checkout started',
                    'at_ms' => 1.234,
                    'context' => [
                        'cart_id' => 42,
                        'metadata' => ['currency' => 'CHF'],
                    ],
                    'callsite' => [
                        'file' => 'app/Checkout.php',
                        'line' => 18,
                    ],
                ]],
            ],
        ],
    ])->render();

    expect($html)
        ->toContain('data-ndb-checkpoint-workspace')
        ->toContain('data-ndb-checkpoint-item="0"')
        ->toContain('Checkout started')
        ->toContain('+1.234 ms')
        ->toContain('app/Checkout.php:18')
        ->toContain('data-ndb-checkpoint-context-list')
        ->toContain('cart_id')
        ->toContain('metadata')
        ->toContain('data-ndb-language="json"')
        ->not->toContain('data-ndb-checkpoint-connector');
});

it('renders an intentional empty checkpoint state', function () {
    $html = view('newdebugbar::livewire.sections.messages', [
        'section' => ['payload' => ['items' => []]],
    ])->render();

    expect($html)
        ->toContain('What are checkpoints?')
        ->toContain('No checkpoints were added.')
        ->toContain('https://laravel.com/docs/logging#writing-log-messages')
        ->not->toContain('data-ndb-checkpoint-list');
});
