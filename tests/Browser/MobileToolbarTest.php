<?php

it('shows three mobile metrics and anchors both menus with arrows', function () {
    $page = visit('/profiled')
        ->resize(390, 844)
        ->assertVisible('[data-ndb-mobile-toolbar-trigger="facts"]')
        ->assertCount('[data-ndb-mobile-request-metrics="toolbar"] [data-ndb-mobile-toolbar-summary]', 3)
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const facts = document.querySelector('[data-ndb-mobile-toolbar-trigger="facts"]');
                const metrics = document.querySelector('[data-ndb-mobile-request-metrics="toolbar"]');
                const values = Array.from(metrics.querySelectorAll('[data-ndb-mobile-toolbar-summary]'));
                const labels = Array.from(metrics.querySelectorAll('[data-ndb-mobile-toolbar-metric-label]'));

                return toolbar.scrollWidth <= toolbar.clientWidth
                    && facts.querySelector('svg') === null
                    && getComputedStyle(metrics).gridTemplateColumns.split(' ').length === 3
                    && values.every((value) => value.getBoundingClientRect().width > 0 && value.scrollWidth <= value.clientWidth)
                    && values[0].textContent.trim() !== ''
                    && labels[1].textContent.includes('ms')
                    && labels[2].textContent.includes('MB');
            })()
            JS)
        ->click('[data-ndb-mobile-toolbar-trigger="facts"]')
        ->assertVisible('[data-ndb-mobile-toolbar-menu="facts"]')
        ->assertVisible('[data-ndb-mobile-toolbar-popover-arrow="facts"]')
        ->assertScript(<<<'JS'
            (() => {
                const trigger = document.querySelector('[data-ndb-mobile-toolbar-trigger="facts"]');
                const menu = document.querySelector('[data-ndb-mobile-toolbar-menu="facts"]');
                const surface = menu.querySelector('[data-ndb-mobile-toolbar-popover-surface]');
                const arrow = menu.querySelector('[data-ndb-mobile-toolbar-popover-arrow="facts"]');
                const triggerBox = trigger.getBoundingClientRect();
                const surfaceBox = surface.getBoundingClientRect();
                const arrowBox = arrow.getBoundingClientRect();
                const summaryMemory = document.querySelector('[data-ndb-mobile-request-metrics="toolbar"] [data-ndb-mobile-toolbar-summary="memory"]').textContent.trim();
                const factMemory = document.querySelector('[data-ndb-mobile-request-fact-scope="toolbar"] [data-ndb-mobile-toolbar-fact-value="memory"]').textContent.trim();

                return arrowBox.top < surfaceBox.bottom
                    && arrowBox.bottom > surfaceBox.bottom
                    && Math.abs((arrowBox.left + arrowBox.width / 2) - (triggerBox.left + triggerBox.width / 2)) <= 1
                    && getComputedStyle(arrow).backgroundColor === getComputedStyle(surface).backgroundColor
                    && Number.parseFloat(summaryMemory) === Number.parseFloat(factMemory);
            })()
            JS)
        ->keys('[data-ndb-mobile-request-fact-scope="toolbar"][data-ndb-mobile-request-fact="environment"]', 'Escape')
        ->click('[data-ndb-mobile-toolbar-trigger="actions"]')
        ->assertVisible('[data-ndb-mobile-toolbar-menu="actions"]')
        ->assertVisible('[data-ndb-mobile-toolbar-popover-arrow="actions"]')
        ->assertScript(<<<'JS'
            (() => {
                const trigger = document.querySelector('[data-ndb-mobile-toolbar-trigger="actions"]');
                const menu = document.querySelector('[data-ndb-mobile-toolbar-menu="actions"]');
                const surface = menu.querySelector('[data-ndb-mobile-toolbar-popover-surface]');
                const arrow = menu.querySelector('[data-ndb-mobile-toolbar-popover-arrow="actions"]');
                const triggerBox = trigger.getBoundingClientRect();
                const surfaceBox = surface.getBoundingClientRect();
                const arrowBox = arrow.getBoundingClientRect();

                return arrowBox.top < surfaceBox.bottom
                    && arrowBox.bottom > surfaceBox.bottom
                    && Math.abs((arrowBox.left + arrowBox.width / 2) - (triggerBox.left + triggerBox.width / 2)) <= 1
                    && getComputedStyle(arrow).backgroundColor === getComputedStyle(surface).backgroundColor;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('stays compact and unclipped from narrow phones through tablets', function () {
    $page = visit('/profiled-reported-exception');

    foreach ([320, 360, 430, 768, 1023] as $width) {
        $page
            ->resize($width, 844)
            ->assertVisible('[data-ndb-mobile-toolbar-trigger="facts"]')
            ->assertScript(<<<'JS'
                (() => {
                    const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                    const request = document.querySelector('[data-ndb-toolbar="request"]');
                    const facts = document.querySelector('[data-ndb-mobile-toolbar-trigger="facts"]');
                    const values = Array.from(facts.querySelectorAll('[data-ndb-mobile-toolbar-summary]'));
                    const labels = Array.from(facts.querySelectorAll('[data-ndb-mobile-toolbar-metric-label]'));
                    const actions = document.querySelector('[data-ndb-mobile-toolbar-trigger="actions"]');

                    return toolbar.scrollWidth <= toolbar.clientWidth + 1
                        && request.scrollWidth <= request.clientWidth + 1
                        && values.every((value) => value.scrollWidth <= value.clientWidth + 1)
                        && labels.every((label) => label.scrollWidth <= label.clientWidth + 1)
                        && facts.getBoundingClientRect().width <= 384
                        && facts.getBoundingClientRect().height >= 44
                        && facts.querySelector('svg') === null
                        && actions.getBoundingClientRect().width >= 44
                        && actions.getBoundingClientRect().height >= 44;
                })()
                JS);
    }

    $page
        ->resize(320, 844)
        ->assertScript(<<<'JS'
            (() => {
                const path = document.querySelector('[data-ndb-toolbar-request-path]');
                const facts = document.querySelector('[data-ndb-mobile-toolbar-trigger="facts"]');

                return path.scrollWidth > path.clientWidth
                    && path.title === path.textContent.trim()
                    && getComputedStyle(facts).backgroundColor === 'rgba(0, 0, 0, 0)';
            })()
            JS)
        ->click('[data-ndb-mobile-toolbar-trigger="facts"]')
        ->assertVisible('[data-ndb-mobile-toolbar-menu="facts"]')
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const menu = document.querySelector('[data-ndb-mobile-toolbar-menu="facts"]');
                const box = menu.getBoundingClientRect();

                return toolbar.scrollWidth <= toolbar.clientWidth + 1
                    && box.left >= 12
                    && box.right <= window.innerWidth - 12;
            })()
            JS)
        ->keys('[data-ndb-mobile-request-fact-scope="toolbar"][data-ndb-mobile-request-fact="environment"]', 'Escape')
        ->click('[data-ndb-mobile-toolbar-trigger="actions"]')
        ->assertVisible('[data-ndb-mobile-toolbar-menu="actions"]')
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const menu = document.querySelector('[data-ndb-mobile-toolbar-menu="actions"]');
                const box = menu.getBoundingClientRect();

                return toolbar.scrollWidth <= toolbar.clientWidth + 1
                    && box.left >= 12
                    && box.right <= window.innerWidth - 12;
            })()
            JS)
        ->keys('[data-ndb-mobile-toolbar-action="palette"]', 'Escape')
        ->resize(768, 844)
        ->assertScript(<<<'JS'
            (() => {
                const control = document.querySelector('[data-ndb-mobile-toolbar-control="facts"]');
                const facts = document.querySelector('[data-ndb-mobile-toolbar-trigger="facts"]');
                const controlBox = control.getBoundingClientRect();
                const factsBox = facts.getBoundingClientRect();

                return Math.abs((window.innerWidth / 2) - (factsBox.left + factsBox.width / 2)) <= 1
                    && Math.abs(
                        (controlBox.left + controlBox.width / 2) - (factsBox.left + factsBox.width / 2),
                    ) <= 1;
            })()
            JS)
        ->resize(1024, 844)
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const mobileFacts = document.querySelector('[data-ndb-mobile-toolbar-control="facts"]');
                const mobileActions = document.querySelector('[data-ndb-mobile-toolbar-control="actions"]');
                const desktopFacts = document.querySelector('[data-ndb-toolbar-facts]');
                const desktopActions = document.querySelector('[data-ndb-toolbar-actions]');

                return toolbar.scrollWidth <= toolbar.clientWidth + 1
                    && getComputedStyle(mobileFacts).display === 'none'
                    && getComputedStyle(mobileActions).display === 'none'
                    && getComputedStyle(desktopFacts).display !== 'none'
                    && getComputedStyle(desktopActions).display !== 'none';
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('gives the expanded header the same compact treatment through tablet sizes', function () {
    $page = visit('/profiled-reported-exception')
        ->resize(390, 844)
        ->click('[data-ndb-mobile-toolbar-trigger="actions"]')
        ->click('[data-ndb-mobile-toolbar-action="inspector"]')
        ->wait(0.2);

    foreach ([320, 360, 430, 768, 1023] as $width) {
        $page
            ->resize($width, 844)
            ->assertVisible('[data-ndb-header-mobile-toolbar]')
            ->assertScript(<<<'JS'
                (() => {
                    const dialog = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                    const toolbar = document.querySelector('[data-ndb-header-mobile-toolbar]');
                    const request = document.querySelector('[data-ndb-header-mobile-request]');
                    const metrics = document.querySelector('[data-ndb-mobile-request-metrics="header"]');
                    const facts = document.querySelector('[data-ndb-header-mobile-trigger="facts"]');
                    const actions = document.querySelector('[data-ndb-header-mobile-trigger="actions"]');
                    const actionStyles = getComputedStyle(actions);

                    return dialog.scrollWidth <= dialog.clientWidth + 1
                        && toolbar.scrollWidth <= toolbar.clientWidth + 1
                        && request.scrollWidth <= request.clientWidth + 1
                        && metrics.querySelectorAll('[data-ndb-mobile-toolbar-summary]').length === 3
                        && facts.querySelector('svg') === null
                        && facts.getBoundingClientRect().height >= 44
                        && actions.getBoundingClientRect().width >= 44
                        && actions.getBoundingClientRect().height >= 44
                        && actions.querySelectorAll('svg').length === 1
                        && Number.parseFloat(actionStyles.borderTopWidth) === 0
                        && actionStyles.boxShadow === 'none'
                        && actionStyles.backgroundColor === 'rgba(0, 0, 0, 0)';
                })()
                JS);
    }

    $page
        ->resize(320, 844)
        ->assertScript(<<<'JS'
            (() => {
                const path = document.querySelector('[data-ndb-header-mobile-request-path]');

                return path.scrollWidth > path.clientWidth
                    && path.title === path.textContent.trim();
            })()
            JS)
        ->click('[data-ndb-header-mobile-trigger="facts"]')
        ->assertVisible('[data-ndb-mobile-toolbar-menu="header-facts"]')
        ->assertVisible('[data-ndb-mobile-toolbar-popover-arrow="header-facts"]')
        ->assertScript(<<<'JS'
            (() => {
                const trigger = document.querySelector('[data-ndb-header-mobile-trigger="facts"]');
                const menu = document.querySelector('[data-ndb-mobile-toolbar-menu="header-facts"]');
                const surface = menu.querySelector('[data-ndb-mobile-toolbar-popover-surface]');
                const arrow = menu.querySelector('[data-ndb-mobile-toolbar-popover-arrow="header-facts"]');
                const items = Array.from(menu.querySelectorAll('[role="menuitem"]'));
                const triggerBox = trigger.getBoundingClientRect();
                const surfaceBox = surface.getBoundingClientRect();
                const arrowBox = arrow.getBoundingClientRect();

                return menu.querySelector('h1, h2, h3, [role="heading"]') === null
                    && !menu.textContent.includes('Request facts')
                    && surfaceBox.left >= 6
                    && surfaceBox.right <= window.innerWidth - 6
                    && surfaceBox.top > triggerBox.bottom
                    && arrowBox.top < surfaceBox.top
                    && arrowBox.bottom > surfaceBox.top
                    && Math.abs((arrowBox.left + arrowBox.width / 2) - (triggerBox.left + triggerBox.width / 2)) <= 1
                    && items.length === 4
                    && items.every((item) => item.getBoundingClientRect().height >= 44)
                    && document.activeElement === items[0];
            })()
            JS)
        ->keys('[data-ndb-mobile-request-fact-scope="header"][data-ndb-mobile-request-fact="environment"]', 'Escape')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-header-mobile-trigger=\\"facts\\"]")')
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->assertVisible('[data-ndb-mobile-toolbar-menu="header-actions"]')
        ->assertVisible('[data-ndb-mobile-toolbar-popover-arrow="header-actions"]')
        ->assertScript(<<<'JS'
            (() => {
                const trigger = document.querySelector('[data-ndb-header-mobile-trigger="actions"]');
                const menu = document.querySelector('[data-ndb-mobile-toolbar-menu="header-actions"]');
                const surface = menu.querySelector('[data-ndb-mobile-toolbar-popover-surface]');
                const arrow = menu.querySelector('[data-ndb-mobile-toolbar-popover-arrow="header-actions"]');
                const visibleItems = Array.from(menu.querySelectorAll('[role="menuitem"]'))
                    .filter((item) => item.getClientRects().length > 0);
                const triggerBox = trigger.getBoundingClientRect();
                const surfaceBox = surface.getBoundingClientRect();
                const arrowBox = arrow.getBoundingClientRect();

                return menu.querySelector('h1, h2, h3, [role="heading"]') === null
                    && !menu.textContent.includes('Inspector actions')
                    && surfaceBox.left >= 6
                    && surfaceBox.right <= window.innerWidth - 6
                    && surfaceBox.top > triggerBox.bottom
                    && arrowBox.top < surfaceBox.top
                    && arrowBox.bottom > surfaceBox.top
                    && Math.abs((arrowBox.left + arrowBox.width / 2) - (triggerBox.left + triggerBox.width / 2)) <= 1
                    && visibleItems.length === 5
                    && visibleItems.every((item) => item.getBoundingClientRect().height >= 44)
                    && document.activeElement === visibleItems[0];
            })()
            JS)
        ->click('[data-ndb-header-mobile-action="sections"]')
        ->assertVisible('#newdebugbar-section-navigation')
        ->keys('#newdebugbar-section-navigation [data-ndb-select-section][aria-current="page"]', 'Escape')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-header-mobile-trigger=\\"actions\\"]")')
        ->resize(768, 844)
        ->assertScript(<<<'JS'
            (() => {
                const facts = document.querySelector('[data-ndb-header-mobile-trigger="facts"]');
                const box = facts.getBoundingClientRect();

                return Math.abs((window.innerWidth / 2) - (box.left + box.width / 2)) <= 1;
            })()
            JS)
        ->resize(1024, 844)
        ->assertScript(<<<'JS'
            getComputedStyle(document.querySelector('[data-ndb-header-mobile-toolbar]')).display === 'none'
                && getComputedStyle(document.querySelector('[data-ndb-header-toolbar]')).display !== 'none'
            JS)
        ->assertNoJavaScriptErrors();
});
