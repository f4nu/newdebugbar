<?php

use NewDebugBar\Livewire\ExecutionContext;
use NewDebugBar\Livewire\StateDiff;
use NewDebugBar\Support\Redactor;

it('keeps execution attribution on a stack and unwinds after failure', function () {
    $context = new ExecutionContext;
    $component = $context->push(['component_id' => 'component-1', 'phase' => 'hydrate']);

    expect($context->current())->toBe(['component_id' => 'component-1', 'phase' => 'hydrate']);

    try {
        $context->run(['component_id' => 'component-1', 'action_id' => 'action-1', 'phase' => 'call'], function () use ($context): void {
            expect($context->current())->toBe([
                'component_id' => 'component-1',
                'action_id' => 'action-1',
                'phase' => 'call',
            ]);

            throw new RuntimeException('fixture failure');
        });
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('fixture failure');
    }

    expect($context->current())->toBe(['component_id' => 'component-1', 'phase' => 'hydrate']);

    $context->pop($component);

    expect($context->current())->toBeNull();
});

it('records bounded diffs while hiding changed secret values', function () {
    $diff = new StateDiff(new Redactor, maxChanges: 2);
    $result = $diff->between(
        ['email' => 'before@example.test', 'password' => 'before-secret', 'status' => 'pending'],
        ['email' => 'after@example.test', 'password' => 'after-secret', 'status' => 'ready'],
        ['email' => 'after@example.test', 'password' => 'after-secret', 'status' => 'ready'],
    );

    expect($result['changes'])->toHaveCount(2)
        ->and($result['dropped'])->toBe(1)
        ->and($result['changes'][0])
        ->path->toBe('email')
        ->before->toBe('before@example.test')
        ->submitted->toBe('after@example.test')
        ->server->toBe('after@example.test')
        ->redacted->toBeFalse()
        ->and($result['changes'][1])
        ->path->toBe('password')
        ->before->toBe('[redacted]')
        ->submitted->toBe('[redacted]')
        ->server->toBe('[redacted]')
        ->redacted->toBeTrue();
});
