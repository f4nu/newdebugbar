<?php

use NewDebugBar\Support\CallSiteResolver;

it('captures bounded project relative call sites', function () {
    $root = dirname(__DIR__, 2);
    $location = (new CallSiteResolver(
        projectPath: $root,
        packagePath: $root,
        maxFrames: 2,
    ))->capture();

    expect($location['callsite']['file'])->toBe('tests/Unit/CallSiteResolverTest.php')
        ->and($location['callsite']['file'])->not->toStartWith('/')
        ->and($location['stack'])->toHaveCount(1);
});

it('can disable call site capture', function () {
    $location = (new CallSiteResolver(
        projectPath: dirname(__DIR__, 2),
        packagePath: dirname(__DIR__, 2),
        enabled: false,
    ))->capture();

    expect($location)->toBe(['callsite' => null, 'stack' => []]);
});

it('resolves template files without applying the application call site filter', function () {
    $root = dirname(__DIR__, 2);
    $resolver = new CallSiteResolver(
        projectPath: $root,
        packagePath: $root,
        enabled: false,
    );

    expect($resolver->location($root.'/vendor/autoload.php'))->toBeNull()
        ->and($resolver->templateLocation($root.'/vendor/autoload.php'))->toBe([
            'file' => 'vendor/autoload.php',
            'line' => 1,
        ]);
});
