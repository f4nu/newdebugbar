<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('presents a chronological diagnostic stream with useful filters and details', function () {
    $page = visit('/profiled-logs')
        ->resize(1440, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="logs"]');

    DebugBarBrowser::waitForDetails($page);

    $page
        ->assertCount('[data-ndb-log-entry]', 24)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-log-entry]'))
                .reduce((count, entry) => count + Number(entry.dataset.ndbLogRecordCount), 0)
            JS, 26)
        ->assertScript(<<<'JS'
            ['debug', 'info', 'notice', 'warning', 'error', 'critical'].every((level) =>
                document.querySelector(`[data-ndb-log-entry][data-ndb-log-level="${level}"]`)
                    && Array.from(document.querySelector('[data-ndb-log-level-select]').options)
                        .some((option) => option.value === level)
            )
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const entries = Array.from(document.querySelectorAll('[data-ndb-log-entry]'));
                const sequences = entries.map((entry) => Number(entry.dataset.ndbLogFirstSequence));
                const controls = document.querySelector('[data-ndb-log-controls]');
                const content = document.querySelector('[data-ndb-inspector-content]');
                const list = document.querySelector('[data-ndb-log-list]');

                return sequences.every((sequence, index) => index === 0 || sequence > sequences[index - 1])
                    && document.querySelector('[data-ndb-log-order]').textContent.trim() === 'Oldest first'
                    && getComputedStyle(content).overflowY === 'auto'
                    && getComputedStyle(list).overflowY === 'visible'
                    && ! ['•', '·'].some((separator) => controls.textContent.includes(separator));
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const repeated = document.querySelector('[data-ndb-log-entry][data-ndb-log-record-count="3"]');

                return repeated?.dataset.ndbLogLevel === 'warning'
                    && repeated.querySelector('[data-ndb-log-repeat-label]').textContent.includes('3 records')
                    && getComputedStyle(repeated.querySelector('[data-ndb-log-repeat-label]')).backgroundColor === 'rgba(0, 0, 0, 0)'
                    && repeated.querySelector('[data-ndb-log-message]').textContent.includes('needs attention');
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const notice = document.querySelector('[data-ndb-log-entry][data-ndb-log-level="notice"]');
                const message = notice.querySelector('[data-ndb-log-message]');
                const critical = document.querySelector('[data-ndb-log-entry][data-ndb-log-level="critical"]');

                return message.textContent.includes('\n')
                    && getComputedStyle(message).whiteSpace === 'pre-wrap'
                    && critical.scrollWidth <= critical.clientWidth;
            })()
            JS)
        ->keys('[data-ndb-log-entry][data-ndb-log-record-count="3"] > summary', 'Enter')
        ->assertAttribute('[data-ndb-log-entry][data-ndb-log-record-count="3"]', 'open', '')
        ->assertCount('[data-ndb-log-entry][data-ndb-log-record-count="3"] [data-ndb-log-occurrences] li', 3)
        ->assertPresent('[data-ndb-log-entry][data-ndb-log-record-count="3"] [data-ndb-log-context]')
        ->assertMissing('[data-ndb-log-entry][data-ndb-log-record-count="3"] [data-ndb-log-actions]')
        ->assertMissing('[data-ndb-log-entry][data-ndb-log-record-count="3"] [data-ndb-copy-log-message]')
        ->assertMissing('[data-ndb-log-entry][data-ndb-log-record-count="3"] [data-ndb-copy-log-context]')
        ->assertMissing('[data-ndb-log-entry][data-ndb-log-record-count="3"] [data-ndb-copy-log-source]')
        ->click('[data-ndb-log-entry][data-ndb-log-level="error"] > summary')
        ->assertAttribute('[data-ndb-log-entry][data-ndb-log-level="error"]', 'open', '')
        ->assertPresent('[data-ndb-log-entry][data-ndb-log-level="error"] [data-ndb-log-related-exception]')
        ->assertPresent('[data-ndb-log-entry][data-ndb-log-level="error"] [data-ndb-log-review-exception]')
        ->assertScript(<<<'JS'
            (() => {
                const paragraphs = document.querySelectorAll(
                    '[data-ndb-log-entry][data-ndb-log-level="error"] [data-ndb-log-related-exception] p'
                );

                const message = paragraphs[1]?.querySelector('span');

                return message && message.textContent === message.textContent.trim();
            })()
            JS)
        ->assertPresent('[data-ndb-log-entry][data-ndb-log-level="error"] [data-ndb-log-source]')
        ->assertPresent('[data-ndb-log-entry][data-ndb-log-level="error"] [data-ndb-log-raw]')
        ->select('[data-ndb-log-level-select]', 'attention')
        ->assertScript('document.querySelector("[data-ndb-log-level-select]").value === "attention"')
        ->assertCount('[data-ndb-log-entry]:not([hidden])', 3)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-log-entry]:not([hidden])'))
                .reduce((count, entry) => count + Number(entry.dataset.ndbLogRecordCount), 0)
            JS, 5)
        ->select('[data-ndb-log-level-select]', 'all')
        ->select('[data-ndb-log-channel-select]', 'newdebugbar-audit')
        ->assertCount('[data-ndb-log-entry]:not([hidden])', 1)
        ->assertScript('document.querySelector("[data-ndb-log-entry]:not([hidden])").dataset.ndbLogLevel === "info"')
        ->select('[data-ndb-log-channel-select]', 'all')
        ->fill('[data-ndb-log-search]', 'KYO-441')
        ->assertCount('[data-ndb-log-entry]:not([hidden])', 1)
        ->assertScript('document.querySelector("[data-ndb-log-entry]:not([hidden])").dataset.ndbLogLevel === "error"')
        ->assertNoJavaScriptErrors();

    DebugBarBrowser::assertSectionSelected($page, 'logs');
});

it('keeps log reading usable on mobile dark mode empty results and reopen', function () {
    $page = visit('/profiled-logs')
        ->resize(390, 844)
        ->click('[data-ndb-mobile-toolbar-trigger="actions"]')
        ->click('[data-ndb-mobile-toolbar-action="inspector"]')
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="sections"]')
        ->click('[data-ndb-select-section="logs"]');

    DebugBarBrowser::waitForDetails($page);

    $page
        ->assertScript(<<<'JS'
            (() => {
                const content = document.querySelector('[data-ndb-inspector-content]');
                const controls = document.querySelector('[data-ndb-log-controls]');
                const entries = Array.from(document.querySelectorAll('[data-ndb-log-entry]'));
                const input = document.querySelector('[data-ndb-log-search]');
                const selects = [...document.querySelectorAll('[data-ndb-log-level-select], [data-ndb-log-channel-select]')];

                return content.scrollWidth <= content.clientWidth
                    && controls.scrollWidth <= controls.clientWidth
                    && input.getBoundingClientRect().width <= controls.getBoundingClientRect().width
                    && selects.every((select) => select.getBoundingClientRect().width <= controls.getBoundingClientRect().width)
                    && entries.every((entry) => entry.scrollWidth <= entry.clientWidth);
            })()
            JS)
        ->keys('[data-ndb-log-entry][data-ndb-log-level="notice"] > summary', 'Enter')
        ->assertAttribute('[data-ndb-log-entry][data-ndb-log-level="notice"]', 'open', '')
        ->assertMissing('[data-ndb-log-entry][data-ndb-log-level="notice"] [data-ndb-log-context]')
        ->assertMissing('[data-ndb-log-entry][data-ndb-log-level="notice"] [data-ndb-log-context-empty]')
        ->assertScript(<<<'JS'
            (() => {
                const content = document.querySelector('[data-ndb-inspector-content]');
                const entry = document.querySelector('[data-ndb-log-entry][data-ndb-log-level="notice"]');

                return content.scrollWidth <= content.clientWidth
                    && entry.scrollWidth <= entry.clientWidth
                    && document.activeElement === entry.querySelector(':scope > summary');
            })()
            JS)
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="theme"]')
        ->assertAttribute('#newdebugbar', 'data-ndb-theme', 'dark')
        ->fill('[data-ndb-log-search]', 'no-record-can-match-this-search')
        ->assertCount('[data-ndb-log-entry]:not([hidden])', 0)
        ->assertVisible('[data-ndb-log-filter-empty]')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-log-entry][hidden]')).every((entry) =>
                getComputedStyle(entry).display === 'none'
            )
            JS)
        ->fill('[data-ndb-log-search]', '')
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="shrink"]')
        ->click('[data-ndb-mobile-toolbar-trigger="actions"]')
        ->click('[data-ndb-mobile-toolbar-action="inspector"]');

    DebugBarBrowser::assertSectionSelected($page, 'overview');

    $page
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="sections"]')
        ->click('[data-ndb-select-section="logs"]');

    DebugBarBrowser::waitForDetails($page);
    DebugBarBrowser::assertSectionSelected($page, 'logs');

    $page
        ->assertCount('[data-ndb-log-entry]', 24)
        ->assertScript('document.querySelector("[data-ndb-log-level-select]").value === "all"')
        ->assertScript('document.querySelector("[data-ndb-log-channel-select]").value === "all"')
        ->assertNoJavaScriptErrors();
});
