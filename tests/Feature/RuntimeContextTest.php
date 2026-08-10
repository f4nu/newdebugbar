<?php

use NewDebugBar\Support\RuntimeContext;

it('reports host runtime facts', function () {
    $context = app(RuntimeContext::class)->build();

    expect($context['runtime'])
        ->environment->toBe('testing')
        ->laravel->toBe(app()->version())
        ->php->toBe(PHP_VERSION)
        ->php_sapi->toBe(PHP_SAPI)
        ->and($context['cache_state'])->toHaveKeys(['configuration', 'routes', 'events'])
        ->not->toHaveKey('views')
        ->and($context['drivers'])->toHaveKeys(['database', 'cache', 'queue', 'session', 'mail']);
});
