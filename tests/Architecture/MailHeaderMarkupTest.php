<?php

it('composes Mail header facts from the shared fact family', function () {
    $header = file_get_contents(dirname(__DIR__, 2).'/resources/views/components/mail-header.blade.php');

    expect($header)
        ->toContain('<x-newdebugbar::inspector-facts')
        ->toContain(':bordered="false"')
        ->toContain('columns="4"')
        ->and(substr_count($header, '<x-newdebugbar::inspector-fact '))->toBe(4)
        ->and($header)
        ->not->toContain('<div data-ndb-mail-fact');
});
