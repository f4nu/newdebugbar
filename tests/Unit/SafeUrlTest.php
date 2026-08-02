<?php

use NewDebugBar\Support\Redactor;
use NewDebugBar\Support\SafeUrl;

it('removes credentials fragments and private query values from URLs', function () {
    $urls = new SafeUrl(new Redactor);

    expect($urls->clean('https://user:password@example.test:8443/v1/items?token=secret&limit=5#private'))
        ->toBe('https://example.test:8443/v1/items?token=%5Bredacted%5D&limit=5')
        ->and($urls->clean('/relative/private'))->toBe('[invalid-url]');
});
