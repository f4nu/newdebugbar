<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('pins overview before alphabetized active sections and keeps quiet sections in the palette', function () {
    $page = visit('/profiled-rich');
    $page->script("localStorage.setItem('newdebugbar.preferences.v1', JSON.stringify({theme: 'light', sectionMode: 'all', favorites: []}))");

    $page
        ->refresh()
        ->resize(1440, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->assertMissing('[data-ndb-section-mode]')
        ->assertMissing('[data-ndb-quiet-count]')
        ->assertDontSee('quiet hidden')
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));
                const visible = state.orderedSections.filter((section) => state.isSectionVisible(section));

                return visible.length < state.summary.sections.length
                    && visible.every((section) => section.active !== false || state.favorites.includes(section.key) || section.key === state.selected);
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const labels = Array.from(document.querySelectorAll('[data-ndb-section-visible="true"] .ndb-section-label'))
                    .map((label) => label.textContent.trim());
                const remaining = labels.slice(1);
                const sorted = [...remaining].sort((left, right) => left.localeCompare(right, undefined, { sensitivity: 'base' }));

                return labels[0] === 'Overview'
                    && JSON.stringify(remaining) === JSON.stringify(sorted);
            })()
            JS)
        ->assertAttribute('[data-ndb-section="validation"]', 'data-ndb-section-visible', 'false')
        ->assertScript('document.querySelector("[data-ndb-header-environment]").textContent.trim() === "testing"')
        ->assertScript('!["·", "•", "|"].some((separator) => document.querySelector("[data-ndb-header-facts]").textContent.includes(separator))')
        ->assertScript(<<<'JS'
            (() => {
                const top = getComputedStyle(document.querySelector('[data-ndb-header-fact="duration"]'));
                const bottom = getComputedStyle(document.querySelector('[data-ndb-toolbar="duration"]'));

                return top.borderRadius === bottom.borderRadius
                    && top.paddingLeft === bottom.paddingLeft
                    && top.paddingTop === bottom.paddingTop;
            })()
            JS)
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-header-toolbar]").parentElement).backgroundColor', 'rgb(255, 255, 255)')
        ->assertMissing('[data-ndb-section-attention]')
        ->assertVisible('[data-ndb-section="queries"] .ndb-section-count')
        ->assertMissing('[data-ndb-findings]');

    DebugBarBrowser::selectSectionViaPalette($page, 'validation');
    DebugBarBrowser::assertSectionSelected($page, 'validation');

    $page
        ->assertAttribute('[data-ndb-section="validation"]', 'data-ndb-section-visible', 'true')
        ->assertNoJavaScriptErrors();
});

it('prioritizes relevant activity and keeps runtime details collapsed until requested', function () {
    $page = visit('/profiled-rich')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::waitForDetails($page);

    $page
        ->assertVisible('[data-ndb-overview-activity]')
        ->assertCount('[data-ndb-overview-activity-section]', 5)
        ->assertMissing('[data-ndb-overview-activity-section] svg')
        ->assertScript(<<<'JS'
            (() => {
                const row = document.querySelector('[data-ndb-overview-activity-section]');
                const style = getComputedStyle(row);

                return style.paddingLeft === '0px' && style.paddingRight === '0px';
            })()
            JS)
        ->assertVisible('[data-ndb-overview-runtime]')
        ->assertScript('document.querySelector("[data-ndb-overview-runtime]").open === false')
        ->click('[data-ndb-overview-runtime] > summary');

    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-runtime-detail-panel="runtime"]');

    $page
        ->assertVisible('[data-ndb-runtime-detail-panel="runtime"]')
        ->assertVisible('[data-ndb-runtime-detail-navigation]')
        ->assertScript('getComputedStyle(document.querySelector(\'[data-ndb-runtime-detail-select-wrapper]\')).display === "none"')
        ->assertMissing('[data-ndb-runtime-detail-count]')
        ->assertMissing('[data-ndb-runtime-detail-panel-count]')
        ->assertNoJavaScriptErrors();

    $page
        ->keys('[data-ndb-runtime-detail="drivers"]', 'Enter')
        ->assertVisible('[data-ndb-runtime-detail-panel="drivers"]')
        ->assertScript('document.querySelector(\'[data-ndb-runtime-detail="drivers"]\').getAttribute("aria-pressed") === "true"')
        ->resize(390, 844);

    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-runtime-detail-select]');

    $page
        ->assertScript('getComputedStyle(document.querySelector(\'[data-ndb-runtime-detail-navigation]\')).display === "none"')
        ->assertScript('document.querySelector(\'[data-ndb-runtime-detail-select]\').value === "drivers"')
        ->select('[data-ndb-runtime-detail-select]', 'ecosystem')
        ->assertVisible('[data-ndb-runtime-detail-panel="ecosystem"]')
        ->assertScript('document.querySelector(\'[data-ndb-runtime-detail-select]\').value === "ecosystem"')
        ->assertScript(<<<'JS'
            (() => {
                const activity = document.querySelector('[data-ndb-overview-activity]');
                const runtime = document.querySelector('[data-ndb-overview-runtime]');

                return activity.scrollWidth <= activity.clientWidth
                    && runtime.scrollWidth <= runtime.clientWidth;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('uses one non-sticky title and description hierarchy for every section', function () {
    $page = visit('/profiled-context')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->assertCount('[data-ndb-section-header]', 1)
        ->assertScript(<<<'JS'
            (() => {
                const header = document.querySelector('[data-ndb-section-header]');
                const heading = header?.querySelector('[data-ndb-section-heading]');
                const description = header?.querySelector('[data-ndb-section-description]');

                return header !== null
                    && heading !== null
                    && description !== null
                    && getComputedStyle(header).position === 'static'
                    && getComputedStyle(heading).fontSize === '14px'
                    && getComputedStyle(description).fontSize === '12px'
                    && heading.getBoundingClientRect().bottom <= description.getBoundingClientRect().top
                    && heading.getAttribute('aria-describedby') === description.id;
            })()
            JS);

    foreach (['authorization', 'views'] as $section) {
        $page
            ->click("[data-ndb-select-section=\"{$section}\"]")
            ->assertScript(<<<JS
                (() => {
                    const selected = document.querySelector('[data-ndb-select-section="{$section}"]');
                    const heading = document.querySelector('[data-ndb-section-heading]');
                    const description = document.querySelector('[data-ndb-section-description]');

                    return heading.textContent.trim() === selected.querySelector('.ndb-section-label').textContent.trim()
                        && description.textContent.trim().length > 0;
                })()
                JS);
    }

    $page->assertNoJavaScriptErrors();
});

it('keeps host main element styles out of inspector content', function () {
    $page = visit('/profiled');
    $page->script(<<<'JS'
        const style = document.createElement('style');
        style.textContent = 'main { width: min(920px, 100% - 32px); margin: 0 auto; padding: 72px 0 160px; }';
        document.head.appendChild(style);
        JS);

    $page
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->assertScript(<<<'JS'
            (() => {
                const content = document.querySelector('[data-ndb-inspector-content]');
                const style = getComputedStyle(content);

                return content.tagName === 'DIV'
                    && style.paddingTop === '0px'
                    && style.paddingBottom === '0px'
                    && style.marginLeft === '0px'
                    && style.marginRight === '0px';
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('caps the compact bar at large and the inspector at eight extra large', function () {
    visit('/profiled')
        ->resize(1440, 900)
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const request = document.querySelector('[data-ndb-request-switcher="toolbar"]');
                const facts = document.querySelector('[data-ndb-toolbar-facts]');
                const actions = document.querySelector('[data-ndb-toolbar-actions]');
                const box = toolbar.getBoundingClientRect();
                const requestStyles = getComputedStyle(request);
                const factsStyles = getComputedStyle(facts);
                const factOrder = Array.from(facts.querySelectorAll('[data-ndb-toolbar]'))
                    .sort((left, right) => left.getBoundingClientRect().left - right.getBoundingClientRect().left)
                    .map((fact) => fact.dataset.ndbToolbar);

                return Math.abs(box.width - 1024) <= 1
                    && Math.abs(box.left - (window.innerWidth - box.width) / 2) <= 1
                    && Math.abs(window.innerWidth - box.right - box.left) <= 1
                    && requestStyles.flexGrow === '0'
                    && request.getBoundingClientRect().width <= 296
                    && factsStyles.flexGrow === '0'
                    && facts.getBoundingClientRect().left - request.getBoundingClientRect().right >= 32
                    && facts.getBoundingClientRect().right <= actions.getBoundingClientRect().left
                    && actions.getBoundingClientRect().left - facts.getBoundingClientRect().right <= 8
                    && JSON.stringify(factOrder) === JSON.stringify(['environment', 'queries', 'duration', 'memory']);
            })()
            JS)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->assertScript(<<<'JS'
            (() => {
                const inspector = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                const request = document.querySelector('[data-ndb-request-switcher="header"]');
                const facts = document.querySelector('[data-ndb-header-facts]');
                const actions = document.querySelector('[data-ndb-inspector-actions]');
                const factOrder = Array.from(document.querySelectorAll('[data-ndb-header-fact]'))
                    .sort((left, right) => left.getBoundingClientRect().left - right.getBoundingClientRect().left)
                    .map((fact) => fact.dataset.ndbHeaderFact);
                const box = inspector.getBoundingClientRect();

                return Math.abs(box.width - window.innerWidth) <= 1
                    && Math.abs(box.left - (window.innerWidth - box.width) / 2) <= 1
                    && Math.abs(window.innerWidth - box.right - box.left) <= 1
                    && getComputedStyle(request).flexGrow === '0'
                    && request.getBoundingClientRect().width <= 296
                    && facts.getBoundingClientRect().left - request.getBoundingClientRect().right >= 32
                    && facts.getBoundingClientRect().right <= actions.getBoundingClientRect().left
                    && actions.getBoundingClientRect().left - facts.getBoundingClientRect().right <= 8
                    && JSON.stringify(factOrder) === JSON.stringify(['environment', 'queries', 'duration', 'memory']);
            })()
            JS)
        ->resize(1680, 900)
        ->assertScript(<<<'JS'
            (() => {
                const inspector = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                const box = inspector.getBoundingClientRect();

                return Math.abs(box.width - 1536) <= 1
                    && Math.abs(box.left - (window.innerWidth - box.width) / 2) <= 1
                    && Math.abs(window.innerWidth - box.right - box.left) <= 1;
            })()
            JS)
        ->resize(900, 900)
        ->assertScript(<<<'JS'
            (() => {
                const inspector = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                const box = inspector.getBoundingClientRect();

                return Math.abs(box.width - window.innerWidth) <= 1
                    && Math.abs(box.left) <= 1
                    && Math.abs(window.innerWidth - box.right) <= 1;
            })()
            JS)
        ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]')
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const box = toolbar.getBoundingClientRect();

                return Math.abs(box.width - (window.innerWidth - 24)) <= 1
                    && Math.abs(box.left - 12) <= 1
                    && Math.abs(window.innerWidth - box.right - 12) <= 1;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('moves focus into the inspector and returns it to its opener', function () {
    visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-window-controls=expanded] [data-ndb-window-action=shrink]")')
        ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-window-controls=compact] [data-ndb-window-action=expand]")')
        ->assertNoJavaScriptErrors();
});
