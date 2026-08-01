<?php

use NewDebugBar\Support\Redactor;

it('redacts sensitive values recursively', function () {
    $redactor = new Redactor;

    expect($redactor->clean([
        'authorization' => 'Bearer secret',
        'nested' => [
            'password' => 'secret',
            'clinic_name' => 'Example Clinic',
        ],
    ]))->toBe([
        'authorization' => '[redacted]',
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
