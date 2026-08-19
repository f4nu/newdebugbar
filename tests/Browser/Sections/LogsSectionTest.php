<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('shows log call sites', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="logs"]')
        ->assertSee('tests/Support/DefinesTestApplication.php')
        ->click('[data-ndb-log-item] > summary')
        ->assertPresent('[data-ndb-copy-log-callsite="0"]')
        ->assertNoJavaScriptErrors();

    DebugBarBrowser::assertSectionSelected($page, 'logs');
});
