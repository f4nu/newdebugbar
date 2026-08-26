<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('shows relative exception frames and highlighted source context', function () {
    $page = visit('/profiled-reported-exception')
        ->resize(1440, 900)
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
        ->assertScript(<<<'JS'
            (() => {
                const primary = document.querySelector('[data-ndb-exception-detail="0"] [data-ndb-inspector-detail-header-primary]');
                const copy = primary?.querySelector('[data-ndb-exception-header-copy]');
                const type = copy?.querySelector('h3');
                const message = copy?.querySelector('p');
                const source = primary?.querySelector('[data-ndb-copy-exception-callsite="0"]');

                if (!primary || !copy || !type || !message || !source) return false;

                const primaryBox = primary.getBoundingClientRect();
                const copyBox = copy.getBoundingClientRect();
                const typeBox = type.getBoundingClientRect();
                const messageBox = message.getBoundingClientRect();
                const sourceBox = source.getBoundingClientRect();

                return primary.children.length === 2
                    && copy.parentElement === primary
                    && source.parentElement === primary
                    && Math.abs(typeBox.left - messageBox.left) <= 1
                    && messageBox.top >= typeBox.bottom
                    && sourceBox.left >= copyBox.right
                    && Math.abs(sourceBox.top - copyBox.top) <= 1
                    && primary.scrollWidth <= primary.clientWidth + 1
                    && copyBox.left >= primaryBox.left
                    && sourceBox.right <= primaryBox.right + 1;
            })()
            JS)
        ->assertScript('document.querySelectorAll("#newdebugbar code[data-ndb-language=php][data-highlighted]").length > 0')
        ->assertScript(<<<'JS'
            (() => {
                const lines = document.querySelector('[data-ndb-exception-detail-panel="source"] code')
                    .textContent.split('\n').filter((line) => line.trim().length > 0);

                return lines.every((line) => !line.startsWith(' '));
            })()
            JS)
        ->click('[data-ndb-exception-detail-tab="stack"]')
        ->assertAttribute('[data-ndb-exception-detail-tab="stack"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-exception-detail-panel="stack"]')
        ->assertSee('Application stack')
        ->assertSee('Vendor stack')
        ->assertNoJavaScriptErrors();
});
