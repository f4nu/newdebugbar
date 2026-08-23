<?php

it('filters, sorts, selects, and inspects outbound HTTP evidence', function () {
    visit('/profiled-http-client-rich')
        ->resize(1440, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="http_client"]')
        ->waitForText('6 requests')
        ->assertSee('6 requests')
        ->assertSee('4 failed')
        ->assertSee('1 slow')
        ->assertValue('[data-ndb-http-client-filter]', 'all')
        ->assertAttribute('[data-ndb-http-client-detail-tab="overview"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item]:not([hidden])").length', 6)
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-http-client-workspace]');
                const [list, detail] = workspace.children;
                const content = document.querySelector('[data-ndb-inspector-content]');
                const details = workspace.parentElement.parentElement.parentElement;
                const workspaceBox = workspace.getBoundingClientRect();
                const contentBox = content.getBoundingClientRect();
                const expectedBottom = contentBox.bottom - Number.parseFloat(getComputedStyle(details).paddingBottom);
                const listBox = list.getBoundingClientRect();
                const detailBox = detail.getBoundingClientRect();
                const selected = document.querySelector('[data-ndb-http-client-item][aria-pressed="true"]');
                const tabs = [...document.querySelectorAll('[data-ndb-http-client] [data-ndb-filter-tabs]')];
                const summary = document.querySelector('[data-ndb-http-client-summary]');
                const summaryCount = document.querySelector('[data-ndb-http-client-summary-count]');
                const summaryRuntime = document.querySelector('[data-ndb-http-client-summary-runtime]');
                const filter = document.querySelector('[data-ndb-http-client-filter]');
                const header = detail.querySelector('header');
                const primary = header.querySelector('[data-ndb-inspector-detail-header-primary]');
                const host = header.querySelector('[data-ndb-http-client-detail-host]');
                const status = header.querySelector('[data-ndb-http-client-detail-status]');
                const identity = header.querySelector('[data-ndb-http-client-identity]');
                const metadata = header.querySelector('[data-ndb-http-client-metadata]');

                return getComputedStyle(workspace).display === 'grid'
                    && workspaceBox.height > 500
                    && Math.abs(workspaceBox.bottom - expectedBottom) <= 1
                    && Math.abs(listBox.top - detailBox.top) <= 1
                    && Math.abs(listBox.right - detailBox.left) <= 1
                    && detailBox.width > listBox.width * 1.6
                    && selected.dataset.ndbHttpClientItem === '1'
                    && getComputedStyle(selected).borderLeftWidth === '0px'
                    && summary.parentElement.contains(filter)
                    && summary.getBoundingClientRect().left < filter.getBoundingClientRect().left
                    && summaryRuntime.getBoundingClientRect().top > summaryCount.getBoundingClientRect().top
                    && filter.options[0].value === 'all'
                    && tabs.length === 1
                    && host.closest('[data-ndb-inspector-detail-header-primary]') === primary
                    && status.closest('[data-ndb-inspector-detail-header-primary]') === primary
                    && identity.textContent.includes('Request')
                    && identity.textContent.includes('Result')
                    && metadata.children.length === 3
                    && metadata.querySelectorAll('svg').length === 0
                    && metadata.scrollWidth <= metadata.clientWidth + 1
                    && getComputedStyle(detail).overflowY === 'auto'
                    && detail.tabIndex === 0
                    && !document.querySelector('[data-ndb-http-client]').textContent.includes('•');
            })()
            JS)
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item][aria-pressed=true]").length', 1)
        ->select('[data-ndb-http-client-filter]', 'attention')
        ->assertValue('[data-ndb-http-client-filter]', 'attention')
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item]:not([hidden])").length', 5)
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-http-client-item=\\"2\\"]")).display === "none"')
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
        ->select('[data-ndb-http-client-filter]', 'all')
        ->assertValue('[data-ndb-http-client-filter]', 'all')
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item]:not([hidden])").length', 6)
        ->select('[data-ndb-http-client-sort]', 'duration')
        ->assertValue('[data-ndb-http-client-sort]', 'duration')
        ->assertScript(<<<'JS'
            (() => {
                const durations = [...document.querySelectorAll('[data-ndb-http-client-item]:not([hidden])')]
                    .map((item) => Number(item.dataset.ndbDuration));

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

it('drills into outbound HTTP request details on mobile in dark mode', function () {
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
        ->waitForText('6 requests')
        ->assertAttribute('#newdebugbar', 'data-ndb-theme', 'dark')
        ->assertScript(<<<'JS'
            (() => {
                const dialog = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                const workspace = document.querySelector('[data-ndb-http-client-workspace]');
                const [list, detail] = workspace.children;
                const rows = [...document.querySelectorAll('[data-ndb-http-client-item]')];

                return dialog.scrollWidth <= dialog.clientWidth + 1
                    && workspace.scrollWidth <= workspace.clientWidth + 1
                    && getComputedStyle(workspace).display !== 'grid'
                    && getComputedStyle(list).display === 'flex'
                    && getComputedStyle(detail).display === 'none'
                    && rows.every((row) => getComputedStyle(row).borderLeftWidth === '0px');
            })()
            JS)
        ->click('[data-ndb-http-client-item="5"]')
        ->assertAttribute('[data-ndb-http-client-item="5"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-http-client-detail]')
        ->assertScript(<<<'JS'
            (() => {
                const detail = document.querySelector('[data-ndb-http-client-detail]');
                const content = document.querySelector('[data-ndb-inspector-content]');
                const workspace = document.querySelector('[data-ndb-http-client-workspace]');
                const list = workspace.firstElementChild;
                const back = document.querySelector('[data-ndb-http-client-detail-back]');
                const actions = [...detail.querySelectorAll('footer button')];
                const metadata = document.querySelector('[data-ndb-http-client-metadata]');
                const tabs = [...document.querySelectorAll('[data-ndb-http-client-detail-tab]')];
                const labels = tabs.map((tab) => tab.querySelector('span'));
                const icons = tabs.map((tab) => tab.querySelector('[data-ndb-http-client-detail-tab-icon]'));

                return getComputedStyle(list).display === 'none'
                    && getComputedStyle(detail).display === 'flex'
                    && detail.getBoundingClientRect().top >= content.getBoundingClientRect().top
                    && detail.getBoundingClientRect().width >= workspace.getBoundingClientRect().width - 2
                    && actions.length === 2
                    && actions.every((action) => action.getBoundingClientRect().height >= 36)
                    && metadata.scrollWidth <= metadata.clientWidth + 1
                    && back.getClientRects().length > 0
                    && back.textContent.trim() === 'Requests'
                    && tabs.length === 4
                    && icons.every((icon) => icon && icon.getClientRects().length > 0)
                    && labels.every((label) => getComputedStyle(label).display === 'none')
                    && tabs.map((tab) => tab.getAttribute('aria-label')).join('|') === 'Overview|Request|Response|Stack'
                    && content.scrollWidth <= content.clientWidth + 1;
            })()
            JS)
        ->click('[data-ndb-http-client-detail-tab="response"]')
        ->assertVisible('[data-ndb-http-client-detail-panel="response"]')
        ->click('[data-ndb-http-client-detail-back]')
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-http-client-workspace]');
                const [list, detail] = workspace.children;
                const selected = document.querySelector('[data-ndb-http-client-item="5"]');

                return getComputedStyle(list).display === 'flex'
                    && getComputedStyle(detail).display === 'none'
                    && selected.getAttribute('aria-pressed') === 'true';
            })()
            JS)
        ->assertNoJavaScriptErrors();
});
