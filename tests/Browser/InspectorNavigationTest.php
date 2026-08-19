<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('switches every section after Livewire navigation with one active state', function () {
    $page = visit('/profiled')
        ->click('[data-testid="host-navigation"]')
        ->waitForText('Second request')
        ->assertPathIs('/profiled-next')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    foreach (['request', 'timeline', 'queries', 'models', 'cache', 'views', 'events', 'logs', 'exceptions', 'overview', 'models'] as $section) {
        DebugBarBrowser::selectSectionViaPalette($page, $section);

        DebugBarBrowser::assertSectionSelected($page, $section);
    }

    $page->assertNoJavaScriptErrors();
});
