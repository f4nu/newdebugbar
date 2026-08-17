<?php

use Pest\Browser\Support\Screenshot;
use Symfony\Component\Process\Process;

function assertVisualDebugBaseline($page, string $name): void
{
    $page->assertScript(<<<'JS'
        (() => {
            if (document.getElementById('newdebugbar-snapshot-style')) return true;

            const style = document.createElement('style');
            style.id = 'newdebugbar-snapshot-style';
            style.textContent = `
                * {
                    animation: none !important;
                    transition: none !important;
                    font-family: Arial, sans-serif !important;
                }
                body {
                    -webkit-font-smoothing: antialiased !important;
                    -moz-osx-font-smoothing: grayscale !important;
                }
            `;
            document.head.appendChild(style);

            return true;
        })()
        JS)
        ->wait(0.1);

    $filename = 'newdebugbar-visual-'.bin2hex(random_bytes(8));
    $page->screenshot(false, $filename);
    $actual = Screenshot::path($filename);
    expect($actual)->toBeFile();

    $directory = dirname(__DIR__).'/VisualBaselines';
    $baseline = "{$directory}/{$name}.png";

    try {
        if (getenv('UPDATE_VISUAL_BASELINES') === '1') {
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            expect(copy($actual, $baseline))->toBeTrue();

            return;
        }

        expect($baseline)->toBeFile();

        $process = new Process([
            'node',
            dirname(__DIR__, 2).'/scripts/compare-screenshot.mjs',
            $baseline,
            $actual,
        ]);
        $process->run();

        if (! $process->isSuccessful()) {
            copy($actual, Screenshot::path("failed-{$name}"));
        }

        expect($process->isSuccessful())
            ->toBeTrue(trim($process->getErrorOutput().$process->getOutput()));
    } finally {
        unlink($actual);
    }
}

function visualDebugPage(string $section, string $theme)
{
    if ($section === 'livewire') {
        $page = visit('/profiled-livewire');
        setVisualDebugTheme($page, $theme);

        return $page;
    }

    if ($section === 'exceptions') {
        $page = visit('/profiled-reported-exception');
        setVisualDebugTheme($page, $theme);

        return $page;
    }

    if ($section === 'history') {
        $page = visit('/profiled');
        setVisualDebugTheme($page, $theme);

        return $page;
    }

    if (in_array($section, ['authorization', 'lifecycle', 'messages', 'views'], true)) {
        $page = visit('/profiled-context');
        setVisualDebugTheme($page, $theme);

        return $page;
    }

    if ($section === 'models') {
        $page = visit('/profiled-models');
        setVisualDebugTheme($page, $theme);

        return $page;
    }

    $page = visit('/profiled-rich');
    setVisualDebugTheme($page, $theme);

    return $page;
}

function setVisualDebugTheme($page, string $theme, array $favorites = []): void
{
    $preferences = json_encode([
        'theme' => $theme,
        'favorites' => $favorites,
    ], JSON_THROW_ON_ERROR);
    $page->assertScript(<<<JS
        (() => {
            localStorage.setItem('newdebugbar.preferences.v1', '{$preferences}');

            return true;
        })()
        JS)
        ->refresh()
        ->assertAttribute('#newdebugbar', 'data-theme', $theme);
}

function selectVisualDebugSection($page, string $section): void
{
    $page
        ->click('[data-ndb-inspector-action="palette"]')
        ->assertVisible('[role="dialog"][aria-label="Command palette"]')
        ->click('[data-ndb-command="collectors:show"]')
        ->wait(0.1)
        ->click("[data-ndb-command=\"section:{$section}\"]")
        ->wait(0.1);
}

function openNarrowVisualDebugInspector($page): void
{
    $page
        ->click('[data-ndb-mobile-toolbar-trigger="actions"]')
        ->click('[data-ndb-mobile-toolbar-action="inspector"]');
}

function stabilizeVisualDebugValues($page): void
{
    $page->wait(0.25)->assertScript(<<<'JS'
        (() => {
            let numericIndex = 0;
            document.querySelectorAll('[data-ndb-model-record]').forEach((record, index) => {
                const firstSeen = record.querySelector('[data-ndb-model-first-seen]');
                const lastSeen = record.querySelector('[data-ndb-model-last-seen]');

                if (firstSeen) firstSeen.textContent = `${index + 1}.2 ms`;
                if (lastSeen) lastSeen.textContent = `${index + 2}.4 ms`;
            });
            const walker = document.createTreeWalker(
                document.getElementById('newdebugbar'),
                NodeFilter.SHOW_TEXT,
            );

            while (walker.nextNode()) {
                const parent = walker.currentNode.parentElement;
                const summaryValue = parent?.closest('[data-ndb-query-summary-value]')?.dataset.ndbQuerySummaryValue;
                const preservesQueryEvidence = parent?.closest(`
                    [data-ndb-query-finding-summary],
                    [data-ndb-query-filter-count],
                    [data-ndb-query-result-count],
                    [data-ndb-query-group-count],
                    [data-ndb-query-group-extra],
                    [data-ndb-query-execution-number],
                    [data-ndb-query-repeat-count],
                    [data-ndb-query-bindings-count],
                    [data-ndb-query-stack-count],
                    code[data-ndb-language="sql"]
                `) !== null;
                const preservesModelEvidence = parent?.closest(`
                    [data-ndb-model-load-count],
                    [data-ndb-model-record-count],
                    [data-ndb-model-repeat-count],
                    [data-ndb-model-mobile-summary],
                    [data-ndb-model-record],
                    [data-ndb-model-raw],
                    [data-ndb-model-boot]
                `) !== null;
                const preservesSectionEvidence = parent?.closest(`
                    [data-ndb-livewire],
                    [data-ndb-event-source-count],
                    [data-ndb-event-visible-count],
                    [data-ndb-view-summary-value],
                    [data-ndb-view-group-count],
                    [data-ndb-view-render-order],
                    [data-ndb-view-source],
                    [data-ndb-view-data-count]
                `) !== null;

                if (preservesQueryEvidence || preservesModelEvidence || preservesSectionEvidence || ['queries', 'repeated', 'extra-runs'].includes(summaryValue)) {
                    continue;
                }

                const preserved = walker.currentNode.nodeValue.replaceAll('N+1', 'N_PLUS_ONE');
                walker.currentNode.nodeValue = preserved
                    .replace(/\d+(?:[.,:]\d+)*/g, () => String((numericIndex++ % 9) + 1))
                    .replaceAll('N_PLUS_ONE', 'N+1');
            }

            const stableMobileToolbarValues = {
                '[data-ndb-mobile-toolbar-summary="queries"]': '3',
                '[data-ndb-mobile-toolbar-summary="duration"]': '4 ms',
                '[data-ndb-mobile-toolbar-summary="memory"]': '7 MB',
                '[data-ndb-mobile-toolbar-fact-value="queries"]': '3',
                '[data-ndb-mobile-toolbar-fact-value="duration"]': '4 ms',
                '[data-ndb-mobile-toolbar-fact-value="memory"]': '7 MB',
            };

            Object.entries(stableMobileToolbarValues).forEach(([selector, value]) => {
                const element = document.querySelector(selector);
                if (element) element.textContent = value;
            });

            const normalizeQueryMetrics = (articles) => {
                let totalDuration = 0;
                let totalPercent = 0;

                articles.forEach((article, index) => {
                    const duration = articles.length - index;
                    const percent = duration * 2;

                    article.querySelector('[data-ndb-query-duration]').textContent = `${duration} ms`;
                    article.querySelector('[data-ndb-query-percent]').textContent = `${percent}% of query time`;
                    totalDuration += duration;
                    totalPercent += percent;
                });

                return { totalDuration, totalPercent };
            };
            const queryItems = Array.from(document.querySelectorAll('[data-ndb-query-item]'));

            if (queryItems.length > 0) {
                const totals = normalizeQueryMetrics(queryItems);
                const queryTime = document.querySelector('[data-ndb-query-summary-value="query-time"]');
                const requestShare = document.querySelector('[data-ndb-query-summary-value="request-share"]');

                if (queryTime) queryTime.textContent = `${totals.totalDuration} ms`;
                if (requestShare) requestShare.textContent = `${totals.totalPercent}%`;
            }

            document.querySelectorAll('[data-ndb-query-group]').forEach((group) => {
                const articles = Array.from(group.querySelectorAll('[data-ndb-query-group-executions] > details'));
                const totals = normalizeQueryMetrics(articles);
                const duration = group.querySelector('[data-ndb-query-group-duration]');

                if (duration) duration.textContent = `${totals.totalDuration} ms total`;
            });

            const timelineTicks = Array.from(document.querySelectorAll('[data-ndb-timeline-tick]'));

            if (timelineTicks.length > 0) {
                const axisValues = ['0 ms', '2.5 ms', '5 ms', '7.5 ms', '10 ms'];
                const visibleItems = Array.from(document.querySelectorAll('[data-ndb-timeline-item]:not([hidden])')).length;
                const summary = document.querySelector('[data-ndb-timeline-summary]');

                timelineTicks.forEach((tick, index) => {
                    tick.textContent = axisValues[index];
                });
                if (summary) summary.textContent = `${visibleItems} events across 10 ms`;
            }

            if (! document.getElementById('newdebugbar-visual-stability')) {
                const style = document.createElement('style');
                style.id = 'newdebugbar-visual-stability';
                style.textContent = '#newdebugbar * { caret-color: transparent !important; }';
                document.head.appendChild(style);
            }

            const inspector = document.querySelector('[role="dialog"][aria-label="Request inspector"]');

            if (inspector) {
                const mask = document.createElement('div');
                mask.dataset.ndbVisualBottomMask = '';
                mask.style.cssText = 'position:absolute;z-index:30;inset:auto 0 0;height:2px;pointer-events:none';
                mask.style.backgroundColor = getComputedStyle(document.querySelector('#newdebugbar main')).backgroundColor;
                inspector.appendChild(mask);
            }

            return true;
        })()
        JS);
}

$visualSections = [
    'request',
    'timeline',
    'queries',
    'http_client',
    'queue',
    'mail',
    'notifications',
    'redis',
    'models',
    'cache',
    'views',
    'events',
    'logs',
    'exceptions',
    'authorization',
    'validation',
    'lifecycle',
    'messages',
    'livewire',
    'history',
];
$visualSectionCases = [];

foreach (['light', 'dark'] as $theme) {
    foreach ($visualSections as $section) {
        $visualSectionCases["{$theme} {$section}"] = [$section, $theme];
    }
}

it('matches the visual baseline for the :dataset section', function (string $section, string $theme) {
    $page = visualDebugPage($section, $theme)
        ->resize(1440, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->waitForText('Runtime details');

    selectVisualDebugSection($page, $section);

    $page
        ->assertVisible("[data-ndb-section-panel=\"{$section}\"]");

    if ($section === 'views') {
        $page
            ->click('[data-ndb-view-group] > summary')
            ->assertVisible('[data-ndb-view-render]')
            ->click('[data-ndb-view-data-trigger]')
            ->assertVisible('[data-ndb-view-data-popover]')
            ->assertVisible('[data-ndb-view-data]');
    }

    stabilizeVisualDebugValues($page);

    $page
        ->assertNoJavaScriptErrors();

    assertVisualDebugBaseline($page, "section-{$theme}-{$section}");
})->with($visualSectionCases);

it('matches the visual baseline for the :dataset progressive overview', function (string $theme) {
    $page = visit('/profiled-rich');
    setVisualDebugTheme($page, $theme);

    $page
        ->resize(1440, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->waitForText('Runtime details')
        ->assertMissing('[data-ndb-section-mode]')
        ->assertMissing('[data-ndb-quiet-count]')
        ->assertNoJavaScriptErrors();

    stabilizeVisualDebugValues($page);

    assertVisualDebugBaseline($page, "progressive-overview-{$theme}");
})->with(['light', 'dark']);

it('matches the visual baseline for the :dataset narrow progressive overview', function (string $theme) {
    $page = visit('/profiled-rich');
    setVisualDebugTheme($page, $theme);

    $page->resize(390, 844);
    openNarrowVisualDebugInspector($page);

    $page
        ->waitForText('Runtime details')
        ->assertMissing('[data-ndb-section-mode]')
        ->assertMissing('[data-ndb-quiet-count]')
        ->assertNoJavaScriptErrors();

    stabilizeVisualDebugValues($page);

    assertVisualDebugBaseline($page, "progressive-overview-narrow-{$theme}");
})->with(['light', 'dark']);

it('matches the visual baseline for the :dataset narrow Models section', function (string $theme) {
    $page = visualDebugPage('models', $theme)
        ->resize(390, 844);
    openNarrowVisualDebugInspector($page);
    $page->waitForText('Runtime details');

    selectVisualDebugSection($page, 'models');

    $page
        ->assertVisible('[data-ndb-section-panel="models"]')
        ->assertNoJavaScriptErrors();

    stabilizeVisualDebugValues($page);

    assertVisualDebugBaseline($page, "section-narrow-{$theme}-models");
})->with(['light', 'dark']);

it('matches the visual baseline for the :dataset narrow Livewire section', function (string $theme) {
    $page = visualDebugPage('livewire', $theme)
        ->resize(390, 844);
    openNarrowVisualDebugInspector($page);
    $page->waitForText('Runtime details');

    selectVisualDebugSection($page, 'livewire');

    $page
        ->assertVisible('[data-ndb-section-panel="livewire"]')
        ->assertScript(<<<'JS'
            (() => {
                const inspector = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                const tabs = document.querySelector('[data-ndb-livewire-tabs]');
                const box = inspector.getBoundingClientRect();

                return box.width === window.innerWidth
                    && box.left === 0
                    && box.right === window.innerWidth
                    && tabs.querySelectorAll('[role="tab"]').length === 3
                    && tabs.scrollWidth <= tabs.clientWidth + 1;
            })()
            JS)
        ->assertNoJavaScriptErrors();

    stabilizeVisualDebugValues($page);

    assertVisualDebugBaseline($page, "section-narrow-{$theme}-livewire");
})->with(['light', 'dark']);

it('matches the visual baseline for the :dataset toolbar', function (string $theme) {
    $page = visit('/profiled-rich');

    setVisualDebugTheme($page, $theme);
    $page->resize(1440, 900);
    stabilizeVisualDebugValues($page);

    $page
        ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]')
        ->assertNoJavaScriptErrors();

    assertVisualDebugBaseline($page, "toolbar-{$theme}");
})->with(['light', 'dark']);

it('matches the visual baseline for the :dataset narrow toolbar', function (string $theme) {
    $page = visit('/profiled-rich');

    setVisualDebugTheme($page, $theme);
    $page->resize(390, 844);
    stabilizeVisualDebugValues($page);

    $page
        ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]')
        ->assertNoJavaScriptErrors();

    assertVisualDebugBaseline($page, "toolbar-narrow-{$theme}");
})->with(['light', 'dark']);

it('matches the visual baseline for the :dataset narrow request facts menu', function (string $theme) {
    $page = visit('/profiled-rich');

    setVisualDebugTheme($page, $theme);
    $page->resize(390, 844);
    stabilizeVisualDebugValues($page);

    $page
        ->click('[data-ndb-mobile-toolbar-trigger="facts"]')
        ->assertVisible('[data-ndb-mobile-toolbar-menu="facts"]')
        ->wait(0.2)
        ->assertNoJavaScriptErrors();

    assertVisualDebugBaseline($page, "toolbar-narrow-facts-{$theme}");
})->with(['light', 'dark']);

it('matches the visual baseline for the :dataset narrow action menu', function (string $theme) {
    $page = visit('/profiled-rich');

    setVisualDebugTheme($page, $theme);
    $page->resize(390, 844);
    stabilizeVisualDebugValues($page);

    $page
        ->click('[data-ndb-mobile-toolbar-trigger="actions"]')
        ->assertVisible('[data-ndb-mobile-toolbar-menu="actions"]')
        ->wait(0.2)
        ->assertNoJavaScriptErrors();

    assertVisualDebugBaseline($page, "toolbar-narrow-actions-{$theme}");
})->with(['light', 'dark']);

it('matches the visual baseline for the :dataset command palette', function (string $theme) {
    $page = visit('/profiled-rich');
    setVisualDebugTheme($page, $theme);

    $page
        ->resize(1440, 900)
        ->click('[data-ndb-toolbar="palette"]')
        ->waitForText('Go to Overview');

    stabilizeVisualDebugValues($page);

    $page
        ->assertVisible('[role="dialog"][aria-label="Command palette"]')
        ->assertNoJavaScriptErrors();

    assertVisualDebugBaseline($page, "command-palette-{$theme}");
})->with(['light', 'dark']);

it('matches the visual baseline for :dataset expanded query bindings', function (string $theme) {
    $page = visualDebugPage('queries', $theme)
        ->resize(1440, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->waitForText('Runtime details');

    selectVisualDebugSection($page, 'queries');

    $page
        ->click('[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-execution][open] [data-ndb-query-tab="bindings"]')
        ->assertAttribute('[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-execution][open] [data-ndb-query-tab="bindings"]', 'aria-selected', 'true')
        ->assertScript(<<<'JS'
            (() => {
                const content = document.querySelector('#newdebugbar main');
                content.scrollTop = 0;

                return content.scrollTop === 0;
            })()
            JS);

    stabilizeVisualDebugValues($page);

    $page
        ->assertNoJavaScriptErrors();

    assertVisualDebugBaseline($page, "query-bindings-{$theme}");
})->with(['light', 'dark']);

it('matches the visual baseline for :dataset repeated query evidence', function (string $theme) {
    $page = visualDebugPage('queries', $theme)
        ->resize(1440, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->waitForText('Runtime details');

    selectVisualDebugSection($page, 'queries');

    $page
        ->click('[data-ndb-query-filter="attention"]')
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-executions] > details").length > 0');

    stabilizeVisualDebugValues($page);

    $page
        ->assertNoJavaScriptErrors();

    assertVisualDebugBaseline($page, "query-repeated-{$theme}");
})->with(['light', 'dark']);

it('matches the visual baseline for the :dataset narrow Queries section', function (string $theme) {
    $page = visualDebugPage('queries', $theme)
        ->resize(390, 844);
    openNarrowVisualDebugInspector($page);
    $page->waitForText('Runtime details');

    selectVisualDebugSection($page, 'queries');

    $page
        ->assertVisible('[data-ndb-section-panel="queries"]')
        ->assertScript('document.querySelector("#newdebugbar main").scrollWidth === document.querySelector("#newdebugbar main").clientWidth')
        ->assertNoJavaScriptErrors();

    stabilizeVisualDebugValues($page);

    assertVisualDebugBaseline($page, "section-narrow-{$theme}-queries");
})->with(['light', 'dark']);

$expandedDetailCases = [
    'light models' => ['models', 'light'],
    'dark models' => ['models', 'dark'],
    'light cache' => ['cache', 'light'],
    'dark cache' => ['cache', 'dark'],
];

it('matches the visual baseline for expanded :dataset details', function (string $section, string $theme) {
    $page = visualDebugPage($section, $theme)
        ->resize(1440, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->waitForText('Runtime details');

    selectVisualDebugSection($page, $section);

    $detailsSelector = $section === 'models'
        ? '[data-ndb-section-panel="models"] [data-ndb-model-group]:first-of-type'
        : '[data-ndb-section-panel="cache"] details:first-of-type';

    $page
        ->click("{$detailsSelector} > summary")
        ->assertAttribute($detailsSelector, 'open', '');

    stabilizeVisualDebugValues($page);

    $page
        ->assertNoJavaScriptErrors();

    assertVisualDebugBaseline($page, "details-expanded-{$section}-{$theme}");
})->with($expandedDetailCases);

it('matches the visual baseline for :dataset favorite ordering', function (string $theme) {
    $page = visit('/profiled-rich');
    setVisualDebugTheme($page, $theme, ['request', 'overview', 'queries']);

    $page
        ->resize(1440, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->waitForText('Favorites');

    stabilizeVisualDebugValues($page);

    $page
        ->assertNoJavaScriptErrors();

    assertVisualDebugBaseline($page, "favorites-{$theme}");
})->with(['light', 'dark']);

it('matches the visual baseline for :dataset favorite dragging', function (string $theme) {
    $page = visit('/profiled-rich');
    setVisualDebugTheme($page, $theme, ['request', 'overview', 'queries']);

    $page
        ->resize(1440, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->waitForText('Favorites')
        ->wait(0.5)
        ->assertScript(<<<'JS'
            (() => {
                const source = document.querySelector('[data-ndb-section="queries"]');
                const target = document.querySelector('[data-ndb-section="overview"]');
                const state = Alpine.$data(source);
                state.startFavoriteDrag('queries');
                Alpine.$data(target).hoverFavorite('overview');

                return state.favoriteDrag === 'queries' && state.favoriteDrop === 'overview';
            })()
            JS)
        ->wait(0.1)
        ->assertAttribute('[data-ndb-section="queries"]', 'data-ndb-favorite', 'true')
        ->assertAttribute('[data-ndb-section="queries"]', 'data-ndb-dragging', 'true')
        ->assertVisible('[data-ndb-favorite-drop-before="overview"]');

    stabilizeVisualDebugValues($page);

    $page
        ->assertNoJavaScriptErrors();

    assertVisualDebugBaseline($page, "favorite-dragging-{$theme}");
})->with(['light', 'dark']);

it('matches the visual baseline for the :dataset narrow inspector', function (string $theme) {
    $page = visualDebugPage('queries', $theme)
        ->resize(390, 844);
    openNarrowVisualDebugInspector($page);

    $page
        ->wait(0.2)
        ->assertVisible('[role="dialog"][aria-label="Request inspector"]');

    selectVisualDebugSection($page, 'queries');

    $page
        ->assertVisible('[data-ndb-section-panel="queries"]');

    stabilizeVisualDebugValues($page);

    $page
        ->assertNoJavaScriptErrors();

    assertVisualDebugBaseline($page, "narrow-inspector-{$theme}");
})->with(['light', 'dark']);

it('matches the visual baseline for the :dataset narrow section drawer', function (string $theme) {
    $page = visualDebugPage('overview', $theme)
        ->resize(390, 844);
    openNarrowVisualDebugInspector($page);

    $page
        ->wait(0.2)
        ->click('[data-ndb-mobile-sections-toggle]')
        ->assertVisible('#newdebugbar-section-navigation')
        ->assertVisible('[data-ndb-mobile-sections-backdrop]')
        ->assertScript(<<<'JS'
            (() => {
                const backdrop = document.querySelector('[data-ndb-mobile-sections-backdrop]');
                const styles = getComputedStyle(backdrop);
                const background = styles.backgroundColor.replaceAll(' ', '');

                return ['transparent', 'rgba(0,0,0,0)'].includes(background)
                    && styles.backdropFilter === 'none';
            })()
            JS);

    stabilizeVisualDebugValues($page);

    $page
        ->assertNoJavaScriptErrors();

    assertVisualDebugBaseline($page, "narrow-section-drawer-{$theme}");
})->with(['light', 'dark']);
