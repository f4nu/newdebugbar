<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('centers activity dots with their labels', function () {
    $page = visit('/profiled-livewire')
        ->resize(1024, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::selectSectionViaPalette($page, 'livewire');

    $page
        ->assertVisible('[data-ndb-livewire-activity-list]')
        ->assertScript(<<<'JS'
            (() => {
                const items = Array.from(document.querySelectorAll('[data-ndb-livewire-activity-list] > li'));
                const detail = document.querySelector('[data-ndb-livewire-activity]').lastElementChild;
                const detailStyle = getComputedStyle(detail);

                return items.length > 0 && items.every((item) => {
                    const dot = item.querySelector('[data-ndb-livewire-activity-dot]').getBoundingClientRect();
                    const title = item.querySelector('[data-ndb-livewire-activity-title]').getBoundingClientRect();

                    return Math.abs((dot.top + dot.height / 2) - (title.top + title.height / 2)) <= 0.75;
                })
                    && detailStyle.position === 'sticky'
                    && detailStyle.overflowX === 'clip'
                    && detailStyle.overflowY === 'visible';
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('collapses component branches with an aligned plus and minus control', function () {
    $page = visit('/profiled-livewire-nested')
        ->resize(1024, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::selectSectionViaPalette($page, 'livewire');

    $rootToggle = '[data-ndb-livewire-component-row][data-ndb-livewire-component-depth="0"] [data-ndb-livewire-component-toggle]';

    $page
        ->click('[data-ndb-livewire-tab="components"]')
        ->assertCount('[data-ndb-livewire-component-row]', 2)
        ->assertAttribute($rootToggle, 'aria-expanded', 'true')
        ->assertScript(<<<'JS'
            (() => {
                const row = document.querySelector('[data-ndb-livewire-component-row][data-ndb-livewire-component-depth="0"]');
                const toggle = row.querySelector('[data-ndb-livewire-component-toggle]');
                const vertical = toggle.querySelector('[data-ndb-livewire-component-toggle-vertical]');
                const dot = row.querySelector('[data-ndb-livewire-component-dot]');
                const title = row.querySelector('[data-ndb-livewire-component-title]');
                const toggleBox = toggle.getBoundingClientRect();
                const dotBox = dot.getBoundingClientRect();
                const titleBox = title.getBoundingClientRect();
                const style = getComputedStyle(toggle);

                return Math.abs(toggleBox.width - 16) <= 0.5
                    && Math.abs(toggleBox.height - 16) <= 0.5
                    && parseFloat(style.borderRadius) <= 2.5
                    && Math.abs(
                        (toggleBox.top + toggleBox.height / 2)
                        - (dotBox.top + dotBox.height / 2),
                    ) <= 0.75
                    && Math.abs(dotBox.left - toggleBox.right - 8) <= 0.75
                    && Math.abs(titleBox.left - dotBox.right - 8) <= 0.75
                    && getComputedStyle(vertical).display === 'none';
            })()
            JS)
        ->click($rootToggle)
        ->assertCount('[data-ndb-livewire-component-row]', 1)
        ->assertAttribute($rootToggle, 'aria-expanded', 'false')
        ->assertScript(<<<'JS'
            getComputedStyle(
                document.querySelector('[data-ndb-livewire-component-toggle-vertical]'),
            ).display !== 'none'
            JS)
        ->click($rootToggle)
        ->assertCount('[data-ndb-livewire-component-row]', 2)
        ->assertScript(<<<'JS'
            getComputedStyle(
                document.querySelector('[data-ndb-livewire-component-toggle-vertical]'),
            ).display === 'none'
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const detail = document.querySelector('[data-ndb-livewire-components]').lastElementChild;
                const style = getComputedStyle(detail);

                return style.position === 'sticky'
                    && style.overflowX === 'clip'
                    && style.overflowY === 'visible';
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
            const back = Array.from(document.querySelectorAll('[data-ndb-livewire-detail-back]'))
                .find((element) => element.getClientRects().length > 0);
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
        ->assertAttribute('#newdebugbar', 'data-theme', 'dark')
        ->click('[data-ndb-livewire-activity-list] button')
        ->assertScript($assertMobileBackInset)
        ->click('[data-ndb-livewire-detail-back="activity"]')
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
        ->assertNoJavaScriptErrors();
});

it('opens and applies a component property edit from its popover', function () {
    $page = visit('/profiled-livewire')
        ->resize(1024, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::selectSectionViaPalette($page, 'livewire');

    $page
        ->click('[data-ndb-livewire-tab="components"]')
        ->assertVisible('[data-ndb-livewire-components]')
        ->assertVisible('[data-ndb-livewire-edit-key$=":count"]')
        ->click('[data-ndb-livewire-edit-key$=":count"]')
        ->assertVisible('[data-ndb-livewire-property-popover]')
        ->assertVisible('[data-ndb-livewire-edit-key$=":count"]')
        ->assertAttribute('[data-ndb-livewire-edit-key$=":count"]', 'aria-expanded', 'true')
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
            const linked = () => {
                const triggerBox = trigger.getBoundingClientRect();
                const popoverBox = popover.getBoundingClientRect();
                const aligned = Math.abs(popoverBox.right - triggerBox.right) <= 3;
                const below = Math.abs(popoverBox.top - triggerBox.bottom - 12) <= 3;
                const above = Math.abs(triggerBox.top - popoverBox.bottom - 12) <= 3;

                return aligned && (below || above);
            };

            return (async () => {
                if (limit < 12 || !linked()) return false;

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
                if (!triggerMoved || !linked()) return false;

                container[property] = original;
                await afterPositioning();

                return linked();
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
        ->assertMissing('[data-ndb-livewire-property-popover]')
        ->click('[data-ndb-livewire-property-path="settings"] button[aria-label^="Expand"]')
        ->click('[data-ndb-livewire-edit-key$=":settings.enabled"]')
        ->assertVisible('[data-ndb-livewire-property-popover]')
        ->assertAttribute('[data-ndb-livewire-edit-control][role="switch"]', 'aria-checked', 'true')
        ->assertScript(<<<'JS'
            (() => {
                const control = document.querySelector('[data-ndb-livewire-edit-control][role="switch"]');
                const track = control.querySelector('[aria-hidden="true"]');
                const thumb = track.firstElementChild;
                const trackBox = track.getBoundingClientRect();
                const thumbBox = thumb.getBoundingClientRect();

                return Math.abs(trackBox.width - 44) <= 1
                    && Math.abs(trackBox.height - 24) <= 1
                    && Math.abs(thumbBox.width - 20) <= 1
                    && thumbBox.left > trackBox.left + 16;
            })()
            JS)
        ->click('[data-ndb-livewire-edit-control][role="switch"]')
        ->assertAttribute('[data-ndb-livewire-edit-control][role="switch"]', 'aria-checked', 'false')
        ->click('[data-ndb-livewire-edit-apply]')
        ->assertMissing('[data-ndb-livewire-property-popover]')
        ->assertNoJavaScriptErrors();
});
