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
                const labels = Array.from(metrics.querySelectorAll('[data-ndb-mobile-toolbar-metric-label]'));

                return toolbar.scrollWidth <= toolbar.clientWidth
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
                const summaryMemory = document.querySelector('[data-ndb-mobile-toolbar-summary="memory"]').textContent.trim();
                const factMemory = document.querySelector('[data-ndb-mobile-toolbar-fact-value="memory"]').textContent.trim();

                return arrowBox.top < surfaceBox.bottom
                    && arrowBox.bottom > surfaceBox.bottom
                    && Math.abs((arrowBox.left + arrowBox.width / 2) - (triggerBox.left + triggerBox.width / 2)) <= 1
                    && getComputedStyle(arrow).backgroundColor === getComputedStyle(surface).backgroundColor
                    && Number.parseFloat(summaryMemory) === Number.parseFloat(factMemory);
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
        ->keys('[data-ndb-mobile-request-fact="environment"]', 'Escape')
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

                return Math.abs(
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
