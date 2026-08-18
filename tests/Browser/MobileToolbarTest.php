<?php

it('makes mobile metrics direct actions and anchors the window menu with a clean caret', function () {
    $page = visit('/profiled')
        ->resize(390, 844)
        ->assertVisible('[data-ndb-mobile-request-metrics="toolbar"]')
        ->assertCount('[data-ndb-mobile-toolbar-metric-scope="toolbar"]', 3)
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const metrics = document.querySelector('[data-ndb-mobile-request-metrics="toolbar"]');
                const buttons = Array.from(metrics.querySelectorAll('[data-ndb-mobile-toolbar-metric]'));
                const values = Array.from(metrics.querySelectorAll('[data-ndb-mobile-toolbar-summary]'));
                const labels = Array.from(metrics.querySelectorAll('[data-ndb-mobile-toolbar-metric-label]'));

                return toolbar.scrollWidth <= toolbar.clientWidth
                    && metrics.getAttribute('role') === 'group'
                    && metrics.getAttribute('aria-label') === 'Request metrics'
                    && getComputedStyle(metrics).gridTemplateColumns.split(' ').length === 3
                    && buttons.length === 3
                    && buttons.every((button) => button.getBoundingClientRect().height >= 44)
                    && buttons.every((button) => button.querySelector('svg') === null)
                    && buttons.every((button) => button.getAttribute('aria-label')?.startsWith('Open '))
                    && values.every((value) => value.getBoundingClientRect().width > 0 && value.scrollWidth <= value.clientWidth)
                    && values[0].textContent.trim() !== ''
                    && labels[1].textContent.includes('ms')
                    && labels[2].textContent.includes('MB');
            })()
            JS)
        ->click('[data-ndb-mobile-toolbar-metric-scope="toolbar"][data-ndb-mobile-toolbar-metric="queries"]')
        ->wait(0.2)
        ->assertVisible('[role="dialog"][aria-label="Request inspector"]')
        ->assertVisible('[data-ndb-section-panel="queries"]')
        ->assertScript('document.querySelector("[data-ndb-section-heading]").textContent.trim() === "Queries"')
        ->click('[data-ndb-mobile-toolbar-metric-scope="header"][data-ndb-mobile-toolbar-metric="duration"]')
        ->assertVisible('[data-ndb-section-panel="request"]')
        ->assertScript('document.querySelector("[data-ndb-section-heading]").textContent.trim() === "Request"')
        ->click('[data-ndb-mobile-toolbar-metric-scope="header"][data-ndb-mobile-toolbar-metric="memory"]')
        ->assertVisible('[data-ndb-section-panel="overview"]')
        ->assertScript('document.querySelector("[data-ndb-section-heading]").textContent.trim() === "Overview"')
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="shrink"]')
        ->wait(0.2)
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
                const paths = arrow.querySelectorAll('path');

                return arrowBox.top < surfaceBox.bottom
                    && arrowBox.bottom > surfaceBox.bottom
                    && Math.abs(arrowBox.width - 16) <= 0.5
                    && Math.abs(arrowBox.height - 8) <= 0.5
                    && Math.abs((arrowBox.left + arrowBox.width / 2) - (triggerBox.left + triggerBox.width / 2)) <= 1
                    && paths.length === 2
                    && getComputedStyle(paths[0]).fill !== 'none'
                    && getComputedStyle(paths[1]).stroke !== 'none';
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('stays compact and unclipped from narrow phones through tablets', function () {
    $page = visit('/profiled-reported-exception');

    foreach ([320, 360, 430, 768, 1023] as $width) {
        $page
            ->resize($width, 844)
            ->assertVisible('[data-ndb-mobile-request-metrics="toolbar"]')
            ->assertScript(<<<'JS'
                (() => {
                    const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                    const request = document.querySelector('[data-ndb-toolbar="request"]');
                    const metrics = document.querySelector('[data-ndb-mobile-request-metrics="toolbar"]');
                    const buttons = Array.from(metrics.querySelectorAll('[data-ndb-mobile-toolbar-metric]'));
                    const values = Array.from(metrics.querySelectorAll('[data-ndb-mobile-toolbar-summary]'));
                    const labels = Array.from(metrics.querySelectorAll('[data-ndb-mobile-toolbar-metric-label]'));
                    const actions = document.querySelector('[data-ndb-mobile-toolbar-trigger="actions"]');

                    return toolbar.scrollWidth <= toolbar.clientWidth + 1
                        && request.scrollWidth <= request.clientWidth + 1
                        && values.every((value) => value.scrollWidth <= value.clientWidth + 1)
                        && labels.every((label) => label.scrollWidth <= label.clientWidth + 1)
                        && metrics.getBoundingClientRect().width <= 384
                        && buttons.length === 3
                        && buttons.every((button) => button.getBoundingClientRect().height >= 44)
                        && buttons.every((button) => button.querySelector('svg') === null)
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
                const metrics = document.querySelector('[data-ndb-mobile-request-metrics="toolbar"]');

                return path.scrollWidth > path.clientWidth
                    && path.title === path.textContent.trim()
                    && getComputedStyle(metrics).backgroundColor === 'rgba(0, 0, 0, 0)';
            })()
            JS)
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
                const metrics = document.querySelector('[data-ndb-mobile-request-metrics="toolbar"]');
                const box = metrics.getBoundingClientRect();

                return Math.abs((window.innerWidth / 2) - (box.left + box.width / 2)) <= 1;
            })()
            JS)
        ->resize(1024, 844)
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const mobileMetrics = document.querySelector('[data-ndb-mobile-request-metrics="toolbar"]');
                const mobileActions = document.querySelector('[data-ndb-mobile-toolbar-control="actions"]');
                const desktopFacts = document.querySelector('[data-ndb-toolbar-facts]');
                const desktopActions = document.querySelector('[data-ndb-toolbar-actions]');

                return toolbar.scrollWidth <= toolbar.clientWidth + 1
                    && getComputedStyle(mobileMetrics).display === 'none'
                    && getComputedStyle(mobileActions).display === 'none'
                    && getComputedStyle(desktopFacts).display !== 'none'
                    && getComputedStyle(desktopActions).display !== 'none';
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('gives the expanded header the same direct metrics and clean window menu through tablet sizes', function () {
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
                    const buttons = Array.from(metrics.querySelectorAll('[data-ndb-mobile-toolbar-metric]'));
                    const actions = document.querySelector('[data-ndb-header-mobile-trigger="actions"]');
                    const actionStyles = getComputedStyle(actions);

                    return dialog.scrollWidth <= dialog.clientWidth + 1
                        && toolbar.scrollWidth <= toolbar.clientWidth + 1
                        && request.scrollWidth <= request.clientWidth + 1
                        && buttons.length === 3
                        && buttons.every((button) => button.getBoundingClientRect().height >= 44)
                        && buttons.every((button) => button.querySelector('svg') === null)
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
        ->click('[data-ndb-mobile-toolbar-metric-scope="header"][data-ndb-mobile-toolbar-metric="queries"]')
        ->assertVisible('[data-ndb-section-panel="queries"]')
        ->assertScript('document.querySelector("[data-ndb-section-heading]").textContent.trim() === "Queries"')
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
                    && Math.abs(arrowBox.width - 16) <= 0.5
                    && Math.abs(arrowBox.height - 8) <= 0.5
                    && Math.abs((arrowBox.left + arrowBox.width / 2) - (triggerBox.left + triggerBox.width / 2)) <= 1
                    && arrow.querySelectorAll('path').length === 2
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
                const metrics = document.querySelector('[data-ndb-mobile-request-metrics="header"]');
                const box = metrics.getBoundingClientRect();

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
