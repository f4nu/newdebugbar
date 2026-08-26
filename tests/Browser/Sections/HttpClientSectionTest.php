<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('filters, selects, and inspects outbound HTTP evidence', function () {
    visit('/profiled-http-client-rich')
        ->resize(1440, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="http_client"]')
        ->waitForText('7 requests')
        ->assertSee('7 requests')
        ->assertDontSee('HTTP client needs attention.')
        ->assertScript('document.querySelector("[data-ndb-http-client-attention]") === null')
        ->assertValue('[data-ndb-http-client-filter]', 'all')
        ->assertAttribute('[data-ndb-http-client-detail-tab="response"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item]:not([hidden])").length', 7)
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-http-client-workspace]');
                const [list, detail] = workspace.children;
                const sectionNavigation = document.querySelector('#newdebugbar-section-navigation');
                const inspectorContent = document.querySelector('[data-ndb-inspector-content]');
                const rows = [...document.querySelectorAll('[data-ndb-http-client-item]')];
                const methods = rows.map((row) => row.querySelector('[data-ndb-http-client-method]'));
                const hosts = rows.map((row) => row.querySelector('[data-ndb-http-client-host]'));
                const paths = hosts.map((host) => host.nextElementSibling);
                const statuses = rows.map((row) => row.querySelector('[data-ndb-http-client-list-status]'));
                const durations = rows.map((row) => row.querySelector('[data-ndb-http-client-list-duration]'));
                const header = document.querySelector('[data-ndb-http-client-header]');
                const detailTabs = [...document.querySelectorAll('[data-ndb-http-client-detail-tab]')];
                const detailTabGroup = detailTabs[0].closest('[data-ndb-filter-tabs]');
                const requestFacts = document.querySelector('[data-ndb-http-client-request-facts]');
                const responseFacts = document.querySelector('[data-ndb-http-client-response-facts]');
                const sourceFacts = document.querySelector('[data-ndb-http-client-source-facts]');
                const search = document.querySelector('[data-ndb-http-client-search]');
                const searchIcon = search.parentElement.querySelector('svg');
                const filter = document.querySelector('[data-ndb-http-client-filter]');
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
                    && Math.abs(workspace.getBoundingClientRect().right - inspectorContent.getBoundingClientRect().right) <= 1
                    && getComputedStyle(workspace).borderTopWidth === '1px'
                    && getComputedStyle(workspace).borderRightWidth === '0px'
                    && getComputedStyle(workspace).borderBottomWidth === '0px'
                    && getComputedStyle(workspace).borderLeftWidth === '0px'
                    && getComputedStyle(workspace).borderRadius === '0px'
                    && detail.getBoundingClientRect().width > list.getBoundingClientRect().width * 1.6
                    && document.querySelector('[data-ndb-http-client-item][aria-pressed="true"]').dataset.ndbHttpClientItem === '1'
                    && rows.every((row) => getComputedStyle(row).borderLeftWidth === '0px')
                    && methods.length === 7
                    && methods.every((method) => Math.round(method.getBoundingClientRect().width) === 48)
                    && aligned(hosts, 'left')
                    && paths.every((path) => !getComputedStyle(path).fontFamily.includes('JetBrains Mono'))
                    && aligned(statuses, 'left')
                    && aligned(durations, 'left')
                    && rows.every((row, index) => verticallyAligned([
                        methods[index],
                        hosts[index].parentElement,
                        statuses[index],
                        durations[index],
                    ]))
                    && Math.abs(search.getBoundingClientRect().top - filter.getBoundingClientRect().top) <= 1
                    && search.getBoundingClientRect().right < filter.getBoundingClientRect().left
                    && searchIcon.getBoundingClientRect().left - search.getBoundingClientRect().left >= 9
                    && searchIcon.getBoundingClientRect().left - search.getBoundingClientRect().left <= 11
                    && Math.round(searchIcon.getBoundingClientRect().width) === 16
                    && searchIcon.getBoundingClientRect().right
                        <= search.getBoundingClientRect().left + parseFloat(getComputedStyle(search).paddingLeft)
                    && filter.tagName === 'SELECT'
                    && filter.options[0].value === 'all'
                    && filter.options[0].textContent.trim().startsWith('All (')
                    && document.querySelector('[data-ndb-http-client-sort]') === null
                    && !document.querySelector('[data-ndb-http-client]').textContent.includes('Oldest')
                    && !document.querySelector('[data-ndb-http-client]').textContent.includes('Slowest')
                    && hosts.every((host, index) => {
                        const gap = host.getBoundingClientRect().left - methods[index].getBoundingClientRect().right;

                        return gap >= 6 && gap <= 10;
                    })
                    && statuses.every((status) => getComputedStyle(status).backgroundColor === 'rgba(0, 0, 0, 0)')
                    && header.querySelector('[data-ndb-http-client-detail-status]') === null
                    && !header.textContent.includes('200 OK')
                    && !header.textContent.includes('ms')
                    && responseFacts && !header.contains(responseFacts)
                    && responseFacts.textContent.includes('Status')
                    && responseFacts.textContent.includes('Runtime')
                    && responseFacts.textContent.includes('Response body')
                    && !responseFacts.textContent.includes('Host')
                    && !responseFacts.textContent.includes('Source')
                    && requestFacts.textContent.includes('Host')
                    && requestFacts.textContent.includes('Request body')
                    && !requestFacts.textContent.includes('Status')
                    && sourceFacts.textContent.includes('Request initiated at')
                    && header.querySelectorAll('button').length === 1
                    && !getComputedStyle(headerPath).fontFamily.includes('JetBrains Mono')
                    && verticallyAligned([headerMethod, headerPath, urlAction])
                    && urlAction.textContent.trim() === 'Copy URL'
                    && urlIcon.querySelectorAll('path').length === 2
                    && urlIcon.querySelector('rect') === null
                    && document.querySelectorAll('[data-ndb-http-client-copy-curl]').length === 1
                    && detailTabs.map((tab) => tab.textContent.trim()).join('|') === 'Response|Request|Source'
                    && detailTabs.every((tab) => tab.matches('[data-ndb-filter-tab]'))
                    && detailTabs.every((tab) => tab.dataset.ndbFilterTabVariant === 'segmented')
                    && detailTabGroup.dataset.ndbFilterTabsVariant === 'segmented'
                    && Math.abs(
                        detailTabGroup.getBoundingClientRect().left + detailTabGroup.getBoundingClientRect().width / 2
                        - detail.getBoundingClientRect().left - detail.getBoundingClientRect().width / 2
                    ) <= 1
                    && detailTabs.every((tab) => tab.querySelector('svg') === null)
                    && getComputedStyle(successStatus).color !== getComputedStyle(failedStatus).color
                    && getComputedStyle(slowDuration).color !== getComputedStyle(successStatus).color
                    && getComputedStyle(detail).overflowY === 'auto'
                    && detail.tabIndex === 0
                    && getComputedStyle(document.querySelector('[data-ndb-http-client-detail-panel="response"]')).display !== 'none'
                    && document.querySelector('[data-ndb-http-client-detail-panel="overview"]') === null
                    && document.querySelector('[data-ndb-http-client-guidance]') === null
                    && !document.querySelector('[data-ndb-http-client]').textContent.includes('•')
                    && rows.every((row) => ! /#\d+/.test(row.textContent));
            })()
            JS)
        ->select('[data-ndb-http-client-filter]', 'failed')
        ->assertValue('[data-ndb-http-client-filter]', 'failed')
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item]:not([hidden])").length', 4)
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-http-client-item=\\"1\\"]")).display === "none"')
        ->select('[data-ndb-http-client-filter]', 'slow')
        ->assertValue('[data-ndb-http-client-filter]', 'slow')
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item]:not([hidden])").length', 1)
        ->assertAttribute('[data-ndb-http-client-item="1"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-http-client-detail-panel="response"]')
        ->select('[data-ndb-http-client-filter]', 'failed')
        ->click('[data-ndb-http-client-item="6"]')
        ->assertAttribute('[data-ndb-http-client-item="6"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelector("[data-ndb-http-client-detail-status]").textContent.trim() === "503 Service Unavailable"')
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
        ->assertScript('document.querySelector("[data-ndb-http-client-detail-host]").textContent.trim() === "api.error.test"')
        ->assertScript('document.querySelector("[data-ndb-http-client-detail-panel=\"request\"] code[data-ndb-language=\"json\"][data-highlighted]") !== null')
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
        ->assertScript('document.querySelector("[data-ndb-http-client-detail-panel=\"response\"] code[data-ndb-language=\"json\"][data-highlighted]") !== null')
        ->click('[data-ndb-http-client-detail-tab="source"]')
        ->assertVisible('[data-ndb-http-client-detail-panel="source"]')
        ->assertScript('document.querySelector("[data-ndb-http-client-detail-source]").textContent.includes("tests/Support/DefinesTestApplication.php")')
        ->assertSee('tests/Support/DefinesTestApplication.php')
        ->assertScript(<<<'JS'
            (() => {
                const source = document.querySelector('[data-ndb-http-client-detail-source]');
                const fact = source.closest('[data-ndb-inspector-source-fact]');
                const stack = document.querySelector('[data-ndb-http-client-detail-panel="source"] [data-ndb-inspector-stack]');
                const action = document.querySelector('[data-ndb-http-client-copy-url]');
                const functionCall = stack.querySelector('li code');
                const stackPath = stack.querySelector('li > span:last-child > span');

                return fact !== null
                    && stack !== null
                    && fact.querySelector('svg') === null
                    && !getComputedStyle(source).fontFamily.includes('JetBrains Mono Variable')
                    && getComputedStyle(functionCall).fontFamily.includes('JetBrains Mono Variable')
                    && getComputedStyle(functionCall).fontFeatureSettings.includes('"calt"')
                    && !getComputedStyle(stackPath).fontFamily.includes('JetBrains Mono Variable')
                    && getComputedStyle(source).color !== getComputedStyle(action).color;
            })()
            JS)
        ->select('[data-ndb-http-client-filter]', 'all')
        ->click('[data-ndb-http-client-item="3"]')
        ->assertVisible('[data-ndb-http-client-detail-panel="response"]')
        ->assertSee('Redirect to')
        ->assertSee('https://api.redirect.test/v2/current')
        ->click('[data-ndb-http-client-item="4"]')
        ->assertVisible('[data-ndb-http-client-detail-panel="response"]')
        ->click('[data-ndb-http-client-item="7"]')
        ->assertScript('document.querySelector("[data-ndb-http-client-detail-status]").textContent.trim() === "Connection failed"')
        ->assertSee('No HTTP response was received.')
        ->select('[data-ndb-http-client-filter]', 'all')
        ->type('[data-ndb-http-client-search]', 'healthy.test')
        ->assertScript('document.querySelectorAll("[data-ndb-http-client-item]:not([hidden])").length', 1)
        ->assertAttribute('[data-ndb-http-client-item="2"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelector("[data-ndb-http-client-detail-status]").textContent.trim() === "204 No Content"')
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
                    && tabs.length === 3
                    && tabs.map((tab) => tab.textContent.trim()).join('|') === 'Response|Request|Source'
                    && tabs[0].getAttribute('aria-pressed') === 'true'
                    && tabs.every((tab) => tab.getClientRects().length > 0)
                    && tabs.every((tab) => tab.querySelector('svg') === null)
                    && urlAction.getBoundingClientRect().height >= 36;
            })()
            JS)
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
