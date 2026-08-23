<?php

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
                const content = document.querySelector('[data-ndb-inspector-content]');
                const panel = document.querySelector('[data-ndb-section-panel="models"]');

                return panel.getBoundingClientRect().width <= content.clientWidth + 1
                    && content.scrollWidth <= content.clientWidth + 1;
            })()
            JS)
        ->keys('[data-ndb-model-group]:first-of-type > summary', 'Enter')
        ->assertAttribute('[data-ndb-model-group]:first-of-type', 'open', '')
        ->assertScript(<<<'JS'
            (() => {
                const content = document.querySelector('[data-ndb-inspector-content]');
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
        ->assertScript(<<<'JS'
            (() => {
                const summary = document.querySelector('[data-ndb-model-group]:first-of-type > summary');
                summary.focus();

                return document.activeElement === summary;
            })()
            JS)
        ->keys('[data-ndb-model-group]:first-of-type > summary', 'Enter')
        ->assertAttribute('[data-ndb-model-group]:first-of-type', 'open', '')
        ->assertVisible('[data-ndb-model-group]:first-of-type [data-ndb-model-changes]')
        ->assertSee('Model changes')
        ->assertSee('1 updated')
        ->assertNoJavaScriptErrors();
});
