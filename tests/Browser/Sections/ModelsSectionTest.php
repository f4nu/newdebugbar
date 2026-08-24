<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('explains populated model activity with progressive evidence', function () {
    $page = visit('/profiled-models')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="models"]')
        ->assertSee('See which Eloquent models were retrieved or changed, where the activity started, and which records may deserve a closer look.')
        ->assertSee('Retrieved instances count Eloquent retrieved events.')
        ->assertSee('They are not query counts or database row counts.')
        ->assertSee('Model classes')
        ->assertSee('Retrieved instances')
        ->assertSee('Write operations')
        ->assertSee('Extra retrievals')
        ->assertScript('document.querySelector("[data-ndb-select-section=models]").textContent.trim().endsWith("44")')
        ->assertMissing('[data-ndb-model-expand-all]')
        ->assertMissing('[data-ndb-model-finding]')
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
            Array.from(document.querySelectorAll('[data-ndb-models] *')).every((element) =>
                Array.from(element.attributes)
                    .filter((attribute) => attribute.name.startsWith('data-'))
                    .every((attribute) => attribute.name.startsWith('data-ndb-'))
            )
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const summary = document.querySelector('[data-ndb-model-group] > summary');
                summary.focus();

                return document.activeElement === summary;
            })()
            JS)
        ->keys('[data-ndb-model-group]:first-of-type > summary', 'Enter')
        ->assertAttribute('[data-ndb-model-group]:first-of-type', 'open', '')
        ->assertSee('NewDebugBar\\Tests\\Fixtures\\Models\\StudioJob')
        ->assertSee('studio_jobs')
        ->assertSee('Application sources')
        ->assertSee('Retrieved records')
        ->assertSee('Raw lifecycle callbacks')
        ->assertScript('document.querySelectorAll("[data-ndb-model-group]:first-of-type [data-ndb-model-record]").length', 6)
        ->assertScript('document.querySelectorAll("[data-ndb-model-group]:first-of-type [data-ndb-model-record][data-ndb-model-record-retrievals]:not([data-ndb-model-record-retrievals=\"1\"])").length', 5)
        ->assertScript(<<<'JS'
            (() => {
                window.newdebugbarModelClipboardWrites = [];
                Object.defineProperty(window.navigator, 'clipboard', {
                    configurable: true,
                    value: {
                        writeText: async (value) => window.newdebugbarModelClipboardWrites.push(value),
                    },
                });

                return true;
            })()
            JS)
        ->click('[data-ndb-model-group]:first-of-type [data-ndb-model-copy-class]')
        ->click('[data-ndb-model-group]:first-of-type [data-ndb-model-copy-source="0"]')
        ->assertScript(<<<'JS'
            window.newdebugbarModelClipboardWrites.length === 2
                && window.newdebugbarModelClipboardWrites[0] === 'NewDebugBar\\Tests\\Fixtures\\Models\\StudioJob'
                && window.newdebugbarModelClipboardWrites[1].includes('tests/Support/DefinesTestApplication.php:')
            JS)
        ->assertNoJavaScriptErrors();
});

it('keeps expanded model evidence contained at 390 pixels', function () {
    $page = visit('/profiled-models')
        ->resize(390, 844)
        ->click('[data-ndb-mobile-toolbar-trigger="actions"]')
        ->click('[data-ndb-mobile-toolbar-action="inspector"]')
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="palette"]')
        ->assertVisible('[data-ndb-command="section:models"]')
        ->click('[data-ndb-command="section:models"]')
        ->assertVisible('[data-ndb-section-panel="models"]')
        ->keys('[data-ndb-model-group]:first-of-type > summary', 'Enter')
        ->assertAttribute('[data-ndb-model-group]:first-of-type', 'open', '')
        ->assertScript(<<<'JS'
            (() => {
                const content = document.querySelector('[data-ndb-inspector-content]');
                const panel = document.querySelector('[data-ndb-section-panel="models"]');
                const models = document.querySelector('[data-ndb-models]');
                const records = Array.from(document.querySelectorAll('[data-ndb-model-record]'));

                return panel.getBoundingClientRect().width <= content.clientWidth + 1
                    && content.scrollWidth <= content.clientWidth + 1
                    && getComputedStyle(models).overflowY === 'visible'
                    && records.every((record) => record.scrollWidth <= record.clientWidth + 1);
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('folds lifecycle callbacks into truthful model write operations', function () {
    $page = visit('/profiled-models?changes=1')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::waitForDetails($page);

    $page
        ->click('[data-ndb-select-section="models"]')
        ->assertSee('7 intermediate callbacks were folded into 4 logical write operations.')
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
        ->keys('[data-ndb-model-group][data-ndb-model-short-name="Client"] [data-ndb-model-operation-changes] > summary', 'Enter')
        ->assertAttribute('[data-ndb-model-group][data-ndb-model-short-name="Client"] [data-ndb-model-operation-changes]', 'open', '')
        ->assertSee('status')
        ->assertSee("'approved'")
        ->assertSee('api_token')
        ->assertSee("'[redacted]'")
        ->assertDontSee('private-token')
        ->assertDontSee('updated-private-token')
        ->keys('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"] > summary', 'Enter')
        ->assertScript('document.querySelectorAll("[data-ndb-model-group][data-ndb-model-short-name=\"JobActivity\"] [data-ndb-model-operation]").length', 1)
        ->assertSeeIn('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"] [data-ndb-model-operation]', 'Trashed')
        ->assertNoJavaScriptErrors();
});

it('bounds large record lists and explains missing identifiers', function () {
    visit('/profiled-models?large=1&missing=1')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="models"]')
        ->keys('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"] > summary', 'Enter')
        ->assertScript('document.querySelectorAll("[data-ndb-model-group][data-ndb-model-short-name=\"JobActivity\"] [data-ndb-model-record]").length', 25)
        ->assertSeeIn('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"] [data-ndb-model-record-limit]', 'Showing 25 of 40 identified records.')
        ->keys('[data-ndb-model-group][data-ndb-model-short-name="ProfiledModel"] > summary', 'Enter')
        ->assertVisible('[data-ndb-model-group][data-ndb-model-short-name="ProfiledModel"] [data-ndb-model-missing-identifiers]')
        ->assertSee('Record identifiers were unavailable for these retrievals, so repeated records cannot be determined.')
        ->assertNoJavaScriptErrors();
});

it('links only exact source query evidence without claiming causation', function () {
    visit('/profiled-models?queries=1')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="models"]')
        ->keys('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"] > summary', 'Enter')
        ->assertVisible('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"] [data-ndb-model-query-evidence]')
        ->assertSee('The source location matches exactly. That does not prove which query hydrated or changed this model.')
        ->click('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"] [data-ndb-model-view-queries]')
        ->assertVisible('[data-ndb-section-panel="queries"]')
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
