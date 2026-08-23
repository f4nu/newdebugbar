<?php

it('highlights repeated SQL and switches query evidence tabs', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-toolbar="queries"]')
        ->waitForText('Repeated pattern')
        ->assertSee('Find repeated work, slow SQL, and the application code that triggered it.')
        ->assertScript('document.querySelectorAll("#newdebugbar code[data-ndb-language=sql][data-highlighted]").length > 0')
        ->assertAttribute('[data-ndb-query-group-execution][open]', 'open', '')
        ->assertScript(<<<'JS'
            (() => {
                const timing = document.querySelector('[data-ndb-query-group-execution][open] [data-ndb-query-timing]');
                const duration = timing?.querySelector('[data-ndb-query-duration]');
                const percent = timing?.querySelector('[data-ndb-query-percent]');

                if (! timing || ! duration || ! percent) return false;

                const timingStyle = getComputedStyle(timing);
                const durationRect = duration.getBoundingClientRect();
                const percentRect = percent.getBoundingClientRect();

                return timingStyle.flexDirection === 'column'
                    && timingStyle.alignItems === 'flex-end'
                    && timingStyle.textAlign === 'right'
                    && durationRect.bottom <= percentRect.top
                    && Math.abs(durationRect.right - percentRect.right) <= 1;
            })()
            JS)
        ->click('[data-ndb-query-group-execution][open] [data-ndb-query-tab="bindings"]')
        ->assertAttribute('[data-ndb-query-group-execution][open] [data-ndb-query-tab="bindings"]', 'aria-selected', 'true')
        ->assertScript(<<<'JS'
            (() => {
                const tablist = document.querySelector('[data-ndb-query-group-execution][open] [data-ndb-query-tabs]');
                const active = tablist?.querySelector('[role="tab"][aria-selected="true"]');
                const inactive = tablist?.querySelector('[role="tab"][aria-selected="false"]');

                if (! active || ! inactive) return false;

                const activeStyle = getComputedStyle(active);
                const inactiveStyle = getComputedStyle(inactive);

                return activeStyle.backgroundColor !== inactiveStyle.backgroundColor
                    && activeStyle.color !== inactiveStyle.color
                    && Number.parseFloat(activeStyle.minHeight) >= 32;
            })()
            JS)
        ->keys('[data-ndb-query-group-execution][open] [data-ndb-query-tab="bindings"]', 'ArrowRight')
        ->assertAttribute('[data-ndb-query-group-execution][open] [data-ndb-query-tab="stack"]', 'aria-selected', 'true')
        ->keys('[data-ndb-query-group-execution][open] [data-ndb-query-tab="stack"]', 'ArrowLeft')
        ->assertAttribute('[data-ndb-query-group-execution][open] [data-ndb-query-tab="bindings"]', 'aria-selected', 'true')
        ->click('[data-ndb-query-group-execution][open] [data-ndb-query-actions] > summary')
        ->assertVisible('[data-ndb-query-group-execution][open] [data-ndb-query-actions] button:first-of-type')
        ->assertScript(<<<'JS'
            (() => {
                const actions = document.querySelector('[data-ndb-query-group-execution][open] [data-ndb-query-actions]');
                const trigger = actions?.querySelector(':scope > summary');
                const popover = actions?.querySelector('[data-ndb-query-actions-popover]');
                const surface = popover?.querySelector('[data-ndb-popover-surface]');
                const arrow = popover?.querySelector('[data-ndb-popover-arrow]');
                const firstAction = popover?.querySelector('button');
                if (! trigger || ! popover || ! surface || ! arrow || ! firstAction) return false;

                const surfaceStyle = getComputedStyle(surface);
                const triggerRect = trigger.getBoundingClientRect();
                const arrowRect = arrow.getBoundingClientRect();

                return popover.getAttribute('role') === 'menu'
                    && firstAction.getAttribute('role') === 'menuitem'
                    && Number.parseFloat(getComputedStyle(firstAction).minHeight) >= 44
                    && Number.parseFloat(getComputedStyle(firstAction).fontSize) >= 14
                    && Number.parseFloat(surfaceStyle.borderRadius) === 16
                    && surfaceStyle.borderStyle === 'solid'
                    && surfaceStyle.boxShadow !== 'none'
                    && surfaceStyle.backdropFilter !== 'none'
                    && Math.abs(
                        (triggerRect.left + triggerRect.right) / 2
                        - (arrowRect.left + arrowRect.right) / 2
                    ) <= 4;
            })()
            JS)
        ->keys('[data-ndb-query-group-execution][open] [data-ndb-query-actions] > summary', 'Escape')
        ->assertScript('document.querySelector("[data-ndb-query-group-execution][open] [data-ndb-query-actions]").open === false')
        ->assertNoJavaScriptErrors();
});

it('shows an explained query in place without losing the open query or scroll position', function () {
    $query = '[data-ndb-query-group-execution][open]';
    $actions = $query.' [data-ndb-query-actions]';

    visit('/profiled')
        ->resize(1100, 620)
        ->click('[data-ndb-toolbar="queries"]')
        ->waitForText('Repeated pattern')
        ->assertPresent($query.' [data-ndb-query-explain-loading]')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-query-explain-loading]")).display === "none"')
        ->click($actions.' > summary')
        ->assertVisible($actions.' [data-ndb-query-explain-action]')
        ->assertScript(<<<'JS'
            (() => {
                const content = document.querySelector('[data-ndb-inspector-content]');
                const query = document.querySelector('[data-ndb-query-group-execution][open]');

                content.scrollTop = Math.min(120, content.scrollHeight - content.clientHeight);
                query.__newDebugBarExplainMarker = 'preserved';

                return content.scrollTop > 0;
            })()
            JS)
        ->click($actions.' [data-ndb-query-explain-action]')
        ->waitForText('EXPLAIN QUERY PLAN')
        ->assertVisible($query.' [data-ndb-query-explain-result]')
        ->assertAttribute($query, 'open', '')
        ->assertVisible('[data-ndb-section-panel="queries"]')
        ->assertScript('document.querySelector("[data-ndb-section-heading]").textContent.trim() === "Queries"')
        ->assertScript('document.querySelector("[data-ndb-query-group-execution][open]").__newDebugBarExplainMarker === "preserved"')
        ->assertScript(<<<'JS'
            (() => {
                const content = document.querySelector('[data-ndb-inspector-content]');
                const query = document.querySelector('[data-ndb-query-group-execution][open]');

                return Math.abs(content.scrollTop - Alpine.$data(query).queryExplainScrollTop) <= 1;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('keeps repeated SQL on one shared syntax-highlighted surface in :dataset mode', function (string $theme) {
    $preferences = json_encode([
        'theme' => $theme,
        'favorites' => [],
    ], JSON_THROW_ON_ERROR);

    visit('/profiled-rich')
        ->assertScript(<<<JS
            (() => {
                localStorage.setItem('newdebugbar.preferences.v1', '{$preferences}');

                return true;
            })()
            JS)
        ->refresh()
        ->assertAttribute('#newdebugbar', 'data-ndb-theme', $theme)
        ->click('[data-ndb-toolbar="queries"]')
        ->assertScript(<<<'JS'
            (() => {
                const sharedSql = document.querySelectorAll(
                    '[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-pattern] code[data-ndb-language="sql"][data-highlighted]',
                );
                const duplicateItems = document.querySelectorAll('[data-ndb-query-item]:not([hidden])');

                return sharedSql.length === 1 && duplicateItems.length === 0;
            })()
            JS)
        ->assertNoJavaScriptErrors();
})->with(['light', 'dark']);

it('filters searches sorts and shows repeated query evidence without another disclosure', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-toolbar="queries"]')
        ->waitForText('Needs attention')
        ->assertMissing('[data-ndb-findings]')
        ->assertMissing('[data-ndb-query-summary-value]')
        ->assertVisible('[data-ndb-query-total-time]')
        ->assertScript(<<<'JS'
            (() => {
                const time = document.querySelector('[data-ndb-query-total-time]');
                const count = document.querySelector('[data-ndb-query-result-label]');

                return time.parentElement === count.parentElement
                    && /^\d+(?:\.\d+)? ms query time$/.test(time.textContent.trim());
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const buttons = Array.from(document.querySelectorAll('[data-ndb-query-filter]'));

                return buttons.filter((button) => button.getAttribute('aria-pressed') === 'true').length === 1
                    && buttons.every((button) => {
                        const style = getComputedStyle(button);

                        return parseFloat(style.borderBottomLeftRadius) > 0
                            && style.borderTopColor === style.borderBottomColor;
                    });
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const items = Array.from(document.querySelectorAll('[data-ndb-query-item]'));
                const groups = Array.from(document.querySelectorAll('[data-ndb-query-group]'));
                const expected = {
                    all: items.length,
                    attention: groups.reduce((count, group) => count + Number(group.dataset.resultCount), 0)
                        + items.filter((item) => item.dataset.repeated !== 'true' && item.dataset.slow === 'true').length,
                    read: items.filter((item) => item.dataset.type === 'read').length,
                    write: items.filter((item) => item.dataset.type === 'write').length,
                };

                return Object.entries(expected).every(([filter, count]) =>
                    Number(document.querySelector(`[data-ndb-query-filter-count="${filter}"]`).textContent.trim()) === count
                );
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const tabs = document.querySelector('[aria-label="Filter queries"]').getBoundingClientRect();
                const count = document.querySelector('[data-ndb-query-result-count]').getBoundingClientRect();
                const search = document.querySelector('[data-ndb-query-search]').getBoundingClientRect();

                return tabs.bottom <= count.top
                    && tabs.bottom <= search.top
                    && count.right < search.left;
            })()
            JS)
        ->assertScript('document.querySelectorAll("[data-ndb-query-item]:not([hidden])").length', 0)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden])").length', 1)
        ->assertScript('document.querySelector("[data-ndb-query-result-label]").textContent.replace(/\\s+/g, " ").trim() === "3 results"')
        ->click('[data-ndb-query-filter="attention"]')
        ->assertAttribute('[data-ndb-query-filter="attention"]', 'aria-pressed', 'true')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-query-total-time]")).display === "none"')
        ->assertScript('document.querySelectorAll("[data-ndb-query-item]:not([hidden])").length', 0)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden])").length', 1)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-pattern] code[data-ndb-language=sql]").length', 1)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-executions] > details").length', 3)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-executions] > details[open]").length', 1)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden]) [data-ndb-query-connection]").length', 3)
        ->assertScript(<<<'JS'
            document.querySelector('[data-ndb-query-group]:not([hidden])').getBoundingClientRect().top
                >= document.querySelector('[data-ndb-section-heading]').parentElement.getBoundingClientRect().bottom - 1
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-executions] > details'))
                .every((article) => article.querySelector(':scope > pre code[data-ndb-language="sql"]') === null)
            JS)
        ->assertSee('Likely N+1 pattern')
        ->click('[data-ndb-query-filter="read"]')
        ->assertScript('document.querySelectorAll("[data-ndb-query-filter][aria-pressed=true]").length', 1)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden])").length', 1)
        ->select('[data-ndb-query-sort]', 'duration')
        ->assertValue('[data-ndb-query-sort]', 'duration')
        ->assertScript(<<<'JS'
            (() => {
                const durations = Array.from(document.querySelectorAll('[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-execution]'))
                    .map((query) => Number(query.dataset.duration));

                return durations.every((duration, index) => index === 0 || durations[index - 1] >= duration);
            })()
            JS)
        ->type('[data-ndb-query-search]', 'no query can match this')
        ->assertScript('document.querySelectorAll("[data-ndb-query-item]:not([hidden])").length', 0)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden])").length', 0)
        ->assertSee('No queries match these filters.')
        ->assertNoJavaScriptErrors();
});
