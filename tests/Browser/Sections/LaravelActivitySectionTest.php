<?php

use NewDebugBar\Tests\Fixtures\Events\ProfiledApplicationListener;
use NewDebugBar\Tests\Fixtures\Events\ProfiledQueuedApplicationListener;
use NewDebugBar\Tests\Support\DebugBarBrowser;

it('groups noisy Laravel events around application evidence', function () {
    $page = visit('/profiled-events')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="cache"]')
        ->assertSee('Hit rate')
        ->assertScript(<<<'JS'
            (() => {
                const results = Array.from(document.querySelectorAll('[data-ndb-cache-result]'))
                    .map((result) => result.textContent.trim());

                return results.includes('Hit') && results.includes('Miss');
            })()
            JS)
        ->click('[data-ndb-select-section="events"]');

    DebugBarBrowser::waitForDetails($page);

    $page
        ->assertScript(<<<'JS'
            (() => {
                const buttons = Array.from(document.querySelectorAll('[data-ndb-event-source]'));

                return buttons.map((button) => button.dataset.ndbEventSource).join('|') === 'all|application|framework'
                    && document.querySelector('[data-ndb-event-source="application"]').getAttribute('aria-pressed') === 'true';
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const items = [...document.querySelectorAll('[data-ndb-event-item]')];

                return ['application', 'all', 'framework'].every((source) => {
                    const expected = items
                        .filter((item) => source === 'all' || item.dataset.ndbEventSourceValue === source)
                        .reduce((count, item) => count + Number(item.dataset.ndbEventOccurrenceCount), 0);
                    const count = document.querySelector(`[data-ndb-event-source-count="${source}"]`);

                    return count && Number(count.textContent.trim()) === expected;
                });
            })()
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-event-source]')).every((button) => {
                const style = getComputedStyle(button);

                return parseFloat(style.borderBottomLeftRadius) > 0
                    && style.borderTopColor === style.borderBottomColor
                    && ! style.transitionProperty.includes('border');
            })
            JS)
        ->click('[data-ndb-event-source="all"]')
        ->assertAttribute('[data-ndb-event-source="all"]', 'aria-pressed', 'true')
        ->assertScript(<<<'JS'
            (() => {
                const visible = [...document.querySelectorAll('[data-ndb-event-item]:not([hidden])')];
                const all = [...document.querySelectorAll('[data-ndb-event-item]')];

                return visible.length === all.length
                    && new Set(visible.map((item) => item.dataset.ndbEventSourceValue)).size === 2;
            })()
            JS)
        ->click('[data-ndb-event-source="application"]')
        ->assertAttribute('[data-ndb-event-source="application"]', 'aria-pressed', 'true')
        ->assertScript(<<<'JS'
            (() => {
                const visible = [...document.querySelectorAll('[data-ndb-event-item]:not([hidden])')];
                const selected = document.querySelector('[data-ndb-event-item][aria-pressed="true"]');

                return visible.length === 4
                    && visible.every((item) => item.dataset.ndbEventSourceValue === 'application')
                    && visible.reduce((count, item) => count + Number(item.dataset.ndbEventOccurrenceCount), 0) === 12
                    && selected?.dataset.ndbEventSourceValue === 'application'
                    && document.querySelector('[data-ndb-event-visible-summary]').textContent.trim() === '12 events in 4 groups';
            })()
            JS)
        ->assertSee(ProfiledApplicationListener::class.'@handle')
        ->assertSee(ProfiledQueuedApplicationListener::class.'@handle')
        ->assertSee('2 registrations')
        ->assertSee('1 extra registration')
        ->assertSee('Listener handling')
        ->assertSee('What to inspect next')
        ->assertMissing('private fixture value')
        ->assertVisible('[data-ndb-event-copy-name]')
        ->assertAttribute('[data-ndb-event-copy-name]', 'aria-label', 'Copy event name')
        ->assertScript(<<<'JS'
            (() => {
                const state = document.getElementById('newdebugbar')._x_dataStack?.[0];

                window.newdebugbarEventClipboardWrites = [];
                window.newdebugbarExpectedEventClipboard = {
                    name: state?.selectedEvent?.name,
                    payload: JSON.stringify(state?.selectedEvent?.payload_shape, null, 2),
                };
                Object.defineProperty(window.navigator, 'clipboard', {
                    configurable: true,
                    value: {
                        writeText: async (value) => window.newdebugbarEventClipboardWrites.push(value),
                    },
                });

                return window.newdebugbarExpectedEventClipboard.name?.includes('ProfiledApplicationEvent');
            })()
            JS)
        ->click('[data-ndb-event-copy-name]')
        ->wait(0.05)
        ->assertAttribute('[data-ndb-event-detail-tab="overview"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-event-detail-panel="overview"]')
        ->click('[data-ndb-event-detail-tab="payload"]')
        ->assertAttribute('[data-ndb-event-detail-tab="payload"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-event-detail-panel="payload"]')
        ->assertSee('trip')
        ->assertSee('changes')
        ->assertVisible('[data-ndb-event-copy-payload-shape]')
        ->click('[data-ndb-event-copy-payload-shape]')
        ->wait(0.05)
        ->assertScript(<<<'JS'
            (() => {
                const [name, payload] = window.newdebugbarEventClipboardWrites;

                return window.newdebugbarEventClipboardWrites.length === 2
                    && name === window.newdebugbarExpectedEventClipboard.name
                    && payload === window.newdebugbarExpectedEventClipboard.payload
                    && payload.includes('"trip"')
                    && payload.includes('"changes"');
            })()
            JS)
        ->click('[data-ndb-event-detail-tab="source"]')
        ->assertAttribute('[data-ndb-event-detail-tab="source"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-event-detail-panel="source"]')
        ->assertCount('[data-ndb-event-copy-dispatch-source]', 2)
        ->assertScript(<<<'JS'
            [...document.querySelectorAll('[data-ndb-event-copy-dispatch-source]')]
                .every((button) => button.getClientRects().length > 0)
            JS)
        ->click('[data-ndb-event-detail-tab="overview"]')
        ->type('[data-ndb-event-search]', 'ItineraryRecalculation');

    DebugBarBrowser::waitForVisibleElement(
        $page,
        '[data-ndb-event-item][data-ndb-event-occurrence-count="8"]:not([hidden])',
    );

    $page
        ->assertScript(<<<'JS'
            (() => {
                const visible = [...document.querySelectorAll('[data-ndb-event-item]:not([hidden])')];

                return visible.length === 1
                    && visible[0].dataset.ndbEventOccurrenceCount === '8'
                    && document.querySelector('[data-ndb-event-detail-title]').textContent.includes('ItineraryRecalculation');
            })()
            JS)
        ->type('[data-ndb-event-search]', 'event-that-does-not-exist');

    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-event-empty]');

    $page
        ->assertSee('No events match this source and search.')
        ->assertSee('No event is selected. Adjust the source filter or search.')
        ->assertScript('document.querySelectorAll("[data-ndb-event-item]:not([hidden])").length === 0')
        ->type('[data-ndb-event-search]', '')
        ->click('[data-ndb-event-source="framework"]')
        ->select('[data-ndb-event-sort]', 'frequency')
        ->assertAttribute('[data-ndb-event-source="framework"]', 'aria-pressed', 'true')
        ->assertScript(<<<'JS'
            (() => {
                const visible = [...document.querySelectorAll('[data-ndb-event-item]:not([hidden])')];
                const counts = visible.map((item) => Number(item.dataset.ndbEventOccurrenceCount));

                return visible.length > 0
                    && visible.every((item) => item.dataset.ndbEventSourceValue === 'framework')
                    && counts.every((count, index) => index === 0 || counts[index - 1] >= count)
                    && document.querySelector('[data-ndb-event-detail-title]').textContent.trim().length > 0;
            })()
            JS)
        ->assertNoJavaScriptErrors();

    DebugBarBrowser::assertSectionSelected($page, 'events');
});

it('keeps Events selection focused with one mobile scroll owner', function () {
    $page = visit('/profiled-events')
        ->resize(390, 844)
        ->click('[data-ndb-mobile-toolbar-trigger="actions"]')
        ->click('[data-ndb-mobile-toolbar-action="inspector"]')
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="sections"]')
        ->click('[data-ndb-select-section="events"]');

    DebugBarBrowser::waitForDetails($page);

    $page
        ->assertVisible('[data-ndb-event-item][aria-pressed="true"]')
        ->assertScript(<<<'JS'
            (() => {
                const dialog = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                const workspace = document.querySelector('[data-ndb-event-workspace]');
                const [list, detail] = workspace.children;
                const rows = [...document.querySelectorAll('[data-ndb-event-item]:not([hidden])')];

                return dialog.scrollWidth <= dialog.clientWidth + 1
                    && workspace.scrollWidth <= workspace.clientWidth + 1
                    && getComputedStyle(workspace).display !== 'grid'
                    && getComputedStyle(list).display === 'flex'
                    && getComputedStyle(detail).display === 'none'
                    && rows.every((row) => row.getBoundingClientRect().height <= 84);
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const selected = document.querySelector('[data-ndb-event-item][aria-pressed="true"]');
                selected.focus();

                return document.activeElement === selected;
            })()
            JS)
        ->keys('[data-ndb-event-item][aria-pressed="true"]', 'Enter');

    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-event-detail]');

    $page
        ->assertVisible('[data-ndb-event-detail]')
        ->assertScript(<<<'JS'
            (() => {
                const content = document.querySelector('[data-ndb-inspector-content]');
                const workspace = document.querySelector('[data-ndb-event-workspace]');
                const [list, detail] = workspace.children;
                const tabs = [...document.querySelectorAll('[data-ndb-event-detail-tab]')];
                const labels = tabs.map((tab) => tab.querySelector('span'));
                const icons = tabs.map((tab) => tab.querySelector('[data-ndb-event-detail-tab-icon]'));
                const candidates = [content, list, detail];
                const scrollOwners = candidates.filter((element) => {
                    const overflow = getComputedStyle(element).overflowY;

                    return element.scrollHeight > element.clientHeight + 1
                        && (overflow === 'auto' || overflow === 'scroll');
                });

                return getComputedStyle(list).display === 'none'
                    && getComputedStyle(detail).display === 'flex'
                    && document.activeElement === detail
                    && detail.scrollWidth <= detail.clientWidth + 1
                    && tabs.length === 3
                    && labels.every((label) => getComputedStyle(label).display === 'none')
                    && icons.every((icon) => getComputedStyle(icon).display !== 'none')
                    && scrollOwners.length === 1
                    && scrollOwners[0] === content;
            })()
            JS)
        ->keys('[data-ndb-event-detail-tab="payload"]', 'Enter')
        ->assertAttribute('[data-ndb-event-detail-tab="payload"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-event-detail-panel="payload"]')
        ->keys('[data-ndb-event-detail-back]', 'Enter');

    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-event-item][aria-pressed="true"]');

    $page
        ->assertScript(<<<'JS'
            (() => {
                const selected = document.querySelector('[data-ndb-event-item][aria-pressed="true"]');
                const detail = document.querySelector('[data-ndb-event-detail]');

                return document.activeElement === selected
                    && getComputedStyle(detail).display === 'none';
            })()
            JS)
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
        ->assertScript('document.querySelector("[data-ndb-authorization-item]:not([hidden])").dataset.ndbAuthorizationResult === "denied"')
        ->assertSee('delete-profile')
        ->click('[data-ndb-authorization-filter="allowed"]')
        ->assertAttribute('[data-ndb-authorization-filter="allowed"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelector("[data-ndb-authorization-item]:not([hidden])").dataset.ndbAuthorizationResult === "allowed"')
        ->assertSee('inspect-profile');

    $page
        ->click('[data-ndb-select-section="messages"]')
        ->assertSee('Checkout checkpoint')
        ->click('[data-ndb-select-section="events"]');

    DebugBarBrowser::waitForDetails($page);

    $page
        ->click('[data-ndb-event-item]:not([hidden])')
        ->assertSee(ProfiledApplicationListener::class.'@handle')
        ->assertSee(ProfiledQueuedApplicationListener::class.'@handle')
        ->assertSee('Completed and queued')
        ->assertSee('2 registrations')
        ->assertCount('[data-ndb-event-copy-listener-source]', 2)
        ->assertScript(<<<'JS'
            [...document.querySelectorAll('[data-ndb-event-copy-listener-source]')]
                .every((button) => button.getClientRects().length > 0)
            JS)
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
                const trigger = document.querySelector('[data-ndb-view-group][open] [data-ndb-view-render="1"] [data-ndb-view-data-trigger]');

                return trigger.textContent.trim() === 'View data'
                    && trigger.querySelector('svg') === null;
            })()
            JS)
        ->assertAttribute('[data-ndb-view-group][open] [data-ndb-view-render="1"] [data-ndb-view-data-trigger]', 'aria-expanded', 'false')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-view-group][open] [data-ndb-view-render=\\"1\\"] [data-ndb-view-data-popover]")).display === "none"')
        ->assertScript(<<<'JS'
            (() => {
                const render = document.querySelector('[data-ndb-view-group][open] [data-ndb-view-render="1"]');
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
        '[data-ndb-view-group][open] [data-ndb-view-render="1"] [data-ndb-view-data-trigger]',
    );

    $page->keys(
        '[data-ndb-view-group][open] [data-ndb-view-render="1"] [data-ndb-view-data-trigger]',
        'Enter',
    );

    DebugBarBrowser::waitForVisibleElement(
        $page,
        '[data-ndb-view-group][open] [data-ndb-view-render="1"] [data-ndb-view-data-popover]',
    );

    $page
        ->waitForText('view-data-value')
        ->assertAttribute('[data-ndb-view-group][open] [data-ndb-view-render="1"] [data-ndb-view-data-trigger]', 'aria-expanded', 'true')
        ->assertVisible('[data-ndb-view-group][open] [data-ndb-view-render="1"] [data-ndb-view-data-popover]')
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
                const render = document.querySelector('[data-ndb-view-group][open] [data-ndb-view-render="1"]');
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
        ->keys('[data-ndb-view-group][open] [data-ndb-view-render="1"] [data-ndb-view-data-trigger]', 'Escape')
        ->assertAttribute('[data-ndb-view-group][open] [data-ndb-view-render="1"] [data-ndb-view-data-trigger]', 'aria-expanded', 'false')
        ->assertScript("[...document.querySelectorAll('[data-ndb-view-data-popover]')].every((popover) => getComputedStyle(popover).display === 'none')")
        ->assertMissing('a[href^="vscode://file/"]')
        ->assertNoJavaScriptErrors();

    DebugBarBrowser::assertSectionSelected($page, 'views');
});
