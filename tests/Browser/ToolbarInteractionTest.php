<?php

it('opens every compact toolbar destination and shrinks cleanly', function () {
    $page = visit('/profiled')
        ->assertPresent('[data-testid="host-page"]')
        ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]')
        ->assertMissing('[data-ndb-toolbar-status-meaning]')
        ->assertVisible('[data-ndb-toolbar-action="theme"]')
        ->assertAttribute('#newdebugbar', 'data-theme', 'light')
        ->click('[data-ndb-toolbar-action="theme"]')
        ->assertAttribute('#newdebugbar', 'data-theme', 'dark')
        ->click('[data-ndb-toolbar-action="theme"]')
        ->assertAttribute('#newdebugbar', 'data-theme', 'light')
        ->assertScript(<<<'JS'
            (() => {
                const theme = document.querySelector('[data-ndb-toolbar-action="theme"]');
                const icon = theme?.querySelector('span:not([style*="display: none"]) svg');

                if (! theme || ! icon) return false;

                const center = (element) => {
                    const bounds = element.getBoundingClientRect();

                    return bounds.top + bounds.height / 2;
                };

                return Math.abs(center(theme) - center(icon)) <= 0.5;
            })()
            JS);

    foreach ([
        'expand' => 'overview',
        'request' => 'request',
        'environment' => 'overview',
        'duration' => 'request',
        'memory' => 'overview',
        'queries' => 'queries',
    ] as $toolbar => $section) {
        $selector = $toolbar === 'expand'
            ? '[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]'
            : "[data-ndb-toolbar=\"{$toolbar}\"]";

        $page
            ->click($selector);

        assertDebugSectionSelected($page, $section);

        if ($toolbar === 'expand') {
            $page
                ->assertScript('document.querySelector("[data-ndb-header-memory]").textContent.includes("MB")')
                ->assertMissing('[data-ndb-header-status-meaning]')
                ->assertScript(<<<'JS'
                    (() => {
                        const center = (element) => {
                            const bounds = element.getBoundingClientRect();

                            return bounds.top + bounds.height / 2;
                        };
                        const section = document.querySelector('[data-ndb-section="queries"]');
                        const favorite = section.querySelector('[data-ndb-toggle-favorite]');
                        const count = section.querySelector('.ndb-section-count');
                        const theme = document.querySelector('[data-ndb-inspector-action="theme"]');

                        return Math.abs(center(favorite) - center(favorite.querySelector('svg'))) <= 0.5
                            && Math.abs(center(favorite) - center(count)) <= 0.5
                            && Math.abs(center(theme) - center(theme.querySelector('svg'))) <= 0.5;
                    })()
                    JS)
                ->assertScript('/^\\d+(?:\\.\\d{2})? (?:B|KB|MB)$/.test(document.querySelector("[data-ndb-header-response-size]").textContent.trim())');
        }

        $page
            ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]')
            ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]');
    }

    $page->assertNoJavaScriptErrors();
});

it('provides stateful window controls and closes until reload', function () {
    $page = visit('/profiled')
        ->resize(1440, 900)
        ->assertVisible('[data-ndb-window-controls="compact"]')
        ->assertScript(<<<'JS'
            (() => {
                const controls = document.querySelector('[data-ndb-window-controls="compact"]');
                const expand = controls.querySelector('[data-ndb-window-action="expand"]');
                const shrink = controls.querySelector('[data-ndb-window-action="shrink"]');
                const close = controls.querySelector('[data-ndb-window-action="close"]');
                const utility = document.querySelector('[data-ndb-toolbar-utility-actions]');
                const separator = document.querySelector('[data-ndb-toolbar-actions] [data-ndb-window-controls-separator]');

                return expand.disabled === false
                    && shrink.disabled === true
                    && close.disabled === false
                    && Number.parseFloat(getComputedStyle(shrink).opacity) < Number.parseFloat(getComputedStyle(expand).opacity)
                    && utility.getAttribute('aria-label') === 'Tools'
                    && controls.getAttribute('aria-label') === 'Window controls'
                    && separator === null
                    && Number.parseFloat(getComputedStyle(utility.parentElement).columnGap) > 0;
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                window.ndbWindowControlColor = getComputedStyle(
                    document.querySelector('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]'),
                ).color;

                return true;
            })()
            JS)
        ->hover('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->assertScript(<<<'JS'
            (() => {
                const control = document.querySelector('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');
                const style = getComputedStyle(control);

                return style.backgroundColor === 'rgba(0, 0, 0, 0)'
                    && style.color !== window.ndbWindowControlColor;
            })()
            JS)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->assertVisible('[data-ndb-window-controls="expanded"]')
        ->assertScript('document.querySelector(\'[data-ndb-window-controls="expanded"] [data-ndb-window-action="expand"]\').disabled === true')
        ->assertScript('document.querySelector(\'[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]\').disabled === false')
        ->assertScript('document.querySelector(\'[data-ndb-window-controls="expanded"] [data-ndb-window-action="close"]\').disabled === false')
        ->assertScript(<<<'JS'
            (() => {
                const controls = document.querySelector('[data-ndb-window-controls="expanded"]');
                const expand = controls.querySelector('[data-ndb-window-action="expand"]');
                const shrink = controls.querySelector('[data-ndb-window-action="shrink"]');

                return Number.parseFloat(getComputedStyle(expand).opacity)
                    < Number.parseFloat(getComputedStyle(shrink).opacity);
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const controls = document.querySelector('[data-ndb-window-controls="expanded"]');
                const utility = document.querySelector('[data-ndb-inspector-utility-actions]');
                const separator = document.querySelector('[data-ndb-inspector-actions] [data-ndb-window-controls-separator]');
                const utilityBox = utility.getBoundingClientRect();
                const controlsBox = controls.getBoundingClientRect();

                return utility.getAttribute('aria-label') === 'Tools'
                    && controls.getAttribute('aria-label') === 'Window controls'
                    && utilityBox.right < controlsBox.left
                    && separator === null
                    && Number.parseFloat(getComputedStyle(utility.parentElement).columnGap) > 0;
            })()
            JS)
        ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]')
        ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="close"]')
        ->assertScript(<<<'JS'
            (() => {
                window.dispatchEvent(new KeyboardEvent('keydown', {
                    key: 'P',
                    metaKey: true,
                    shiftKey: true,
                }));

                return getComputedStyle(document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]')).display === 'none'
                    && getComputedStyle(document.querySelector('[role="dialog"][aria-label="Request inspector"]')).display === 'none'
                    && getComputedStyle(document.querySelector('[role="dialog"][aria-label="Command palette"]')).display === 'none'
                    && document.querySelector('[data-testid="host-page"]').inert === false
                    && document.body.style.overflow === '';
            })()
            JS)
        ->refresh()
        ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="close"]')
        ->assertScript('getComputedStyle(document.querySelector(\'[role="toolbar"][aria-label="Debug toolbar"]\')).display === "none"')
        ->refresh()
        ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]')
        ->assertNoJavaScriptErrors();
});
it('moves the compact toolbar away from host dialogs at either screen edge', function () {
    $page = visit('/profiled')
        ->resize(1440, 900)
        ->assertAttribute('[data-ndb-toolbar-shell]', 'data-placement', 'bottom');

    $page->script(<<<'JS'
        const dialog = document.createElement('dialog');
        dialog.dataset.testid = 'host-dialog';
        dialog.setAttribute('open', '');
        Object.assign(dialog.style, {
            position: 'fixed',
            inset: 'auto 0 0 0',
            width: '100vw',
            height: '180px',
            margin: '0',
        });
        document.body.append(dialog);
        JS);

    $page
        ->assertAttribute('[data-ndb-toolbar-shell]', 'data-placement', 'top')
        ->assertScript('document.querySelector("[data-ndb-toolbar-shell]").getBoundingClientRect().top <= 13');

    $page->script(<<<'JS'
        const dialog = document.querySelector('[data-testid="host-dialog"]');
        dialog.style.inset = '0 0 auto 0';
        JS);

    $page
        ->assertAttribute('[data-ndb-toolbar-shell]', 'data-placement', 'bottom')
        ->assertScript('document.querySelector("[data-ndb-toolbar-shell]").getBoundingClientRect().bottom >= window.innerHeight - 13')
        ->assertNoJavaScriptErrors();
});

it('opens the inspector from the active toolbar anchor', function () {
    $page = visit('/profiled')->resize(1440, 900);

    $page
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->assertAttribute('[role="dialog"][aria-label="Request inspector"]', 'data-placement', 'bottom')
        ->assertScript(<<<'JS'
            (() => {
                const panel = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                const box = panel.getBoundingClientRect();
                const styles = getComputedStyle(panel);

                return Math.abs(box.bottom - window.innerHeight) <= 1
                    && box.top > 0
                    && styles.borderTopLeftRadius !== '0px'
                    && styles.borderBottomLeftRadius === '0px';
            })()
            JS)
        ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]')
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[data-ndb-toolbar-shell]');
                Alpine.$data(toolbar).pinToolbar('top');

                return true;
            })()
            JS)
        ->assertAttribute('[data-ndb-toolbar-shell]', 'data-placement', 'top')
        ->assertScript(<<<'JS'
            (() => {
                const control = document.querySelector('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');
                const panel = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                window.__ndbInspectorSamples = [];

                control.addEventListener('click', () => {
                    let remaining = 20;
                    const sample = () => {
                        if (getComputedStyle(panel).display !== 'none') {
                            const box = panel.getBoundingClientRect();
                            window.__ndbInspectorSamples.push({ top: box.top, bottom: box.bottom });
                        }

                        remaining -= 1;
                        if (remaining > 0) requestAnimationFrame(sample);
                    };

                    requestAnimationFrame(sample);
                }, { capture: true, once: true });

                return panel.dataset.placement === 'top'
                    && panel.getAttribute('x-transition:enter-start') === 'ndb-inspector-offscreen'
                    && panel.getAttribute('x-transition:leave-end') === 'ndb-inspector-offscreen';
            })()
            JS)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->assertVisible('[role="dialog"][aria-label="Request inspector"]')
        ->assertAttribute('[role="dialog"][aria-label="Request inspector"]', 'data-placement', 'top')
        ->assertScript(<<<'JS'
            (() => {
                const panel = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                const box = panel.getBoundingClientRect();
                const styles = getComputedStyle(panel);
                const samples = window.__ndbInspectorSamples ?? [];

                return Math.abs(box.top) <= 1
                    && box.bottom < window.innerHeight
                    && styles.borderTopLeftRadius === '0px'
                    && styles.borderBottomLeftRadius !== '0px'
                    && samples.length >= 8
                    && samples.some((sample) => sample.top < -20)
                    && samples.every((sample) => sample.top <= 1)
                    && samples.at(-1).top >= -2;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('drags the compact toolbar between animated persistent anchors', function () {
    $page = visit('/profiled')
        ->resize(1440, 900)
        ->assertAttribute('[data-ndb-toolbar-shell]', 'data-placement', 'bottom')
        ->assertScript(<<<'JS'
            (() => {
                const top = document.createElement('div');
                top.dataset.testid = 'toolbar-top-drop-target';
                Object.assign(top.style, {
                    position: 'fixed',
                    top: '0',
                    left: '50%',
                    width: '48px',
                    height: '48px',
                    zIndex: '1',
                });
                const bottom = top.cloneNode();
                bottom.dataset.testid = 'toolbar-bottom-drop-target';
                bottom.style.top = 'auto';
                bottom.style.bottom = '0';
                document.body.append(top, bottom);

                const toolbar = document.querySelector('[data-ndb-toolbar-shell]');
                const hint = document.getElementById(toolbar.getAttribute('aria-describedby'));

                return getComputedStyle(toolbar).transitionProperty.includes('transform')
                    && hint.textContent.includes('Drag vertically');
            })()
            JS);

    $page
        ->drag('[data-ndb-toolbar-shell]', '[data-testid="toolbar-top-drop-target"]')
        ->assertAttribute('[data-ndb-toolbar-shell]', 'data-placement', 'top')
        ->assertAttribute('[data-ndb-toolbar-shell]', 'data-preferred-placement', 'top')
        ->assertScript('document.querySelector("[data-ndb-toolbar-shell]").getBoundingClientRect().top <= 13')
        ->assertScript('document.querySelector("[data-ndb-toolbar-shell]").dataset.dragging !== "true"')
        ->assertScript('document.querySelector("[data-ndb-toolbar-shell]").dataset.snapping !== "true"')
        ->assertScript("JSON.parse(localStorage.getItem('newdebugbar.preferences.v1')).toolbarAnchor === 'top'")
        ->refresh()
        ->assertAttribute('[data-ndb-toolbar-shell]', 'data-placement', 'top')
        ->assertScript('document.querySelector("[data-ndb-toolbar-shell]").getBoundingClientRect().top <= 13');

    $page->script(<<<'JS'
        const bottom = document.createElement('div');
        bottom.dataset.testid = 'toolbar-bottom-drop-target';
        Object.assign(bottom.style, {
            position: 'fixed',
            bottom: '0',
            left: '50%',
            width: '48px',
            height: '48px',
            zIndex: '1',
        });
        document.body.append(bottom);
        JS);

    $page
        ->drag('[data-ndb-toolbar-shell]', '[data-testid="toolbar-bottom-drop-target"]')
        ->assertAttribute('[data-ndb-toolbar-shell]', 'data-placement', 'bottom')
        ->assertAttribute('[data-ndb-toolbar-shell]', 'data-preferred-placement', 'bottom')
        ->assertScript('document.querySelector("[data-ndb-toolbar-shell]").getBoundingClientRect().bottom >= window.innerHeight - 13')
        ->assertNoJavaScriptErrors();
});

it('animates from the toolbar release point to the nearest anchor', function () {
    $page = visit('/profiled')->resize(1440, 900);

    $page->script(<<<'JS'
        const target = document.createElement('div');
        target.dataset.testid = 'toolbar-middle-top-target';
        Object.assign(target.style, {
            position: 'fixed',
            top: '300px',
            left: '50%',
            width: '48px',
            height: '48px',
            zIndex: '1',
        });
        document.body.append(target);

        const toolbar = document.querySelector('[data-ndb-toolbar-shell]');
        toolbar.addEventListener('pointerup', () => {
            window.__ndbToolbarDropTop = toolbar.getBoundingClientRect().top;
            window.__ndbToolbarSnapSamples = [];
            let remaining = 36;
            const sample = () => {
                window.__ndbToolbarSnapSamples.push(toolbar.getBoundingClientRect().top);
                remaining -= 1;
                if (remaining > 0) requestAnimationFrame(sample);
            };
            requestAnimationFrame(sample);
        }, { capture: true, once: true });
        JS);

    $page
        ->drag('[data-ndb-toolbar-shell]', '[data-testid="toolbar-middle-top-target"]')
        ->assertAttribute('[data-ndb-toolbar-shell]', 'data-placement', 'top')
        ->assertScript(<<<'JS'
            (() => {
                const drop = window.__ndbToolbarDropTop;
                const samples = window.__ndbToolbarSnapSamples ?? [];

                return samples.length >= 20
                    && Math.abs(samples[0] - drop) <= 3
                    && samples.every((top) => top >= 10 && top <= drop + 3)
                    && samples.every((top, index) => index === 0 || top <= samples[index - 1] + 2)
                    && samples.at(-1) <= 13;
            })()
            JS);

    $page->script(<<<'JS'
        const target = document.createElement('div');
        target.dataset.testid = 'toolbar-middle-bottom-target';
        Object.assign(target.style, {
            position: 'fixed',
            top: '550px',
            left: '50%',
            width: '48px',
            height: '48px',
            zIndex: '1',
        });
        document.body.append(target);

        const toolbar = document.querySelector('[data-ndb-toolbar-shell]');
        toolbar.addEventListener('pointerup', () => {
            window.__ndbToolbarDropTop = toolbar.getBoundingClientRect().top;
            window.__ndbToolbarSnapSamples = [];
            let remaining = 36;
            const sample = () => {
                window.__ndbToolbarSnapSamples.push(toolbar.getBoundingClientRect().top);
                remaining -= 1;
                if (remaining > 0) requestAnimationFrame(sample);
            };
            requestAnimationFrame(sample);
        }, { capture: true, once: true });
        JS);

    $page
        ->drag('[data-ndb-toolbar-shell]', '[data-testid="toolbar-middle-bottom-target"]')
        ->assertAttribute('[data-ndb-toolbar-shell]', 'data-placement', 'bottom')
        ->assertScript(<<<'JS'
            (() => {
                const drop = window.__ndbToolbarDropTop;
                const samples = window.__ndbToolbarSnapSamples ?? [];

                return samples.length >= 20
                    && Math.abs(samples[0] - drop) <= 3
                    && samples.every((top) => top >= drop - 3 && top <= 830)
                    && samples.every((top, index) => index === 0 || top >= samples[index - 1] - 2)
                    && samples.at(-1) >= 827;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

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

    selectDebugSectionViaPalette($page, 'validation');
    assertDebugSectionSelected($page, 'validation');

    $page
        ->assertAttribute('[data-ndb-section="validation"]', 'data-ndb-section-visible', 'true')
        ->assertNoJavaScriptErrors();
});

it('prioritizes relevant activity and keeps runtime details collapsed until requested', function () {
    $page = visit('/profiled-rich')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
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
        ->click('[data-ndb-overview-runtime] > summary')
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
        ->resize(390, 844)
        ->assertVisible('[data-ndb-runtime-detail-select]')
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

it('caps the compact and expanded bars at the large breakpoint', function () {
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

                return Math.abs(box.width - 1024) <= 1
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

it('keeps keyboard focus inside the command palette', function () {
    visit('/profiled')
        ->click('[data-ndb-toolbar="palette"]')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-palette-search]")')
        ->keys('[data-ndb-palette-search]', 'Shift+Tab')
        ->assertScript('document.activeElement?.dataset.ndbCommand === "collectors:show"')
        ->keys('[data-ndb-command="collectors:show"]', 'Tab')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-palette-search]")')
        ->assertNoJavaScriptErrors();
});

it('uses translucent command palette hover colors in :dataset mode', function (string $theme) {
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
        ->click('[data-ndb-toolbar="palette"]')
        ->hover('[data-ndb-command="section:request"]')
        ->assertScript(<<<'JS'
            (() => {
                const command = document.querySelector('[data-ndb-command="section:request"]');
                const background = getComputedStyle(command).backgroundColor;
                const state = Alpine.$data(document.getElementById('newdebugbar'));
                const alpha = Number(
                    background.match(/\/\s*([\d.]+)\s*\)$/)?.[1]
                        ?? background.match(/,\s*([\d.]+)\s*\)$/)?.[1]
                        ?? 1
                );

                return state.filteredCommands[state.paletteIndex]?.id === 'section:request'
                    && alpha > 0
                    && alpha < 1;
            })()
            JS)
        ->assertNoJavaScriptErrors();
})->with(['light', 'dark']);

it('uses one metric color and balanced glass toolbar spacing', function () {
    visit('/profiled')
        ->assertScript(<<<'JS'
            getComputedStyle(document.getElementById('newdebugbar')).fontFamily.includes('Outfit Variable')
            JS)
        ->assertScript(<<<'JS'
            getComputedStyle(document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]')).borderRadius
            JS, '18px')
        ->assertScript(<<<'JS'
            getComputedStyle(document.querySelector('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')).borderRadius
            JS, '8px')
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const filter = getComputedStyle(toolbar).backdropFilter;

                return filter.includes('brightness(1.1)') && filter.includes('saturate(1.25)');
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const close = document.querySelector('[data-ndb-window-controls="compact"] [data-ndb-window-action="close"]');
                const toolbarBox = toolbar.getBoundingClientRect();
                const closeBox = close.getBoundingClientRect();
                const right = toolbarBox.right - closeBox.right;
                const top = closeBox.top - toolbarBox.top;
                const bottom = toolbarBox.bottom - closeBox.bottom;

                return Math.abs(right - top) <= 2
                    && Math.abs(top - bottom) <= 1;
            })()
            JS)
        ->assertScript('document.querySelectorAll(\'[role="toolbar"] > span\').length', 0)
        ->assertScript(<<<'JS'
            (() => {
                const metricColors = ['duration', 'memory', 'queries'].map((name) =>
                    getComputedStyle(document.querySelector(`[data-ndb-toolbar="${name}"] svg`)).color
                );
                const utilityColor = getComputedStyle(document.querySelector('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"] svg')).color;

                return new Set(metricColors).size === 1 && metricColors[0] !== utilityColor;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('uses a darker compact surface without exaggerated backdrop color', function () {
    visit('/profiled')
        ->click('[data-ndb-toolbar="palette"]')
        ->type('[data-ndb-palette-search]', 'dark theme')
        ->keys('[data-ndb-palette-search]', 'Enter')
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const style = getComputedStyle(toolbar);
                const alpha = Number(style.backgroundColor.match(/[\d.]+(?=\))$/)?.[0] ?? 1);

                return alpha >= 0.9
                    && style.backdropFilter.includes('brightness(0.75)')
                    && style.backdropFilter.includes('saturate(1)');
            })()
            JS)
        ->assertNoJavaScriptErrors();
});
