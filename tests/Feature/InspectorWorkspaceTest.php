<?php

use Illuminate\Support\Facades\Blade;

it('makes top-framed inspector workspaces full bleed on mobile in every mode', function (string $mode, string $contents) {
    $html = Blade::render($contents);

    expect($html)
        ->toContain('ndb:-mx-3')
        ->toContain('ndb:sm:mx-0')
        ->when($mode === 'focus', fn ($expectation) => $expectation
            ->toContain('data-ndb-inspector-focus-list')
            ->toContain('data-ndb-inspector-focus-detail'));
})->with([
    'split' => [
        'split',
        <<<'BLADE'
            <x-newdebugbar::inspector-workspace frame="top">
                Split workspace
            </x-newdebugbar::inspector-workspace>
            BLADE,
    ],
    'focus' => [
        'focus',
        <<<'BLADE'
            <x-newdebugbar::inspector-workspace
                mode="focus"
                frame="top"
                detail-id="newdebugbar-test-detail"
            >
                <x-slot:list>Focus list</x-slot:list>
                <x-slot:detail>Focus detail</x-slot:detail>
            </x-newdebugbar::inspector-workspace>
            BLADE,
    ],
    'stream' => [
        'stream',
        <<<'BLADE'
            <x-newdebugbar::inspector-workspace mode="stream" frame="top">
                Stream workspace
            </x-newdebugbar::inspector-workspace>
            BLADE,
    ],
]);

it('keeps card-framed inspector workspaces inset', function () {
    $html = Blade::render(<<<'BLADE'
        <x-newdebugbar::inspector-workspace>
            Card workspace
        </x-newdebugbar::inspector-workspace>
        BLADE);

    expect($html)
        ->toContain('ndb:rounded-xl')
        ->not->toContain('ndb:-mx-3', 'ndb:sm:mx-0');
});
