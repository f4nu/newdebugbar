<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('shows relative exception frames and highlighted source context', function () {
    $page = visit('/profiled-reported-exception')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="exceptions"]');

    DebugBarBrowser::assertSectionSelected($page, 'exceptions');

    $page
        ->assertVisible('[data-ndb-exception-workspace]')
        ->assertVisible('[data-ndb-exception-item="0"]')
        ->assertVisible('[data-ndb-exception-detail="0"]')
        ->assertAttribute('[data-ndb-exception-detail-tab="source"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-exception-detail-panel="source"]')
        ->assertSee('tests/Support/DefinesTestApplication.php')
        ->assertDontSee(base_path().'/tests/Support/DefinesTestApplication.php')
        ->assertPresent('[data-ndb-copy-exception-callsite="0"]')
        ->assertScript('document.querySelectorAll("#newdebugbar code[data-ndb-language=php][data-highlighted]").length > 0')
        ->click('[data-ndb-exception-detail-tab="stack"]')
        ->assertAttribute('[data-ndb-exception-detail-tab="stack"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-exception-detail-panel="stack"]')
        ->assertSee('Application stack')
        ->assertSee('Vendor stack')
        ->assertNoJavaScriptErrors();
});
