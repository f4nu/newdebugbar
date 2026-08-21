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

                return items.length > 0 && items.every((item) => {
                    const dot = item.querySelector('[data-ndb-livewire-activity-dot]').getBoundingClientRect();
                    const title = item.querySelector('[data-ndb-livewire-activity-title]').getBoundingClientRect();

                    return Math.abs((dot.top + dot.height / 2) - (title.top + title.height / 2)) <= 0.75;
                });
            })()
            JS)
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
        ->assertScript(<<<'JS'
            function() {
                const detail = document.querySelector('[data-ndb-livewire-components]').lastElementChild;
                detail.style.setProperty('max-height', '22rem', 'important');

                return window.__ndbCheckLivewireAnchorScroll(
                    detail,
                    'y',
                );
            }
            JS)
        ->assertVisible('[data-ndb-livewire-property-popover]')
        ->assertVisible('[data-ndb-livewire-edit-key$=":count"]')
        ->type('[data-ndb-livewire-edit-control]', '5')
        ->click('[data-ndb-livewire-edit-apply]')
        ->assertSeeIn('[data-testid="host-counter-value"]', '5')
        ->assertMissing('[data-ndb-livewire-property-popover]')
        ->assertNoJavaScriptErrors();
});
