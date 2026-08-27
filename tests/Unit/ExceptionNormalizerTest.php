<?php

use NewDebugBar\Support\ExceptionNormalizer;

$exceptionNormalizer = fn (): ExceptionNormalizer => new ExceptionNormalizer(
    projectPath: dirname(__DIR__, 2),
    packagePath: dirname(__DIR__, 2),
    maxApplicationFrames: 2,
    maxVendorFrames: 2,
    sourceContextLines: 5,
);

$normalizedTestException = function (): Throwable {
    try {
        throw new RuntimeException('A bounded failure');
    } catch (Throwable $exception) {
        return $exception;
    }
};

it('builds bounded project relative exception frames and source context', function () use ($exceptionNormalizer, $normalizedTestException) {
    $root = dirname(__DIR__, 2);
    $normalizer = $exceptionNormalizer();

    $exception = $normalizedTestException();
    $normalized = $normalizer->normalize($exception);

    expect($normalized)
        ->class->toBe(RuntimeException::class)
        ->message->toBe('A bounded failure')
        ->file->toBe('tests/Unit/ExceptionNormalizerTest.php')
        ->line->toBe($exception->getLine())
        ->not->toHaveKey('handled')
        ->and($normalized['frames']['application'])->not->toBeEmpty()->toHaveCount(2)
        ->and($normalized['frames']['vendor'])->toHaveCount(2)
        ->and($normalized['source']['file'])->toBe('tests/Unit/ExceptionNormalizerTest.php')
        ->and($normalized['source']['lines'])->toHaveCount(5)
        ->and(collect($normalized['source']['lines'])->where('focus', true))->toHaveCount(1)
        ->and($normalized['causes'])->toBe([])
        ->and($normalized['chain_truncated'])->toBeFalse()
        ->and(json_encode($normalized))->not->toContain($root.'/');
});

it('retains at most five complete causes in immediate order', function () use ($exceptionNormalizer) {
    $previous = null;

    foreach (array_reverse(range(1, 6)) as $index) {
        $previous = new RuntimeException("Cause {$index}", previous: $previous);
    }

    $normalized = $exceptionNormalizer()->normalize(new LogicException('Root failure', previous: $previous));

    expect($normalized['causes'])
        ->toHaveCount(5)
        ->and(array_column($normalized['causes'], 'message'))->toBe([
            'Cause 1',
            'Cause 2',
            'Cause 3',
            'Cause 4',
            'Cause 5',
        ])
        ->and($normalized['causes'][0])->toHaveKeys(['class', 'message', 'file', 'line', 'frames', 'source'])
        ->and($normalized['chain_truncated'])->toBeTrue();
});

it('stops safely when an exception cause chain cycles', function () use ($exceptionNormalizer) {
    $first = new RuntimeException('First cause');
    $root = new LogicException('Root failure', previous: $first);
    $previous = new ReflectionProperty(Exception::class, 'previous');
    $previous->setValue($first, $root);

    $normalized = $exceptionNormalizer()->normalize($root);

    expect($normalized['causes'])
        ->toHaveCount(1)
        ->and($normalized['causes'][0]['message'])->toBe('First cause')
        ->and($normalized['chain_truncated'])->toBeFalse();
});
