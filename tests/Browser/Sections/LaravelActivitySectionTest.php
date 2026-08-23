<?php

use NewDebugBar\Tests\Fixtures\Events\ProfiledApplicationListener;
use NewDebugBar\Tests\Support\DebugBarBrowser;

it('presents grouped Laravel activity with useful controls', function () {
    visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="cache"]')
        ->assertSee('Hit rate')
        ->assertSee('Misses')
        ->click('[data-ndb-select-section="events"]')
        ->assertScript(<<<'JS'
            (() => {
                const buttons = Array.from(document.querySelectorAll('[data-ndb-event-source]'));

                return buttons.map((button) => button.dataset.ndbEventSource).join('|') === 'all|application|framework'
                    && document.querySelector('[data-ndb-event-source="application"]').getAttribute('aria-pressed') === 'true';
            })()
            JS)
        ->assertScript(<<<'JS'
            ['application', 'all', 'framework'].every((source) => {
                const expected = source === 'all'
                    ? document.querySelectorAll('[data-ndb-event-item]').length
                    : document.querySelectorAll(`[data-ndb-event-item][data-source="${source}"]`).length;
                const count = document.querySelector(`[data-ndb-event-source-count="${source}"]`);

                return count && Number(count.textContent.trim()) === expected;
            })
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-event-source]')).every((button) => {
                const style = getComputedStyle(button);

                return parseFloat(style.borderBottomLeftRadius) > 0
                    && style.borderTopColor === style.borderBottomColor
                    && ! style.transitionProperty.includes('border');
            })
            JS)
        ->click('[data-ndb-event-source="application"]')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-event-item]:not([hidden])'))
                .every((item) => item.dataset.source === 'application')
            JS)
        ->type('[data-ndb-event-search]', 'application.ready')
        ->assertScript('document.querySelectorAll("[data-ndb-event-item]:not([hidden])").length', 1)
        ->click('[data-ndb-select-section="logs"]')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-log-level]')).every((button) => {
                const style = getComputedStyle(button);

                return parseFloat(style.borderBottomLeftRadius) > 0
                    && style.borderTopColor === style.borderBottomColor;
            })
            JS)
        ->click('[data-ndb-log-level="info"]')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-log-item]:not([hidden])'))
                .every((item) => item.dataset.level === 'info')
            JS)
        ->type('[data-ndb-log-search]', 'profiled request')
        ->assertScript('document.querySelectorAll("[data-ndb-log-item]:not([hidden])").length', 1)
        ->assertNoJavaScriptErrors();
});

it('presents Laravel decisions messages and source context without editor links', function () {
    $page = visit('/profiled-context')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->assertMissing('[data-ndb-findings]');

    DebugBarBrowser::waitForDetails($page);

    $page
        ->click('[data-ndb-select-section="authorization"]')
        ->assertScript('document.querySelector("[data-ndb-section-heading]").textContent.trim() === "Authorization"')
        ->assertAttribute('[data-ndb-select-section="authorization"]', 'aria-current', 'page')
        ->assertScript(<<<'JS'
            (() => {
                const authorization = document.querySelector('[data-ndb-authorization-filter]');

                return authorization.matches('[data-ndb-filter-tab]')
                    && authorization.closest('[data-ndb-filter-tabs]') !== null
                    && ! getComputedStyle(authorization).transitionProperty.includes('border');
            })()
            JS)
        ->click('[data-ndb-authorization-filter="denied"]')
        ->assertAttribute('[data-ndb-authorization-filter="denied"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelectorAll("[data-ndb-authorization-item]:not([hidden])").length', 1)
        ->assertScript('document.querySelector("[data-ndb-authorization-item]:not([hidden])").dataset.result === "denied"')
        ->assertSee('delete-profile')
        ->click('[data-ndb-authorization-filter="allowed"]')
        ->assertAttribute('[data-ndb-authorization-filter="allowed"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelector("[data-ndb-authorization-item]:not([hidden])").dataset.result === "allowed"')
        ->assertSee('inspect-profile');

    $page
        ->click('[data-ndb-select-section="messages"]')
        ->assertSee('Checkout checkpoint')
        ->click('[data-ndb-select-section="events"]')
        ->click('[data-ndb-event-item]:first-child summary')
        ->assertSee(ProfiledApplicationListener::class.'@handle')
        ->assertMissing('a[href^="vscode://file/"]')
        ->assertNoJavaScriptErrors();

    DebugBarBrowser::assertSectionSelected($page, 'events');
});

it('presents view data in an accessible popover', function () {
    $page = visit('/profiled-context')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::waitForDetails($page);

    $page->click('[data-ndb-select-section="views"]');

    DebugBarBrowser::waitForDetails($page);

    $page->assertScript(<<<'JS'
            (() => {
                const summary = document.querySelector('[data-ndb-view-group] > summary');
                summary.focus();

                return document.activeElement === summary;
            })()
            JS)
        ->keys('[data-ndb-view-group] > summary', 'Enter')
        ->assertAttribute('[data-ndb-view-group]', 'open', '')
        ->assertSee('tests/Fixtures/views/context.blade.php')
        ->assertScript('!document.querySelector("[data-ndb-view-source]").textContent.replace(/\\s+/g, " ").includes(" :")')
        ->assertMissing('[data-ndb-view-data]')
        ->assertMissing('[data-ndb-view-data-count]')
        ->assertScript(<<<'JS'
            (() => {
                const trigger = document.querySelector('[data-ndb-view-group][open] [data-ndb-view-render]:has([data-ndb-view-data-trigger]) [data-ndb-view-data-trigger]');

                return trigger.textContent.trim() === 'View data'
                    && trigger.querySelector('svg') === null;
            })()
            JS)
        ->assertAttribute('[data-ndb-view-group][open] [data-ndb-view-render]:has([data-ndb-view-data-trigger]) [data-ndb-view-data-trigger]', 'aria-expanded', 'false')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-view-group][open] [data-ndb-view-render]:has([data-ndb-view-data-trigger]) [data-ndb-view-data-popover]")).display === "none"')
        ->assertScript(<<<'JS'
            (() => {
                const render = document.querySelector('[data-ndb-view-group][open] [data-ndb-view-render]:has([data-ndb-view-data-trigger])');
                const renderRow = render?.querySelector('[data-ndb-view-render-row]');
                const renderContext = render?.querySelector('[data-ndb-view-render-context]');
                const viewDataTrigger = render?.querySelector('[data-ndb-view-data-trigger]');
                const viewDataPopover = render?.querySelector('[data-ndb-view-data-popover]');
                const contextRect = renderContext?.getBoundingClientRect();
                const triggerRect = viewDataTrigger?.getBoundingClientRect();

                return render !== null
                    && renderRow !== null
                    && renderContext !== null
                    && viewDataTrigger !== null
                    && viewDataPopover !== null
                    && viewDataTrigger.parentElement === renderRow
                    && renderContext.parentElement === renderRow
                    && getComputedStyle(renderRow).alignItems === 'center'
                    && getComputedStyle(renderContext).alignItems === 'baseline'
                    && Math.abs((contextRect.top + contextRect.bottom) / 2 - (triggerRect.top + triggerRect.bottom) / 2) <= 1
                    && Math.abs(viewDataTrigger.getBoundingClientRect().right - render.getBoundingClientRect().right) <= 1
                    && viewDataTrigger.getAttribute('aria-controls') === viewDataPopover.id
                    && viewDataPopover.getAttribute('role') === 'region'
                    && viewDataPopover.hasAttribute('x-transition:enter')
                    && viewDataPopover.getAttribute('x-transition:enter-start').includes('ndb:scale-95');
            })()
            JS);

    DebugBarBrowser::waitForVisibleElement(
        $page,
        '[data-ndb-view-group][open] [data-ndb-view-render]:has([data-ndb-view-data-trigger]) [data-ndb-view-data-trigger]',
    );

    $page->keys(
        '[data-ndb-view-group][open] [data-ndb-view-render]:has([data-ndb-view-data-trigger]) [data-ndb-view-data-trigger]',
        'Enter',
    );

    DebugBarBrowser::waitForVisibleElement(
        $page,
        '[data-ndb-view-group][open] [data-ndb-view-render]:has([data-ndb-view-data-trigger]) [data-ndb-view-data-popover]',
    );

    $page
        ->waitForText('view-data-value')
        ->assertAttribute('[data-ndb-view-group][open] [data-ndb-view-render]:has([data-ndb-view-data]) [data-ndb-view-data-trigger]', 'aria-expanded', 'true')
        ->assertVisible('[data-ndb-view-group][open] [data-ndb-view-render]:has([data-ndb-view-data]) [data-ndb-view-data-popover]')
        ->assertVisible('[data-ndb-view-data]')
        ->assertSee('view-data-value');

    $page
        ->assertScript(<<<'JS'
            (() => {
                const code = document.querySelector('[data-ndb-view-data] code[data-ndb-language="json"][data-highlighted]');
                const property = code?.querySelector('.hljs-attr');
                const string = code?.querySelector('.hljs-string');

                return code !== null
                    && code.textContent.includes('\n')
                    && code.textContent.includes('"private_value": "view-data-value"')
                    && code.textContent.includes('"rows": [')
                    && Number.parseFloat(getComputedStyle(code).fontSize) >= 12
                    && property !== null
                    && string !== null
                    && code.querySelector('.hljs-literal') !== null
                    && getComputedStyle(property).color !== getComputedStyle(string).color;
            })()
            JS);

    $page
        ->resize(390, 844)
        ->assertScript(<<<'JS'
            (() => {
                const render = document.querySelector('[data-ndb-view-group][open] [data-ndb-view-render]:has([data-ndb-view-data])');
                const viewDataTrigger = render?.querySelector('[data-ndb-view-data-trigger]');
                const viewDataPopover = render?.querySelector('[data-ndb-view-data-popover]');

                return render !== null
                    && viewDataTrigger !== null
                    && viewDataPopover !== null
                    && document.documentElement.scrollWidth <= document.documentElement.clientWidth
                    && viewDataTrigger.getBoundingClientRect().right <= render.getBoundingClientRect().right + 1
                    && viewDataPopover.getBoundingClientRect().left >= 0
                    && viewDataPopover.getBoundingClientRect().right <= window.innerWidth;
            })()
            JS);

    $page
        ->resize(1440, 900)
        ->keys('[data-ndb-view-group][open] [data-ndb-view-render]:has([data-ndb-view-data]) [data-ndb-view-data-trigger]', 'Escape')
        ->assertAttribute('[data-ndb-view-group][open] [data-ndb-view-render]:has([data-ndb-view-data]) [data-ndb-view-data-trigger]', 'aria-expanded', 'false')
        ->assertScript("[...document.querySelectorAll('[data-ndb-view-data-popover]')].every((popover) => getComputedStyle(popover).display === 'none')")
        ->assertMissing('a[href^="vscode://file/"]')
        ->assertNoJavaScriptErrors();

    DebugBarBrowser::assertSectionSelected($page, 'views');
});
