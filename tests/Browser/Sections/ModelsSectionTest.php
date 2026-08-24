<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('presents populated model activity as a flat aligned evidence list', function () {
    $page = visit('/profiled-models')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="models"]')
        ->assertSee('Review Eloquent retrievals, writes, repeated records, source code, and related query evidence.')
        ->assertSee('44 Eloquent activities')
        ->assertSee('Across 5 model classes.')
        ->assertSee('It does not mean database rows or queries.')
        ->assertSee('Callbacks with a shared operation ID are shown once as one write.')
        ->assertSee('Retrieved')
        ->assertSee('Writes')
        ->assertSee('Extra retrievals')
        ->assertSee('5 contexts')
        ->assertMissing('[data-ndb-model-expand-all]')
        ->assertMissing('[data-ndb-model-finding]')
        ->assertMissing('[data-ndb-model-copy-class]')
        ->assertMissing('[data-ndb-model-copy-source]')
        ->assertMissing('[data-ndb-model-lifecycle]')
        ->assertDontSee('Raw lifecycle callbacks')
        ->assertScript('document.querySelector("[data-ndb-select-section=models]").textContent.trim().endsWith("44")')
        ->assertScript(<<<'JS'
            JSON.stringify(Array.from(document.querySelectorAll('[data-ndb-model-group]'))
                .map((group) => [
                    group.dataset.ndbModelShortName,
                    group.dataset.ndbModelWrites,
                    group.dataset.ndbModelRepeats,
                    group.dataset.ndbModelRetrievals,
                    group.dataset.ndbModelRecords,
                ])) === JSON.stringify([
                    ['StudioJob', '0', '8', '14', '6'],
                    ['Client', '0', '6', '10', '4'],
                    ['ProofVersion', '0', '3', '8', '5'],
                    ['User', '0', '3', '5', '2'],
                    ['JobActivity', '0', '0', '7', '7'],
                ])
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const groups = Array.from(document.querySelectorAll('[data-ndb-model-group]'));
                const columns = Array.from(document.querySelectorAll(
                    '[data-ndb-model-retrieved-column], [data-ndb-model-write-column], [data-ndb-model-extra-column]'
                ));

                return getComputedStyle(document.querySelector('[data-ndb-model-list]')).borderTopWidth === '0px'
                    && groups.every((group) => {
                        const style = getComputedStyle(group);

                        return style.borderRadius === '0px'
                            && style.borderLeftWidth === '0px'
                            && style.borderRightWidth === '0px';
                    })
                    && columns.every((column) => {
                        const style = getComputedStyle(column);

                        return style.borderRadius === '0px'
                            && style.backgroundColor === 'rgba(0, 0, 0, 0)';
                    });
            })()
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-models] *')).every((element) =>
                Array.from(element.attributes)
                    .filter((attribute) => attribute.name.startsWith('data-'))
                    .every((attribute) => attribute.name.startsWith('data-ndb-'))
            )
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const summary = document.querySelector('[data-ndb-model-group] > summary');
                const next = document.querySelectorAll('[data-ndb-model-group] > summary')[1];

                window.newdebugbarModelRowGeometry = [summary.offsetLeft, summary.offsetWidth, next.offsetLeft, next.offsetWidth];
                summary.focus();

                return document.activeElement === summary;
            })()
            JS)
        ->keys('[data-ndb-model-group]:first-of-type > summary', 'Enter')
        ->assertAttribute('[data-ndb-model-group]:first-of-type', 'open', '')
        ->assertSee('NewDebugBar\Tests\Fixtures\Models\StudioJob')
        ->assertSee('studio_jobs')
        ->assertSee('Retrieved records')
        ->assertSee('Application sources')
        ->assertScript(<<<'JS'
            (() => {
                const summaries = document.querySelectorAll('[data-ndb-model-group] > summary');

                return JSON.stringify(window.newdebugbarModelRowGeometry) === JSON.stringify([
                    summaries[0].offsetLeft,
                    summaries[0].offsetWidth,
                    summaries[1].offsetLeft,
                    summaries[1].offsetWidth,
                ]) && document.activeElement === summaries[0]
                    && getComputedStyle(document.querySelector('[data-ndb-model-facts]')).display === 'grid';
            })()
            JS)
        ->assertScript('document.querySelectorAll("[data-ndb-model-group]:first-of-type [data-ndb-model-record]").length', 6)
        ->assertScript('document.querySelectorAll("[data-ndb-model-group]:first-of-type [data-ndb-model-record][data-ndb-model-record-retrievals]:not([data-ndb-model-record-retrievals=\"1\"])").length', 5)
        ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->assertAttribute('[data-ndb-model-group]:first-of-type', 'open', '')
        ->refresh()
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="models"]')
        ->assertVisible('[data-ndb-model-list]')
        ->assertNoJavaScriptErrors();
});

it('keeps expanded model evidence contained with one scroll owner at 390 pixels', function () {
    $page = visit('/profiled-models')
        ->resize(390, 844)
        ->click('[data-ndb-mobile-toolbar-trigger="actions"]')
        ->click('[data-ndb-mobile-toolbar-action="inspector"]')
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="palette"]')
        ->assertVisible('[data-ndb-command="section:models"]')
        ->click('[data-ndb-command="section:models"]')
        ->assertVisible('[data-ndb-section-panel="models"]')
        ->keys('[data-ndb-model-group][data-ndb-model-short-name="ProofVersion"] > summary', 'Enter')
        ->assertAttribute('[data-ndb-model-group][data-ndb-model-short-name="ProofVersion"]', 'open', '')
        ->assertSee('NewDebugBar\Tests\Fixtures\Models\ProofVersion')
        ->assertScript(<<<'JS'
            (() => {
                const content = document.querySelector('[data-ndb-inspector-content]');
                const panel = document.querySelector('[data-ndb-section-panel="models"]');
                const models = document.querySelector('[data-ndb-models]');
                const heading = document.querySelector('[data-ndb-model-list-heading]');
                const groups = Array.from(document.querySelectorAll('[data-ndb-model-group]'));
                const details = Array.from(document.querySelectorAll('[data-ndb-model-detail]'));

                return panel.getBoundingClientRect().width <= content.clientWidth + 1
                    && content.scrollWidth <= content.clientWidth + 1
                    && ['auto', 'scroll'].includes(getComputedStyle(content).overflowY)
                    && getComputedStyle(models).overflowY === 'visible'
                    && getComputedStyle(heading).display === 'none'
                    && groups.every((group) => group.scrollWidth <= group.clientWidth + 1)
                    && details.every((detail) => getComputedStyle(detail).overflowY === 'visible');
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('folds lifecycle callbacks into neutral write evidence with redacted changes', function () {
    $page = visit('/profiled-models?changes=1')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::waitForDetails($page);

    $page
        ->click('[data-ndb-select-section="models"]')
        ->assertSee('7 other lifecycle callbacks stay outside the activity total.')
        ->assertScript('document.querySelector("[data-ndb-select-section=models]").textContent.trim().endsWith("48")')
        ->assertScript(<<<'JS'
            JSON.stringify(Array.from(document.querySelectorAll('[data-ndb-model-group]'))
                .map((group) => [group.dataset.ndbModelShortName, group.dataset.ndbModelWrites]))
                === JSON.stringify([
                    ['Client', '1'],
                    ['ProofVersion', '1'],
                    ['User', '1'],
                    ['JobActivity', '1'],
                    ['StudioJob', '0'],
                ])
            JS)
        ->keys('[data-ndb-model-group][data-ndb-model-short-name="Client"] > summary', 'Enter')
        ->assertAttribute('[data-ndb-model-group][data-ndb-model-short-name="Client"]', 'open', '')
        ->assertScript('document.querySelectorAll("[data-ndb-model-group][data-ndb-model-short-name=\"Client\"] [data-ndb-model-operation]").length', 1)
        ->assertSeeIn('[data-ndb-model-group][data-ndb-model-short-name="Client"] [data-ndb-model-operation]', 'Updated')
        ->assertSeeIn('[data-ndb-model-group][data-ndb-model-short-name="Client"] [data-ndb-model-operation]', 'id 4')
        ->assertSeeIn('[data-ndb-model-group][data-ndb-model-short-name="Client"] [data-ndb-model-operation]', '3 callbacks, folded')
        ->assertScript(<<<'JS'
            (() => {
                const group = document.querySelector('[data-ndb-model-group][data-ndb-model-short-name="Client"]');
                const write = group.querySelector('[data-ndb-model-write-column]');
                const extra = group.querySelector('[data-ndb-model-extra-column]');
                const operation = group.querySelector('[data-ndb-model-operation]');

                return !write.className.includes('amber')
                    && extra.className.includes('amber')
                    && !operation.className.includes('amber')
                    && getComputedStyle(operation).borderRadius === '0px';
            })()
            JS)
        ->keys('[data-ndb-model-group][data-ndb-model-short-name="Client"] [data-ndb-model-operation-changes] > summary', 'Enter')
        ->assertAttribute('[data-ndb-model-group][data-ndb-model-short-name="Client"] [data-ndb-model-operation-changes]', 'open', '')
        ->assertSee('status')
        ->assertSee("'approved'")
        ->assertSee('api_token')
        ->assertSee("'[redacted]'")
        ->assertDontSee('private-token')
        ->assertDontSee('updated-private-token')
        ->assertMissing('[data-ndb-model-copy-operation-source]')
        ->assertDontSee('Raw lifecycle callbacks')
        ->keys('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"] > summary', 'Enter')
        ->assertSeeIn('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"] [data-ndb-model-operation]', 'Trashed')
        ->assertSeeIn('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"] [data-ndb-model-operation]', '3 callbacks, folded')
        ->assertNoJavaScriptErrors();
});

it('bounds large record lists and handles unavailable identifiers quietly', function () {
    visit('/profiled-models?large=1&missing=1')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="models"]')
        ->keys('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"] > summary', 'Enter')
        ->assertScript('document.querySelectorAll("[data-ndb-model-group][data-ndb-model-short-name=\"JobActivity\"] [data-ndb-model-record]").length', 25)
        ->assertSeeIn('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"] [data-ndb-model-record-limit]', 'Showing 25 of 40 identified records.')
        ->keys('[data-ndb-model-group][data-ndb-model-short-name="ProfiledModel"] > summary', 'Enter')
        ->assertVisible('[data-ndb-model-group][data-ndb-model-short-name="ProfiledModel"] [data-ndb-model-missing-identifiers]')
        ->assertSee('A dash means the model identifier was unavailable.')
        ->assertSee('These retrievals are excluded from the extra-retrieval count.')
        ->assertNoJavaScriptErrors();
});

it('filters Queries to exact-source correlation without claiming causation', function () {
    visit('/profiled-models?queries=1')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="models"]')
        ->keys('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"] > summary', 'Enter')
        ->assertVisible('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"] [data-ndb-model-query-evidence]')
        ->assertSee('Related queries share the exact file and line.')
        ->assertSee('it does not prove that a query hydrated or changed the model.')
        ->assertScript('document.querySelectorAll("[data-ndb-model-group][data-ndb-model-short-name=\"JobActivity\"] [data-ndb-model-query-guidance]").length', 1)
        ->click('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"] [data-ndb-model-view-queries]')
        ->assertVisible('[data-ndb-section-panel="queries"]')
        ->assertScript(<<<'JS'
            (() => {
                const input = document.querySelector('[data-ndb-query-search]');
                const visible = Array.from(document.querySelectorAll('[data-ndb-query-item], [data-ndb-query-group]'))
                    .filter((result) => !result.hidden);

                return input.value.includes('tests/Support/DefinesTestApplication.php:')
                    && visible.length === 1
                    && visible[0].dataset.search.includes(input.value.toLowerCase())
                    && document.querySelector('[data-ndb-query-result-label]').textContent.includes('1');
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('shows original Blade and exact compiled source evidence', function () {
    visit('/profiled-models?compiled=1')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="models"]')
        ->keys('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"] > summary', 'Enter')
        ->assertVisible('[data-ndb-model-compiled-source]')
        ->assertSee('Blade template')
        ->assertSee('tests/Fixtures/views/model-compiled.blade.php')
        ->assertSee('Compiled location')
        ->assertSee('storage/framework/views/')
        ->assertVisible('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"] [data-ndb-model-query-evidence]')
        ->assertScript(<<<'JS'
            (() => {
                const source = document.querySelector('[data-ndb-model-compiled-source]').closest('[data-ndb-model-source]');

                return source.scrollWidth <= source.clientWidth + 1
                    && source.querySelectorAll('[data-ndb-model-view-queries]').length === 1;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('shows a useful empty model state', function () {
    $page = visit('/profiled-models-empty')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::selectSectionViaPalette($page, 'models');

    $page
        ->assertVisible('[data-ndb-section-panel="models"]')
        ->assertSee('No Eloquent model activity was captured for this request.')
        ->assertMissing('[data-ndb-model-summary]')
        ->assertMissing('[data-ndb-model-group]')
        ->assertNoJavaScriptErrors();
});
