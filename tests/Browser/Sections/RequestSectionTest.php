<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('uses one centered active-only Request workspace on tall and short desktop', function () {
    $preferences = json_encode(['theme' => 'dark', 'favorites' => []], JSON_THROW_ON_ERROR);
    $page = visit('/profiled-request/kyoto?month=november&filters%5Bweather%5D=clear')->resize(1440, 900);

    $page
        ->assertScript(<<<JS
            (() => {
                localStorage.setItem('newdebugbar.preferences.v1', '{$preferences}');

                return true;
            })()
            JS)
        ->refresh()
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="request"]');

    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-request-workspace]');

    $page
        ->assertAttribute('#newdebugbar', 'data-ndb-theme', 'dark')
        ->assertAttribute('[data-ndb-request-tab="route"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-request-panel="route"]')
        ->assertSee('profiled.request.show')
        ->assertSee('ProfiledRequestController@show')
        ->assertSee('kyoto')
        ->assertCount('[data-ndb-request-panel]', 1)
        ->assertMissing('[data-ndb-request-step]')
        ->assertMissing('[data-ndb-request-workspace] details')
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-request-workspace]');

                return getComputedStyle(workspace).display === 'flex'
                    && getComputedStyle(workspace).borderTopWidth === '1px'
                    && getComputedStyle(workspace).borderRightWidth === '0px'
                    && getComputedStyle(workspace).borderBottomWidth === '0px'
                    && getComputedStyle(workspace).borderLeftWidth === '0px'
                    && getComputedStyle(workspace).borderRadius === '0px';
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-request-workspace]');
                const tabs = [...document.querySelectorAll('[data-ndb-request-tab]')];
                const tabGroup = tabs[0].closest('[data-ndb-filter-tabs]');

                return tabs.length === 5
                    && tabs.every((tab) => tab.dataset.ndbFilterTabVariant === 'segmented')
                    && Math.abs(
                        tabGroup.getBoundingClientRect().left + tabGroup.getBoundingClientRect().width / 2
                        - workspace.getBoundingClientRect().left - workspace.getBoundingClientRect().width / 2
                    ) <= 1;
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const body = document.querySelector('[data-ndb-request-scroll]');
                const nestedVerticalOwners = [...body.querySelectorAll('*')].filter((element) => {
                    const overflow = getComputedStyle(element).overflowY;

                    return (overflow === 'auto' || overflow === 'scroll')
                        && element.scrollHeight > element.clientHeight + 1;
                });

                return getComputedStyle(body).overflowY === 'auto' && nestedVerticalOwners.length === 0;
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const source = document.querySelector('[data-ndb-request-source] code');
                const url = document.querySelector('[data-ndb-request-url]');

                return source.dataset.highlighted === 'yes'
                    && !getComputedStyle(url).fontFamily.toLowerCase().includes('mono');
            })()
            JS)
        ->click('[data-ndb-request-tab="headers"]')
        ->assertAttribute('[data-ndb-request-tab="headers"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-request-panel="headers"]')
        ->assertCount('[data-ndb-request-panel]', 1)
        ->assertScript(<<<'JS'
            (() => {
                const body = document.querySelector('[data-ndb-request-scroll]');
                const blocks = [...document.querySelectorAll('[data-ndb-request-panel="headers"] code')];

                return body.scrollTop === 0
                    && blocks.length === 2
                    && blocks.every((block) => block.dataset.highlighted === 'yes');
            })()
            JS)
        ->resize(1180, 620)
        ->assertScript(<<<'JS'
            (() => {
                const dialog = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                const workspace = document.querySelector('[data-ndb-request-workspace]');
                const body = document.querySelector('[data-ndb-request-scroll]');
                const tabs = [...document.querySelectorAll('[data-ndb-request-tab]')];

                return dialog.scrollWidth <= dialog.clientWidth + 1
                    && workspace.scrollWidth <= workspace.clientWidth + 1
                    && body.clientHeight > 100
                    && tabs.every((tab) => tab.getBoundingClientRect().height >= 30)
                    && tabs.every((tab) => tab.getClientRects().length > 0);
            })()
            JS)
        ->click('[data-ndb-inspector-action="theme"]')
        ->assertAttribute('#newdebugbar', 'data-ndb-theme', 'light')
        ->assertNoJavaScriptErrors();
});

it('keeps Request tabs readable and active-only on mobile', function () {
    $preferences = json_encode(['theme' => 'light', 'favorites' => []], JSON_THROW_ON_ERROR);
    $page = visit('/profiled-request/kyoto?month=november&filters%5Bweather%5D=clear')->resize(390, 844);

    $page
        ->assertScript(<<<JS
            (() => {
                localStorage.setItem('newdebugbar.preferences.v1', '{$preferences}');

                return true;
            })()
            JS)
        ->refresh()
        ->click('[data-ndb-mobile-toolbar-metric-scope="toolbar"][data-ndb-mobile-toolbar-metric="duration"]');

    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-request-workspace]');

    $page
        ->assertAttribute('#newdebugbar', 'data-ndb-theme', 'light')
        ->assertAttribute('[data-ndb-request-tab="route"]', 'aria-pressed', 'true')
        ->assertCount('[data-ndb-request-panel]', 1)
        ->assertScript(<<<'JS'
            (() => {
                const dialog = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                const workspace = document.querySelector('[data-ndb-request-workspace]');
                const tabs = [...document.querySelectorAll('[data-ndb-request-tab]')];
                const tabGroup = tabs[0].closest('[data-ndb-filter-tabs]');
                const facts = document.querySelector('[data-ndb-request-route-facts]');

                return dialog.scrollWidth <= dialog.clientWidth + 1
                    && workspace.scrollWidth <= workspace.clientWidth + 1
                    && tabGroup.scrollWidth <= tabGroup.clientWidth + 1
                    && tabs.length === 5
                    && tabs.map((tab) => tab.textContent.trim()).join('|') === 'Route|Input|Headers|Session|Response'
                    && tabs.every((tab) => tab.getBoundingClientRect().height >= 30)
                    && getComputedStyle(facts).gridTemplateColumns.split(' ').length === 2;
            })()
            JS)
        ->click('[data-ndb-request-tab="session"]')
        ->assertAttribute('[data-ndb-request-tab="session"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-request-panel="session"]')
        ->assertSee('Started')
        ->assertSee('workspace')
        ->assertCount('[data-ndb-request-panel]', 1);

    $page->script('document.querySelector("[data-ndb-request-scroll]").scrollTop = 120');

    $page
        ->click('[data-ndb-request-tab="response"]')
        ->assertAttribute('[data-ndb-request-tab="response"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-request-panel="response"]')
        ->assertScript('document.querySelector("[data-ndb-request-scroll]").scrollTop === 0')
        ->refresh()
        ->click('[data-ndb-mobile-toolbar-metric-scope="toolbar"][data-ndb-mobile-toolbar-metric="duration"]');

    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-request-workspace]');

    $page
        ->assertAttribute('[data-ndb-request-tab="route"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-request-panel="route"]')
        ->assertCount('[data-ndb-request-panel]', 1)
        ->assertNoJavaScriptErrors();
});
