<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('uses the shared edge-to-edge workspace and renders only the active Livewire view', function () {
    $page = visit('/profiled-livewire-nested')
        ->resize(1024, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::selectSectionViaPalette($page, 'livewire');

    $page
        ->assertVisible('[data-ndb-livewire-activity-list]')
        ->assertMissing('[data-ndb-livewire-components]')
        ->assertScript(<<<'JS'
            (() => {
                const items = Array.from(document.querySelectorAll('[data-ndb-livewire-activity-list] > li'));
                const workspace = document.querySelector('[data-ndb-livewire-workspace]');
                const list = document.querySelector('[data-ndb-livewire-list]');
                const detail = document.querySelector('[data-ndb-livewire-detail-pane]');
                const buttons = items.map((item) => item.querySelector('[data-ndb-livewire-activity-item]'));
                const mountButtons = buttons.filter((button) => button.dataset.ndbLivewireActivityKind === 'mount');
                const durationEdges = items.map((item) =>
                    item.querySelector('[data-ndb-livewire-activity-duration]').getBoundingClientRect().right,
                );
                const dots = items.map((item) =>
                    item.querySelector('[data-ndb-livewire-activity-dot]').getBoundingClientRect(),
                );
                const connectors = items.slice(0, -1).map((item) =>
                    item.querySelector('[data-ndb-livewire-activity-connector]').getBoundingClientRect(),
                );
                const workspaceStyle = getComputedStyle(workspace);
                const listStyle = getComputedStyle(list);
                const detailStyle = getComputedStyle(detail);

                return items.length > 1
                    && items.every((item) => item.querySelector('[data-ndb-livewire-activity-item]'))
                    && items.every((item) => item.hasAttribute('data-ndb-livewire-activity-timeline-item'))
                    && mountButtons.length > 0
                    && ! buttons.some((button) => button.dataset.ndbLivewireActivityKind === 'render')
                    && mountButtons.every((button) => /^\+(?:<1|\d+(?:\.\d+)?) (?:µs|ms|s)$/.test(
                        button.querySelector('[data-ndb-livewire-activity-time]').textContent.trim(),
                    ))
                    && mountButtons.every((button) => /^Render \d/.test(
                        button.querySelector('[data-ndb-livewire-activity-duration]').textContent.trim(),
                    ))
                    && durationEdges.every((edge) => Math.abs(edge - durationEdges[0]) <= 0.75)
                    && dots.every((dot) => Math.abs((dot.left + dot.width / 2) - (dots[0].left + dots[0].width / 2)) <= 0.75)
                    && connectors.every((connector, index) =>
                        Math.abs((connector.left + connector.width / 2) - (dots[index].left + dots[index].width / 2)) <= 0.75
                            && connector.bottom >= dots[index + 1].top
                    )
                    && parseFloat(workspaceStyle.borderTopWidth) === 1
                    && parseFloat(workspaceStyle.borderRightWidth) === 0
                    && parseFloat(workspaceStyle.borderBottomWidth) === 0
                    && parseFloat(workspaceStyle.borderLeftWidth) === 0
                    && listStyle.overflowY === 'auto'
                    && detailStyle.overflowY === 'auto';
            })()
            JS)
        ->assertVisible('[data-ndb-livewire-mount-facts]')
        ->assertScript(<<<'JS'
            (() => {
                const mountedAt = document.querySelector('[data-ndb-livewire-mount-time]').textContent.trim();
                const initialRender = document.querySelector('[data-ndb-livewire-initial-render-duration]').textContent.trim();

                return /^\+(?:<1|\d+(?:\.\d+)?) (?:µs|ms|s)$/.test(mountedAt)
                    && /^(?:<1|\d+(?:\.\d+)?) (?:µs|ms|s)$/.test(initialRender);
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('keeps one workspace while switching between activity and mounted components', function () {
    $page = visit('/profiled-livewire')
        ->resize(1024, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::selectSectionViaPalette($page, 'livewire');

    $page
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-livewire-workspace]');
                const list = document.querySelector('[data-ndb-livewire-list]').parentElement.getBoundingClientRect();

                window.__ndbLivewireSplitWidth = {
                    list: list.width,
                    total: workspace.getBoundingClientRect().width,
                };

                return list.width > 0;
            })()
            JS)
        ->click('[data-ndb-livewire-tab="components"]')
        ->assertMissing('[data-ndb-livewire-activity]')
        ->assertVisible('[data-ndb-livewire-components]')
        ->assertSeeIn('[data-ndb-livewire-component-list] [data-ndb-livewire-component-property-count]', '3 properties')
        ->assertSeeIn('[data-ndb-livewire-component-detail] [data-ndb-livewire-component-property-count]', '3 properties')
        ->assertSeeIn('[data-ndb-livewire-component-property-summary]', '0 changed, 2 editable')
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-livewire-workspace]');
                const list = document.querySelector('[data-ndb-livewire-list]').parentElement.getBoundingClientRect();
                const total = workspace.getBoundingClientRect().width;

                return Math.abs(list.width - window.__ndbLivewireSplitWidth.list) <= 0.75
                    && Math.abs(total - window.__ndbLivewireSplitWidth.total) <= 0.75;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('collapses component branches with an aligned disclosure control', function () {
    $page = visit('/profiled-livewire-nested')
        ->resize(1024, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::selectSectionViaPalette($page, 'livewire');

    $rootToggle = '[data-ndb-livewire-component-row][data-ndb-livewire-component-depth="0"] [data-ndb-livewire-component-toggle]';

    $page
        ->click('[data-ndb-livewire-tab="components"]')
        ->assertCount('[data-ndb-livewire-component-row]', 1)
        ->assertAttribute($rootToggle, 'aria-expanded', 'false')
        ->assertScript(<<<'JS'
            (() => {
                const row = document.querySelector('[data-ndb-livewire-component-row][data-ndb-livewire-component-depth="0"]');
                const toggle = row.querySelector('[data-ndb-livewire-component-toggle]');
                const select = row.querySelector('[data-ndb-livewire-component-select]');
                const title = row.querySelector('[data-ndb-livewire-component-title]');
                const icon = toggle.querySelector('svg');
                const toggleBox = toggle.getBoundingClientRect();
                const selectBox = select.getBoundingClientRect();
                const titleBox = title.getBoundingClientRect();
                const iconStyle = getComputedStyle(icon);

                return Math.abs(toggleBox.width - 20) <= 0.5
                    && Math.abs(toggleBox.height - 20) <= 0.5
                    && Math.abs(
                        (toggleBox.top + toggleBox.height / 2)
                        - (selectBox.top + selectBox.height / 2),
                    ) <= 1
                    && Math.abs(titleBox.left - toggleBox.right - 8) <= 1
                    && iconStyle.rotate !== 'none';
            })()
            JS)
        ->click($rootToggle)
        ->assertCount('[data-ndb-livewire-component-row]', 2)
        ->assertAttribute($rootToggle, 'aria-expanded', 'true')
        ->assertScript(<<<'JS'
            getComputedStyle(
                document.querySelector('[data-ndb-livewire-component-toggle] svg'),
            ).rotate === 'none'
            JS)
        ->click($rootToggle)
        ->assertCount('[data-ndb-livewire-component-row]', 1)
        ->assertScript(<<<'JS'
            getComputedStyle(
                document.querySelector('[data-ndb-livewire-component-toggle] svg'),
            ).rotate !== 'none'
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const detail = document.querySelector('[data-ndb-livewire-detail-pane]');
                const style = getComputedStyle(detail);

                return style.overflowY === 'auto';
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('keeps the property editor usable in a narrow dark inspector', function () {
    $page = visit('/profiled-livewire')
        ->resize(390, 844)
        ->click('[data-ndb-mobile-toolbar-trigger="actions"]')
        ->click('[data-ndb-mobile-toolbar-action="inspector"]');

    $page->script(<<<'JS'
        Alpine.$data(document.getElementById('newdebugbar')).setTheme('dark');
        JS);

    $page
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="palette"]')
        ->click('[data-ndb-command="section:livewire"]');

    $assertMobileBackInset = <<<'JS'
        (() => {
            const back = document.querySelector('[data-ndb-livewire-detail-back]');
            const detail = back.parentElement;
            const detailBox = detail.getBoundingClientRect();
            const backStyle = getComputedStyle(back);
            const iconBox = back.querySelector('svg').getBoundingClientRect();
            const headerContentBox = detail.querySelector('article > header > div').getBoundingClientRect();

            return Math.abs(parseFloat(backStyle.marginTop) - 8) <= 0.5
                && Math.abs(parseFloat(backStyle.marginLeft) - 8) <= 0.5
                && Math.abs(parseFloat(backStyle.paddingTop) - 8) <= 0.5
                && Math.abs(parseFloat(backStyle.paddingLeft) - 8) <= 0.5
                && Math.abs(iconBox.left - headerContentBox.left) <= 1
                && Math.abs(iconBox.top - (detailBox.top + 16)) <= 2;
        })()
        JS;

    $page
        ->assertAttribute('#newdebugbar', 'data-ndb-theme', 'dark')
        ->click('[data-ndb-livewire-activity-list] button')
        ->assertScript($assertMobileBackInset)
        ->click('[data-ndb-livewire-detail-back]')
        ->click('[data-ndb-livewire-tab="components"]')
        ->click('[data-ndb-livewire-component-select]')
        ->assertScript($assertMobileBackInset)
        ->click('[data-ndb-livewire-edit-key$=":count"]')
        ->assertVisible('[data-ndb-livewire-property-popover]')
        ->assertVisible('[data-ndb-livewire-edit-key$=":count"]');

    $page
        ->assertScript(<<<'JS'
            (() => {
                const popover = document.querySelector('[data-ndb-livewire-property-popover]').getBoundingClientRect();

                return popover.left >= 4
                    && popover.top >= 4
                    && popover.right <= window.innerWidth - 4
                    && popover.bottom <= window.innerHeight - 4;
            })()
            JS);

    $page
        ->keys('[data-ndb-livewire-edit-control]', 'Escape')
        ->assertMissing('[data-ndb-livewire-property-popover]')
        ->assertVisible('[data-ndb-livewire-edit-key$=":count"]')
        ->assertVisible('[data-ndb-livewire-component-detail]')
        ->assertNoJavaScriptErrors();
});

it('auto-sizes string edits and applies them with platform shortcuts', function () {
    $page = visit('/profiled-livewire-validation')
        ->resize(1024, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::selectSectionViaPalette($page, 'livewire');

    $editor = '[data-ndb-livewire-property-path="email"] [data-ndb-livewire-edit-key]';
    $control = '[data-ndb-livewire-edit-control]';

    $page
        ->click('[data-ndb-livewire-tab="components"]')
        ->click($editor)
        ->assertScript(<<<'JS'
            (() => {
                const control = document.querySelector('[data-ndb-livewire-edit-control]');

                window.__ndbLivewireTextareaHeight = control.getBoundingClientRect().height;

                return control.tagName === 'TEXTAREA'
                    && getComputedStyle(control).fieldSizing === 'content';
            })()
            JS)
        ->assertAttribute('[data-ndb-livewire-edit-apply]', 'aria-keyshortcuts', 'Meta+Enter Control+Enter')
        ->type($control, "First line\nSecond line\nThird line\nFourth line\nFifth line")
        ->assertScript(<<<'JS'
            document.querySelector('[data-ndb-livewire-edit-control]').getBoundingClientRect().height
                > window.__ndbLivewireTextareaHeight + 20
            JS)
        ->type($control, 'mac@example.test')
        ->keys($control, 'Meta+Enter')
        ->assertMissing('[data-ndb-livewire-property-popover]')
        ->click($editor)
        ->assertScript(<<<'JS'
            document.querySelector('[data-ndb-livewire-edit-control]').value === 'mac@example.test'
            JS)
        ->type($control, 'windows@example.test')
        ->keys($control, 'Control+Enter')
        ->assertMissing('[data-ndb-livewire-property-popover]')
        ->click($editor)
        ->assertScript(<<<'JS'
            document.querySelector('[data-ndb-livewire-edit-control]').value === 'windows@example.test'
            JS)
        ->keys($control, 'Escape')
        ->assertMissing('[data-ndb-livewire-property-popover]')
        ->assertNoJavaScriptErrors();
});

it('opens and applies a component property edit from its popover', function () {
    $page = visit('/profiled-livewire')
        ->resize(1024, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::waitForDetails($page);
    DebugBarBrowser::selectSectionViaPalette($page, 'livewire');

    $page
        ->click('[data-ndb-livewire-tab="components"]')
        ->assertVisible('[data-ndb-livewire-components]')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-livewire-property-empty]")).display === "none"')
        ->assertCount('[data-ndb-livewire-property-path="count"] [data-ndb-livewire-property-toggle]', 0)
        ->assertVisible('[data-ndb-livewire-property-path="settings"] [data-ndb-livewire-property-toggle]')
        ->assertScript(<<<'JS'
            (() => {
                const row = document.querySelector('[data-ndb-livewire-property-path="count"]');
                const name = row.querySelector('[data-ndb-livewire-property-name]').getBoundingClientRect();
                const label = row.querySelector('[data-ndb-livewire-property-label]').getBoundingClientRect();

                return Math.abs(label.left - name.left) <= 0.75;
            })()
            JS)
        ->assertVisible('[data-ndb-livewire-edit-key$=":count"]')
        ->click('[data-ndb-livewire-edit-key$=":count"]');

    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-livewire-property-popover]');

    $page
        ->assertVisible('[data-ndb-livewire-property-popover]')
        ->assertVisible('[data-ndb-livewire-edit-key$=":count"]')
        ->assertAttribute('[data-ndb-livewire-edit-key$=":count"]', 'aria-expanded', 'true')
        ->assertSeeIn('[data-ndb-livewire-edit-apply]', 'Apply to component')
        ->assertScript(<<<'JS'
            (() => {
                const dialog = document.querySelector('[data-ndb-livewire-property-popover]');
                const title = document.getElementById(dialog.getAttribute('aria-labelledby'));

                return title?.textContent.trim() === 'Edit count';
            })()
            JS)
        ->assertScript('document.activeElement.matches("[data-ndb-livewire-edit-control]")');

    $page->script(<<<'JS'
        window.__ndbCheckLivewireAnchorScroll = (container, axis) => {
            const afterPositioning = () => new Promise((resolve) => {
                requestAnimationFrame(() => requestAnimationFrame(resolve));
            });
            const trigger = document.querySelector('[data-ndb-livewire-edit-key][aria-expanded="true"]');
            const popover = document.querySelector('[data-ndb-livewire-property-popover]');
            const property = axis === 'x' ? 'scrollLeft' : 'scrollTop';
            const limit = axis === 'x'
                ? container.scrollWidth - container.clientWidth
                : container.scrollHeight - container.clientHeight;
            const visible = () => {
                const popoverBox = popover.getBoundingClientRect();

                return popoverBox.left >= 4
                    && popoverBox.top >= 4
                    && popoverBox.right <= window.innerWidth - 4
                    && popoverBox.bottom <= window.innerHeight - 4;
            };

            return (async () => {
                if (limit < 12 || !visible()) return false;

                const original = container[property];
                const target = original + 24 <= limit
                    ? original + 24
                    : Math.max(0, original - 24);
                if (Math.abs(target - original) < 12) return false;

                const before = trigger.getBoundingClientRect();
                container[property] = target;
                await afterPositioning();
                const after = trigger.getBoundingClientRect();
                const triggerMoved = axis === 'x'
                    ? Math.abs(after.left - before.left) >= 12
                    : Math.abs(after.top - before.top) >= 12;
                if (!triggerMoved || !visible()) return false;

                container[property] = original;
                await afterPositioning();

                return visible();
            })();
        };
        void 0;
        JS);

    $page
        ->assertScript(<<<'JS'
            function() {
                return window.__ndbCheckLivewireAnchorScroll(
                    document.querySelector('[data-ndb-livewire-property-table]'),
                    'x',
                );
            }
            JS)
        ->assertVisible('[data-ndb-livewire-property-popover]')
        ->assertVisible('[data-ndb-livewire-edit-key$=":count"]')
        ->type('[data-ndb-livewire-edit-control]', '5')
        ->click('[data-ndb-livewire-edit-apply]')
        ->assertSeeIn('[data-testid="host-counter-value"]', '5')
        ->assertMissing('[data-ndb-livewire-property-popover]');

    DebugBarBrowser::waitForStableElement($page, '[data-ndb-livewire-property-path="settings"]');

    $page->click('[data-ndb-livewire-property-path="settings"] button[aria-label^="Expand"]');

    DebugBarBrowser::waitForStableElement($page, '[data-ndb-livewire-edit-key$=":settings.enabled"]');

    $page->click('[data-ndb-livewire-edit-key$=":settings.enabled"]');

    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-livewire-property-popover]');

    $page
        ->assertVisible('[data-ndb-livewire-property-popover]')
        ->assertAttribute('[data-ndb-livewire-edit-control][role="switch"]', 'aria-checked', 'true')
        ->assertScript(<<<'JS'
            (() => {
                const control = document.querySelector('[data-ndb-livewire-edit-control][role="switch"]');
                const track = control.querySelector('[aria-hidden="true"]');
                const thumb = track.firstElementChild;
                const falseLabel = control.querySelector('[data-ndb-livewire-boolean-label="false"]');
                const trueLabel = control.querySelector('[data-ndb-livewire-boolean-label="true"]');
                const trackBox = track.getBoundingClientRect();
                const thumbBox = thumb.getBoundingClientRect();
                const falseBox = falseLabel.getBoundingClientRect();
                const trueBox = trueLabel.getBoundingClientRect();
                const controlBox = control.getBoundingClientRect();
                const controlStyle = getComputedStyle(control);
                const trackCenter = (trackBox.top + trackBox.bottom) / 2;
                const falseCenter = (falseBox.top + falseBox.bottom) / 2;
                const trueCenter = (trueBox.top + trueBox.bottom) / 2;

                return Math.abs(trackBox.width - 44) <= 1
                    && Math.abs(trackBox.height - 24) <= 1
                    && Math.abs(thumbBox.width - 20) <= 1
                    && thumbBox.left > trackBox.left + 16
                    && Math.abs(trackBox.left - falseBox.right - 12) <= 1
                    && Math.abs(trueBox.left - trackBox.right - 12) <= 1
                    && Math.abs(falseCenter - trackCenter) <= 0.75
                    && Math.abs(trueCenter - trackCenter) <= 0.75
                    && Math.abs(controlBox.height - trackBox.height) <= 1
                    && parseFloat(controlStyle.borderTopWidth) === 0
                    && parseFloat(controlStyle.paddingTop) === 0
                    && parseFloat(controlStyle.paddingBottom) === 0
                    && controlStyle.backgroundColor === 'rgba(0, 0, 0, 0)';
            })()
            JS)
        ->click('[data-ndb-livewire-edit-control][role="switch"]')
        ->assertAttribute('[data-ndb-livewire-edit-control][role="switch"]', 'aria-checked', 'false')
        ->click('[data-ndb-livewire-edit-apply]')
        ->assertMissing('[data-ndb-livewire-property-popover]')
        ->assertNoJavaScriptErrors();
});

it('fills a short desktop inspector without creating a second page scroll owner', function () {
    $page = visit('/profiled-livewire-nested')
        ->resize(1024, 650)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::selectSectionViaPalette($page, 'livewire');

    $page
        ->click('[data-ndb-livewire-tab="components"]')
        ->assertVisible('[data-ndb-livewire-detail-panel="properties"]')
        ->assertScript(<<<'JS'
            (() => {
                const content = document.querySelector('[data-ndb-inspector-content]');
                const stage = document.querySelector('[data-ndb-section-stage]');
                const sectionContent = document.querySelector('[data-ndb-section-content]');
                const loadedSection = document.querySelector('[data-ndb-loaded-section="livewire"]');
                const workspace = document.querySelector('[data-ndb-livewire-workspace]');
                const list = document.querySelector('[data-ndb-livewire-list]');
                const detail = document.querySelector('[data-ndb-livewire-detail-pane]');
                const tabs = detail.querySelector('[data-ndb-filter-tabs-variant="segmented"]');
                const loadedStyle = getComputedStyle(loadedSection);

                return getComputedStyle(content).display === 'flex'
                    && getComputedStyle(stage).display === 'flex'
                    && getComputedStyle(sectionContent).display === 'flex'
                    && workspace.getBoundingClientRect().height > 200
                    && Math.abs(
                        workspace.getBoundingClientRect().bottom
                        - loadedSection.getBoundingClientRect().bottom
                        + Number.parseFloat(loadedStyle.paddingBottom)
                    ) <= 1
                    && list.scrollHeight >= list.clientHeight
                    && getComputedStyle(list).overflowY === 'auto'
                    && getComputedStyle(detail).overflowY === 'auto'
                    && tabs.getBoundingClientRect().bottom <= detail.getBoundingClientRect().bottom;
            })()
            JS)
        ->click('[data-ndb-livewire-detail-tab="source"]')
        ->assertVisible('[data-ndb-livewire-detail-panel="source"]')
        ->assertNoJavaScriptErrors();
});

it('clears stale details when activity or component searches have no matches', function () {
    $page = visit('/profiled-livewire')
        ->resize(1024, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::selectSectionViaPalette($page, 'livewire');

    $page
        ->assertVisible('[data-ndb-livewire-activity-detail]')
        ->type('[data-ndb-livewire-search]', 'no matching activity exists')
        ->assertMissing('[data-ndb-livewire-activity-detail]')
        ->assertVisible('[data-ndb-livewire-activity-detail-empty="filter"]')
        ->assertSeeIn('[data-ndb-livewire-activity-detail-empty="filter"]', 'No activity matches this view.')
        ->click('[data-ndb-livewire-tab="components"]')
        ->assertVisible('[data-ndb-livewire-component-detail]')
        ->type('[data-ndb-livewire-search]', 'no matching component exists')
        ->assertMissing('[data-ndb-livewire-component-detail]')
        ->assertVisible('[data-ndb-livewire-component-detail-empty="filter"]')
        ->assertSeeIn('[data-ndb-livewire-component-detail-empty="filter"]', 'No components match this search.')
        ->assertNoJavaScriptErrors();
});

it('centers segmented detail tabs and instantiates only the active evidence panel', function () {
    $page = visit('/profiled-livewire')
        ->resize(1024, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::selectSectionViaPalette($page, 'livewire');

    $page
        ->assertVisible('[data-ndb-livewire-detail-panel="overview"]')
        ->assertMissing('[data-ndb-livewire-detail-panel="trace"]')
        ->assertScript(<<<'JS'
            (() => {
                const tabs = document.querySelector('[data-ndb-livewire-activity-detail] [data-ndb-filter-tabs-variant="segmented"]');
                const detail = document.querySelector('[data-ndb-livewire-detail-pane]');
                const tabsBox = tabs.getBoundingClientRect();
                const detailBox = detail.getBoundingClientRect();

                return Math.abs(
                    (tabsBox.left + tabsBox.width / 2)
                    - (detailBox.left + detailBox.width / 2),
                ) <= 0.75;
            })()
            JS)
        ->click('[data-ndb-livewire-detail-tab="trace"]')
        ->assertMissing('[data-ndb-livewire-detail-panel="overview"]')
        ->assertVisible('[data-ndb-livewire-detail-panel="trace"]');

    $page->script(<<<'JS'
        (() => {
            const state = Alpine.$data(document.getElementById('newdebugbar'));
            const selected = state.livewireSelectedActivityId;
            state.livewireTrace = {
                ...state.livewireTrace,
                activity: state.livewireTrace.activity.map((item) =>
                    item.id === selected ? { ...item, phases: [] } : item,
                ),
            };
            state.livewireDetailTab = 'overview';
        })()
        JS);

    $page
        ->assertScript(<<<'JS'
            (() => {
                const trace = document.querySelector('[data-ndb-livewire-detail-tab="trace"]');

                return trace.getClientRects().length === 0
                    && document.querySelector('[data-ndb-livewire-detail-panel="trace"]') === null
                    && document.querySelector('[data-ndb-livewire-detail-panel="overview"]') !== null;
            })()
            JS)
        ->click('[data-ndb-livewire-tab="components"]')
        ->assertVisible('[data-ndb-livewire-detail-panel="properties"]')
        ->assertMissing('[data-ndb-livewire-detail-panel="source"]')
        ->click('[data-ndb-livewire-detail-tab="source"]')
        ->assertMissing('[data-ndb-livewire-detail-panel="properties"]')
        ->assertVisible('[data-ndb-livewire-detail-panel="source"]')
        ->assertVisible('[data-ndb-language="php"]')
        ->assertScript(<<<'JS'
            (() => {
                const instance = document.querySelector('[data-ndb-livewire-component-instance]');
                const title = document.querySelector('[data-ndb-livewire-component-header] h3');

                return getComputedStyle(instance).fontFamily === getComputedStyle(title).fontFamily;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});
