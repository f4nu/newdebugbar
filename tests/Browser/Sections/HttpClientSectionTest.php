<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('filters, sorts, selects, and explains outbound HTTP evidence', function () {
    visit('/profiled-http-client-rich')
        ->resize(1440, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="http_client"]')
        ->waitForText('7 requests')
        ->assertSee('7 requests')
        ->assertDontSee('HTTP client needs attention.')
        ->assertScript('document.querySelector("[data-ndb-http-client-attention]") === null')
        ->assertAttribute('[data-ndb-http-client-filter="all"]', 'aria-pressed', 'true')
        ->assertAttribute('[data-ndb-http-client-detail-tab="overview"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item]:not([hidden])").length', 7)
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-http-client-workspace]');
                const [list, detail] = workspace.children;
                const sectionNavigation = document.querySelector('#newdebugbar-section-navigation');
                const rows = [...document.querySelectorAll('[data-ndb-http-client-item]')];
                const methods = rows.map((row) => row.querySelector('[data-ndb-http-client-method]'));
                const hosts = rows.map((row) => row.querySelector('[data-ndb-http-client-host]'));
                const statuses = rows.map((row) => row.querySelector('[data-ndb-http-client-list-status]'));
                const durations = rows.map((row) => row.querySelector('[data-ndb-http-client-list-duration]'));
                const header = document.querySelector('[data-ndb-http-client-header]');
                const detailTabs = [...document.querySelectorAll('[data-ndb-http-client-detail-tab]')];
                const facts = document.querySelector('[data-ndb-http-client-facts]');
                const search = document.querySelector('[data-ndb-http-client-search]');
                const sort = document.querySelector('[data-ndb-http-client-sort]');
                const filters = document.querySelector('[data-ndb-filter-tabs][aria-label="Filter outbound HTTP requests"]');
                const filterButtons = [...filters.querySelectorAll('[data-ndb-filter-tab]')];
                const selectedFilter = filters.querySelector('[aria-pressed="true"]');
                const unselectedFilter = filters.querySelector('[aria-pressed="false"]');
                const urlAction = header.querySelector('[data-ndb-http-client-copy-url]');
                const urlIcon = urlAction.querySelector('svg');
                const headerMethod = header.querySelector('[data-ndb-http-client-detail-method]');
                const headerPath = header.querySelector('[data-ndb-http-client-detail-path]');
                const successStatus = document.querySelector('[data-ndb-http-client-item="2"] [data-ndb-http-client-list-status]');
                const failedStatus = document.querySelector('[data-ndb-http-client-item="6"] [data-ndb-http-client-list-status]');
                const slowDuration = document.querySelector('[data-ndb-http-client-item="1"] [data-ndb-http-client-list-duration] > span');

                const aligned = (elements, edge) => new Set(
                    elements.map((element) => Math.round(element.getBoundingClientRect()[edge])),
                ).size === 1;
                const centerY = (element) => {
                    const bounds = element.getBoundingClientRect();

                    return bounds.top + bounds.height / 2;
                };
                const verticallyAligned = (elements) => {
                    const centers = elements.map(centerY);

                    return Math.max(...centers) - Math.min(...centers) <= 1;
                };

                return getComputedStyle(workspace).display === 'grid'
                    && workspace.getBoundingClientRect().height > 500
                    && Math.abs(workspace.getBoundingClientRect().left - sectionNavigation.getBoundingClientRect().right) <= 1
                    && detail.getBoundingClientRect().width > list.getBoundingClientRect().width * 1.6
                    && document.querySelector('[data-ndb-http-client-item][aria-pressed="true"]').dataset.ndbHttpClientItem === '1'
                    && rows.every((row) => getComputedStyle(row).borderLeftWidth === '0px')
                    && methods.length === 7
                    && methods.every((method) => Math.round(method.getBoundingClientRect().width) === 48)
                    && aligned(hosts, 'left')
                    && aligned(statuses, 'left')
                    && aligned(durations, 'left')
                    && rows.every((row, index) => verticallyAligned([
                        methods[index],
                        hosts[index].parentElement,
                        statuses[index],
                        durations[index],
                    ]))
                    && Math.abs(search.getBoundingClientRect().top - sort.getBoundingClientRect().top) <= 1
                    && search.getBoundingClientRect().right < sort.getBoundingClientRect().left
                    && filters.getBoundingClientRect().top > search.getBoundingClientRect().bottom
                    && filters.dataset.ndbFilterTabsVariant === 'segmented'
                    && getComputedStyle(filters).display === 'grid'
                    && getComputedStyle(filters).backgroundColor !== 'rgba(0, 0, 0, 0)'
                    && parseFloat(getComputedStyle(filters).paddingLeft) > 0
                    && Math.max(...filterButtons.map((button) => button.getBoundingClientRect().width))
                        - Math.min(...filterButtons.map((button) => button.getBoundingClientRect().width)) <= 1
                    && filterButtons.every((button) => button.dataset.ndbFilterTabVariant === 'segmented')
                    && getComputedStyle(selectedFilter).backgroundColor !== getComputedStyle(unselectedFilter).backgroundColor
                    && getComputedStyle(selectedFilter).boxShadow !== 'none'
                    && hosts.every((host, index) => {
                        const gap = host.getBoundingClientRect().left - methods[index].getBoundingClientRect().right;

                        return gap >= 6 && gap <= 10;
                    })
                    && statuses.every((status) => getComputedStyle(status).backgroundColor === 'rgba(0, 0, 0, 0)')
                    && header.querySelector('[data-ndb-http-client-detail-status]') === null
                    && !header.textContent.includes('200 OK')
                    && !header.textContent.includes('ms')
                    && facts && !header.contains(facts)
                    && header.querySelectorAll('button').length === 1
                    && verticallyAligned([headerMethod, headerPath, urlAction])
                    && urlAction.textContent.trim() === 'Copy URL'
                    && urlIcon.querySelectorAll('path').length === 2
                    && urlIcon.querySelector('rect') === null
                    && document.querySelectorAll('[data-ndb-http-client-copy-curl]').length === 1
                    && detailTabs.map((tab) => tab.textContent.trim()).join('|') === 'Overview|Request|Response|Source'
                    && detailTabs.every((tab) => tab.matches('[data-ndb-filter-tab]'))
                    && detailTabs.every((tab) => tab.dataset.ndbFilterTabVariant === 'tabs')
                    && detailTabs.every((tab) => tab.querySelector('svg') === null)
                    && getComputedStyle(successStatus).color !== getComputedStyle(failedStatus).color
                    && getComputedStyle(slowDuration).color !== getComputedStyle(successStatus).color
                    && getComputedStyle(detail).overflowY === 'auto'
                    && detail.tabIndex === 0
                    && !document.querySelector('[data-ndb-http-client]').textContent.includes('•')
                    && rows.every((row) => ! /#\d+/.test(row.textContent));
            })()
            JS)
        ->keys('[data-ndb-http-client-filter="failed"]', 'Enter')
        ->assertAttribute('[data-ndb-http-client-filter="failed"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item]:not([hidden])").length', 4)
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-http-client-item=\\"1\\"]")).display === "none"')
        ->click('[data-ndb-http-client-filter="slow"]')
        ->assertAttribute('[data-ndb-http-client-filter="slow"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item]:not([hidden])").length', 1)
        ->assertAttribute('[data-ndb-http-client-item="1"]', 'aria-pressed', 'true')
        ->assertSee('Inspect the response size, endpoint work, timeout, and whether this call can leave the request path.')
        ->click('[data-ndb-http-client-filter="failed"]')
        ->click('[data-ndb-http-client-item="6"]')
        ->assertAttribute('[data-ndb-http-client-item="6"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelector("[data-ndb-http-client-detail-status]").textContent.trim() === "503 Service Unavailable"')
        ->assertSee('api.error.test returned HTTP 503 Service Unavailable.')
        ->assertSee('Confirm endpoint health, timeout, and retry behavior.')
        ->assertScript(<<<'JS'
            (() => {
                const state = document.getElementById('newdebugbar')._x_dataStack?.[0];

                window.newdebugbarExpectedClipboard = {
                    curl: state?.selectedHttpClientRequest?.curl,
                    url: state?.selectedHttpClientRequest?.url,
                    urlWidth: document.querySelector('[data-ndb-http-client-copy-url]').getBoundingClientRect().width,
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
        ->click('[data-ndb-http-client-copy-url]')
        ->wait(0.05)
        ->click('[data-ndb-http-client-detail-tab="request"]')
        ->assertVisible('[data-ndb-http-client-detail-panel="request"]')
        ->assertSee('Request body')
        ->click('[data-ndb-http-client-copy-curl]')
        ->wait(0.05)
        ->assertScript(<<<'JS'
            (() => {
                const [url, curl] = window.newdebugbarClipboardWrites;
                const currentUrlWidth = document.querySelector('[data-ndb-http-client-copy-url]').getBoundingClientRect().width;

                return url === window.newdebugbarExpectedClipboard.url
                    && curl === window.newdebugbarExpectedClipboard.curl
                    && Math.abs(currentUrlWidth - window.newdebugbarExpectedClipboard.urlWidth) <= 1
                    && curl.includes("--request 'DELETE'")
                    && curl.includes("'https://api.error.test/v1/stale-cache/very-long-resource-identifier'");
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
        ->click('[data-ndb-http-client-detail-tab="response"]')
        ->assertVisible('[data-ndb-http-client-detail-panel="response"]')
        ->assertSee('Response body')
        ->assertSee('Service unavailable.')
        ->click('[data-ndb-http-client-detail-tab="source"]')
        ->assertVisible('[data-ndb-http-client-detail-panel="source"]')
        ->assertSee('tests/Support/DefinesTestApplication.php')
        ->click('[data-ndb-http-client-filter="all"]')
        ->click('[data-ndb-http-client-item="3"]')
        ->assertSee('api.redirect.test returned HTTP 302 Found.')
        ->assertSee('If the redirect was unexpected, inspect the Location header and the client redirect settings.')
        ->click('[data-ndb-http-client-detail-tab="response"]')
        ->assertSee('Redirect to')
        ->assertSee('https://api.redirect.test/v2/current')
        ->click('[data-ndb-http-client-item="4"]')
        ->assertSee('Inspect the response body and compare the submitted payload with the remote validation rules.')
        ->assertDontSee('Check DNS, network access, the endpoint, and timeout settings.')
        ->click('[data-ndb-http-client-item="7"]')
        ->assertScript('document.querySelector("[data-ndb-http-client-detail-status]").textContent.trim() === "Connection failed"')
        ->assertSee('Check DNS, network access, the endpoint, and timeout settings.')
        ->click('[data-ndb-http-client-detail-tab="response"]')
        ->assertSee('No HTTP response was received.')
        ->click('[data-ndb-http-client-filter="all"]')
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
        ->assertSee('No follow-up is needed.')
        ->type('[data-ndb-http-client-search]', ' no request matches')
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item]:not([hidden])").length', 0)
        ->assertSee('No outbound HTTP requests match these controls.')
        ->assertNoJavaScriptErrors();
});

it('keeps HTTP request details readable on mobile in dark mode', function () {
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
        ->waitForText('7 requests')
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
                    && rows.every((row) => row.scrollWidth <= row.clientWidth + 1)
                    && hosts.every((host, index) => {
                        const gap = host.getBoundingClientRect().left - methods[index].getBoundingClientRect().right;

                        return gap >= 6 && gap <= 10;
                    });
            })()
            JS)
        ->click('[data-ndb-http-client-item="7"]')
        ->assertVisible('[data-ndb-http-client-detail]')
        ->assertScript(<<<'JS'
            (() => {
                const detail = document.querySelector('[data-ndb-http-client-detail]');
                const content = document.querySelector('[data-ndb-inspector-content]');
                const workspace = document.querySelector('[data-ndb-http-client-workspace]');
                const [list] = workspace.children;
                const back = document.querySelector('[data-ndb-http-client-detail-back]');
                const tabs = [...document.querySelectorAll('[data-ndb-http-client-detail-tab]')];
                const urlAction = document.querySelector('[data-ndb-http-client-copy-url]');

                return getComputedStyle(list).display === 'none'
                    && getComputedStyle(detail).display === 'flex'
                    && detail.getBoundingClientRect().width >= workspace.getBoundingClientRect().width - 2
                    && detail.scrollWidth <= detail.clientWidth + 1
                    && content.scrollWidth <= content.clientWidth + 1
                    && back.getClientRects().length > 0
                    && back.textContent.trim() === 'Requests'
                    && tabs.length === 4
                    && tabs.map((tab) => tab.textContent.trim()).join('|') === 'Overview|Request|Response|Source'
                    && tabs.every((tab) => tab.getClientRects().length > 0)
                    && tabs.every((tab) => tab.querySelector('svg') === null)
                    && urlAction.getBoundingClientRect().height >= 36;
            })()
            JS)
        ->click('[data-ndb-http-client-detail-tab="response"]')
        ->assertVisible('[data-ndb-http-client-detail-panel="response"]')
        ->assertSee('No HTTP response was received.')
        ->click('[data-ndb-http-client-detail-back]')
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-http-client-workspace]');
                const [list, detail] = workspace.children;
                const selected = document.querySelector('[data-ndb-http-client-item="7"]');

                return getComputedStyle(list).display === 'flex'
                    && getComputedStyle(detail).display === 'none'
                    && selected.getAttribute('aria-pressed') === 'true';
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('shows a useful empty HTTP client state', function () {
    $page = visit('/profiled-http-client-empty')->resize(1280, 720);

    $page->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::selectSectionViaPalette($page, 'http_client');
    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-http-client-empty]');

    $page
        ->assertVisible('[data-ndb-http-client-empty]')
        ->assertSee("Requests made through Laravel's HTTP client will appear here with their response, timing, and source.")
        ->assertScript('document.querySelector("[data-ndb-http-client-workspace]") === null')
        ->assertNoJavaScriptErrors();
});
