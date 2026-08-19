<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('shows relative exception frames and highlighted source context', function () {
    $page = visit('/profiled-reported-exception')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="exceptions"]');

    DebugBarBrowser::assertSectionSelected($page, 'exceptions');

    $page
        ->assertSee('Application frames')
        ->assertSee('Vendor frames')
        ->assertSee('tests/Support/DefinesTestApplication.php')
        ->assertDontSee('/Users/benjamin/Sites/new-debug-bar/tests/Support/DefinesTestApplication.php')
        ->assertPresent('[data-ndb-copy-exception-callsite="0"]')
        ->assertScript('document.querySelectorAll("#newdebugbar code[data-ndb-language=php][data-highlighted]").length > 0')
        ->assertNoJavaScriptErrors();
});
