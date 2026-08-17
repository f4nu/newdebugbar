<?php

it('shows three mobile metrics and anchors both menus with arrows', function () {
    $page = visit('/profiled')
        ->resize(390, 844)
        ->assertVisible('[data-ndb-mobile-toolbar-trigger="facts"]')
        ->assertCount('[data-ndb-mobile-toolbar-metrics] [data-ndb-mobile-toolbar-summary]', 3)
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const metrics = document.querySelector('[data-ndb-mobile-toolbar-metrics]');
                const values = Array.from(metrics.querySelectorAll('[data-ndb-mobile-toolbar-summary]'));

                return toolbar.scrollWidth <= toolbar.clientWidth
                    && getComputedStyle(metrics).gridTemplateColumns.split(' ').length === 3
                    && values.every((value) => value.getBoundingClientRect().width > 0 && value.scrollWidth <= value.clientWidth)
                    && values[0].textContent.trim() !== ''
                    && values[1].textContent.includes('ms')
                    && values[2].textContent.includes('MB');
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
                const summaryMemory = document.querySelector('[data-ndb-mobile-toolbar-summary="memory"]').textContent.trim();
                const factMemory = document.querySelector('[data-ndb-mobile-toolbar-fact-value="memory"]').textContent.trim();

                return arrowBox.top < surfaceBox.bottom
                    && arrowBox.bottom > surfaceBox.bottom
                    && Math.abs((arrowBox.left + arrowBox.width / 2) - (triggerBox.left + triggerBox.width / 2)) <= 1
                    && getComputedStyle(arrow).backgroundColor === getComputedStyle(surface).backgroundColor
                    && summaryMemory === factMemory;
            })()
            JS)
        ->keys('[data-ndb-mobile-request-fact="environment"]', 'Escape')
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
