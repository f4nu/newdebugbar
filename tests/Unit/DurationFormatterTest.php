<?php

use NewDebugBar\Support\DurationFormatter;

it('formats milliseconds with an adaptive duration unit', function (mixed $milliseconds, string $expected) {
    expect(DurationFormatter::format($milliseconds))->toBe($expected);
})->with([
    'missing' => [null, '—'],
    'negative' => [-1, '0 µs'],
    'zero' => [0, '0 µs'],
    'less than one microsecond' => [0.0005, '<1 µs'],
    'microseconds' => [0.19, '190 µs'],
    'milliseconds' => [12.34, '12.34 ms'],
    'whole milliseconds' => [250, '250 ms'],
    'one second' => [1_000, '1 s'],
    'seconds' => [1_453.51, '1.45 s'],
]);
