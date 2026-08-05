<?php

use Pest\Browser\Support\Screenshot;
use Symfony\Component\Process\Process;

function assertVisualDebugBaseline($page, string $name): void
{
    $page->assertScript(<<<'JS'
        (() => {
            if (document.getElementById('new-debug-bar-snapshot-style')) return true;

            const style = document.createElement('style');
            style.id = 'new-debug-bar-snapshot-style';
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

    $filename = 'new-debug-bar-visual-'.bin2hex(random_bytes(8));
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

        return $page
            ->click('[data-testid="profiled-increment"]')
            ->waitForText('1');
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

    if (in_array($section, ['authorization', 'lifecycle', 'messages'], true)) {
        $page = visit('/profiled-context');
        setVisualDebugTheme($page, $theme);

        return $page;
    }

    $page = visit('/profiled-rich');
    setVisualDebugTheme($page, $theme);

    return $page;
}

function setVisualDebugTheme($page, string $theme, array $favorites = [], string $sectionMode = 'all'): void
{
    $preferences = json_encode([
        'theme' => $theme,
        'sectionMode' => $sectionMode,
        'favorites' => $favorites,
    ], JSON_THROW_ON_ERROR);
    $page->assertScript(<<<JS
        (() => {
            localStorage.setItem('new-debug-bar.preferences.v1', '{$preferences}');

            return true;
        })()
        JS)
        ->refresh()
        ->assertAttribute('#new-debug-bar', 'data-theme', $theme);
}

function stabilizeVisualDebugValues($page): void
{
    $page->assertScript(<<<'JS'
        (() => {
            let numericIndex = 0;
            const walker = document.createTreeWalker(
                document.getElementById('new-debug-bar'),
                NodeFilter.SHOW_TEXT,
            );

            while (walker.nextNode()) {
                const preserved = walker.currentNode.nodeValue.replaceAll('N+1', 'N_PLUS_ONE');
                walker.currentNode.nodeValue = preserved
                    .replace(/\d+(?:[.,:]\d+)*/g, () => String((numericIndex++ % 9) + 1))
                    .replaceAll('N_PLUS_ONE', 'N+1');
            }

            if (! document.getElementById('new-debug-bar-visual-stability')) {
                const style = document.createElement('style');
                style.id = 'new-debug-bar-visual-stability';
                style.textContent = '#new-debug-bar * { caret-color: transparent !important; }';
                document.head.appendChild(style);
            }

            const inspector = document.querySelector('[role="dialog"][aria-label="Request inspector"]');

            if (inspector) {
                const mask = document.createElement('div');
                mask.dataset.ndbVisualBottomMask = '';
                mask.style.cssText = 'position:absolute;z-index:30;inset:auto 0 0;height:2px;pointer-events:none';
                mask.style.backgroundColor = getComputedStyle(document.querySelector('#new-debug-bar main')).backgroundColor;
                inspector.appendChild(mask);
            }

            return true;
        })()
        JS);
}

$visualSections = [
    'overview',
    'request',
    'timeline',
    'queries',
    'livewire',
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
        ->click('[data-ndb-toolbar="expand"]')
        ->waitForText('Sections')
        ->click("[data-ndb-select-section=\"{$section}\"]")
        ->wait(0.2)
        ->assertVisible("[data-ndb-section-panel=\"{$section}\"]");

    stabilizeVisualDebugValues($page);

    $page
        ->assertNoJavaScriptErrors();

    assertVisualDebugBaseline($page, "section-{$theme}-{$section}");
})->with($visualSectionCases);

it('matches the visual baseline for the :dataset progressive overview', function (string $theme) {
    $page = visit('/profiled-rich');
    setVisualDebugTheme($page, $theme, [], 'active');

    $page
        ->resize(1440, 900)
        ->click('[data-ndb-toolbar="expand"]')
        ->waitForText('Environment details')
        ->assertAttribute('[data-ndb-section-mode="active"]', 'aria-pressed', 'true')
        ->assertNoJavaScriptErrors();

    stabilizeVisualDebugValues($page);

    assertVisualDebugBaseline($page, "progressive-overview-{$theme}");
})->with(['light', 'dark']);

it('matches the visual baseline for the :dataset narrow progressive overview', function (string $theme) {
    $page = visit('/profiled-rich');
    setVisualDebugTheme($page, $theme, [], 'active');

    $page
        ->resize(390, 844)
        ->click('[data-ndb-toolbar="expand"]')
        ->waitForText('Environment details')
        ->assertAttribute('[data-ndb-section-mode="active"]', 'aria-pressed', 'true')
        ->assertNoJavaScriptErrors();

    stabilizeVisualDebugValues($page);

    assertVisualDebugBaseline($page, "progressive-overview-narrow-{$theme}");
})->with(['light', 'dark']);

it('matches the visual baseline for the :dataset expanded environment details', function (string $theme) {
    $page = visit('/profiled-rich');
    setVisualDebugTheme($page, $theme, [], 'active');

    $page
        ->resize(1440, 900)
        ->click('[data-ndb-toolbar="expand"]')
        ->waitForText('Environment details')
        ->click('[data-ndb-overview-environment] summary')
        ->assertAttribute('[data-ndb-overview-environment]', 'open', '')
        ->assertScript(<<<'JS'
            (() => {
                document.querySelector('[data-ndb-overview-environment-content]').scrollIntoView({ block: 'start' });

                return true;
            })()
            JS)
        ->wait(0.1)
        ->assertNoJavaScriptErrors();

    stabilizeVisualDebugValues($page);

    assertVisualDebugBaseline($page, "progressive-environment-expanded-{$theme}");
})->with(['light', 'dark']);

it('matches the visual baseline for the :dataset narrow expanded environment details', function (string $theme) {
    $page = visit('/profiled-rich');
    setVisualDebugTheme($page, $theme, [], 'active');

    $page
        ->resize(390, 844)
        ->click('[data-ndb-toolbar="expand"]')
        ->waitForText('Environment details')
        ->click('[data-ndb-overview-environment] summary')
        ->assertAttribute('[data-ndb-overview-environment]', 'open', '')
        ->assertScript(<<<'JS'
            (() => {
                document.querySelector('[data-ndb-overview-environment-content]').scrollIntoView({ block: 'start' });

                return true;
            })()
            JS)
        ->wait(0.1)
        ->assertNoJavaScriptErrors();

    stabilizeVisualDebugValues($page);

    assertVisualDebugBaseline($page, "progressive-environment-expanded-narrow-{$theme}");
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
        ->click('[data-ndb-toolbar="expand"]')
        ->waitForText('Sections')
        ->click('[data-ndb-select-section="queries"]')
        ->click('[data-ndb-query-bindings="item-1"] summary')
        ->assertAttribute('[data-ndb-query-bindings="item-1"]', 'open', '');

    stabilizeVisualDebugValues($page);

    $page
        ->assertNoJavaScriptErrors();

    assertVisualDebugBaseline($page, "query-bindings-{$theme}");
})->with(['light', 'dark']);

it('matches the visual baseline for :dataset repeated query evidence', function (string $theme) {
    $page = visualDebugPage('queries', $theme)
        ->resize(1440, 900)
        ->click('[data-ndb-toolbar="expand"]')
        ->waitForText('Sections')
        ->click('[data-ndb-select-section="queries"]')
        ->click('[data-ndb-query-filter="repeated"]')
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden]) > div:last-child > article").length > 0');

    stabilizeVisualDebugValues($page);

    $page
        ->assertNoJavaScriptErrors();

    assertVisualDebugBaseline($page, "query-repeated-{$theme}");
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
        ->click('[data-ndb-toolbar="expand"]')
        ->waitForText('Sections')
        ->click("[data-ndb-select-section=\"{$section}\"]")
        ->click("[data-ndb-section-panel=\"{$section}\"] details:first-of-type summary")
        ->assertAttribute("[data-ndb-section-panel=\"{$section}\"] details:first-of-type", 'open', '');

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
        ->click('[data-ndb-toolbar="expand"]')
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
        ->click('[data-ndb-toolbar="expand"]')
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
        ->resize(390, 844)
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->assertVisible('[role="dialog"][aria-label="Request inspector"]')
        ->click('[data-ndb-select-section="queries"]')
        ->assertVisible('[data-ndb-section-panel="queries"]');

    stabilizeVisualDebugValues($page);

    $page
        ->assertNoJavaScriptErrors();

    assertVisualDebugBaseline($page, "narrow-inspector-{$theme}");
})->with(['light', 'dark']);
