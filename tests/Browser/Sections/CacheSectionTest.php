<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('filters sorts selects and inspects rich cache diagnostics', function () {
    $preferences = json_encode(['theme' => 'light', 'favorites' => []], JSON_THROW_ON_ERROR);
    $page = visit('/profiled-cache-rich')->resize(1440, 900);

    $page
        ->assertScript(<<<JS
            (() => {
                localStorage.setItem('newdebugbar.preferences.v1', '{$preferences}');

                return true;
            })()
            JS)
        ->refresh()
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="cache"]');

    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-cache-workspace]');

    $page
        ->assertAttribute('#newdebugbar', 'data-ndb-theme', 'light')
        ->assertSee('Cache needs attention')
        ->assertSee('17')
        ->assertSee('40.0%')
        ->assertValue('[data-ndb-cache-filter]', 'all')
        ->assertAttribute('[data-ndb-cache-detail-tab="overview"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelector("[data-ndb-cache-detail-panel=overview] dd pre").textContent.trim()', 'stale option')
        ->assertScript('document.querySelectorAll("[data-ndb-cache-item]:not([hidden])").length', 17)
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-cache-workspace]');
                const [list, detail] = workspace.children;
                const content = document.querySelector('[data-ndb-inspector-content]');
                const rows = [...document.querySelectorAll('[data-ndb-cache-item]')];
                const selected = document.querySelector('[data-ndb-cache-item][aria-pressed="true"]');
                const detailTabs = [...document.querySelectorAll('[data-ndb-cache-detail-tab]')];
                const header = document.querySelector('[data-ndb-cache-header]');
                const metadata = document.querySelector('[data-ndb-cache-metadata]');
                const metadataFacts = [...metadata.querySelectorAll(':scope > div')].filter(
                    (fact) => fact.getClientRects().length > 0,
                );
                const metadataLabels = metadataFacts.map((fact) => fact.querySelector('dt').textContent.trim());
                const metadataValues = metadataFacts.map((fact) => fact.querySelector('dd').textContent.trim());
                const sourceLink = metadata.querySelector('button');
                const keys = rows.map((row) => row.querySelector('[data-ndb-cache-key]'));
                const results = rows.map((row) => row.querySelector('[data-ndb-cache-result]'));
                const durations = rows.map((row) => row.querySelector('[data-ndb-cache-list-duration]'));
                const stores = rows.map((row) => row.children[3].textContent.trim());
                const executionIds = rows.map((row) => row.children[0].lastElementChild.textContent.trim());
                const keyOffsets = rows.map((row) => row.querySelector('[data-ndb-cache-key]').getBoundingClientRect().left);
                const rightTrackWidths = rows.map((row) => Number.parseFloat(getComputedStyle(row).gridTemplateColumns.split(' ').at(-1)));
                const resultRightEdges = results.map((result) => Math.round(result.getBoundingClientRect().right));
                const durationRightEdges = durations.map((duration) => Math.round(duration.getBoundingClientRect().right));

                return getComputedStyle(workspace).display === 'grid'
                    && workspace.getBoundingClientRect().height > 320
                    && workspace.getBoundingClientRect().bottom <= content.getBoundingClientRect().bottom + 1
                    && Math.abs(list.getBoundingClientRect().right - detail.getBoundingClientRect().left) <= 1
                    && detail.getBoundingClientRect().width > list.getBoundingClientRect().width * 1.6
                    && selected.dataset.ndbCacheItem === '1'
                    && detailTabs.length === 3
                    && detailTabs.every((tab) => tab.matches('[data-ndb-filter-tab]'))
                    && header.children.length === 2
                    && header.children[0].matches('[data-ndb-cache-detail-operation]')
                    && header.children[1].matches('[data-ndb-cache-detail-key]')
                    && header.querySelector('button, dl, [data-ndb-inspector-detail-header-primary]') === null
                    && document.querySelector('[data-ndb-cache-copy-key]') === null
                    && document.querySelector('[data-ndb-cache-copy-raw]') === null
                    && getComputedStyle(metadata).display === 'grid'
                    && metadataLabels.join('|') === 'Result|Runtime|Store|Driver|Source'
                    && metadataValues[0] === 'Stored'
                    && metadataValues[1].endsWith(' ms')
                    && metadataValues[2] === 'array'
                    && metadataValues[3] === 'array'
                    && sourceLink?.textContent.trim().includes('.php:')
                    && executionIds.every((id, index) => id === `#${rows[index].dataset.ndbCacheExecution}`)
                    && new Set(keyOffsets.map(Math.round)).size === 1
                    && keys.every((key) => key.clientWidth <= key.parentElement.getBoundingClientRect().width)
                    && keys.every((key) => getComputedStyle(key).textOverflow === 'ellipsis')
                    && results.every((result) => getComputedStyle(result).backgroundColor === 'rgba(0, 0, 0, 0)')
                    && results.every((result) => getComputedStyle(result).textAlign === 'right')
                    && new Set(rightTrackWidths.map(Math.round)).size === 1
                    && rightTrackWidths.every((width) => width >= 72)
                    && new Set(resultRightEdges).size === 1
                    && new Set(durationRightEdges).size === 1
                    && resultRightEdges.every((right, index) => right === durationRightEdges[index])
                    && stores.every((store) => !store.endsWith(' store'))
                    && getComputedStyle(list.querySelector('[data-ndb-cache-list]')).overflowY === 'auto'
                    && getComputedStyle(detail).overflowY === 'auto'
                    && detail.tabIndex === 0
                    && content.scrollHeight <= content.clientHeight + 2
                    && workspace.scrollWidth <= workspace.clientWidth + 1
                    && !document.querySelector('[data-ndb-cache]').textContent.includes('•')
                    && !document.querySelector('[data-ndb-cache]').textContent.includes('·');
            })()
            JS)
        ->click('[data-ndb-cache-item="5"]')
        ->assertSee('2 operations for this key in this store:')
        ->assertScript(<<<'JS'
            [...document.querySelectorAll('[aria-label^="Open cache execution "]')]
                .map((link) => link.textContent.trim())
                .join('|')
            JS, '#5|#6')
        ->assertAttribute('[aria-label="Open cache execution 5"]', 'aria-current', 'true')
        ->click('[aria-label="Open cache execution 6"]')
        ->assertAttribute('[data-ndb-cache-item="6"]', 'aria-pressed', 'true')
        ->assertAttribute('[aria-label="Open cache execution 6"]', 'aria-current', 'true')
        ->assertSee('Hit')
        ->select('[data-ndb-cache-filter]', 'failed')
        ->assertValue('[data-ndb-cache-filter]', 'failed')
        ->assertScript('document.querySelectorAll("[data-ndb-cache-item]:not([hidden])").length', 3)
        ->keys('[data-ndb-cache-item="15"]', 'Enter')
        ->assertAttribute('[data-ndb-cache-item="15"]', 'aria-pressed', 'true')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-cache-item=\\"15\\"]")')
        ->assertSee('Failed')
        ->assertScript(
            'document.querySelector("[data-ndb-cache-item=\\"15\\"] [data-ndb-cache-list-duration]").textContent.trim()',
            '—',
        )
        ->assertScript(
            'document.querySelectorAll("[data-ndb-cache-metadata] dd")[1].textContent.trim()',
            '—',
        )
        ->assertScript('document.querySelector("[data-ndb-cache-detail-panel=overview] dd pre").textContent.trim()', 'not retained')
        ->assertSee('The app may be doing extra work')
        ->assertSee('Check the store connection')
        ->click('[data-ndb-cache-metadata] button')
        ->assertAttribute('[data-ndb-cache-detail-tab="source"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-cache-detail-panel="source"]')
        ->assertSee('tests/Support/DefinesTestApplication.php')
        ->click('[data-ndb-cache-detail-tab="raw"]')
        ->assertVisible('[data-ndb-cache-detail-panel="raw"]')
        ->assertSee('Captured collector fields only')
        ->assertSee('Values are bounded and sensitive fields are redacted')
        ->assertSee('not retained')
        ->assertScript(<<<'JS'
            (() => {
                const raw = document.querySelector('[data-ndb-cache-detail-panel="raw"] pre').textContent;

                return raw.includes('"operation": "write_failed"')
                    && raw.includes('"value": "not retained"');
            })()
            JS)
        ->select('[data-ndb-cache-filter]', 'all')
        ->select('[data-ndb-cache-sort]', 'duration')
        ->assertValue('[data-ndb-cache-sort]', 'duration')
        ->assertScript(<<<'JS'
            (() => {
                const visible = [...document.querySelectorAll('[data-ndb-cache-item]:not([hidden])')];
                const timed = visible.filter((item) => item.dataset.ndbCacheTimed === 'true');
                const untimed = visible.filter((item) => item.dataset.ndbCacheTimed === 'false');
                const durations = timed.map((item) => Number(item.dataset.ndbCacheDuration));

                return visible.indexOf(untimed[0]) >= timed.length
                    && durations.every((duration, index) => index === 0 || durations[index - 1] >= duration);
            })()
            JS)
        ->type('[data-ndb-cache-search]', 'missing-note')
        ->assertScript('document.querySelectorAll("[data-ndb-cache-item]:not([hidden])").length', 1)
        ->assertSee('trip:kyoto:missing-note')
        ->type('[data-ndb-cache-search]', ' no operation matches')
        ->assertScript('document.querySelectorAll("[data-ndb-cache-item]:not([hidden])").length', 0)
        ->assertSee('No cache operations match these controls.')
        ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-cache-workspace]');

    $page
        ->assertValue('[data-ndb-cache-search]', ' no operation matches')
        ->assertSee('No cache operations match these controls.')
        ->refresh()
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="cache"]');

    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-cache-workspace]');

    $page
        ->assertValue('[data-ndb-cache-filter]', 'all')
        ->assertValue('[data-ndb-cache-search]', '')
        ->assertValue('[data-ndb-cache-sort]', 'execution')
        ->assertScript('document.querySelectorAll("[data-ndb-cache-item]:not([hidden])").length', 17)
        ->assertNoJavaScriptErrors();
});

it('drills into cache detail on mobile in dark mode', function () {
    $preferences = json_encode(['theme' => 'dark', 'favorites' => []], JSON_THROW_ON_ERROR);
    $page = visit('/profiled-cache-rich')->resize(390, 844);

    $page
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
        ->click('[data-ndb-select-section="cache"]');

    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-cache-workspace]');

    $page
        ->assertAttribute('#newdebugbar', 'data-ndb-theme', 'dark')
        ->assertScript(<<<'JS'
            (() => {
                const dialog = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                const workspace = document.querySelector('[data-ndb-cache-workspace]');
                const [list, detail] = workspace.children;
                const rows = [...document.querySelectorAll('[data-ndb-cache-item]')];

                return dialog.scrollWidth <= dialog.clientWidth + 1
                    && workspace.scrollWidth <= workspace.clientWidth + 1
                    && getComputedStyle(workspace).display !== 'grid'
                    && getComputedStyle(list).display === 'flex'
                    && getComputedStyle(detail).display === 'none'
                    && rows.every((row) => row.scrollWidth <= row.clientWidth + 1)
                    && document.querySelector('[data-ndb-cache-summary]').getBoundingClientRect().width <= workspace.getBoundingClientRect().width + 1;
            })()
            JS)
        ->select('[data-ndb-cache-filter]', 'failed')
        ->click('[data-ndb-cache-item="15"]')
        ->assertAttribute('[data-ndb-cache-item="15"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-cache-detail]')
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-cache-workspace]');
                const [list, detail] = workspace.children;
                const content = document.querySelector('[data-ndb-inspector-content]');
                const back = document.querySelector('[data-ndb-cache-detail-back]');
                const tabs = [...document.querySelectorAll('[data-ndb-cache-detail-tab]')];
                const labels = tabs.map((tab) => tab.querySelector('span'));

                return getComputedStyle(list).display === 'none'
                    && getComputedStyle(detail).display === 'flex'
                    && detail.getBoundingClientRect().width >= workspace.getBoundingClientRect().width - 2
                    && detail.scrollWidth <= detail.clientWidth + 1
                    && detail.scrollTop === 0
                    && content.scrollTop === 0
                    && content.scrollWidth <= content.clientWidth + 1
                    && back.getClientRects().length > 0
                    && back.textContent.trim() === 'Operations'
                    && tabs.length === 3
                    && tabs.map((tab) => tab.getAttribute('aria-label')).join('|') === 'Overview|Source|Raw'
                    && labels.every((label) => getComputedStyle(label).display === 'none')
                    && document.querySelector('[data-ndb-cache-copy-key]') === null
                    && document.querySelector('[data-ndb-cache-copy-raw]') === null;
            })()
            JS)
        ->click('[data-ndb-cache-detail-tab="raw"]')
        ->assertVisible('[data-ndb-cache-detail-panel="raw"]')
        ->assertScript('document.querySelector("[data-ndb-cache-detail-panel=raw]").scrollWidth <= document.querySelector("[data-ndb-cache-detail]").clientWidth + 1')
        ->click('[data-ndb-cache-detail-back]')
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-cache-workspace]');
                const [list, detail] = workspace.children;
                const selected = document.querySelector('[data-ndb-cache-item="15"]');

                return getComputedStyle(list).display === 'flex'
                    && getComputedStyle(detail).display === 'none'
                    && selected.getAttribute('aria-pressed') === 'true';
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('shows a clear empty Cache state', function () {
    $page = visit('/profiled-cache-empty')->resize(1180, 720);

    $page
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::selectSectionViaPalette($page, 'cache');

    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-cache-empty]');

    $page
        ->assertSee('No cache operations were captured for this request.')
        ->assertSee('Reads, writes, deletes, and store flushes will appear here')
        ->assertScript('document.querySelector("[data-ndb-cache-workspace]") === null')
        ->assertScript('document.querySelector("[data-ndb-cache-empty]").scrollWidth <= document.querySelector("[data-ndb-inspector-content]").clientWidth + 1')
        ->assertNoJavaScriptErrors();
});
