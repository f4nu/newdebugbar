<?php

use NewDebugBar\Tests\ProfiledApplicationListener;

function assertDebugSectionSelected($page, string $section): void
{
    $page
        ->assertCount('#newdebugbar [data-ndb-select-section][aria-current="page"]', 1)
        ->assertAttribute("#newdebugbar [data-ndb-select-section=\"{$section}\"]", 'aria-current', 'page')
        ->assertCount('#newdebugbar [data-ndb-section-panel]:not([hidden])', 1)
        ->assertVisible("#newdebugbar [data-ndb-section-panel=\"{$section}\"]");
}

function assertFavoriteOrder($page, string $order): void
{
    $page->assertScript(<<<'JS'
        Array.from(document.querySelectorAll('#newdebugbar [data-ndb-section][data-ndb-favorite="true"]'))
            .map((section) => section.dataset.ndbSection)
            .join(',')
        JS, $order);
}

function selectDebugSectionViaPalette($page, string $section): void
{
    $page
        ->click('[data-ndb-inspector-action="palette"]')
        ->assertVisible('[role="dialog"][aria-label="Command palette"]')
        ->click('[data-ndb-command="collectors:show"]')
        ->wait(0.1)
        ->click("[data-ndb-command=\"section:{$section}\"]")
        ->wait(0.1);
}

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
            ->click($selector)
            ->wait(0.2);

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
        ->wait(0.2)
        ->assertScript(<<<'JS'
            (() => {
                const control = document.querySelector('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');
                const style = getComputedStyle(control);

                return style.backgroundColor === 'rgba(0, 0, 0, 0)'
                    && style.color !== window.ndbWindowControlColor;
            })()
            JS)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
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
        ->wait(0.2)
        ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="close"]')
        ->wait(0.2)
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
        ->wait(0.2)
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
        ->wait(0.2)
        ->assertAttribute('[data-ndb-toolbar-shell]', 'data-placement', 'top')
        ->assertScript('document.querySelector("[data-ndb-toolbar-shell]").getBoundingClientRect().top <= 13');

    $page->script(<<<'JS'
        const dialog = document.querySelector('[data-testid="host-dialog"]');
        dialog.style.inset = '0 0 auto 0';
        JS);

    $page
        ->wait(0.2)
        ->assertAttribute('[data-ndb-toolbar-shell]', 'data-placement', 'bottom')
        ->assertScript('document.querySelector("[data-ndb-toolbar-shell]").getBoundingClientRect().bottom >= window.innerHeight - 13')
        ->assertNoJavaScriptErrors();
});

it('opens the inspector from the active toolbar anchor', function () {
    $page = visit('/profiled')->resize(1440, 900);

    $page
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.25)
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
        ->wait(0.2)
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[data-ndb-toolbar-shell]');
                Alpine.$data(toolbar).pinToolbar('top');

                return true;
            })()
            JS)
        ->wait(0.6)
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
        ->wait(0.4)
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
        ->wait(0.6)
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
        ->wait(0.6)
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
        ->wait(0.8)
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
        ->wait(0.8)
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
        ->wait(0.2)
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
        ->wait(0.2)
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
        ->wait(0.2)
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

    foreach (['authorization', 'lifecycle', 'views'] as $section) {
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
                const request = document.querySelector('[data-ndb-toolbar="request"]');
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
                    && request.getBoundingClientRect().width <= 256
                    && factsStyles.flexGrow === '0'
                    && facts.getBoundingClientRect().left - request.getBoundingClientRect().right >= 32
                    && facts.getBoundingClientRect().right <= actions.getBoundingClientRect().left
                    && actions.getBoundingClientRect().left - facts.getBoundingClientRect().right <= 8
                    && JSON.stringify(factOrder) === JSON.stringify(['environment', 'queries', 'duration', 'memory']);
            })()
            JS)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->assertScript(<<<'JS'
            (() => {
                const inspector = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                const request = document.querySelector('[data-ndb-header-request]');
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
                    && request.getBoundingClientRect().width <= 256
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
        ->wait(0.2)
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
        ->wait(0.2)
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-window-controls=expanded] [data-ndb-window-action=shrink]")')
        ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]')
        ->wait(0.2)
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-window-controls=compact] [data-ndb-window-action=expand]")')
        ->assertNoJavaScriptErrors();
});

it('keeps keyboard focus inside the command palette', function () {
    visit('/profiled')
        ->click('[data-ndb-toolbar="palette"]')
        ->wait(0.2)
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

it('keeps package asset updates inside Livewire navigation', function () {
    $page = visit('/profiled');

    $page->script(<<<'JS'
        window.__newDebugBarNavigationSentinel = true;
        const stylesheet = document.querySelector('link[href*="/__newdebugbar/assets/newdebugbar.css"]');
        stylesheet.href = stylesheet.href.replace(/id=[^&]+/, 'id=stale-test-build');
        JS);

    $page
        ->click('[data-testid="host-navigation"]')
        ->waitForText('Second request')
        ->assertScript('window.__newDebugBarNavigationSentinel === true')
        ->assertCount('#newdebugbar', 1)
        ->assertNoJavaScriptErrors();
});

it('ignores background fetch profiles without switching reloading or flashing the host', function () {
    $page = visit('/profiled')
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));
                window.__newDebugBarActiveProfile = state.summary.id;
                window.__newDebugBarFetchSentinel = true;
                window.__newDebugBarDiscoveries = [];
                window.addEventListener('newdebugbar-profile-discovered', (event) => {
                    window.__newDebugBarDiscoveries.push(event.detail.profileId);
                });
                fetch('/api/plain-json?sequence=first');

                return true;
            })()
            JS)
        ->wait(0.3)
        ->assertScript(<<<'JS'
            (() => {
                fetch('/api/plain-json?sequence=second');

                return true;
            })()
            JS)
        ->wait(0.3)
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));
                const discoveries = window.__newDebugBarDiscoveries;

                return window.__newDebugBarFetchSentinel === true
                    && state.summary.id === window.__newDebugBarActiveProfile
                    && discoveries.length === 0
                    && location.pathname === '/profiled'
                    && document.querySelectorAll('#newdebugbar').length === 1;
            })()
            JS)
        ->assertVisible('[data-testid="host-page"]')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->assertMissing('[data-ndb-select-section="history"]')
        ->assertMissing('[data-ndb-section-panel="history"]')
        ->assertNoJavaScriptErrors();
});

it('keeps the bar working after host Livewire updates without a dedicated section', function () {
    visit('/profiled-livewire')
        ->assertSeeIn('[data-testid="host-counter-value"]', '0')
        ->click('[data-testid="host-counter"] button')
        ->wait(0.5)
        ->assertSeeIn('[data-testid="host-counter-value"]', '1')
        ->assertScript(<<<'JS'
            /^\/livewire-[0-9a-f]{8}\/update$/i.test(
                Alpine.$data(document.getElementById('newdebugbar')).summary.path,
            )
            JS)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->assertMissing('[data-ndb-select-section="livewire"]')
        ->assertMissing('[data-ndb-section-panel="livewire"]')
        ->assertNoJavaScriptErrors();
});

it('keeps host styles and package styles isolated', function () {
    visit('/hostile-styles')
        ->assertScript(<<<'JS'
            (() => {
                const style = getComputedStyle(document.querySelector('[data-testid="host-button"]'));

                return style.backgroundColor === 'rgb(255, 0, 0)'
                    && style.borderRadius === '0px'
                    && style.color === 'rgb(0, 128, 0)'
                    && style.height === '91px';
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const style = getComputedStyle(document.querySelector('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]'));

                return style.backgroundColor === 'rgba(0, 0, 0, 0)'
                    && style.borderRadius === '8px'
                    && style.height === '32px';
            })()
            JS)
        ->assertScript("getComputedStyle(document.getElementById('newdebugbar')).fontFamily.includes('Outfit Variable')")
        ->assertNoJavaScriptErrors();
});

it('switches every section after Livewire navigation with one active state', function () {
    $page = visit('/profiled')
        ->click('[data-testid="host-navigation"]')
        ->waitForText('Second request')
        ->assertPathIs('/profiled-next')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2);

    foreach (['request', 'timeline', 'queries', 'models', 'cache', 'views', 'events', 'logs', 'exceptions', 'overview', 'models'] as $section) {
        selectDebugSectionViaPalette($page, $section);

        assertDebugSectionSelected($page, $section);
    }

    $page->assertNoJavaScriptErrors();
});

it('filters the timeline without inventing spans for point events', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="timeline"]')
        ->wait(0.2);

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
                    && values.includes('lifecycle')
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
        ->wait(0.2)
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
        ->wait(0.2)
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
        ->wait(0.2)
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
        ->wait(0.2)
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
        ->wait(0.2)
        ->click('[data-ndb-select-section="cache"]')
        ->click('[data-ndb-section-panel="cache"] details:first-of-type summary')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-section-panel=\\"cache\\"] details pre")).borderTopColor === getComputedStyle(document.querySelector("[data-ndb-section-panel=\\"cache\\"] details")).borderTopColor')
        ->assertNoJavaScriptErrors();
});

it('shows an aligned request trace and switches request detail groups', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="request"]')
        ->assertVisible('[data-ndb-request-trace]')
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
        ->wait(0.2)
        ->click('[data-ndb-select-section="logs"]')
        ->assertSee('tests/TestCase.php')
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
        ->wait(0.2)
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

it('presents Laravel decisions lifecycle messages and source context without editor links', function () {
    $page = visit('/profiled-context')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
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
        ->click('[data-ndb-select-section="lifecycle"]')
        ->assertSee('Route matching')
        ->assertSee('Route response preparation')
        ->assertSee('Final response preparation')
        ->click('[data-ndb-select-section="messages"]')
        ->assertSee('Checkout checkpoint');

    $page
        ->click('[data-ndb-select-section="views"]')
        ->click('[data-ndb-view-group] > summary')
        ->assertSee('tests/views/context.blade.php')
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
        ->wait(0.2)
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
        ->wait(0.2)
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
        ->wait(0.2)
        ->click('[data-ndb-select-section="exceptions"]');

    assertDebugSectionSelected($page, 'exceptions');

    $page
        ->assertSee('Application frames')
        ->assertSee('Vendor frames')
        ->assertSee('tests/TestCase.php')
        ->assertDontSee('/Users/benjamin/Sites/new-debug-bar/tests/TestCase.php')
        ->assertPresent('[data-ndb-copy-exception-callsite="0"]')
        ->assertScript('document.querySelectorAll("#newdebugbar code[data-ndb-language=php][data-highlighted]").length > 0')
        ->assertNoJavaScriptErrors();
});

it('keeps favoriting active and repeatable after Livewire navigation', function () {
    $page = visit('/profiled')
        ->click('[data-testid="host-navigation"]')
        ->waitForText('Second request')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
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
        ->wait(0.2)
        ->assertAttribute($favorite, 'aria-pressed', 'true')
        ->assertNoJavaScriptErrors();
});

it('reorders favorites with the keyboard and drag and drop', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2);

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
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2);

    assertFavoriteOrder($page, 'queries,overview,request');

    $page->assertNoJavaScriptErrors();
});

it('shows the favorite source and insertion point while dragging', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2);

    foreach (['request', 'overview', 'queries'] as $section) {
        $page->click("[data-ndb-toggle-favorite=\"{$section}\"]");
    }

    $page
        ->wait(0.5)
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
        ->wait(0.6)
        ->assertAttribute('[data-ndb-toolbar-shell]', 'data-placement', 'top')
        ->refresh()
        ->assertAttribute('[data-ndb-toolbar-shell]', 'data-placement', 'top')
        ->click('[data-ndb-toolbar="palette"]')
        ->type('[data-ndb-palette-search]', 'models')
        ->keys('[data-ndb-palette-search]', 'Enter')
        ->wait(0.2);

    assertDebugSectionSelected($page, 'models');

    $page
        ->click('[data-ndb-inspector-action="palette"]')
        ->type('[data-ndb-palette-search]', 'dark theme')
        ->keys('[data-ndb-palette-search]', 'Enter')
        ->assertAttribute('#newdebugbar', 'data-theme', 'dark')
        ->refresh()
        ->assertAttribute('#newdebugbar', 'data-theme', 'dark')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
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
        ->wait(0.2)
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
        ->wait(0.25)
        ->assertScript('getComputedStyle(document.querySelector("#newdebugbar-section-navigation")).visibility === "hidden"')
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="sections"]')
        ->click('[data-ndb-select-section="queries"]')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-section-heading]")')
        ->wait(0.25)
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
        ->wait(0.25)
        ->assertScript('getComputedStyle(document.querySelector("#newdebugbar-section-navigation")).visibility === "hidden"')
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="sections"]')
        ->click('[data-ndb-mobile-sections-backdrop]')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-header-mobile-trigger=\\"actions\\"]")')
        ->wait(0.25)
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
