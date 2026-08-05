<?php

use NewDebugBar\Support\RuntimeContext;

it('reports host runtime facts without claiming unused Livewire activity', function () {
    $context = app(RuntimeContext::class);
    $inactive = $context->build(false);
    $active = $context->build(true);

    expect($inactive['runtime'])
        ->environment->toBe('testing')
        ->laravel->toBe(app()->version())
        ->php->toBe(PHP_VERSION)
        ->php_sapi->toBe(PHP_SAPI)
        ->and($inactive['cache_state'])->toHaveKeys(['configuration', 'routes', 'events'])
        ->and($inactive['drivers'])->toHaveKeys(['database', 'cache', 'queue', 'session', 'mail'])
        ->and(array_column($inactive['ecosystem'], 'key'))->not->toContain('livewire')
        ->and(array_column($active['ecosystem'], 'key'))->toContain('livewire');
});
