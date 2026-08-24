<?php

it('filters, sorts, selects, and inspects outbound HTTP evidence', function () {
    visit('/profiled-http-client-rich')
        ->resize(1440, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="http_client"]')
        ->waitForText('6 requests')
        ->assertSee('6 requests')
        ->assertSee('cumulative')
        ->assertAttribute('[data-ndb-http-client-filter="all"]', 'aria-pressed', 'true')
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
                const tabGroups = [...document.querySelectorAll('[data-ndb-http-client] [data-ndb-filter-tabs]')];
                const detailTabs = [...document.querySelectorAll('[data-ndb-http-client-detail-tab]')];
                const summary = document.querySelector('[data-ndb-http-client-summary]');
                const summaryCount = document.querySelector('[data-ndb-http-client-summary-count]');
                const summaryRuntime = document.querySelector('[data-ndb-http-client-summary-runtime]');
                const filters = document.querySelector('[data-ndb-filter-tabs][aria-label="Filter outbound HTTP requests"]');
                const header = detail.querySelector('header');
                const primary = header.querySelector('[data-ndb-inspector-detail-header-primary]');
                const host = header.querySelector('[data-ndb-http-client-detail-host]');
                const status = header.querySelector('[data-ndb-http-client-detail-status]');
                const identity = header.querySelector('[data-ndb-http-client-identity]');
                const metadata = header.querySelector('[data-ndb-http-client-metadata]');
                const actions = header.querySelector('[data-ndb-http-client-actions]');
                const actionIcons = [...actions.querySelectorAll('svg')];
                const urlIcon = actions.querySelector('[data-ndb-http-client-copy-url] svg');
                const methods = [...document.querySelectorAll('[data-ndb-http-client-method]')];
                const hosts = [...document.querySelectorAll('[data-ndb-http-client-host]')];
                const outcomes = [...document.querySelectorAll('[data-ndb-http-client-list-outcome]')];
                const firstMethod = methods[0].getBoundingClientRect();
                const firstHost = document.querySelector('[data-ndb-http-client-item="1"] [data-ndb-http-client-host]').getBoundingClientRect();
                const firstOutcome = outcomes[0].getBoundingClientRect();

                return getComputedStyle(workspace).display === 'grid'
                    && workspaceBox.height > 500
                    && Math.abs(workspaceBox.bottom - expectedBottom) <= 1
                    && Math.abs(listBox.top - detailBox.top) <= 1
                    && Math.abs(listBox.right - detailBox.left) <= 1
                    && detailBox.width > listBox.width * 1.6
                    && selected.dataset.ndbHttpClientItem === '1'
                    && getComputedStyle(selected).borderLeftWidth === '0px'
                    && summary.parentElement.contains(filters)
                    && filters.getBoundingClientRect().top > summary.getBoundingClientRect().top
                    && summaryRuntime.getBoundingClientRect().top > summaryCount.getBoundingClientRect().top
                    && filters.firstElementChild.dataset.ndbHttpClientFilter === 'all'
                    && tabGroups.length === 2
                    && detailTabs.every((tab) => tab.matches('[data-ndb-filter-tab]'))
                    && host.closest('[data-ndb-inspector-detail-header-primary]') === primary
                    && status.closest('[data-ndb-inspector-detail-header-primary]') === primary
                    && identity.textContent.includes('Request')
                    && !identity.textContent.includes('Result')
                    && actions.querySelectorAll('button').length === 2
                    && actionIcons.every((icon) => icon.getBoundingClientRect().width === 14)
                    && urlIcon.querySelectorAll('path').length === 2
                    && urlIcon.querySelector('rect') === null
                    && detail.querySelector('footer') === null
                    && metadata.children.length === 3
                    && metadata.querySelectorAll('svg').length === 0
                    && metadata.scrollWidth <= metadata.clientWidth + 1
                    && methods.length === 6
                    && new Set(methods.map((method) => Math.round(method.getBoundingClientRect().width))).size === 1
                    && methods.every((method) => Math.round(method.getBoundingClientRect().width) === 48)
                    && methods.every((method) => getComputedStyle(method).backgroundColor !== 'rgba(0, 0, 0, 0)')
                    && hosts.every((host, index) => Math.abs(host.getBoundingClientRect().left - methods[index].getBoundingClientRect().right - 4) <= 1)
                    && new Set(hosts.map((host) => Math.round(host.getBoundingClientRect().left))).size === 1
                    && outcomes.every((outcome) => getComputedStyle(outcome).backgroundColor === 'rgba(0, 0, 0, 0)')
                    && outcomes.every((outcome) => getComputedStyle(outcome).borderWidth === '0px')
                    && [...document.querySelectorAll('[data-ndb-http-client-item]')]
                        .every((item) => ! /#\d{2}/.test(item.textContent))
                    && Math.abs(firstMethod.top - firstHost.top) <= 3
                    && Math.abs(firstMethod.top - firstOutcome.top) <= 3
                    && getComputedStyle(detail).overflowY === 'auto'
                    && detail.tabIndex === 0
                    && !document.querySelector('[data-ndb-http-client]').textContent.includes('•');
            })()
            JS)
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item][aria-pressed=true]").length', 1)
        ->click('[data-ndb-http-client-filter="failed"]')
        ->assertAttribute('[data-ndb-http-client-filter="failed"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item]:not([hidden])").length', 4)
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-http-client-item=\\"1\\"]")).display === "none"')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-http-client-item=\\"2\\"]")).display === "none"')
        ->click('[data-ndb-http-client-filter="slow"]')
        ->assertAttribute('[data-ndb-http-client-filter="slow"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item]:not([hidden])").length', 1)
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-http-client-item=\\"1\\"]")).display !== "none"')
        ->click('[data-ndb-http-client-filter="failed"]')
        ->click('[data-ndb-http-client-item="5"]')
        ->assertAttribute('[data-ndb-http-client-item="5"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelector("[data-ndb-http-client-detail-status-code]").textContent.trim() === "503"')
        ->assertScript('document.querySelector("[data-ndb-http-client-detail-status]").textContent.includes("Service Unavailable")')
        ->assertSee('Service unavailable.')
        ->assertSee('Confirm endpoint health, timeout, and retry behavior.')
        ->click('[data-ndb-http-client-detail-tab="request"]')
        ->assertAttribute('[data-ndb-http-client-detail-tab="request"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-http-client-detail-panel="request"]')
        ->assertSee('Headers')
        ->click('[data-ndb-http-client-detail-tab="response"]')
        ->assertVisible('[data-ndb-http-client-detail-panel="response"]')
        ->assertSee('Service unavailable.')
        ->click('[data-ndb-http-client-detail-tab="source"]')
        ->assertVisible('[data-ndb-http-client-detail-panel="source"]')
        ->assertSee('tests/Support/DefinesTestApplication.php')
        ->assertScript(<<<'JS'
            (() => {
                const state = document.getElementById('newdebugbar')._x_dataStack?.[0];

                window.newdebugbarExpectedClipboard = {
                    curl: state?.selectedHttpClientRequest?.curl,
                    url: state?.selectedHttpClientRequest?.url,
                    actionWidths: [...document.querySelectorAll('[data-ndb-http-client-actions] button')]
                        .map((button) => button.getBoundingClientRect().width),
                };
                window.newdebugbarClipboardWrites = [];
                Object.defineProperty(window.navigator, 'clipboard', {
                    configurable: true,
                    value: {
                        writeText: async (value) => window.newdebugbarClipboardWrites.push(value),
                    },
                });

                return window.newdebugbarExpectedClipboard.url === 'https://api.error.test/v1/stale-cache/very-long-resource-identifier';
            })()
            JS)
        ->click('[data-ndb-http-client-copy-curl]')
        ->wait(0.05)
        ->click('[data-ndb-http-client-copy-url]')
        ->wait(0.05)
        ->assertScript(<<<'JS'
            (() => {
                const [curl, url] = window.newdebugbarClipboardWrites;
                const actionWidths = [...document.querySelectorAll('[data-ndb-http-client-actions] button')]
                    .map((button) => button.getBoundingClientRect().width);

                return window.newdebugbarClipboardWrites.length === 2
                    && curl === window.newdebugbarExpectedClipboard.curl
                    && url === window.newdebugbarExpectedClipboard.url
                    && actionWidths.every((width, index) => Math.abs(width - window.newdebugbarExpectedClipboard.actionWidths[index]) <= 1)
                    && curl.includes("--request 'DELETE'")
                    && curl.includes("'https://api.error.test/v1/stale-cache/very-long-resource-identifier'")
                    && url === 'https://api.error.test/v1/stale-cache/very-long-resource-identifier';
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                window.navigator.clipboard.writeText = async () => {
                    throw new Error('Clipboard permission denied');
                };
                document.execCommand = (command) => {
                    window.newdebugbarFallbackClipboard = command === 'copy'
                        ? document.activeElement?.value
                        : null;

                    return command === 'copy';
                };

                return true;
            })()
            JS)
        ->click('[data-ndb-http-client-copy-curl]')
        ->wait(0.05)
        ->assertScript('window.newdebugbarFallbackClipboard === window.newdebugbarExpectedClipboard.curl')
        ->click('[data-ndb-http-client-filter="all"]')
        ->assertAttribute('[data-ndb-http-client-filter="all"]', 'aria-pressed', 'true')
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
                const methods = rows.map((row) => row.querySelector('[data-ndb-http-client-method]'));
                const hosts = rows.map((row) => row.querySelector('[data-ndb-http-client-host]'));

                return dialog.scrollWidth <= dialog.clientWidth + 1
                    && workspace.scrollWidth <= workspace.clientWidth + 1
                    && getComputedStyle(workspace).display !== 'grid'
                    && getComputedStyle(list).display === 'flex'
                    && getComputedStyle(detail).display === 'none'
                    && rows.every((row) => getComputedStyle(row).borderLeftWidth === '0px')
                    && hosts.every((host, index) => Math.abs(host.getBoundingClientRect().left - methods[index].getBoundingClientRect().right - 4) <= 1)
                    && new Set(hosts.map((host) => Math.round(host.getBoundingClientRect().left))).size === 1;
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
                const actions = [...detail.querySelectorAll('[data-ndb-http-client-actions] button')];
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
                    && tabs.every((tab) => tab.matches('[data-ndb-filter-tab]'))
                    && tabs.map((tab) => tab.getAttribute('aria-label')).join('|') === 'Overview|Request|Response|Source'
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
