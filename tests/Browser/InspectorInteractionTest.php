<?php

it('keeps favoriting active and repeatable after Livewire navigation', function () {
    $page = visit('/profiled')
        ->click('[data-testid="host-navigation"]')
        ->waitForText('Second request')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="models"]');

    assertDebugSectionSelected($page, 'models');

    $favorite = '[data-ndb-toggle-favorite="models"]';
    $row = '[data-ndb-section="models"]';

    $page
        ->assertCount($row, 1)
        ->assertAttribute($favorite, 'aria-pressed', 'false')
        ->click($favorite)
        ->assertAttribute($favorite, 'aria-pressed', 'true')
        ->assertAttribute($row, 'data-ndb-favorite', 'true');

    assertDebugSectionSelected($page, 'models');

    $page
        ->click($favorite)
        ->assertAttribute($favorite, 'aria-pressed', 'false')
        ->assertAttribute($row, 'data-ndb-favorite', 'false');

    assertDebugSectionSelected($page, 'models');

    $page
        ->click($favorite)
        ->assertAttribute($favorite, 'aria-pressed', 'true')
        ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]')
        ->click('[data-testid="host-navigation"]')
        ->waitForText('First request')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->assertAttribute($favorite, 'aria-pressed', 'true')
        ->assertNoJavaScriptErrors();
});

it('reorders favorites with the keyboard and drag and drop', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    foreach (['request', 'overview', 'queries'] as $section) {
        $page->click("[data-ndb-toggle-favorite=\"{$section}\"]");
    }

    assertFavoriteOrder($page, 'request,overview,queries');

    $page
        ->assertScript('Array.from(document.querySelectorAll("[data-ndb-favorites-heading]")).filter((heading) => heading.offsetParent !== null).length', 1)
        ->assertScript('Array.from(document.querySelectorAll("[data-ndb-sections-heading]")).filter((heading) => heading.offsetParent !== null).length', 1)
        ->assertScript(<<<'JS'
            (() => {
                const heading = document.querySelector('[data-ndb-favorites-heading]');
                const firstFavorite = document.querySelector('[data-ndb-section][data-ndb-favorite="true"]');

                return (heading.compareDocumentPosition(firstFavorite) & Node.DOCUMENT_POSITION_FOLLOWING) !== 0;
            })()
            JS);

    $page->keys('[data-ndb-select-section="overview"]', 'Shift+ArrowUp');
    assertFavoriteOrder($page, 'overview,request,queries');

    $page->drag('[data-ndb-section="queries"]', '[data-ndb-section="overview"]');
    assertFavoriteOrder($page, 'queries,overview,request');

    $page
        ->refresh()
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    assertFavoriteOrder($page, 'queries,overview,request');

    $page->assertNoJavaScriptErrors();
});

it('shows the favorite source and insertion point while dragging', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    foreach (['request', 'overview', 'queries'] as $section) {
        $page->click("[data-ndb-toggle-favorite=\"{$section}\"]");
    }

    $page
        ->assertAttribute('[data-ndb-toggle-favorite="request"]', 'aria-pressed', 'true')
        ->assertAttribute('[data-ndb-toggle-favorite="overview"]', 'aria-pressed', 'true')
        ->assertAttribute('[data-ndb-toggle-favorite="queries"]', 'aria-pressed', 'true')
        ->assertScript(<<<'JS'
            (() => {
                const source = document.querySelector('[data-ndb-section="queries"]');
                const target = document.querySelector('[data-ndb-section="overview"]');
                const state = Alpine.$data(source);
                state.startFavoriteDrag('queries');
                Alpine.$data(target).hoverFavorite('overview');

                return state.favoriteDrag === 'queries' && state.favoriteDrop === 'overview';
            })()
            JS)
        ->assertAttribute('[data-ndb-section="queries"]', 'data-ndb-dragging', 'true')
        ->assertVisible('[data-ndb-favorite-drop-before="overview"]')
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.querySelector('[data-ndb-section="queries"]'));
                state.endFavoriteDrag();

                return state.favoriteDrag === null && state.favoriteDrop === null;
            })()
            JS)
        ->assertAttribute('[data-ndb-section="queries"]', 'data-ndb-dragging', 'false')
        ->assertAttribute('[data-ndb-favorite-drop-before="overview"]', 'hidden', '')
        ->assertNoJavaScriptErrors();
});

it('uses the command palette, theme preference, and escape layers', function () {
    $page = visit('/profiled')
        ->assertAttribute('#newdebugbar', 'data-theme', 'light')
        ->click('[data-ndb-toolbar="palette"]')
        ->assertVisible('[role="dialog"][aria-label="Command palette"]')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-palette-search]")')
        ->type('[data-ndb-palette-search]', 'pin to top')
        ->keys('[data-ndb-palette-search]', 'Enter')
        ->assertAttribute('[data-ndb-toolbar-shell]', 'data-placement', 'top')
        ->refresh()
        ->assertAttribute('[data-ndb-toolbar-shell]', 'data-placement', 'top')
        ->click('[data-ndb-toolbar="palette"]')
        ->type('[data-ndb-palette-search]', 'models')
        ->keys('[data-ndb-palette-search]', 'Enter');

    assertDebugSectionSelected($page, 'models');

    $page
        ->click('[data-ndb-inspector-action="palette"]')
        ->type('[data-ndb-palette-search]', 'dark theme')
        ->keys('[data-ndb-palette-search]', 'Enter')
        ->assertAttribute('#newdebugbar', 'data-theme', 'dark')
        ->refresh()
        ->assertAttribute('#newdebugbar', 'data-theme', 'dark')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->keys('[data-ndb-inspector-action="palette"]', 'Meta+Shift+P')
        ->assertVisible('[role="dialog"][aria-label="Command palette"]')
        ->keys('[data-ndb-palette-search]', 'Escape')
        ->assertScript('getComputedStyle(document.querySelector("[role=dialog][aria-label=\\"Command palette\\"]")).display === "none"')
        ->assertVisible('[role="dialog"][aria-label="Request inspector"]')
        ->keys('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]', 'Escape')
        ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]')
        ->assertNoJavaScriptErrors();
});

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
                const content = document.querySelector('#newdebugbar main');
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
                const content = document.querySelector('#newdebugbar main');
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
        ->assertAttribute('#newdebugbar', 'data-theme', $theme)
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

it('keeps the main interactions usable on a phone viewport', function () {
    $page = visit('/profiled')
        ->on()->iPhone14Pro()
        ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]')
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const box = toolbar.getBoundingClientRect();

                return Math.abs(box.width - (window.innerWidth - 24)) <= 1
                    && Math.abs(box.left - 12) <= 1
                    && Math.abs(window.innerWidth - box.right - 12) <= 1;
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const request = document.querySelector('[data-ndb-toolbar="request"]');
                const metrics = document.querySelector('[data-ndb-mobile-request-metrics="toolbar"]');
                const actions = document.querySelector('[data-ndb-mobile-toolbar-trigger="actions"]');
                const toolbarBox = toolbar.getBoundingClientRect();
                const requestBox = request.getBoundingClientRect();
                const metricsBox = metrics.getBoundingClientRect();
                const actionsBox = actions.getBoundingClientRect();
                const actionStyles = getComputedStyle(actions);
                const metricButtons = Array.from(metrics.querySelectorAll('[data-ndb-mobile-toolbar-metric]'));

                return requestBox.width <= 113
                    && requestBox.width < toolbarBox.width / 3
                    && metricsBox.width > 120
                    && metricButtons.length === 3
                    && metricButtons.every((button) => button.getBoundingClientRect().height >= 44)
                    && metrics.querySelectorAll('svg').length === 0
                    && metrics.querySelectorAll('[data-ndb-mobile-toolbar-summary]').length === 3
                    && metrics.textContent.includes('Queries')
                    && metrics.textContent.includes('Time')
                    && metrics.textContent.includes('Peak')
                    && metrics.textContent.includes('ms')
                    && actionsBox.width >= 44
                    && actionsBox.height >= 44
                    && actions.querySelectorAll('svg').length === 1
                    && Number.parseFloat(actionStyles.borderTopWidth) === 0
                    && actionStyles.boxShadow === 'none'
                    && actionStyles.backgroundColor === 'rgba(0, 0, 0, 0)'
                    && actionsBox.left >= metricsBox.right;
            })()
            JS)
        ->assertScript(<<<'JS'
            getComputedStyle(document.querySelector('[data-ndb-toolbar-facts]')).display === 'none'
                && getComputedStyle(document.querySelector('[data-ndb-toolbar-actions]')).display === 'none'
            JS)
        ->assertMissing('[data-ndb-toolbar-status-meaning]')
        ->assertScript("getComputedStyle(document.querySelector('[data-ndb-toolbar-response-size]')).display === 'none'")
        ->assertCount('[data-ndb-mobile-toolbar-metric-scope="toolbar"]', 3)
        ->click('[data-ndb-mobile-toolbar-trigger="actions"]')
        ->assertAttribute('[data-ndb-mobile-toolbar-trigger="actions"]', 'aria-expanded', 'true')
        ->assertVisible('[data-ndb-mobile-toolbar-menu="actions"]')
        ->assertScript(<<<'JS'
            (() => {
                const menu = document.querySelector('[data-ndb-mobile-toolbar-menu="actions"]');
                const items = Array.from(menu.querySelectorAll('[role="menuitem"]'));

                return menu.querySelector('h1, h2, h3, [role="heading"]') === null
                    && !menu.textContent.includes('Debug bar')
                    && items.length === 4
                    && menu.querySelector('[data-ndb-mobile-toolbar-action="placement"]') === null
                    && menu.querySelector('[data-ndb-mobile-toolbar-action="inspector"]').textContent.trim() === 'Open'
                    && items.every((item) => item.getBoundingClientRect().height >= 44)
                    && document.activeElement === items[0];
            })()
            JS)
        ->click('[data-ndb-mobile-toolbar-action="palette"]')
        ->waitForText('Go to Overview')
        ->assertVisible('[role="dialog"][aria-label="Command palette"]')
        ->keys('[data-ndb-palette-search]', 'Escape')
        ->assertScript('getComputedStyle(document.querySelector("[role=\"dialog\"][aria-label=\"Command palette\"]")).display === "none"')
        ->click('[data-ndb-mobile-toolbar-trigger="actions"]')
        ->click('[data-ndb-mobile-toolbar-action="inspector"]')
        ->assertVisible('[data-ndb-header-mobile-toolbar]')
        ->assertVisible('[data-ndb-mobile-request-metrics="header"]')
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[data-ndb-header-mobile-toolbar]');
                const metrics = document.querySelector('[data-ndb-mobile-request-metrics="header"]');
                const actions = document.querySelector('[data-ndb-header-mobile-trigger="actions"]');
                const actionStyles = getComputedStyle(actions);
                const metricButtons = Array.from(metrics.querySelectorAll('[data-ndb-mobile-toolbar-metric]'));

                return toolbar.scrollWidth <= toolbar.clientWidth + 1
                    && metrics.querySelector('svg') === null
                    && metrics.querySelectorAll('[data-ndb-mobile-toolbar-summary]').length === 3
                    && metricButtons.length === 3
                    && metricButtons.every((button) => button.getBoundingClientRect().height >= 44)
                    && actions.getBoundingClientRect().width >= 44
                    && actions.getBoundingClientRect().height >= 44
                    && actions.querySelectorAll('svg').length === 1
                    && Number.parseFloat(actionStyles.borderTopWidth) === 0
                    && actionStyles.boxShadow === 'none'
                    && actionStyles.backgroundColor === 'rgba(0, 0, 0, 0)'
                    && document.querySelector('[data-ndb-mobile-sections-toggle]').getClientRects().length === 0;
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const navigation = document.querySelector('#newdebugbar-section-navigation');
                const styles = getComputedStyle(navigation);
                const transitionProperties = styles.transitionProperty.split(',').map((property) => property.trim());
                const transitionDurations = styles.transitionDuration.split(',').map((duration) => duration.trim());
                const transitionDelays = styles.transitionDelay.split(',').map((delay) => delay.trim());
                const transformIndex = transitionProperties.indexOf('transform');
                const visibilityIndex = transitionProperties.indexOf('visibility');
                const transformDuration = Number.parseFloat(transitionDurations[transformIndex] ?? transitionDurations[0]);
                const visibilityDelay = Number.parseFloat(transitionDelays[visibilityIndex] ?? transitionDelays[0]);

                return styles.visibility === 'hidden'
                    && navigation.getBoundingClientRect().right <= 1
                    && transformIndex >= 0
                    && visibilityIndex >= 0
                    && transformDuration > 0
                    && visibilityDelay >= transformDuration;
            })()
            JS)
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->assertAttribute('[data-ndb-header-mobile-trigger="actions"]', 'aria-expanded', 'true')
        ->click('[data-ndb-header-mobile-action="sections"]')
        ->assertAttribute('[data-ndb-header-mobile-trigger="actions"]', 'aria-expanded', 'false')
        ->assertVisible('#newdebugbar-section-navigation')
        ->assertVisible('[data-ndb-mobile-sections-backdrop]')
        ->assertScript(<<<'JS'
            (() => {
                const navigation = document.querySelector('#newdebugbar-section-navigation');
                const box = navigation.getBoundingClientRect();

                return getComputedStyle(navigation).position === 'absolute'
                    && box.left >= 0
                    && box.right <= window.innerWidth
                    && box.width <= 281
                    && document.activeElement === navigation.querySelector('[data-ndb-select-section][aria-current="page"]');
            })()
            JS)
        ->keys('#newdebugbar-section-navigation [data-ndb-select-section][aria-current="page"]', 'Escape')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-header-mobile-trigger=\\"actions\\"]")')
        ->assertScript('getComputedStyle(document.querySelector("#newdebugbar-section-navigation")).visibility === "visible"')
        ->assertScript('getComputedStyle(document.querySelector("#newdebugbar-section-navigation")).visibility === "hidden"')
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="sections"]')
        ->click('[data-ndb-select-section="queries"]')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-section-heading]")')
        ->assertScript('getComputedStyle(document.querySelector("#newdebugbar-section-navigation")).visibility === "hidden"');

    assertDebugSectionSelected($page, 'queries');

    $page
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="sections"]')
        ->click('[data-ndb-toggle-favorite="queries"]')
        ->assertAttribute('[data-ndb-toggle-favorite="queries"]', 'aria-pressed', 'true')
        ->keys('[data-ndb-toggle-favorite="queries"]', 'Escape')
        ->assertVisible('[role="dialog"][aria-label="Request inspector"]')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-header-mobile-trigger=\\"actions\\"]")')
        ->assertScript('getComputedStyle(document.querySelector("#newdebugbar-section-navigation")).visibility === "hidden"')
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="sections"]')
        ->click('[data-ndb-mobile-sections-backdrop]')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-header-mobile-trigger=\\"actions\\"]")')
        ->assertScript('getComputedStyle(document.querySelector("#newdebugbar-section-navigation")).visibility === "hidden"')
        ->resize(1440, 900)
        ->assertScript(<<<'JS'
            (() => {
                const toggle = document.querySelector('[data-ndb-mobile-sections-toggle]');
                const navigation = document.querySelector('#newdebugbar-section-navigation');
                const mobileToolbar = document.querySelector('[data-ndb-header-mobile-toolbar]');
                const desktopToolbar = document.querySelector('[data-ndb-header-toolbar]');

                return getComputedStyle(toggle).display === 'none'
                    && getComputedStyle(mobileToolbar).display === 'none'
                    && getComputedStyle(desktopToolbar).display !== 'none'
                    && getComputedStyle(navigation).position === 'static'
                    && getComputedStyle(navigation).visibility === 'visible';
            })()
            JS)
        ->assertNoJavaScriptErrors();
});
