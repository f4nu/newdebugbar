<?php

use NewDebugBar\Support\ExceptionNormalizer;

function normalizedTestException(): Throwable
{
    try {
        throw new RuntimeException('A bounded failure');
    } catch (Throwable $exception) {
        return $exception;
    }
}

it('builds bounded project relative exception frames and source context', function () {
    $root = dirname(__DIR__, 2);
    $normalizer = new ExceptionNormalizer(
        projectPath: $root,
        packagePath: $root,
        maxApplicationFrames: 2,
        maxVendorFrames: 2,
        sourceContextLines: 5,
    );

    $exception = normalizedTestException();
    $normalized = $normalizer->normalize($exception);

    expect($normalized)
        ->class->toBe(RuntimeException::class)
        ->message->toBe('A bounded failure')
        ->file->toBe('tests/Unit/ExceptionNormalizerTest.php')
        ->line->toBe($exception->getLine())
        ->and($normalized['frames']['application'])->not->toBeEmpty()->toHaveCount(2)
        ->and($normalized['frames']['vendor'])->toHaveCount(2)
        ->and($normalized['source']['file'])->toBe('tests/Unit/ExceptionNormalizerTest.php')
        ->and($normalized['source']['lines'])->toHaveCount(5)
        ->and(collect($normalized['source']['lines'])->where('focus', true))->toHaveCount(1)
        ->and(json_encode($normalized))->not->toContain($root.'/');
});
