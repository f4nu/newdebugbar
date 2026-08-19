<?php

use NewDebugBar\Tests\Fixtures\Events\ProfiledApplicationListener;

it('filters the timeline without inventing spans for point events', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="timeline"]');

    assertDebugSectionSelected($page, 'timeline');

    $page
        ->assertPresent('[data-ndb-timeline-item="request-start"]')
        ->assertVisible('[data-ndb-timeline-waterfall]')
        ->assertScript(<<<'JS'
            (() => {
                const subtitles = Array.from(document.querySelectorAll('[data-ndb-timeline-activity-section]'));

                return subtitles.length > 0
                    && subtitles.every((subtitle) => getComputedStyle(subtitle).textTransform === 'none');
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[data-ndb-timeline-toolbar]');
                const toolbarBounds = toolbar.getBoundingClientRect();
                const overview = document.querySelector('[data-ndb-timeline-overview]').getBoundingClientRect();
                const filter = document.querySelector('[data-ndb-timeline-filter]').getBoundingClientRect();
                const search = document.querySelector('[data-ndb-timeline-search]').getBoundingClientRect();

                return overview.bottom <= toolbarBounds.top
                    && Math.abs(filter.left - toolbarBounds.left) <= 1
                    && Math.abs(search.right - toolbarBounds.right) <= 1
                    && filter.right <= search.left
                    && toolbar.scrollWidth <= toolbar.clientWidth;
            })()
            JS)
        ->assertMissing('[data-ndb-timeline-tabs]')
        ->assertValue('[data-ndb-timeline-filter]', 'key')
        ->assertScript(<<<'JS'
            (() => {
                const values = Array.from(document.querySelector('[data-ndb-timeline-filter]').options)
                    .map((option) => option.value);

                return JSON.stringify(values.slice(0, 3)) === JSON.stringify(['key', 'all', 'request'])
                    && new Set(values).size === values.length
                    && values.includes('queries')
                    && values.includes('events');
            })()
            JS)
        ->select('[data-ndb-timeline-filter]', 'all')
        ->assertValue('[data-ndb-timeline-filter]', 'all')
        ->assertScript('document.querySelector("[data-ndb-timeline-tick=\\"0\\"]").getBoundingClientRect().left > document.querySelector("[data-ndb-timeline-tick=\\"0\\"]").parentElement.parentElement.getBoundingClientRect().left + 4')
        ->assertScript('document.querySelectorAll("[data-ndb-timeline-item]:not([hidden])").length > 2')
        ->assertScript(<<<'JS'
            Number(document.querySelector('[data-ndb-section-panel="timeline"] [x-text="visibleTimelineCount"]').textContent)
                === document.querySelectorAll('[data-ndb-timeline-item]:not([hidden])').length
            JS)
        ->select('[data-ndb-timeline-filter]', 'queries')
        ->assertValue('[data-ndb-timeline-filter]', 'queries')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item]:not([hidden])'))
                .every((item) => item.dataset.section === 'queries')
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item][hidden]'))
                .every((item) => getComputedStyle(item).display === 'none')
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item][data-section="queries"]'))
                .every((item) => {
                    const track = item.querySelector('[data-ndb-timeline-track]').getBoundingClientRect();
                    const mark = item.querySelector('[data-ndb-timeline-mark]').getBoundingClientRect();

                    return item.dataset.kind === 'span'
                        && Number(item.dataset.start) < Number(item.dataset.position)
                        && Number(item.dataset.duration) > 0
                        && mark.width >= 3
                        && mark.left >= track.left
                        && mark.right <= track.right + 1;
                })
            JS)
        ->select('[data-ndb-timeline-filter]', 'events')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item]:not([hidden])'))
                .every((item) => item.dataset.kind === 'point'
                    && item.querySelector('[data-ndb-timeline-mark]').getBoundingClientRect().width > 0)
            JS)
        ->select('[data-ndb-timeline-filter]', 'request')
        ->assertValue('[data-ndb-timeline-filter]', 'request')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item]:not([hidden])'))
                .every((item) => item.dataset.section === 'request')
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item][hidden]'))
                .every((item) => getComputedStyle(item).display === 'none')
            JS)
        ->resize(390, 844)
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[data-ndb-timeline-toolbar]');
                const toolbarBounds = toolbar.getBoundingClientRect();
                const filter = document.querySelector('[data-ndb-timeline-filter]').getBoundingClientRect();
                const search = document.querySelector('[data-ndb-timeline-search]').getBoundingClientRect();

                return toolbar.scrollWidth <= toolbar.clientWidth
                    && Math.abs(filter.left - toolbarBounds.left) <= 1
                    && Math.abs(search.right - toolbarBounds.right) <= 1
                    && filter.right <= search.left;
            })()
            JS)
        ->type('[data-ndb-timeline-search]', 'nothing can match this')
        ->assertScript('document.querySelectorAll("[data-ndb-timeline-item]:not([hidden])").length', 0)
        ->assertSee('No timeline events match these filters.')
        ->assertNoJavaScriptErrors();
});
it('presents useful model evidence with progressive controls', function () {
    $page = visit('/profiled-models')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="models"]')
        ->assertSee('Find repeated record loads, unexpected writes, and when the work happened.')
        ->assertSee('Repeated means extra retrievals after a record’s first load.')
        ->assertMissing('[data-ndb-model-finding]')
        ->assertScript(<<<'JS'
            JSON.stringify(Array.from(document.querySelectorAll('[data-ndb-model-group]'))
                .map((group) => [group.querySelector('[data-ndb-model-name]').textContent.trim(), group.dataset.changes, group.dataset.repeated, group.dataset.loads]))
                === JSON.stringify([
                    ['StudioJob', '0', '8', '14'],
                    ['Client', '0', '6', '10'],
                    ['ProofVersion', '0', '3', '8'],
                    ['User', '0', '3', '5'],
                    ['JobActivity', '0', '0', '7'],
                ])
            JS)
        ->assertScript(<<<'JS'
            [
                ['loads', '[data-ndb-model-load-count]'],
                ['records', '[data-ndb-model-record-count]'],
                ['repeated', '[data-ndb-model-repeat-count]'],
            ].every(([heading, value]) => {
                const headingBounds = document.querySelector(`[data-ndb-model-heading="${heading}"]`).getBoundingClientRect();
                const valueBounds = document.querySelector(`[data-ndb-model-group] ${value}`).getBoundingClientRect();

                return Math.abs(headingBounds.right - valueBounds.right) < 1;
            })
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const summary = document.querySelector('[data-ndb-model-group]:first-of-type > summary');
                summary.focus();

                return document.activeElement === summary;
            })()
            JS)
        ->keys('[data-ndb-model-group]:first-of-type > summary', 'Enter')
        ->assertAttribute('[data-ndb-model-group]:first-of-type', 'open', '')
        ->assertSee('studio_jobs')
        ->assertScript('document.querySelectorAll("[data-ndb-model-group]:first-of-type [data-ndb-model-record]").length', 6)
        ->assertScript('document.querySelectorAll("[data-ndb-model-group]:first-of-type [data-ndb-model-record][data-loads]:not([data-loads=\"1\"])").length', 5)
        ->assertMissing('[data-ndb-model-raw]')
        ->assertDontSee('raw events')
        ->click('[data-ndb-model-expand-all]')
        ->assertScript('Array.from(document.querySelectorAll("[data-ndb-model-group]")).every((group) => group.open)')
        ->click('[data-ndb-model-expand-all]')
        ->assertScript('Array.from(document.querySelectorAll("[data-ndb-model-group]")).every((group) => ! group.open)')
        ->assertNoJavaScriptErrors();
});

it('keeps model evidence contained on a narrow screen', function () {
    $page = visit('/profiled-models')
        ->resize(390, 844)
        ->click('[data-ndb-mobile-toolbar-trigger="actions"]')
        ->click('[data-ndb-mobile-toolbar-action="inspector"]')
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="palette"]')
        ->assertVisible('[data-ndb-command="section:models"]')
        ->click('[data-ndb-command="section:models"]')
        ->assertVisible('[data-ndb-section-panel="models"]')
        ->assertScript(<<<'JS'
            (() => {
                const content = document.querySelector('#newdebugbar main');
                const panel = document.querySelector('[data-ndb-section-panel="models"]');

                return panel.getBoundingClientRect().width <= content.clientWidth + 1
                    && content.scrollWidth <= content.clientWidth + 1;
            })()
            JS)
        ->keys('[data-ndb-model-group]:first-of-type > summary', 'Enter')
        ->assertAttribute('[data-ndb-model-group]:first-of-type', 'open', '')
        ->assertScript(<<<'JS'
            (() => {
                const content = document.querySelector('#newdebugbar main');
                const tableScroller = document.querySelector('[data-ndb-model-record]').closest('.ndb\\:overflow-x-auto');

                return content.scrollWidth <= content.clientWidth + 1
                    && tableScroller.scrollWidth > tableScroller.clientWidth;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('puts model changes before repeated retrievals', function () {
    visit('/profiled-models?changes=1')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="models"]')
        ->assertMissing('[data-ndb-model-finding]')
        ->assertScript(<<<'JS'
            (() => {
                const first = document.querySelector('[data-ndb-model-group]');

                return first.dataset.changes === '1'
                    && first.querySelector('[data-ndb-model-name]').textContent.trim() === 'Client';
            })()
            JS)
        ->click('[data-ndb-model-group]:first-of-type > summary')
        ->assertSee('Model changes')
        ->assertSee('1 updated')
        ->assertNoJavaScriptErrors();
});

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

it('uses light dividers above expanded cache JSON details', function () {
    $page = visit('/profiled');
    $page->script("localStorage.setItem('newdebugbar.preferences.v1', JSON.stringify({theme: 'light', favorites: []}))");

    $page
        ->refresh()
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="cache"]')
        ->click('[data-ndb-section-panel="cache"] details:first-of-type summary')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-section-panel=\\"cache\\"] details pre")).borderTopColor === getComputedStyle(document.querySelector("[data-ndb-section-panel=\\"cache\\"] details")).borderTopColor')
        ->assertNoJavaScriptErrors();
});

it('shows an aligned request trace and switches request detail groups', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="request"]')
        ->assertVisible('[data-ndb-request-trace]')
        ->assertScript(<<<'JS'
            (() => {
                const description = document.querySelector('[data-ndb-section-description]').getBoundingClientRect();
                const trace = document.querySelector('[data-ndb-request-trace]').getBoundingClientRect();

                return trace.top - description.bottom <= 16.5;
            })()
            JS)
        ->assertScript('document.querySelector("[data-ndb-request-status]").textContent.trim() === "200"')
        ->assertScript('/^Completed in \\d+(?:\\.\\d+)? ms$/.test(document.querySelector("[data-ndb-request-completion]").textContent.replace(/\\s+/g, " ").trim())')
        ->assertScript('!["Success", "Failed", "Completed successfully", "Completed with an error"].some((meaning) => document.querySelector("[data-ndb-request-trace]").textContent.includes(meaning))')
        ->assertVisible('[data-ndb-request-details]')
        ->assertScript('document.querySelector("[data-ndb-request-details]").open === false')
        ->click('[data-ndb-request-details] > summary')
        ->assertScript('document.querySelector("[data-ndb-request-details]").open === true')
        ->assertScript('document.querySelectorAll("[data-ndb-request-step]").length', 3)
        ->assertScript('document.querySelectorAll("[data-ndb-request-line]").length', 2)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-request-step]')).every((step) => {
                const dot = step.querySelector('[data-ndb-request-dot]').getBoundingClientRect();
                const heading = step.querySelector('h3').getBoundingClientRect();

                return Math.abs((dot.top + dot.height / 2) - (heading.top + heading.height / 2)) < 1;
            })
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-request-line]')).every((line, index) => {
                const nextDot = document.querySelectorAll('[data-ndb-request-dot]')[index + 1].getBoundingClientRect();
                const bounds = line.getBoundingClientRect();

                return Math.abs(bounds.bottom - nextDot.top) < 1
                    && Math.abs(bounds.width - 2) < 0.1;
            })
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-request-detail]')).every((button) => {
                const parent = button.parentElement;
                const styles = getComputedStyle(parent);
                const availableWidth = parent.clientWidth
                    - parseFloat(styles.paddingLeft)
                    - parseFloat(styles.paddingRight);

                return Math.abs(button.getBoundingClientRect().width - availableWidth) < 1;
            })
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-request-detail-count], [data-ndb-request-detail-panel-count]'))
                .every((count) => /^\d+$/.test(count.textContent.trim()))
            JS)
        ->assertAttribute('[data-ndb-request-detail="headers"]', 'aria-pressed', 'true')
        ->click('[data-ndb-request-detail="session"]')
        ->assertAttribute('[data-ndb-request-detail="session"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-request-detail-panel="session"]')
        ->assertNoJavaScriptErrors();
});

it('shows log call sites', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="logs"]')
        ->assertSee('tests/Support/DefinesTestApplication.php')
        ->click('[data-ndb-log-item] > summary')
        ->assertPresent('[data-ndb-copy-log-callsite="0"]')
        ->assertNoJavaScriptErrors();

    assertDebugSectionSelected($page, 'logs');
});

it('sorts views from the column headers with clear direction feedback', function () {
    $groupNames = <<<'JS'
        Array.from(document.querySelectorAll('[data-ndb-view-group]'))
            .map((group) => group.querySelector('summary span').textContent.trim())
            .join('|')
        JS;

    $page = visit('/profiled-views');
    $page->script("localStorage.setItem('newdebugbar.preferences.v1', JSON.stringify({theme: 'dark', favorites: []}))");

    $page
        ->refresh()
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="views"]')
        ->assertMissing('select[data-ndb-view-sort]')
        ->assertAttribute('[data-ndb-view-sort="name"]', 'type', 'button')
        ->assertAttribute('[data-ndb-view-sort="name"]', 'data-ndb-view-sort', 'name')
        ->assertScript('!document.querySelector("[data-ndb-view-sort=\"name\"]").hasAttribute("aria-expanded")')
        ->assertScript('document.querySelector("[data-ndb-view-sort=\"name\"]").parentElement.getAttribute("aria-sort") === "ascending"')
        ->assertScript(<<<'JS'
            (() => {
                const buttons = Array.from(document.querySelectorAll('[data-ndb-view-sort]'));

                return buttons.every((button) => {
                    const styles = getComputedStyle(button);

                    return button.querySelector('svg') === null
                        && styles.paddingTop === '0px'
                        && styles.paddingRight === '0px'
                        && styles.paddingBottom === '0px'
                        && styles.paddingLeft === '0px'
                        && styles.backgroundColor === 'rgba(0, 0, 0, 0)';
                }) && getComputedStyle(document.querySelector('[data-ndb-view-sort="name"]')).color === 'rgb(255, 255, 255)';
            })()
            JS)
        ->hover('[data-ndb-view-sort="name"]')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-view-sort=\\"name\\"]")).backgroundColor === "rgba(0, 0, 0, 0)"')
        ->assertScript($groupNames, 'context|original-response')
        ->click('[data-ndb-view-sort="count"]')
        ->assertScript('document.querySelector("[data-ndb-view-sort=\"count\"]").parentElement.getAttribute("aria-sort") === "descending"')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-view-sort=\\"count\\"]")).color === "rgb(255, 255, 255)"')
        ->assertScript($groupNames, 'original-response|context')
        ->click('[data-ndb-view-sort="count"]')
        ->assertScript('document.querySelector("[data-ndb-view-sort=\"count\"]").parentElement.getAttribute("aria-sort") === "ascending"')
        ->assertScript($groupNames, 'context|original-response')
        ->keys('[data-ndb-view-sort="name"]', 'Enter')
        ->assertScript('document.querySelector("[data-ndb-view-sort=\"name\"]").parentElement.getAttribute("aria-sort") === "ascending"')
        ->keys('[data-ndb-view-sort="name"]', 'Enter')
        ->assertScript('document.querySelector("[data-ndb-view-sort=\"name\"]").parentElement.getAttribute("aria-sort") === "descending"')
        ->assertScript($groupNames, 'original-response|context')
        ->assertNoJavaScriptErrors();
});

it('presents Laravel decisions messages and source context without editor links', function () {
    $page = visit('/profiled-context')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->assertMissing('[data-ndb-findings]');

    $page
        ->click('[data-ndb-select-section="authorization"]')
        ->assertScript('document.querySelector("[data-ndb-section-heading]").textContent.trim() === "Authorization"')
        ->assertAttribute('[data-ndb-select-section="authorization"]', 'aria-current', 'page')
        ->assertScript(<<<'JS'
            (() => {
                const authorization = document.querySelector('[data-ndb-authorization-filter]');
                const events = document.querySelector('[data-ndb-event-source]');
                const queries = document.querySelector('[data-ndb-query-filter]');

                return authorization.className === events.className
                    && events.className === queries.className
                    && [authorization, events, queries].every((tab) =>
                        tab.matches('[data-ndb-filter-tab]')
                        && tab.closest('[data-ndb-filter-tabs]') !== null
                        && ! getComputedStyle(tab).transitionProperty.includes('border')
                    );
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
        ->assertSee('Checkout checkpoint');

    $page
        ->click('[data-ndb-select-section="views"]')
        ->click('[data-ndb-view-group] > summary')
        ->assertSee('tests/Fixtures/views/context.blade.php')
        ->assertScript('!document.querySelector("[data-ndb-view-source]").textContent.replace(/\\s+/g, " ").includes(" :")')
        ->assertPresent('[data-ndb-view-data]')
        ->assertMissing('[data-ndb-view-data-count]')
        ->assertScript(<<<'JS'
            (() => {
                const trigger = document.querySelector('[data-ndb-view-data-trigger]');

                return trigger.textContent.trim() === 'View data'
                    && trigger.querySelector('svg') === null;
            })()
            JS)
        ->assertAttribute('[data-ndb-view-data-trigger]', 'aria-expanded', 'false')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-view-data-popover]")).display === "none"')
        ->assertScript(<<<'JS'
            (() => {
                const render = document.querySelector('[data-ndb-view-render]');
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

    $page
        ->click('[data-ndb-view-data-trigger]')
        ->assertAttribute('[data-ndb-view-data-trigger]', 'aria-expanded', 'true')
        ->assertVisible('[data-ndb-view-data-popover]')
        ->assertVisible('[data-ndb-view-data]')
        ->assertSee('view-data-value')
        ->assertScript(<<<'JS'
            (() => {
                const popover = document.querySelector('[data-ndb-view-data-popover]');
                const trigger = document.querySelector('[data-ndb-view-data-trigger]');
                const surface = popover?.querySelector('[data-ndb-popover-surface]');
                const arrow = popover?.querySelector('[data-ndb-popover-arrow]');
                if (! popover || ! trigger || ! surface || ! arrow) return false;

                const surfaceStyle = getComputedStyle(surface);
                const triggerRect = trigger.getBoundingClientRect();
                const arrowRect = arrow.getBoundingClientRect();

                return Number.parseFloat(surfaceStyle.borderRadius) === 16
                    && surfaceStyle.borderStyle === 'solid'
                    && surfaceStyle.boxShadow !== 'none'
                    && surfaceStyle.backdropFilter !== 'none'
                    && Math.abs(
                        (triggerRect.left + triggerRect.right) / 2
                        - (arrowRect.left + arrowRect.right) / 2
                    ) <= 4;
            })()
            JS);

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
        ->keys('[data-ndb-view-data-trigger]', 'Escape')
        ->assertAttribute('[data-ndb-view-data-trigger]', 'aria-expanded', 'false')
        ->assertScript(<<<'JS'
            (() => {
                const trigger = document.querySelector('[data-ndb-view-data-trigger]');
                const popover = document.querySelector('[data-ndb-view-data-popover]');

                return document.activeElement === trigger
                    && getComputedStyle(popover).display === 'none';
            })()
            JS);

    $page
        ->click('[data-ndb-view-data-trigger]')
        ->resize(390, 844)
        ->assertScript(<<<'JS'
            (() => {
                const render = document.querySelector('[data-ndb-view-render]');
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
        ->click('[data-ndb-view-source]')
        ->assertAttribute('[data-ndb-view-data-trigger]', 'aria-expanded', 'false')
        ->assertMissing('a[href^="vscode://file/"]')
        ->click('[data-ndb-select-section="events"]')
        ->click('[data-ndb-event-item]:first-child summary')
        ->assertSee(ProfiledApplicationListener::class.'@handle')
        ->assertMissing('a[href^="vscode://file/"]')
        ->assertNoJavaScriptErrors();

    assertDebugSectionSelected($page, 'events');
});

it('shows relative exception frames and highlighted source context', function () {
    $page = visit('/profiled-reported-exception')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="exceptions"]');

    assertDebugSectionSelected($page, 'exceptions');

    $page
        ->assertSee('Application frames')
        ->assertSee('Vendor frames')
        ->assertSee('tests/Support/DefinesTestApplication.php')
        ->assertDontSee('/Users/benjamin/Sites/new-debug-bar/tests/Support/DefinesTestApplication.php')
        ->assertPresent('[data-ndb-copy-exception-callsite="0"]')
        ->assertScript('document.querySelectorAll("#newdebugbar code[data-ndb-language=php][data-highlighted]").length > 0')
        ->assertNoJavaScriptErrors();
});
