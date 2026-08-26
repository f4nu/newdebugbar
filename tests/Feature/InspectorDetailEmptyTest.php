<?php

use Illuminate\Support\Facades\Blade;

it('centers intentional unselected detail panes within the available height', function () {
    $html = Blade::render(<<<'BLADE'
        <x-newdebugbar::inspector-detail-empty label="Choose a record to inspect." />
        BLADE);

    expect($html)
        ->toContain(
            'data-ndb-inspector-detail-empty',
            'ndb:flex',
            'ndb:flex-1',
            'ndb:items-center',
            'ndb:justify-center',
            'ndb:text-center',
            'Choose a record to inspect.',
        )
        ->not->toContain('ndb:place-items-center');
});
