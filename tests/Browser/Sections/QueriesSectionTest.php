<?php

it('presents repeated queries as one shared list detail record', function () {
    visit('/profiled')
        ->resize(1440, 900)
        ->click('[data-ndb-toolbar="queries"]')
        ->waitForText('Repeated query pattern')
        ->assertVisible('[data-ndb-query-workspace]')
        ->assertVisible('[data-ndb-query-list]')
        ->assertVisible('[data-ndb-query-detail]')
        ->assertSee('Likely N+1')
        ->assertScript(<<<'JS'
            (() => {
                const root = document.querySelector('[data-ndb-queries]');
                const rows = [...root.querySelectorAll('[data-ndb-query-item]')];
                const repeated = rows.filter((row) => row.dataset.ndbRepeated === 'true');
                const sql = root.querySelector('[data-ndb-query-sql][data-highlighted]');
                const list = root.querySelector('[data-ndb-query-list]');
                const detail = root.querySelector('[data-ndb-query-detail]');
                const detailHeader = root.querySelector('[data-ndb-query-detail-header]');
                const search = root.querySelector('[data-ndb-query-search]');
                const searchIcon = search?.parentElement.querySelector('svg');

                return rows.length === 1
                    && repeated.length === 1
                    && Number(repeated[0].dataset.ndbQueryExecutionCount) === 3
                    && repeated[0].getAttribute('aria-pressed') === 'true'
                    && sql?.querySelector('.hljs-keyword') !== null
                    && root.querySelectorAll('[data-ndb-query-detail-panel]').length === 1
                    && root.querySelector('[data-ndb-query-detail-panel="query"]') !== null
                    && root.querySelector('[data-ndb-query-list-source]') === null
                    && detailHeader?.querySelector('dl') === null
                    && detailHeader?.getBoundingClientRect().height <= 54
                    && root.querySelector('details') === null
                    && getComputedStyle(list).overflowY === 'auto'
                    && getComputedStyle(detail).overflowY === 'auto'
                    && searchIcon?.getBoundingClientRect().left < search.getBoundingClientRect().left + 32
                    && ! root.textContent.includes('•')
                    && ! root.textContent.includes('·');
            })()
            JS)
        ->assertScript('document.querySelector("[data-ndb-query-execution-select]").options.length', 3)
        ->select('[data-ndb-query-execution-select]', '2')
        ->assertValue('[data-ndb-query-execution-select]', '2')
        ->assertScript(<<<'JS'
            (() => {
                const root = document.querySelector('[data-ndb-queries]');
                const query = root.querySelector('[data-ndb-query-sql][data-highlighted]');

                return query?.textContent.trim() === 'select 2 as number'
                    && root.querySelector('[data-ndb-query-detail-tab="bindings"]') === null
                    && root.querySelector('[data-ndb-query-detail-panel="bindings"]') === null
                    && root.querySelectorAll('[data-ndb-query-detail-panel]').length === 1
                    && root.querySelector('[data-ndb-query-detail-panel="query"]') !== null
                    && root.querySelector('[data-ndb-query-detail-panel="source"]') === null;
            })()
            JS)
        ->click('[data-ndb-query-detail-tab="source"]')
        ->assertAttribute('[data-ndb-query-detail-tab="source"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-query-detail-panel="source"]')
        ->assertSee('DefinesTestApplication.php')
        ->assertScript('document.querySelectorAll("[data-ndb-query-detail-panel]").length', 1)
        ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]')
        ->click('[data-ndb-toolbar="queries"]')
        ->assertVisible('[data-ndb-query-detail-panel="source"]')
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.querySelector('[data-ndb-queries]'));

                return state.querySelectedExecution === 2
                    && state.queryDetailTab === 'source'
                    && state.queryRecords[0].executions.length === 3;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('filters searches and sorts a varied query profile', function () {
    visit('/profiled-queries-rich')
        ->resize(1440, 760)
        ->click('[data-ndb-toolbar="queries"]')
        ->waitForText('queries')
        ->assertScript(<<<'JS'
            (() => {
                const root = document.querySelector('[data-ndb-queries]');
                const state = Alpine.$data(root);
                const rows = [...root.querySelectorAll('[data-ndb-query-item]')];
                const retained = rows.reduce((count, row) => count + Number(row.dataset.ndbQueryExecutionCount), 0);
                const repeated = state.queryRecords.find((record) => record.repeated && record.count === 8);
                const connections = new Set(
                    state.queryRecords.flatMap((record) => record.executions.map((query) => query.connection)),
                );

                return retained >= 16
                    && state.visibleQueryCount === retained
                    && rows.length >= 8
                    && repeated?.executions.length === 8
                    && new Set(repeated.executions.map((query) => query.display_sql)).size === 8
                    && repeated.executions.every((query) => ! Object.hasOwn(query, 'bindings'))
                    && connections.has('testing')
                    && connections.has('query_replica')
                    && state.queryRecords.some((record) => record.sql.length > 150);
            })()
            JS)
        ->select('[data-ndb-query-filter]', 'attention')
        ->assertValue('[data-ndb-query-filter]', 'attention')
        ->assertScript(<<<'JS'
            [...document.querySelectorAll('[data-ndb-query-item]:not([hidden])')]
                .every((row) => row.dataset.ndbAttention === 'true')
            JS)
        ->select('[data-ndb-query-sort]', 'duration')
        ->assertValue('[data-ndb-query-sort]', 'duration')
        ->assertScript(<<<'JS'
            (() => {
                const visible = [...document.querySelectorAll('[data-ndb-query-item]:not([hidden])')];
                const durations = visible.map((row) => Number(row.dataset.ndbDuration));

                return durations.every((duration, index) => index === 0 || durations[index - 1] >= duration);
            })()
            JS)
        ->select('[data-ndb-query-filter]', 'write')
        ->assertScript(<<<'JS'
            (() => {
                const visible = [...document.querySelectorAll('[data-ndb-query-item]:not([hidden])')];

                return visible.length >= 4 && visible.every((row) => row.dataset.ndbQueryType === 'write');
            })()
            JS)
        ->select('[data-ndb-query-filter]', 'all')
        ->fill('[data-ndb-query-search]', 'connection_probe')
        ->assertScript(<<<'JS'
            [...document.querySelectorAll('[data-ndb-query-item]:not([hidden])')].length === 1
                && Alpine.$data(document.querySelector('[data-ndb-queries]')).visibleQueryCount === 1
            JS)
        ->assertSee('query_replica')
        ->fill('[data-ndb-query-search]', 'nothing can match this query')
        ->waitForText('No queries match these controls.')
        ->assertScript('document.querySelectorAll("[data-ndb-query-item]:not([hidden])").length', 0)
        ->assertMissing('[data-ndb-query-active-detail]')
        ->assertNoJavaScriptErrors();
});

it('runs EXPLAIN for the selected repeated execution', function () {
    visit('/profiled')
        ->resize(1100, 620)
        ->click('[data-ndb-toolbar="queries"]')
        ->waitForText('Repeated query pattern')
        ->select('[data-ndb-query-execution-select]', '2')
        ->click('[data-ndb-query-detail-tab="explain"]')
        ->waitForText('EXPLAIN QUERY PLAN')
        ->assertVisible('[data-ndb-query-explain-result]')
        ->assertVisible('[data-ndb-query-explain-action]')
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.querySelector('[data-ndb-queries]'));

                return state.querySelectedExecution === 2
                    && state.queryExplainExecution === 2
                    && state.queryExplainLoading === false
                    && Array.isArray(state.queryExplain?.rows)
                    && document.querySelector('[data-ndb-query-explain-plan][data-highlighted]') !== null;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('keeps an EXPLAIN failure visible with a recovery path', function () {
    visit('/profiled-queries-rich')
        ->resize(1200, 760)
        ->click('[data-ndb-toolbar="queries"]')
        ->fill('[data-ndb-query-search]', 'explain_failure_probe')
        ->waitForText('1 shown')
        ->click('[data-ndb-query-detail-tab="explain"]')
        ->waitForText('The database could not explain this query.')
        ->assertVisible('[data-ndb-query-explain-error]')
        ->assertSee('copy the full query from Query')
        ->assertSeeIn('[data-ndb-query-explain-action]', 'Run EXPLAIN again')
        ->assertNoJavaScriptErrors();
});

it('moves from the query list to one focused mobile detail', function () {
    visit('/profiled-queries-rich')
        ->resize(390, 844)
        ->click('[data-ndb-mobile-toolbar-metric-scope="toolbar"][data-ndb-mobile-toolbar-metric="queries"]')
        ->assertVisible('[data-ndb-query-search]')
        ->assertVisible('[data-ndb-query-list]')
        ->assertScript(<<<'JS'
            (() => {
                const root = document.getElementById('newdebugbar');
                const workspace = document.querySelector('[data-ndb-query-workspace]');
                const detail = document.querySelector('[data-ndb-query-detail]');

                return root.scrollWidth <= root.clientWidth + 1
                    && workspace.scrollWidth <= workspace.clientWidth + 1
                    && getComputedStyle(detail).display === 'none';
            })()
            JS)
        ->click('[data-ndb-query-item][data-ndb-repeated="true"]')
        ->assertVisible('[data-ndb-query-detail]')
        ->assertVisible('[data-ndb-query-detail-back]')
        ->assertSeeIn('[data-ndb-query-detail-tab="query"]', 'Query')
        ->assertMissing('[data-ndb-query-detail-tab="bindings"]')
        ->assertSeeIn('[data-ndb-query-detail-tab="source"]', 'Source')
        ->assertSeeIn('[data-ndb-query-detail-tab="explain"]', 'EXPLAIN')
        ->assertScript(<<<'JS'
            (() => {
                const detail = document.querySelector('[data-ndb-query-detail]');
                const list = document.querySelector('[data-ndb-query-list]');

                return detail.getBoundingClientRect().width <= 390
                    && getComputedStyle(list.parentElement).display === 'none'
                    && document.querySelectorAll('[data-ndb-query-detail-panel]').length === 1;
            })()
            JS)
        ->click('[data-ndb-query-detail-tab="source"]')
        ->assertVisible('[data-ndb-query-detail-panel="source"]')
        ->click('[data-ndb-query-detail-back]')
        ->assertVisible('[data-ndb-query-list]')
        ->wait(0.1)
        ->assertScript('document.activeElement.matches("[data-ndb-query-item][data-ndb-repeated=true]")')
        ->assertNoJavaScriptErrors();
});

it('renders highlighted query evidence in each product theme', function (string $theme) {
    $preferences = json_encode(['theme' => $theme, 'favorites' => []], JSON_THROW_ON_ERROR);

    visit('/profiled')
        ->assertScript(<<<JS
            (() => {
                localStorage.setItem('newdebugbar.preferences.v1', '{$preferences}');

                return true;
            })()
            JS)
        ->refresh()
        ->assertAttribute('#newdebugbar', 'data-ndb-theme', $theme)
        ->click('[data-ndb-toolbar="queries"]')
        ->waitForText('Repeated query pattern')
        ->assertScript(<<<'JS'
            (() => {
                const keyword = document.querySelector('[data-ndb-query-sql][data-highlighted] .hljs-keyword');
                const detail = document.querySelector('[data-ndb-query-detail]');

                return keyword !== null
                    && detail !== null
                    && getComputedStyle(keyword).color !== 'rgb(0, 0, 0)';
            })()
            JS)
        ->assertNoJavaScriptErrors();
})->with(['light', 'dark']);

it('shows a clear empty query state when no SQL was captured', function () {
    $preferences = json_encode(['theme' => 'light', 'favorites' => ['queries']], JSON_THROW_ON_ERROR);

    visit('/profiled-queries-empty')
        ->assertScript(<<<JS
            (() => {
                localStorage.setItem('newdebugbar.preferences.v1', '{$preferences}');

                return true;
            })()
            JS)
        ->refresh()
        ->click('[data-ndb-toolbar="request"]')
        ->click('[data-ndb-section="queries"]')
        ->waitForText('No database queries were captured for this request.')
        ->assertMissing('[data-ndb-query-workspace]')
        ->assertNoJavaScriptErrors();
});
