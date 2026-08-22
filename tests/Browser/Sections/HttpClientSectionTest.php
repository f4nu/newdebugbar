<?php

it('filters, sorts, selects, and inspects outbound HTTP evidence', function () {
    visit('/profiled-http-client-rich')
        ->resize(1440, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="http_client"]')
        ->waitForText('Needs attention')
        ->assertSee('6 requests')
        ->assertSee('4 failed')
        ->assertSee('1 slow')
        ->assertAttribute('[data-ndb-http-client-filter="attention"]', 'aria-pressed', 'true')
        ->assertAttribute('[data-ndb-http-client-detail-tab="overview"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item]:not([hidden])").length', 5)
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-http-client-item=\\"2\\"]")).display === "none"')
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-http-client-workspace]');
                const [list, detail] = workspace.children;
                const listBox = list.getBoundingClientRect();
                const detailBox = detail.getBoundingClientRect();
                const sharedTabs = [...document.querySelectorAll('[data-ndb-http-client] [data-ndb-filter-tabs]')];
                const tabButtons = [...document.querySelectorAll('[data-ndb-http-client] [data-ndb-filter-tab]')];
                const methodBadges = [...document.querySelectorAll('[data-ndb-http-client-item]:not([hidden]) > span:first-child')];
                const normal = document.querySelector('[data-ndb-http-client-item="2"]');
                const failure = document.querySelector('[data-ndb-http-client-item="3"]');

                return getComputedStyle(workspace).display === 'grid'
                    && Math.abs(listBox.top - detailBox.top) <= 1
                    && Math.abs(listBox.right - detailBox.left) <= 1
                    && sharedTabs.length === 2
                    && tabButtons.every((button) => Number.parseFloat(getComputedStyle(button).borderRadius) > 0)
                    && methodBadges.every((badge) => Math.abs(badge.getBoundingClientRect().width - methodBadges[0].getBoundingClientRect().width) <= 0.5)
                    && getComputedStyle(normal).backgroundColor === getComputedStyle(failure).backgroundColor;
            })()
            JS)
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item][aria-pressed=true]").length', 1)
        ->click('[data-ndb-http-client-item="5"]')
        ->assertAttribute('[data-ndb-http-client-item="5"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelector("[data-ndb-http-client-detail-status-code]").textContent.trim() === "503"')
        ->assertScript('document.querySelector("[data-ndb-http-client-detail-status]").textContent.includes("Service Unavailable")')
        ->assertSee('The upstream service could not complete this request.')
        ->assertSee('Confirm endpoint health, timeout, and retry behavior.')
        ->click('[data-ndb-http-client-detail-tab="request"]')
        ->assertAttribute('[data-ndb-http-client-detail-tab="request"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-http-client-detail-panel="request"]')
        ->assertSee('Headers')
        ->click('[data-ndb-http-client-detail-tab="response"]')
        ->assertVisible('[data-ndb-http-client-detail-panel="response"]')
        ->assertSee('Service unavailable.')
        ->click('[data-ndb-http-client-detail-tab="stack"]')
        ->assertVisible('[data-ndb-http-client-detail-panel="stack"]')
        ->assertSee('tests/Support/DefinesTestApplication.php')
        ->click('[data-ndb-http-client-copy-curl]')
        ->click('[data-ndb-http-client-copy-url]')
        ->click('[data-ndb-http-client-filter="all"]')
        ->assertAttribute('[data-ndb-http-client-filter="all"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item]:not([hidden])").length', 6)
        ->select('[data-ndb-http-client-sort]', 'duration')
        ->assertValue('[data-ndb-http-client-sort]', 'duration')
        ->assertScript(<<<'JS'
            (() => {
                const durations = [...document.querySelectorAll('[data-ndb-http-client-item]:not([hidden])')]
                    .map((item) => Number(item.dataset.duration));

                return durations.every((duration, index) => index === 0 || durations[index - 1] >= duration);
            })()
            JS)
        ->type('[data-ndb-http-client-search]', 'healthy.test')
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item]:not([hidden])").length', 1)
        ->assertAttribute('[data-ndb-http-client-item="2"]', 'aria-pressed', 'true')
        ->type('[data-ndb-http-client-search]', ' no request matches')
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item]:not([hidden])").length', 0)
        ->assertSee('No outbound HTTP requests match these filters.')
        ->assertNoJavaScriptErrors();
});

it('stacks the request list and detail cleanly on mobile in dark mode', function () {
    $preferences = json_encode([
        'theme' => 'dark',
        'favorites' => [],
    ], JSON_THROW_ON_ERROR);

    visit('/profiled-http-client-rich')
        ->resize(390, 844)
        ->assertScript(<<<JS
            (() => {
                localStorage.setItem('newdebugbar.preferences.v1', '{$preferences}');

                return true;
            })()
            JS)
        ->refresh()
        ->click('[data-ndb-mobile-toolbar-trigger="actions"]')
        ->click('[data-ndb-mobile-toolbar-action="inspector"]')
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="sections"]')
        ->click('[data-ndb-select-section="http_client"]')
        ->waitForText('Needs attention')
        ->assertAttribute('#newdebugbar', 'data-theme', 'dark')
        ->assertScript(<<<'JS'
            (() => {
                const dialog = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                const workspace = document.querySelector('[data-ndb-http-client-workspace]');
                const [list, detail] = workspace.children;
                const listBox = list.getBoundingClientRect();
                const detailBox = detail.getBoundingClientRect();

                return dialog.scrollWidth <= dialog.clientWidth + 1
                    && workspace.scrollWidth <= workspace.clientWidth + 1
                    && getComputedStyle(workspace).display !== 'grid'
                    && detailBox.top >= listBox.bottom - 1;
            })()
            JS)
        ->click('[data-ndb-http-client-item="5"]')
        ->assertAttribute('[data-ndb-http-client-item="5"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-http-client-detail]')
        ->assertScript(<<<'JS'
            (() => {
                const detail = document.querySelector('[data-ndb-http-client-detail]');
                const content = document.querySelector('[data-ndb-inspector-content]');
                const actions = [...detail.querySelectorAll('footer button')];

                return detail.getBoundingClientRect().top >= content.getBoundingClientRect().top
                    && actions.length === 2
                    && actions.every((action) => action.getBoundingClientRect().height >= 36)
                    && content.scrollWidth <= content.clientWidth + 1;
            })()
            JS)
        ->click('[data-ndb-http-client-detail-tab="response"]')
        ->assertVisible('[data-ndb-http-client-detail-panel="response"]')
        ->assertNoJavaScriptErrors();
});
