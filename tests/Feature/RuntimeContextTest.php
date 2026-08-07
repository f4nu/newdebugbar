<?php

use Composer\InstalledVersions;
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

it('keeps profiling when a path dependency still has the old package name', function () {
    $originalInstalled = InstalledVersions::getRawData();
    $installed = $originalInstalled;
    $oldPackage = $installed['versions']['newdebugbar/newdebugbar'];
    unset($installed['versions']['newdebugbar/newdebugbar']);
    $installed['versions']['newdebugbar/new-debug-bar'] = $oldPackage;
    InstalledVersions::reload($installed);

    try {
        expect(app(RuntimeContext::class)->build(false)['package'])
            ->toBe($oldPackage['pretty_version']);
    } finally {
        InstalledVersions::reload($originalInstalled);
    }
});
