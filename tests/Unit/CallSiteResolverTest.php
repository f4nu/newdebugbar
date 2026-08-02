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
