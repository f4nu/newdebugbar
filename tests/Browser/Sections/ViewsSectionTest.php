<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('keeps application views primary and lazily inspects one desktop render', function () {
    $page = visit('/profiled-views');
    $page->script("localStorage.setItem('newdebugbar.preferences.v1', JSON.stringify({theme: 'dark', favorites: []}))");

    $page
        ->refresh()
        ->resize(1280, 720)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="views"]');

    DebugBarBrowser::assertSectionSelected($page, 'views');

    $page
        ->assertAttribute('#newdebugbar', 'data-ndb-theme', 'dark')
        ->assertPresent('[data-ndb-view-workspace]')
        ->assertValue('[data-ndb-view-filter]', 'application')
        ->assertMissing('[data-ndb-view-data]')
        ->assertDontSee('view-data-value')
        ->assertMissing('[data-ndb-view-sort]')
        ->assertMissing('[data-ndb-view-data-popover]')
        ->assertScript(<<<'JS'
            (() => {
                const controls = document.querySelector('[data-ndb-view-list-panel] [data-ndb-inspector-list-controls]');
                const search = document.querySelector('[data-ndb-view-search]').getBoundingClientRect();
                const filter = document.querySelector('[data-ndb-view-filter]').getBoundingClientRect();
                const workspace = document.querySelector('[data-ndb-view-workspace]');
                const visible = [...document.querySelectorAll('[data-ndb-view-group]:not([hidden])')];

                return search.left < filter.left
                    && search.right <= filter.left
                    && controls.scrollWidth <= controls.clientWidth
                    && workspace.getBoundingClientRect().height > 400
                    && workspace.scrollWidth <= workspace.clientWidth
                    && visible.length === 2
                    && visible.every((view) => view.dataset.ndbViewOrigin === 'application')
                    && visible.every((view) => ! view.textContent.includes('tests/Fixtures/views'));
            })()
            JS)
        ->click('[data-ndb-view-group="view-2"]')
        ->assertVisible('[data-ndb-view-detail]')
        ->assertSee('original-response')
        ->assertVisible('[data-ndb-view-render-select]')
        ->assertScript('document.querySelectorAll("[data-ndb-view-detail]").length === 1')
        ->click('[data-ndb-view-detail-tab="data"]')
        ->waitForText('First response')
        ->assertVisible('[data-ndb-view-data]')
        ->select('[data-ndb-view-render-select]', '3')
        ->waitForText('Second response')
        ->assertScript(<<<'JS'
            (() => {
                const code = document.querySelector('[data-ndb-view-data] code[data-ndb-language="json"][data-highlighted]');

                return code !== null
                    && code.textContent.includes('"label": "Second response"')
                    && code.querySelector('.hljs-attr') !== null
                    && code.querySelector('.hljs-string') !== null;
            })()
            JS)
        ->click('[data-ndb-view-detail-tab="source"]')
        ->assertVisible('[data-ndb-view-detail-panel="source"]')
        ->assertSee('tests/Fixtures/views/original-response.blade.php:1')
        ->select('[data-ndb-view-filter]', 'framework')
        ->assertScript('document.querySelectorAll("[data-ndb-view-group]:not([hidden])").length', 0)
        ->assertSee('No views match this origin and search.')
        ->assertVisible('[data-ndb-view-detail-empty]')
        ->select('[data-ndb-view-filter]', 'all')
        ->type('[data-ndb-view-search]', 'context')
        ->assertScript('document.querySelectorAll("[data-ndb-view-group]:not([hidden])").length', 1)
        ->assertNoJavaScriptErrors();
});

it('uses a bounded mobile list drill-in with a working Views back action', function () {
    $page = visit('/profiled-views')->resize(390, 844);
    $page->script("localStorage.setItem('newdebugbar.preferences.v1', JSON.stringify({theme: 'light', favorites: []}))");

    $page
        ->refresh()
        ->click('[data-ndb-mobile-toolbar-trigger="actions"]')
        ->click('[data-ndb-mobile-toolbar-action="inspector"]')
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="sections"]')
        ->click('[data-ndb-select-section="views"]');

    DebugBarBrowser::assertSectionSelected($page, 'views');

    $page
        ->assertAttribute('#newdebugbar', 'data-ndb-theme', 'light')
        ->assertValue('[data-ndb-view-filter]', 'application')
        ->assertScript(<<<'JS'
            (() => {
                const panel = document.querySelector('[data-ndb-section-panel="views"]');
                const workspace = document.querySelector('[data-ndb-view-workspace]');
                const [list, detail] = workspace.children;
                const rows = [...document.querySelectorAll('[data-ndb-view-group]:not([hidden])')];

                return panel.scrollWidth <= panel.clientWidth
                    && workspace.scrollWidth <= workspace.clientWidth
                    && getComputedStyle(workspace).display !== 'grid'
                    && getComputedStyle(list).display === 'flex'
                    && getComputedStyle(detail).display === 'none'
                    && rows.every((row) => row.scrollWidth <= row.clientWidth);
            })()
            JS)
        ->click('[data-ndb-view-group="view-2"]')
        ->assertVisible('[data-ndb-view-detail]')
        ->assertVisible('[data-ndb-view-detail-back]')
        ->click('[data-ndb-view-detail-tab="source"]')
        ->assertSee('tests/Fixtures/views/original-response.blade.php:1')
        ->assertScript(<<<'JS'
            (() => {
                const panel = document.querySelector('[data-ndb-section-panel="views"]');
                const workspace = document.querySelector('[data-ndb-view-workspace]');
                const [list, detail] = workspace.children;

                return getComputedStyle(list).display === 'none'
                    && getComputedStyle(detail).display === 'flex'
                    && detail.getBoundingClientRect().width >= workspace.getBoundingClientRect().width - 2
                    && panel.scrollWidth <= panel.clientWidth
                    && detail.scrollWidth <= detail.clientWidth;
            })()
            JS)
        ->click('[data-ndb-view-detail-back]')
        ->assertVisible('[data-ndb-view-list]');

    DebugBarBrowser::waitForFocus($page, '[data-ndb-view-group][aria-pressed="true"]');

    $page->assertNoJavaScriptErrors();
});
