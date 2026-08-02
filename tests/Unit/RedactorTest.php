<?php

use NewDebugBar\Support\Redactor;

enum RedactorBackedValue: string
{
    case Ready = 'ready';
}

enum RedactorNamedValue
{
    case Waiting;
}

it('redacts sensitive values recursively', function () {
    $redactor = new Redactor;

    expect($redactor->clean([
        'authorization' => 'Bearer secret',
        'proxy-authorization' => 'Basic secret',
        'set-cookie' => 'session=secret',
        'x-api-key' => 'secret',
        'nested' => [
            'password' => 'secret',
            'clinic_name' => 'Example Clinic',
        ],
    ]))->toBe([
        'authorization' => '[redacted]',
        'proxy-authorization' => '[redacted]',
        'set-cookie' => '[redacted]',
        'x-api-key' => '[redacted]',
        'nested' => [
            'password' => '[redacted]',
            'clinic_name' => 'Example Clinic',
        ],
    ]);
});

it('bounds nested and long values', function () {
    $redactor = new Redactor(maxDepth: 2, maxStringLength: 4, maxArrayItems: 2);

    expect($redactor->clean([
        'long' => 'abcdef',
        'nested' => ['too_deep' => ['value']],
        'extra' => true,
    ]))->toBe([
        'long' => 'abcd…',
        'nested' => ['too_deep' => '[maximum depth reached]'],
        '__truncated__' => 1,
    ]);
});

it('normalizes common debug values without leaking object internals', function () {
    $resource = fopen('php://memory', 'rb');
    $stringable = new class implements Stringable
    {
        public function __toString(): string
        {
            return 'visible';
        }
    };

    try {
        expect((new Redactor)->clean([
            'date' => new DateTimeImmutable('2026-08-01T10:00:00+00:00'),
            'backed' => RedactorBackedValue::Ready,
            'named' => RedactorNamedValue::Waiting,
            'stringable' => $stringable,
            'object' => new stdClass,
            'resource' => $resource,
        ]))->toBe([
            'date' => '2026-08-01T10:00:00+00:00',
            'backed' => 'ready',
            'named' => 'Waiting',
            'stringable' => 'visible',
            'object' => '[stdClass]',
            'resource' => '[resource]',
        ]);
    } finally {
        fclose($resource);
    }
});
