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

it('finds the first application location in a throwable', function () {
    $root = dirname(__DIR__, 2);
    $resolver = new CallSiteResolver(
        projectPath: $root,
        packagePath: $root,
    );
    $exception = new RuntimeException('Validation failed.');

    expect($resolver->fromThrowable($exception))->toMatchArray([
        'file' => 'tests/Unit/CallSiteResolverTest.php',
    ]);
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

it('does not expose an ordinary compiled Blade file as an application call site', function () {
    $root = sys_get_temp_dir().'/newdebugbar-callsite-'.bin2hex(random_bytes(6));
    $compiledDirectory = $root.'/storage/framework/views';
    $sourceDirectory = $root.'/resources/views';
    mkdir($compiledDirectory, 0777, true);
    mkdir($sourceDirectory, 0777, true);
    $source = $sourceDirectory.'/plain.blade.php';
    $compiled = $compiledDirectory.'/plain.php';
    file_put_contents($source, '<p>Plain view</p>');
    file_put_contents($compiled, '<?php return $resolver->capture(); ?>'.PHP_EOL.'<?php /**PATH '.$source.' ENDPATH**/ ?>');
    $resolver = new CallSiteResolver(
        projectPath: $root,
        packagePath: dirname(__DIR__, 2),
    );

    $location = (static fn (string $file, CallSiteResolver $resolver): array => include $file)($compiled, $resolver);

    expect($location)->toBe(['callsite' => null, 'stack' => []]);

    unlink($compiled);
    unlink($source);
    rmdir($compiledDirectory);
    rmdir(dirname($compiledDirectory));
    rmdir(dirname($compiledDirectory, 2));
    rmdir($sourceDirectory);
    rmdir(dirname($sourceDirectory));
    rmdir($root);
});
