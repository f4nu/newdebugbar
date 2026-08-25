<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('presents model activity as a persistent two column inspector', function () {
    $page = visit('/profiled-models')
        ->resize(1440, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="models"]');

    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-model-workspace]');

    $page
        ->assertSee('Review Eloquent retrievals, writes, repeated records, and application sources.')
        ->assertSee('44 Eloquent activities')
        ->assertSee('Across 5 model classes')
        ->assertSee('Counts describe Eloquent events, not database rows or queries.')
        ->assertSee('Retrieved')
        ->assertSee('Writes')
        ->assertSee('Extra')
        ->assertAttribute('[data-ndb-model-group]:first-of-type', 'aria-pressed', 'true')
        ->assertAttribute('[data-ndb-model-group]:first-of-type', 'aria-controls', 'newdebugbar-model-detail')
        ->assertAttribute('[data-ndb-model-detail-tab="overview"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-model-detail-panel="overview"]')
        ->assertSee('NewDebugBar\Tests\Fixtures\Models\StudioJob')
        ->assertSee('Activity window')
        ->assertSee('after request start')
        ->assertDontSee('Write events')
        ->assertDontSee('Write evidence')
        ->assertMissing('[data-ndb-model-operation]')
        ->assertMissing('[data-ndb-model-view-queries]')
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-model-workspace]');
                const loadedSection = document.querySelector('[data-ndb-loaded-section="models"]');
                const stage = document.querySelector('[data-ndb-section-stage]');
                const [list, detail] = workspace.children;
                const content = document.querySelector('[data-ndb-inspector-content]');
                const heading = document.querySelector('[data-ndb-model-list-heading]');
                const row = document.querySelector('[data-ndb-model-group]');
                const tabs = [...document.querySelectorAll('[data-ndb-model-detail-tab]')];
                const tabGroup = tabs[0].closest('[data-ndb-filter-tabs]');
                const rowCells = [
                    row.querySelector('[data-ndb-model-name]').parentElement,
                    row.querySelector('[data-ndb-model-retrieved-column]'),
                    row.querySelector('[data-ndb-model-write-column]'),
                    row.querySelector('[data-ndb-model-extra-column]'),
                ];
                const headingCells = [...heading.children];

                const checks = {
                    grid: getComputedStyle(workspace).display === 'grid',
                    horizontalPadding: getComputedStyle(loadedSection).paddingLeft === '0px'
                        && getComputedStyle(loadedSection).paddingRight === '0px',
                    edgeToEdge: Math.abs(workspace.getBoundingClientRect().left - stage.getBoundingClientRect().left) <= 1
                        && Math.abs(workspace.getBoundingClientRect().right - stage.getBoundingClientRect().right) <= 1,
                    frame: getComputedStyle(workspace).borderTopWidth === '1px'
                        && getComputedStyle(workspace).borderRightWidth === '0px'
                        && getComputedStyle(workspace).borderBottomWidth === '0px'
                        && getComputedStyle(workspace).borderLeftWidth === '0px'
                        && getComputedStyle(workspace).borderRadius === '0px',
                    seam: Math.abs(list.getBoundingClientRect().right - detail.getBoundingClientRect().left) <= 1,
                    proportions: detail.getBoundingClientRect().width > list.getBoundingClientRect().width * 1.6,
                    panes: getComputedStyle(list).display === 'flex' && getComputedStyle(detail).display === 'flex',
                    heading: getComputedStyle(heading).display === 'grid' && headingCells.length === 4,
                    columns: rowCells.every((cell, index) =>
                        Math.abs(cell.getBoundingClientRect().left - headingCells[index].getBoundingClientRect().left) <= 1
                    ),
                    tabLabels: tabs.map((tab) => tab.textContent.trim()).join('|') === 'Overview|Records|Source',
                    segmentedTabs: tabs.every((tab) => tab.dataset.ndbFilterTabVariant === 'segmented')
                        && tabGroup.dataset.ndbFilterTabsVariant === 'segmented',
                    centeredTabs: Math.abs(
                        tabGroup.getBoundingClientRect().left + tabGroup.getBoundingClientRect().width / 2
                        - detail.getBoundingClientRect().left - detail.getBoundingClientRect().width / 2
                    ) <= 1,
                    desktopBack: getComputedStyle(document.querySelector('[data-ndb-model-detail-back]')).display === 'none',
                    scrollOwners: getComputedStyle(list.querySelector('[data-ndb-model-list]')).overflowY === 'auto'
                        && getComputedStyle(detail).overflowY === 'auto',
                    focusTarget: detail.tabIndex === 0,
                    contentFit: content.scrollHeight <= content.clientHeight + 2,
                    horizontalFit: workspace.scrollWidth <= workspace.clientWidth + 1,
                };
                const failures = Object.entries(checks).filter(([, passed]) => ! passed).map(([name]) => name);

                if (failures.length > 0) throw new Error('Models layout failed: ' + failures.join(', '));

                return true;
            })()
            JS)
        ->click('[data-ndb-model-group][data-ndb-model-short-name="ProofVersion"]')
        ->assertAttribute('[data-ndb-model-group][data-ndb-model-short-name="ProofVersion"]', 'aria-pressed', 'true')
        ->assertSee('NewDebugBar\Tests\Fixtures\Models\ProofVersion')
        ->assertAttribute('[data-ndb-model-detail-tab="overview"]', 'aria-pressed', 'true')
        ->click('[data-ndb-model-detail-tab="records"]')
        ->assertAttribute('[data-ndb-model-detail-tab="records"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-model-detail-panel="records"]')
        ->assertScript('document.querySelectorAll("[data-ndb-model-record]").length', 5)
        ->click('[data-ndb-model-detail-tab="source"]')
        ->assertVisible('[data-ndb-model-detail-panel="source"]')
        ->assertVisible('[data-ndb-model-source]:first-of-type')
        ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->assertVisible('[data-ndb-model-workspace]')
        ->assertAttribute('[data-ndb-model-group][data-ndb-model-short-name="ProofVersion"]', 'aria-pressed', 'true')
        ->refresh()
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="models"]')
        ->assertVisible('[data-ndb-model-list]')
        ->assertAttribute('[data-ndb-model-group]:first-of-type', 'aria-pressed', 'true')
        ->assertNoJavaScriptErrors();
});

it('adapts the model list and details into a mobile drill in flow', function () {
    $page = visit('/profiled-models')
        ->resize(390, 844)
        ->click('[data-ndb-mobile-toolbar-trigger="actions"]')
        ->click('[data-ndb-mobile-toolbar-action="inspector"]')
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="palette"]')
        ->click('[data-ndb-command="section:models"]')
        ->assertVisible('[data-ndb-model-list]')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-model-detail-pane]")).display === "none"')
        ->keys('[data-ndb-model-group][data-ndb-model-short-name="ProofVersion"]', 'Enter');

    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-model-detail-pane]');

    $page
        ->assertVisible('[data-ndb-model-detail-back]')
        ->assertSee('NewDebugBar\Tests\Fixtures\Models\ProofVersion')
        ->assertScript(<<<'JS'
            (() => {
                const content = document.querySelector('[data-ndb-inspector-content]');
                const panel = document.querySelector('[data-ndb-section-panel="models"]');
                const models = document.querySelector('[data-ndb-models]');
                const heading = document.querySelector('[data-ndb-model-list-heading]');
                const rows = [...document.querySelectorAll('[data-ndb-model-group]')];
                const listPanel = document.querySelector('[data-ndb-model-workspace]').children[0];
                const detail = document.querySelector('[data-ndb-model-detail-pane]');

                return panel.getBoundingClientRect().width <= content.clientWidth + 1
                    && content.scrollWidth <= content.clientWidth + 1
                    && ['auto', 'scroll'].includes(getComputedStyle(content).overflowY)
                    && getComputedStyle(models).overflowY === 'visible'
                    && getComputedStyle(heading).display === 'none'
                    && getComputedStyle(listPanel).display === 'none'
                    && rows.every((row) => row.getClientRects().length === 0)
                    && getComputedStyle(detail).display === 'flex'
                    && getComputedStyle(detail).overflowY === 'visible'
                    && detail.scrollWidth <= detail.clientWidth + 1
                    && document.activeElement === detail;
            })()
            JS)
        ->click('[data-ndb-model-detail-tab="records"]')
        ->assertVisible('[data-ndb-model-detail-panel="records"]')
        ->assertScript('document.querySelectorAll("[data-ndb-model-record]").length', 5)
        ->click('[data-ndb-model-detail-back]')
        ->assertVisible('[data-ndb-model-list]');

    DebugBarBrowser::waitForFocus(
        $page,
        '[data-ndb-model-group][data-ndb-model-short-name="ProofVersion"]',
    );

    $page
        ->assertAttribute('[data-ndb-model-group][data-ndb-model-short-name="ProofVersion"]', 'aria-pressed', 'true')
        ->assertNoJavaScriptErrors();
});

it('keeps writes as a useful count without rendering write evidence', function () {
    visit('/profiled-models?changes=1')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="models"]')
        ->assertScript('document.querySelector("[data-ndb-select-section=models]").textContent.trim().endsWith("48")')
        ->assertScript(<<<'JS'
            JSON.stringify([...document.querySelectorAll('[data-ndb-model-group]')]
                .map((group) => [group.dataset.ndbModelShortName, group.dataset.ndbModelWrites]))
                === JSON.stringify([
                    ['Client', '1'],
                    ['ProofVersion', '1'],
                    ['User', '1'],
                    ['JobActivity', '1'],
                    ['StudioJob', '0'],
                ])
            JS)
        ->assertSee('NewDebugBar\Tests\Fixtures\Models\Client')
        ->assertSee('Write events')
        ->assertSee('Updating 1, Updated 1, Saved 1')
        ->assertScript(<<<'JS'
            (() => {
                const facts = [...document.querySelectorAll('[data-ndb-model-facts] [data-ndb-inspector-fact]')];
                const writes = facts.find((fact) => fact.querySelector('dt').textContent.trim() === 'Logical writes');

                return writes?.querySelector('dd').textContent.trim() === '1';
            })()
            JS)
        ->assertDontSee('Write evidence')
        ->assertDontSee('Changed attributes')
        ->assertDontSee('private-token')
        ->assertDontSee('updated-private-token')
        ->assertMissing('[data-ndb-model-operation]')
        ->assertMissing('[data-ndb-model-operation-changes]')
        ->assertNoJavaScriptErrors();
});

it('bounds record tables and explains unavailable identifiers', function () {
    visit('/profiled-models?large=1&missing=1')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="models"]')
        ->click('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"]')
        ->click('[data-ndb-model-detail-tab="records"]')
        ->assertScript('document.querySelectorAll("[data-ndb-model-record]").length', 25)
        ->assertSeeIn('[data-ndb-model-record-limit]', 'Showing 25 of 40 identified records.')
        ->click('[data-ndb-model-group][data-ndb-model-short-name="ProfiledModel"]')
        ->click('[data-ndb-model-detail-tab="records"]')
        ->assertVisible('[data-ndb-model-missing-identifiers]')
        ->assertSee('A dash means the model identifier was unavailable.')
        ->assertSee('These retrievals are excluded from the extra-retrieval count.')
        ->assertNoJavaScriptErrors();
});

it('shows application sources without related query controls', function () {
    visit('/profiled-models?queries=1')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="models"]')
        ->click('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"]')
        ->click('[data-ndb-model-detail-tab="source"]')
        ->assertVisible('[data-ndb-model-detail-panel="source"]')
        ->assertVisible('[data-ndb-model-source]:first-of-type')
        ->assertSee('Application sources')
        ->assertSee('Start with locations responsible for the most model activity.')
        ->assertDontSee('Related queries')
        ->assertMissing('[data-ndb-model-query-guidance]')
        ->assertMissing('[data-ndb-model-query-evidence]')
        ->assertMissing('[data-ndb-model-view-queries]')
        ->assertNoJavaScriptErrors();
});

it('shows original Blade and compiled source paths in normal interface type', function () {
    visit('/profiled-models?compiled=1')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="models"]')
        ->click('[data-ndb-model-group][data-ndb-model-short-name="JobActivity"]')
        ->click('[data-ndb-model-detail-tab="source"]')
        ->assertVisible('[data-ndb-model-compiled-source]')
        ->assertSee('Blade template')
        ->assertSee('tests/Fixtures/views/model-compiled.blade.php')
        ->assertSee('Compiled location')
        ->assertSee('storage/framework/views/')
        ->assertScript(<<<'JS'
            (() => {
                const source = document.querySelector('[data-ndb-model-compiled-source]').closest('[data-ndb-model-source]');
                const paths = [...source.querySelectorAll('[data-ndb-model-source-path]')];

                return source.scrollWidth <= source.clientWidth + 1
                    && paths.length === 2
                    && paths.every((path) => !getComputedStyle(path).fontFamily.includes('JetBrains Mono'))
                    && source.querySelector('[data-ndb-model-view-queries]') === null;
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
