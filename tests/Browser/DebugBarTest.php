<?php

use Illuminate\Support\Facades\File;
use NewDebugBar\Tests\ProfiledApplicationListener;

function assertDebugSectionSelected($page, string $section): void
{
    $page
        ->assertCount('#newdebugbar [data-ndb-select-section][aria-current="page"]', 1)
        ->assertAttribute("#newdebugbar [data-ndb-select-section=\"{$section}\"]", 'aria-current', 'page')
        ->assertCount('#newdebugbar [data-ndb-section-panel]:not([hidden])', 1)
        ->assertVisible("#newdebugbar [data-ndb-section-panel=\"{$section}\"]");
}

function assertFavoriteOrder($page, string $order): void
{
    $page->assertScript(<<<'JS'
        Array.from(document.querySelectorAll('#newdebugbar [data-ndb-section][data-ndb-favorite="true"]'))
            .map((section) => section.dataset.ndbSection)
            .join(',')
        JS, $order);
}

function selectDebugSectionViaPalette($page, string $section): void
{
    $page
        ->click('[data-ndb-inspector-action="palette"]')
        ->assertVisible('[role="dialog"][aria-label="Command palette"]')
        ->click('[data-ndb-command="collectors:show"]')
        ->wait(0.1)
        ->click("[data-ndb-command=\"section:{$section}\"]")
        ->wait(0.1);
}

it('opens every compact toolbar destination and shrinks cleanly', function () {
    $page = visit('/profiled')
        ->assertPresent('[data-testid="host-page"]')
        ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]');

    foreach ([
        'expand' => 'overview',
        'request' => 'request',
        'environment' => 'overview',
        'duration' => 'request',
        'memory' => 'overview',
        'queries' => 'queries',
    ] as $toolbar => $section) {
        $selector = $toolbar === 'expand'
            ? '[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]'
            : "[data-ndb-toolbar=\"{$toolbar}\"]";

        $page
            ->click($selector)
            ->wait(0.2);

        assertDebugSectionSelected($page, $section);

        if ($toolbar === 'expand') {
            $page->assertScript('document.querySelector("[data-ndb-header-memory]").textContent.includes("MB")');
        }

        $page
            ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]')
            ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]');
    }

    $page->assertNoJavaScriptErrors();
});

it('provides stateful window controls and closes until reload', function () {
    $page = visit('/profiled')
        ->resize(1440, 900)
        ->assertVisible('[data-ndb-window-controls="compact"]')
        ->assertScript(<<<'JS'
            (() => {
                const controls = document.querySelector('[data-ndb-window-controls="compact"]');
                const expand = controls.querySelector('[data-ndb-window-action="expand"]');
                const shrink = controls.querySelector('[data-ndb-window-action="shrink"]');
                const close = controls.querySelector('[data-ndb-window-action="close"]');
                const utility = document.querySelector('[data-ndb-toolbar-utility-actions]');
                const separator = document.querySelector('[data-ndb-toolbar-actions] [data-ndb-window-controls-separator]');
                const utilityBox = utility.getBoundingClientRect();
                const separatorBox = separator.getBoundingClientRect();
                const controlsBox = controls.getBoundingClientRect();

                return expand.disabled === false
                    && shrink.disabled === true
                    && close.disabled === false
                    && Number.parseFloat(getComputedStyle(shrink).opacity) < Number.parseFloat(getComputedStyle(expand).opacity)
                    && utility.getAttribute('aria-label') === 'Tools'
                    && controls.getAttribute('aria-label') === 'Window controls'
                    && separatorBox.left >= utilityBox.right
                    && separatorBox.right <= controlsBox.left
                    && separatorBox.height > 0;
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                window.ndbWindowControlColor = getComputedStyle(
                    document.querySelector('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]'),
                ).color;

                return true;
            })()
            JS)
        ->hover('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->assertScript(<<<'JS'
            (() => {
                const control = document.querySelector('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');
                const style = getComputedStyle(control);

                return style.backgroundColor === 'rgba(0, 0, 0, 0)'
                    && style.color !== window.ndbWindowControlColor;
            })()
            JS)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->assertVisible('[data-ndb-window-controls="expanded"]')
        ->assertScript('document.querySelector(\'[data-ndb-window-controls="expanded"] [data-ndb-window-action="expand"]\').disabled === true')
        ->assertScript('document.querySelector(\'[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]\').disabled === false')
        ->assertScript('document.querySelector(\'[data-ndb-window-controls="expanded"] [data-ndb-window-action="close"]\').disabled === false')
        ->assertScript(<<<'JS'
            (() => {
                const controls = document.querySelector('[data-ndb-window-controls="expanded"]');
                const expand = controls.querySelector('[data-ndb-window-action="expand"]');
                const shrink = controls.querySelector('[data-ndb-window-action="shrink"]');

                return Number.parseFloat(getComputedStyle(expand).opacity)
                    < Number.parseFloat(getComputedStyle(shrink).opacity);
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const controls = document.querySelector('[data-ndb-window-controls="expanded"]');
                const utility = document.querySelector('[data-ndb-inspector-utility-actions]');
                const separator = document.querySelector('[data-ndb-inspector-actions] [data-ndb-window-controls-separator]');
                const utilityBox = utility.getBoundingClientRect();
                const separatorBox = separator.getBoundingClientRect();
                const controlsBox = controls.getBoundingClientRect();

                return utility.getAttribute('aria-label') === 'Tools'
                    && controls.getAttribute('aria-label') === 'Window controls'
                    && utilityBox.right < controlsBox.left
                    && separatorBox.width >= 1
                    && separatorBox.height > 0
                    && Boolean(utility.compareDocumentPosition(separator) & Node.DOCUMENT_POSITION_FOLLOWING)
                    && Boolean(separator.compareDocumentPosition(controls) & Node.DOCUMENT_POSITION_FOLLOWING);
            })()
            JS)
        ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]')
        ->wait(0.2)
        ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="close"]')
        ->wait(0.2)
        ->assertScript(<<<'JS'
            (() => {
                window.dispatchEvent(new KeyboardEvent('keydown', {
                    key: 'P',
                    metaKey: true,
                    shiftKey: true,
                }));

                return getComputedStyle(document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]')).display === 'none'
                    && getComputedStyle(document.querySelector('[role="dialog"][aria-label="Request inspector"]')).display === 'none'
                    && getComputedStyle(document.querySelector('[role="dialog"][aria-label="Command palette"]')).display === 'none'
                    && document.querySelector('[data-testid="host-page"]').inert === false
                    && document.body.style.overflow === '';
            })()
            JS)
        ->refresh()
        ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="close"]')
        ->wait(0.2)
        ->assertScript('getComputedStyle(document.querySelector(\'[role="toolbar"][aria-label="Debug toolbar"]\')).display === "none"')
        ->refresh()
        ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]')
        ->assertNoJavaScriptErrors();
});

it('pins overview before alphabetized active sections and keeps quiet sections in the palette', function () {
    $page = visit('/profiled-rich');
    $page->script("localStorage.setItem('newdebugbar.preferences.v1', JSON.stringify({theme: 'light', sectionMode: 'all', favorites: []}))");

    $page
        ->refresh()
        ->resize(1440, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->assertMissing('[data-ndb-section-mode]')
        ->assertMissing('[data-ndb-quiet-count]')
        ->assertDontSee('quiet hidden')
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));
                const visible = state.orderedSections.filter((section) => state.isSectionVisible(section));

                return visible.length < state.summary.sections.length
                    && visible.every((section) => section.active !== false || state.favorites.includes(section.key) || section.key === state.selected);
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const labels = Array.from(document.querySelectorAll('[data-ndb-section-visible="true"] .ndb-section-label'))
                    .map((label) => label.textContent.trim());
                const remaining = labels.slice(1);
                const sorted = [...remaining].sort((left, right) => left.localeCompare(right, undefined, { sensitivity: 'base' }));

                return labels[0] === 'Overview'
                    && JSON.stringify(remaining) === JSON.stringify(sorted);
            })()
            JS)
        ->assertAttribute('[data-ndb-section="validation"]', 'data-ndb-section-visible', 'false')
        ->assertScript('document.querySelector("[data-ndb-header-environment]").textContent.trim() === "testing"')
        ->assertScript('!["·", "•", "|"].some((separator) => document.querySelector("[data-ndb-header-facts]").textContent.includes(separator))')
        ->assertScript(<<<'JS'
            (() => {
                const top = getComputedStyle(document.querySelector('[data-ndb-header-fact="duration"]'));
                const bottom = getComputedStyle(document.querySelector('[data-ndb-toolbar="duration"]'));

                return top.borderRadius === bottom.borderRadius
                    && top.paddingLeft === bottom.paddingLeft
                    && top.paddingTop === bottom.paddingTop;
            })()
            JS)
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-header-toolbar]").parentElement).backgroundColor', 'rgb(255, 255, 255)')
        ->assertMissing('[data-ndb-section-attention]')
        ->assertVisible('[data-ndb-section="queries"] .ndb-section-count')
        ->assertMissing('[data-ndb-findings]');

    selectDebugSectionViaPalette($page, 'validation');
    assertDebugSectionSelected($page, 'validation');

    $page
        ->assertAttribute('[data-ndb-section="validation"]', 'data-ndb-section-visible', 'true')
        ->assertNoJavaScriptErrors();
});

it('prioritizes relevant activity and opens the runtime details', function () {
    $page = visit('/profiled-rich')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->assertVisible('[data-ndb-overview-activity]')
        ->assertCount('[data-ndb-overview-activity-section]', 5)
        ->assertMissing('[data-ndb-overview-activity-section] svg')
        ->assertScript(<<<'JS'
            (() => {
                const row = document.querySelector('[data-ndb-overview-activity-section]');
                const style = getComputedStyle(row);

                return style.paddingLeft === '0px' && style.paddingRight === '0px';
            })()
            JS)
        ->assertVisible('[data-ndb-overview-runtime]')
        ->assertAttribute('[data-ndb-overview-runtime]', 'open', '')
        ->assertVisible('[data-ndb-runtime-detail-panel="runtime"]')
        ->assertVisible('[data-ndb-runtime-detail-navigation]')
        ->assertScript('getComputedStyle(document.querySelector(\'[data-ndb-runtime-detail-select-wrapper]\')).display === "none"')
        ->assertMissing('[data-ndb-runtime-detail-count]')
        ->assertMissing('[data-ndb-runtime-detail-panel-count]')
        ->assertNoJavaScriptErrors();

    $page
        ->keys('[data-ndb-runtime-detail="drivers"]', 'Enter')
        ->assertVisible('[data-ndb-runtime-detail-panel="drivers"]')
        ->assertScript('document.querySelector(\'[data-ndb-runtime-detail="drivers"]\').getAttribute("aria-pressed") === "true"')
        ->resize(390, 844)
        ->assertVisible('[data-ndb-runtime-detail-select]')
        ->assertScript('getComputedStyle(document.querySelector(\'[data-ndb-runtime-detail-navigation]\')).display === "none"')
        ->assertScript('document.querySelector(\'[data-ndb-runtime-detail-select]\').value === "drivers"')
        ->select('[data-ndb-runtime-detail-select]', 'ecosystem')
        ->assertVisible('[data-ndb-runtime-detail-panel="ecosystem"]')
        ->assertScript('document.querySelector(\'[data-ndb-runtime-detail-select]\').value === "ecosystem"')
        ->assertScript(<<<'JS'
            (() => {
                const activity = document.querySelector('[data-ndb-overview-activity]');
                const runtime = document.querySelector('[data-ndb-overview-runtime]');

                return activity.scrollWidth <= activity.clientWidth
                    && runtime.scrollWidth <= runtime.clientWidth;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('caps the compact and expanded bars at the large breakpoint', function () {
    visit('/profiled')
        ->resize(1440, 900)
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const request = document.querySelector('[data-ndb-toolbar="request"]');
                const facts = document.querySelector('[data-ndb-toolbar-facts]');
                const actions = document.querySelector('[data-ndb-toolbar-actions]');
                const box = toolbar.getBoundingClientRect();
                const requestStyles = getComputedStyle(request);
                const factsStyles = getComputedStyle(facts);
                const factOrder = Array.from(facts.querySelectorAll('[data-ndb-toolbar]'))
                    .sort((left, right) => left.getBoundingClientRect().left - right.getBoundingClientRect().left)
                    .map((fact) => fact.dataset.ndbToolbar);

                return Math.abs(box.width - 1024) <= 1
                    && Math.abs(box.left - (window.innerWidth - box.width) / 2) <= 1
                    && Math.abs(window.innerWidth - box.right - box.left) <= 1
                    && requestStyles.flexGrow === '1'
                    && factsStyles.flexGrow === '0'
                    && request.getBoundingClientRect().right <= facts.getBoundingClientRect().left
                    && facts.getBoundingClientRect().right <= actions.getBoundingClientRect().left
                    && JSON.stringify(factOrder) === JSON.stringify(['environment', 'queries', 'duration', 'memory']);
            })()
            JS)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->assertScript(<<<'JS'
            (() => {
                const inspector = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                const factOrder = Array.from(document.querySelectorAll('[data-ndb-header-fact]'))
                    .sort((left, right) => left.getBoundingClientRect().left - right.getBoundingClientRect().left)
                    .map((fact) => fact.dataset.ndbHeaderFact);
                const box = inspector.getBoundingClientRect();

                return Math.abs(box.width - 1024) <= 1
                    && Math.abs(box.left - (window.innerWidth - box.width) / 2) <= 1
                    && Math.abs(window.innerWidth - box.right - box.left) <= 1
                    && JSON.stringify(factOrder) === JSON.stringify(['environment', 'queries', 'duration', 'memory']);
            })()
            JS)
        ->resize(900, 900)
        ->assertScript(<<<'JS'
            (() => {
                const inspector = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                const box = inspector.getBoundingClientRect();

                return Math.abs(box.width - window.innerWidth) <= 1
                    && Math.abs(box.left) <= 1
                    && Math.abs(window.innerWidth - box.right) <= 1;
            })()
            JS)
        ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]')
        ->wait(0.2)
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const box = toolbar.getBoundingClientRect();

                return Math.abs(box.width - (window.innerWidth - 24)) <= 1
                    && Math.abs(box.left - 12) <= 1
                    && Math.abs(window.innerWidth - box.right - 12) <= 1;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('moves focus into the inspector and returns it to its opener', function () {
    visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-window-controls=expanded] [data-ndb-window-action=shrink]")')
        ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]')
        ->wait(0.2)
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-window-controls=compact] [data-ndb-window-action=expand]")')
        ->assertNoJavaScriptErrors();
});

it('profiles application Livewire updates without profiling itself', function () {
    $page = visit('/profiled-livewire')
        ->click('[data-testid="profiled-increment"]')
        ->waitForText('1')
        ->assertScript('Alpine.$data(document.getElementById("newdebugbar")).summary.path.includes("/livewire-")');
    $profiles = collect(File::files(config('newdebugbar.storage.path')))
        ->map(fn ($file) => json_decode(File::get($file->getPathname()), true, flags: JSON_THROW_ON_ERROR));
    $livewireProfile = $profiles->first(
        fn (array $profile): bool => str_contains($profile['sections']['request']['payload']['path'], '/livewire-'),
    );

    expect($livewireProfile)->not->toBeNull()
        ->and($livewireProfile['sections']['livewire']['summary']['count'])->toBe(1);

    $page
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="livewire"]');

    assertDebugSectionSelected($page, 'livewire');

    $page
        ->assertSee('profiled-counter')
        ->assertSee('increment')
        ->assertSee('Request')
        ->assertSee('Response')
        ->assertScript('document.querySelector(\'[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]\').disabled === false')
        ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]')
        ->click('[data-testid="profiled-save"]')
        ->wait(0.2)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="livewire"]')
        ->assertSee('1 validation failure')
        ->assertSee('name')
        ->assertNoJavaScriptErrors();
});

it('keeps keyboard focus inside the command palette', function () {
    visit('/profiled')
        ->click('[data-ndb-toolbar="palette"]')
        ->wait(0.2)
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-palette-search]")')
        ->keys('[data-ndb-palette-search]', 'Shift+Tab')
        ->assertScript('document.activeElement?.dataset.ndbCommand === "collectors:show"')
        ->keys('[data-ndb-command="collectors:show"]', 'Tab')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-palette-search]")')
        ->assertNoJavaScriptErrors();
});

it('uses translucent command palette hover colors in :dataset mode', function (string $theme) {
    $preferences = json_encode([
        'theme' => $theme,
        'favorites' => [],
    ], JSON_THROW_ON_ERROR);

    visit('/profiled-rich')
        ->assertScript(<<<JS
            (() => {
                localStorage.setItem('newdebugbar.preferences.v1', '{$preferences}');

                return true;
            })()
            JS)
        ->refresh()
        ->assertAttribute('#newdebugbar', 'data-theme', $theme)
        ->click('[data-ndb-toolbar="palette"]')
        ->hover('[data-ndb-command="section:request"]')
        ->assertScript(<<<'JS'
            (() => {
                const command = document.querySelector('[data-ndb-command="section:request"]');
                const background = getComputedStyle(command).backgroundColor;
                const state = Alpine.$data(document.getElementById('newdebugbar'));
                const alpha = Number(
                    background.match(/\/\s*([\d.]+)\s*\)$/)?.[1]
                        ?? background.match(/,\s*([\d.]+)\s*\)$/)?.[1]
                        ?? 1
                );

                return state.filteredCommands[state.paletteIndex]?.id === 'section:request'
                    && alpha > 0
                    && alpha < 1;
            })()
            JS)
        ->assertNoJavaScriptErrors();
})->with(['light', 'dark']);

it('uses one metric color and balanced glass toolbar spacing', function () {
    visit('/profiled')
        ->assertScript(<<<'JS'
            getComputedStyle(document.getElementById('newdebugbar')).fontFamily.includes('Outfit Variable')
            JS)
        ->assertScript(<<<'JS'
            getComputedStyle(document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]')).borderRadius
            JS, '18px')
        ->assertScript(<<<'JS'
            getComputedStyle(document.querySelector('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')).borderRadius
            JS, '8px')
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const filter = getComputedStyle(toolbar).backdropFilter;

                return filter.includes('brightness(1.1)') && filter.includes('saturate(1.25)');
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const close = document.querySelector('[data-ndb-window-controls="compact"] [data-ndb-window-action="close"]');
                const toolbarBox = toolbar.getBoundingClientRect();
                const closeBox = close.getBoundingClientRect();
                const right = toolbarBox.right - closeBox.right;
                const top = closeBox.top - toolbarBox.top;
                const bottom = toolbarBox.bottom - closeBox.bottom;

                return Math.abs(right - top) <= 2
                    && Math.abs(top - bottom) <= 1;
            })()
            JS)
        ->assertScript('document.querySelectorAll(\'[role="toolbar"] > span\').length', 0)
        ->assertScript(<<<'JS'
            (() => {
                const metricColors = ['duration', 'memory', 'queries'].map((name) =>
                    getComputedStyle(document.querySelector(`[data-ndb-toolbar="${name}"] svg`)).color
                );
                const utilityColor = getComputedStyle(document.querySelector('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"] svg')).color;

                return new Set(metricColors).size === 1 && metricColors[0] !== utilityColor;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('uses a darker compact surface without exaggerated backdrop color', function () {
    visit('/profiled')
        ->click('[data-ndb-toolbar="palette"]')
        ->type('[data-ndb-palette-search]', 'dark theme')
        ->keys('[data-ndb-palette-search]', 'Enter')
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const style = getComputedStyle(toolbar);
                const alpha = Number(style.backgroundColor.match(/[\d.]+(?=\))$/)?.[0] ?? 1);

                return alpha >= 0.9
                    && style.backdropFilter.includes('brightness(0.75)')
                    && style.backdropFilter.includes('saturate(1)');
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('keeps package asset updates inside Livewire navigation', function () {
    $page = visit('/profiled');

    $page->script(<<<'JS'
        window.__newDebugBarNavigationSentinel = true;
        const stylesheet = document.querySelector('link[href*="/__newdebugbar/assets/newdebugbar.css"]');
        stylesheet.href = stylesheet.href.replace(/id=[^&]+/, 'id=stale-test-build');
        JS);

    $page
        ->click('[data-testid="host-navigation"]')
        ->waitForText('Second request')
        ->assertScript('window.__newDebugBarNavigationSentinel === true')
        ->assertCount('#newdebugbar', 1)
        ->assertNoJavaScriptErrors();
});

it('discovers background fetch profiles without switching reloading or flashing the host', function () {
    $page = visit('/profiled')
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));
                window.__newDebugBarActiveProfile = state.summary.id;
                window.__newDebugBarFetchSentinel = true;
                window.__newDebugBarDiscoveries = [];
                window.addEventListener('newdebugbar-profile-discovered', (event) => {
                    window.__newDebugBarDiscoveries.push(event.detail.profileId);
                });
                fetch('/api/plain-json?sequence=first');

                return true;
            })()
            JS)
        ->wait(0.3)
        ->assertScript(<<<'JS'
            (() => {
                fetch('/api/plain-json?sequence=second');

                return true;
            })()
            JS)
        ->wait(0.3)
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));
                const discoveries = window.__newDebugBarDiscoveries;

                return window.__newDebugBarFetchSentinel === true
                    && state.summary.id === window.__newDebugBarActiveProfile
                    && discoveries.length === 2
                    && discoveries[0] !== discoveries[1]
                    && location.pathname === '/profiled'
                    && document.querySelectorAll('#newdebugbar').length === 1;
            })()
            JS)
        ->assertVisible('[data-testid="host-page"]')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="history"]')
        ->assertSee('A background request was added to History.')
        ->assertSee('/api/plain-json')
        ->assertNoJavaScriptErrors();
});

it('keeps host styles and package styles isolated', function () {
    visit('/hostile-styles')
        ->assertScript(<<<'JS'
            (() => {
                const style = getComputedStyle(document.querySelector('[data-testid="host-button"]'));

                return style.backgroundColor === 'rgb(255, 0, 0)'
                    && style.borderRadius === '0px'
                    && style.color === 'rgb(0, 128, 0)'
                    && style.height === '91px';
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const style = getComputedStyle(document.querySelector('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]'));

                return style.backgroundColor === 'rgba(0, 0, 0, 0)'
                    && style.borderRadius === '8px'
                    && style.height === '32px';
            })()
            JS)
        ->assertScript("getComputedStyle(document.getElementById('newdebugbar')).fontFamily.includes('Outfit Variable')")
        ->assertNoJavaScriptErrors();
});

it('switches every section after Livewire navigation with one active state', function () {
    $page = visit('/profiled')
        ->click('[data-testid="host-navigation"]')
        ->waitForText('Second request')
        ->assertPathIs('/profiled-next')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2);

    foreach (['request', 'timeline', 'queries', 'models', 'cache', 'views', 'events', 'logs', 'exceptions', 'history', 'overview', 'models'] as $section) {
        selectDebugSectionViaPalette($page, $section);

        assertDebugSectionSelected($page, $section);
    }

    $page->assertNoJavaScriptErrors();
});

it('filters the timeline without inventing spans for point events', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="timeline"]')
        ->wait(0.2);

    assertDebugSectionSelected($page, 'timeline');

    $page
        ->assertPresent('[data-ndb-timeline-item="request-start"]')
        ->assertVisible('[data-ndb-timeline-waterfall]')
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[data-ndb-timeline-toolbar]');
                const search = document.querySelector('[data-ndb-timeline-search]').getBoundingClientRect();
                const resultsHeader = document.querySelector('[data-ndb-timeline-results-header]').getBoundingClientRect();

                return toolbar.getBoundingClientRect().bottom <= resultsHeader.top
                    && toolbar.scrollWidth <= toolbar.clientWidth
                    && search.top >= resultsHeader.top
                    && search.bottom <= resultsHeader.bottom;
            })()
            JS)
        ->assertScript('document.querySelectorAll("[data-ndb-timeline-filter]").length', 3)
        ->assertScript(<<<'JS'
            (() => {
                const values = Array.from(document.querySelector('[data-ndb-timeline-more]').options)
                    .map((option) => option.value);

                return values[0] === ''
                    && !values.includes('request')
                    && values.includes('lifecycle')
                    && values.includes('queries')
                    && values.includes('events');
            })()
            JS)
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-timeline-filter=key]")).whiteSpace === "nowrap"')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-filter]')).every((button) => {
                const style = getComputedStyle(button);

                return parseFloat(style.borderBottomLeftRadius) > 0
                    && style.borderTopColor === style.borderBottomColor;
            })
            JS)
        ->keys('[data-ndb-timeline-filter="all"]', 'Enter')
        ->assertAttribute('[data-ndb-timeline-filter="all"]', 'aria-pressed', 'true')
        ->assertValue('[data-ndb-timeline-more]', '')
        ->assertScript('document.querySelector("[data-ndb-timeline-tick=\\"0\\"]").getBoundingClientRect().left > document.querySelector("[data-ndb-timeline-tick=\\"0\\"]").parentElement.parentElement.getBoundingClientRect().left + 4')
        ->assertScript('document.querySelectorAll("[data-ndb-timeline-item]:not([hidden])").length > 2')
        ->assertScript(<<<'JS'
            Number(document.querySelector('[data-ndb-section-panel="timeline"] [x-text="visibleTimelineCount"]').textContent)
                === document.querySelectorAll('[data-ndb-timeline-item]:not([hidden])').length
            JS)
        ->select('[data-ndb-timeline-more]', 'queries')
        ->assertValue('[data-ndb-timeline-more]', 'queries')
        ->assertScript('document.querySelectorAll("[data-ndb-timeline-filter][aria-pressed=true]").length', 0)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item]:not([hidden])'))
                .every((item) => item.dataset.section === 'queries')
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item][hidden]'))
                .every((item) => getComputedStyle(item).display === 'none')
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item][data-section="queries"]'))
                .every((item) => {
                    const track = item.querySelector('[data-ndb-timeline-track]').getBoundingClientRect();
                    const mark = item.querySelector('[data-ndb-timeline-mark]').getBoundingClientRect();

                    return item.dataset.kind === 'span'
                        && Number(item.dataset.start) < Number(item.dataset.position)
                        && Number(item.dataset.duration) > 0
                        && mark.width >= 3
                        && mark.left >= track.left
                        && mark.right <= track.right + 1;
                })
            JS)
        ->select('[data-ndb-timeline-more]', 'events')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item]:not([hidden])'))
                .every((item) => item.dataset.kind === 'point'
                    && item.querySelector('[data-ndb-timeline-mark]').getBoundingClientRect().width > 0)
            JS)
        ->click('[data-ndb-timeline-filter="request"]')
        ->assertAttribute('[data-ndb-timeline-filter="request"]', 'aria-pressed', 'true')
        ->assertValue('[data-ndb-timeline-more]', '')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item]:not([hidden])'))
                .every((item) => item.dataset.section === 'request')
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item][hidden]'))
                .every((item) => getComputedStyle(item).display === 'none')
            JS)
        ->resize(390, 844)
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[data-ndb-timeline-toolbar]');

                return toolbar.scrollWidth <= toolbar.clientWidth
                    && document.querySelector('[data-ndb-timeline-tabs]').scrollWidth
                        <= document.querySelector('[data-ndb-timeline-tabs]').clientWidth;
            })()
            JS)
        ->type('[data-ndb-timeline-search]', 'nothing can match this')
        ->assertScript('document.querySelectorAll("[data-ndb-timeline-item]:not([hidden])").length', 0)
        ->assertSee('No timeline events match these filters.')
        ->assertNoJavaScriptErrors();
});

it('presents useful model evidence with progressive controls', function () {
    $page = visit('/profiled-models')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="models"]')
        ->assertSee('Find repeated record loads, unexpected writes, and when the work happened.')
        ->assertSee('Repeated means extra retrievals after a record’s first load.')
        ->assertSee('20 repeated loads')
        ->assertSee('44 retrievals across 24 distinct records')
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
        ->assertSee('Model boot lifecycle')
        ->keys('[data-ndb-model-group]:first-of-type > summary', 'Enter')
        ->assertAttribute('[data-ndb-model-group]:first-of-type', 'open', '')
        ->assertSee('studio_jobs')
        ->assertScript('document.querySelectorAll("[data-ndb-model-group]:first-of-type [data-ndb-model-record]").length', 6)
        ->assertScript('document.querySelectorAll("[data-ndb-model-group]:first-of-type [data-ndb-model-record][data-loads]:not([data-loads=\"1\"])").length', 5)
        ->assertScript('document.querySelector("[data-ndb-model-group]:first-of-type [data-ndb-model-raw]").open === false')
        ->click('[data-ndb-model-expand-all]')
        ->assertScript('Array.from(document.querySelectorAll("[data-ndb-model-group]")).every((group) => group.open)')
        ->assertScript('Array.from(document.querySelectorAll("[data-ndb-model-raw]")).every((group) => ! group.open)')
        ->click('[data-ndb-model-expand-all]')
        ->assertScript('Array.from(document.querySelectorAll("[data-ndb-model-group]")).every((group) => ! group.open)')
        ->assertNoJavaScriptErrors();
});

it('keeps model evidence contained on a narrow screen', function () {
    $page = visit('/profiled-models')
        ->resize(390, 844)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-inspector-action="palette"]')
        ->click('[data-ndb-command="collectors:show"]')
        ->wait(0.1)
        ->click('[data-ndb-command="section:models"]')
        ->assertVisible('[data-ndb-section-panel="models"]')
        ->assertScript(<<<'JS'
            (() => {
                const content = document.querySelector('#newdebugbar main');
                const panel = document.querySelector('[data-ndb-section-panel="models"]');

                return panel.getBoundingClientRect().width <= content.clientWidth + 1
                    && content.scrollWidth <= content.clientWidth + 1;
            })()
            JS)
        ->keys('[data-ndb-model-group]:first-of-type > summary', 'Enter')
        ->assertAttribute('[data-ndb-model-group]:first-of-type', 'open', '')
        ->assertScript(<<<'JS'
            (() => {
                const content = document.querySelector('#newdebugbar main');
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
        ->wait(0.2)
        ->click('[data-ndb-select-section="models"]')
        ->assertSee('1 model change')
        ->assertSee('Changes appear first because they can affect application state.')
        ->assertScript(<<<'JS'
            (() => {
                const first = document.querySelector('[data-ndb-model-group]');

                return first.dataset.changes === '1'
                    && first.querySelector('[data-ndb-model-name]').textContent.trim() === 'Client';
            })()
            JS)
        ->keys('[data-ndb-model-group]:first-of-type > summary', 'Enter')
        ->assertSee('Model changes')
        ->assertSee('1 updated')
        ->assertNoJavaScriptErrors();
});

it('presents grouped Laravel activity with useful controls', function () {
    visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="cache"]')
        ->assertSee('Hit rate')
        ->assertSee('Misses')
        ->click('[data-ndb-select-section="events"]')
        ->assertScript(<<<'JS'
            ['application', 'all', 'framework'].every((source) => {
                const expected = source === 'all'
                    ? document.querySelectorAll('[data-ndb-event-item]').length
                    : document.querySelectorAll(`[data-ndb-event-item][data-source="${source}"]`).length;
                const count = document.querySelector(`[data-ndb-event-source-count="${source}"]`);

                return count && Number(count.textContent.trim()) === expected;
            })
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-event-source]')).every((button) => {
                const style = getComputedStyle(button);

                return parseFloat(style.borderBottomLeftRadius) > 0
                    && style.borderTopColor === style.borderBottomColor;
            })
            JS)
        ->click('[data-ndb-event-source="application"]')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-event-item]:not([hidden])'))
                .every((item) => item.dataset.source === 'application')
            JS)
        ->type('[data-ndb-event-search]', 'application.ready')
        ->assertScript('document.querySelectorAll("[data-ndb-event-item]:not([hidden])").length', 1)
        ->click('[data-ndb-select-section="logs"]')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-log-level]')).every((button) => {
                const style = getComputedStyle(button);

                return parseFloat(style.borderBottomLeftRadius) > 0
                    && style.borderTopColor === style.borderBottomColor;
            })
            JS)
        ->click('[data-ndb-log-level="info"]')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-log-item]:not([hidden])'))
                .every((item) => item.dataset.level === 'info')
            JS)
        ->type('[data-ndb-log-search]', 'profiled request')
        ->assertScript('document.querySelectorAll("[data-ndb-log-item]:not([hidden])").length', 1)
        ->assertNoJavaScriptErrors();
});

it('uses light dividers above expanded shared JSON details', function () {
    $page = visit('/profiled-models');
    $page->script("localStorage.setItem('newdebugbar.preferences.v1', JSON.stringify({theme: 'light', favorites: []}))");

    $page
        ->refresh()
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="models"]')
        ->click('[data-ndb-model-group]:first-of-type > summary')
        ->click('[data-ndb-model-group]:first-of-type [data-ndb-model-raw] > summary')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-model-raw] pre")).borderTopColor === getComputedStyle(document.querySelector("[data-ndb-model-raw]")).borderTopColor')
        ->assertNoJavaScriptErrors();
});

it('uses light dividers above expanded cache JSON details', function () {
    $page = visit('/profiled');
    $page->script("localStorage.setItem('newdebugbar.preferences.v1', JSON.stringify({theme: 'light', favorites: []}))");

    $page
        ->refresh()
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="cache"]')
        ->click('[data-ndb-section-panel="cache"] details:first-of-type summary')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-section-panel=\\"cache\\"] details pre")).borderTopColor === getComputedStyle(document.querySelector("[data-ndb-section-panel=\\"cache\\"] details")).borderTopColor')
        ->assertNoJavaScriptErrors();
});

it('shows an aligned request trace and switches request detail groups', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="request"]')
        ->assertVisible('[data-ndb-request-trace]')
        ->assertVisible('[data-ndb-request-details]')
        ->assertScript('document.querySelector("[data-ndb-request-details]").open === false')
        ->click('[data-ndb-request-details] > summary')
        ->assertScript('document.querySelector("[data-ndb-request-details]").open === true')
        ->assertScript('document.querySelectorAll("[data-ndb-request-step]").length', 3)
        ->assertScript('document.querySelectorAll("[data-ndb-request-line]").length', 2)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-request-step]')).every((step) => {
                const dot = step.querySelector('[data-ndb-request-dot]').getBoundingClientRect();
                const heading = step.querySelector('h3').getBoundingClientRect();

                return Math.abs((dot.top + dot.height / 2) - (heading.top + heading.height / 2)) < 1;
            })
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-request-line]')).every((line, index) => {
                const nextDot = document.querySelectorAll('[data-ndb-request-dot]')[index + 1].getBoundingClientRect();
                const bounds = line.getBoundingClientRect();

                return Math.abs(bounds.bottom - nextDot.top) < 1
                    && Math.abs(bounds.width - 2) < 0.1;
            })
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-request-detail]')).every((button) => {
                const parent = button.parentElement;
                const styles = getComputedStyle(parent);
                const availableWidth = parent.clientWidth
                    - parseFloat(styles.paddingLeft)
                    - parseFloat(styles.paddingRight);

                return Math.abs(button.getBoundingClientRect().width - availableWidth) < 1;
            })
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-request-detail-count], [data-ndb-request-detail-panel-count]'))
                .every((count) => /^\d+$/.test(count.textContent.trim()))
            JS)
        ->assertAttribute('[data-ndb-request-detail="headers"]', 'aria-pressed', 'true')
        ->click('[data-ndb-request-detail="session"]')
        ->assertAttribute('[data-ndb-request-detail="session"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-request-detail-panel="session"]')
        ->assertNoJavaScriptErrors();
});

it('shows log call sites', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="logs"]')
        ->assertSee('tests/TestCase.php')
        ->click('[data-ndb-log-item] > summary')
        ->assertPresent('[data-ndb-copy-log-callsite="0"]')
        ->assertNoJavaScriptErrors();

    assertDebugSectionSelected($page, 'logs');
});

it('presents Laravel decisions lifecycle messages and source context without editor links', function () {
    $page = visit('/profiled-context')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->assertMissing('[data-ndb-findings]')
        ->click('[data-ndb-select-section="authorization"]')
        ->assertScript('document.querySelector("[data-ndb-section-heading]").textContent.trim() === "Authorization"')
        ->assertAttribute('[data-ndb-select-section="authorization"]', 'aria-current', 'page')
        ->click('[data-ndb-authorization-filter="denied"]')
        ->assertAttribute('[data-ndb-authorization-filter="denied"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelectorAll("[data-ndb-authorization-item]:not([hidden])").length', 1)
        ->assertScript('document.querySelector("[data-ndb-authorization-item]:not([hidden])").dataset.result === "denied"')
        ->assertSee('delete-profile')
        ->click('[data-ndb-authorization-filter="allowed"]')
        ->assertAttribute('[data-ndb-authorization-filter="allowed"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelector("[data-ndb-authorization-item]:not([hidden])").dataset.result === "allowed"')
        ->assertSee('inspect-profile')
        ->click('[data-ndb-select-section="lifecycle"]')
        ->assertSee('Route matching')
        ->assertSee('Route response preparation')
        ->assertSee('Final response preparation')
        ->click('[data-ndb-select-section="messages"]')
        ->assertSee('Checkout checkpoint')
        ->click('[data-ndb-select-section="views"]')
        ->click('[data-ndb-view-group] > summary')
        ->assertSee('tests/views/context.blade.php')
        ->assertPresent('[data-ndb-view-data]')
        ->assertAttribute('[data-ndb-view-data-trigger]', 'aria-expanded', 'false')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-view-data-popover]")).display === "none"')
        ->assertScript(<<<'JS'
            (() => {
                const render = document.querySelector('[data-ndb-view-render]');
                const renderRow = render?.querySelector('[data-ndb-view-render-row]');
                const renderContext = render?.querySelector('[data-ndb-view-render-context]');
                const viewDataTrigger = render?.querySelector('[data-ndb-view-data-trigger]');
                const viewDataPopover = render?.querySelector('[data-ndb-view-data-popover]');
                const contextRect = renderContext?.getBoundingClientRect();
                const triggerRect = viewDataTrigger?.getBoundingClientRect();

                return render !== null
                    && renderRow !== null
                    && renderContext !== null
                    && viewDataTrigger !== null
                    && viewDataPopover !== null
                    && viewDataTrigger.parentElement === renderRow
                    && renderContext.parentElement === renderRow
                    && getComputedStyle(renderRow).alignItems === 'center'
                    && getComputedStyle(renderContext).alignItems === 'baseline'
                    && Math.abs((contextRect.top + contextRect.bottom) / 2 - (triggerRect.top + triggerRect.bottom) / 2) <= 1
                    && Math.abs(viewDataTrigger.getBoundingClientRect().right - render.getBoundingClientRect().right) <= 1
                    && viewDataTrigger.getAttribute('aria-controls') === viewDataPopover.id
                    && viewDataPopover.getAttribute('role') === 'region'
                    && viewDataPopover.hasAttribute('x-transition:enter')
                    && viewDataPopover.getAttribute('x-transition:enter-start').includes('ndb:scale-95');
            })()
            JS)
        ->click('[data-ndb-view-data-trigger]')
        ->assertAttribute('[data-ndb-view-data-trigger]', 'aria-expanded', 'true')
        ->assertVisible('[data-ndb-view-data-popover]')
        ->assertVisible('[data-ndb-view-data]')
        ->assertSee('view-data-value')
        ->assertScript(<<<'JS'
            (() => {
                const code = document.querySelector('[data-ndb-view-data] code[data-ndb-language="json"][data-highlighted]');
                const property = code?.querySelector('.hljs-attr');
                const string = code?.querySelector('.hljs-string');

                return code !== null
                    && code.textContent.includes('\n')
                    && code.textContent.includes('"private_value": "view-data-value"')
                    && code.textContent.includes('"rows": [')
                    && Number.parseFloat(getComputedStyle(code).fontSize) >= 12
                    && property !== null
                    && string !== null
                    && code.querySelector('.hljs-literal') !== null
                    && getComputedStyle(property).color !== getComputedStyle(string).color;
            })()
            JS)
        ->keys('[data-ndb-view-data-trigger]', 'Escape')
        ->wait(0.2)
        ->assertAttribute('[data-ndb-view-data-trigger]', 'aria-expanded', 'false')
        ->assertScript(<<<'JS'
            (() => {
                const trigger = document.querySelector('[data-ndb-view-data-trigger]');
                const popover = document.querySelector('[data-ndb-view-data-popover]');

                return document.activeElement === trigger
                    && getComputedStyle(popover).display === 'none';
            })()
            JS)
        ->click('[data-ndb-view-data-trigger]')
        ->resize(390, 844)
        ->assertScript(<<<'JS'
            (() => {
                const render = document.querySelector('[data-ndb-view-render]');
                const viewDataTrigger = render?.querySelector('[data-ndb-view-data-trigger]');
                const viewDataPopover = render?.querySelector('[data-ndb-view-data-popover]');

                return render !== null
                    && viewDataTrigger !== null
                    && viewDataPopover !== null
                    && document.documentElement.scrollWidth <= document.documentElement.clientWidth
                    && viewDataTrigger.getBoundingClientRect().right <= render.getBoundingClientRect().right + 1
                    && viewDataPopover.getBoundingClientRect().left >= 0
                    && viewDataPopover.getBoundingClientRect().right <= window.innerWidth;
            })()
            JS)
        ->resize(1440, 900)
        ->click('[data-ndb-view-source]')
        ->wait(0.2)
        ->assertAttribute('[data-ndb-view-data-trigger]', 'aria-expanded', 'false')
        ->assertMissing('a[href^="vscode://file/"]')
        ->click('[data-ndb-select-section="events"]')
        ->click('[data-ndb-event-item]:first-child summary')
        ->assertSee(ProfiledApplicationListener::class.'@handle')
        ->assertMissing('a[href^="vscode://file/"]')
        ->assertNoJavaScriptErrors();

    assertDebugSectionSelected($page, 'events');
});

it('shows relative exception frames and highlighted source context', function () {
    $page = visit('/profiled-reported-exception')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="exceptions"]');

    assertDebugSectionSelected($page, 'exceptions');

    $page
        ->assertSee('Application frames')
        ->assertSee('Vendor frames')
        ->assertSee('tests/TestCase.php')
        ->assertDontSee('/Users/benjamin/Sites/new-debug-bar/tests/TestCase.php')
        ->assertPresent('[data-ndb-copy-exception-callsite="0"]')
        ->assertScript('document.querySelectorAll("#newdebugbar code[data-ndb-language=php][data-highlighted]").length > 0')
        ->assertNoJavaScriptErrors();
});

it('keeps favoriting active and repeatable after Livewire navigation', function () {
    $page = visit('/profiled')
        ->click('[data-testid="host-navigation"]')
        ->waitForText('Second request')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="models"]');

    assertDebugSectionSelected($page, 'models');

    $favorite = '[data-ndb-toggle-favorite="models"]';
    $row = '[data-ndb-section="models"]';

    $page
        ->assertCount($row, 1)
        ->assertAttribute($favorite, 'aria-pressed', 'false')
        ->click($favorite)
        ->assertAttribute($favorite, 'aria-pressed', 'true')
        ->assertAttribute($row, 'data-ndb-favorite', 'true');

    assertDebugSectionSelected($page, 'models');

    $page
        ->click($favorite)
        ->assertAttribute($favorite, 'aria-pressed', 'false')
        ->assertAttribute($row, 'data-ndb-favorite', 'false');

    assertDebugSectionSelected($page, 'models');

    $page
        ->click($favorite)
        ->assertAttribute($favorite, 'aria-pressed', 'true')
        ->click('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]')
        ->click('[data-testid="host-navigation"]')
        ->waitForText('First request')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->assertAttribute($favorite, 'aria-pressed', 'true')
        ->assertNoJavaScriptErrors();
});

it('reorders favorites with the keyboard and drag and drop', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2);

    foreach (['request', 'overview', 'queries'] as $section) {
        $page->click("[data-ndb-toggle-favorite=\"{$section}\"]");
    }

    assertFavoriteOrder($page, 'request,overview,queries');

    $page
        ->assertScript('Array.from(document.querySelectorAll("[data-ndb-favorites-heading]")).filter((heading) => heading.offsetParent !== null).length', 1)
        ->assertScript('Array.from(document.querySelectorAll("[data-ndb-sections-heading]")).filter((heading) => heading.offsetParent !== null).length', 1)
        ->assertScript(<<<'JS'
            (() => {
                const heading = document.querySelector('[data-ndb-favorites-heading]');
                const firstFavorite = document.querySelector('[data-ndb-section][data-ndb-favorite="true"]');

                return (heading.compareDocumentPosition(firstFavorite) & Node.DOCUMENT_POSITION_FOLLOWING) !== 0;
            })()
            JS);

    $page->keys('[data-ndb-select-section="overview"]', 'Shift+ArrowUp');
    assertFavoriteOrder($page, 'overview,request,queries');

    $page->drag('[data-ndb-section="queries"]', '[data-ndb-section="overview"]');
    assertFavoriteOrder($page, 'queries,overview,request');

    $page
        ->refresh()
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2);

    assertFavoriteOrder($page, 'queries,overview,request');

    $page->assertNoJavaScriptErrors();
});

it('shows the favorite source and insertion point while dragging', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2);

    foreach (['request', 'overview', 'queries'] as $section) {
        $page->click("[data-ndb-toggle-favorite=\"{$section}\"]");
    }

    $page
        ->wait(0.5)
        ->assertAttribute('[data-ndb-toggle-favorite="request"]', 'aria-pressed', 'true')
        ->assertAttribute('[data-ndb-toggle-favorite="overview"]', 'aria-pressed', 'true')
        ->assertAttribute('[data-ndb-toggle-favorite="queries"]', 'aria-pressed', 'true')
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
        ->assertAttribute('[data-ndb-section="queries"]', 'data-ndb-dragging', 'true')
        ->assertVisible('[data-ndb-favorite-drop-before="overview"]')
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.querySelector('[data-ndb-section="queries"]'));
                state.endFavoriteDrag();

                return state.favoriteDrag === null && state.favoriteDrop === null;
            })()
            JS)
        ->assertAttribute('[data-ndb-section="queries"]', 'data-ndb-dragging', 'false')
        ->assertAttribute('[data-ndb-favorite-drop-before="overview"]', 'hidden', '')
        ->assertNoJavaScriptErrors();
});

it('uses the command palette, theme preference, and escape layers', function () {
    $page = visit('/profiled')
        ->assertAttribute('#newdebugbar', 'data-theme', 'light')
        ->click('[data-ndb-toolbar="palette"]')
        ->assertVisible('[role="dialog"][aria-label="Command palette"]')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-palette-search]")')
        ->type('[data-ndb-palette-search]', 'models')
        ->keys('[data-ndb-palette-search]', 'Enter')
        ->wait(0.2);

    assertDebugSectionSelected($page, 'models');

    $page
        ->click('[data-ndb-inspector-action="palette"]')
        ->type('[data-ndb-palette-search]', 'dark theme')
        ->keys('[data-ndb-palette-search]', 'Enter')
        ->assertAttribute('#newdebugbar', 'data-theme', 'dark')
        ->refresh()
        ->assertAttribute('#newdebugbar', 'data-theme', 'dark')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->keys('[data-ndb-inspector-action="palette"]', 'Meta+Shift+P')
        ->assertVisible('[role="dialog"][aria-label="Command palette"]')
        ->keys('[data-ndb-palette-search]', 'Escape')
        ->assertScript('getComputedStyle(document.querySelector("[role=dialog][aria-label=\\"Command palette\\"]")).display === "none"')
        ->assertVisible('[role="dialog"][aria-label="Request inspector"]')
        ->keys('[data-ndb-window-controls="expanded"] [data-ndb-window-action="shrink"]', 'Escape')
        ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]')
        ->assertNoJavaScriptErrors();
});

it('highlights repeated SQL and switches query evidence tabs', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-toolbar="queries"]')
        ->waitForText('Repeated pattern')
        ->assertSee('Find repeated work, slow SQL, and the application code that triggered it.')
        ->assertScript('document.querySelectorAll("#newdebugbar code[data-ndb-language=sql][data-highlighted]").length > 0')
        ->assertAttribute('[data-ndb-query-group-execution][open]', 'open', '')
        ->click('[data-ndb-query-group-execution][open] [data-ndb-query-tab="bindings"]')
        ->assertAttribute('[data-ndb-query-group-execution][open] [data-ndb-query-tab="bindings"]', 'aria-selected', 'true')
        ->assertScript(<<<'JS'
            (() => {
                const tablist = document.querySelector('[data-ndb-query-group-execution][open] [data-ndb-query-tabs]');
                const active = tablist?.querySelector('[role="tab"][aria-selected="true"]');
                const inactive = tablist?.querySelector('[role="tab"][aria-selected="false"]');

                if (! active || ! inactive) return false;

                const activeStyle = getComputedStyle(active);
                const inactiveStyle = getComputedStyle(inactive);

                return activeStyle.backgroundColor !== inactiveStyle.backgroundColor
                    && activeStyle.color !== inactiveStyle.color
                    && Number.parseFloat(activeStyle.minHeight) >= 32;
            })()
            JS)
        ->keys('[data-ndb-query-group-execution][open] [data-ndb-query-tab="bindings"]', 'ArrowRight')
        ->assertAttribute('[data-ndb-query-group-execution][open] [data-ndb-query-tab="stack"]', 'aria-selected', 'true')
        ->keys('[data-ndb-query-group-execution][open] [data-ndb-query-tab="stack"]', 'ArrowLeft')
        ->assertAttribute('[data-ndb-query-group-execution][open] [data-ndb-query-tab="bindings"]', 'aria-selected', 'true')
        ->click('[data-ndb-query-group-execution][open] [data-ndb-query-actions] > summary')
        ->assertVisible('[data-ndb-query-group-execution][open] [data-ndb-query-actions] button:first-of-type')
        ->keys('[data-ndb-query-group-execution][open] [data-ndb-query-actions] > summary', 'Escape')
        ->assertScript('document.querySelector("[data-ndb-query-group-execution][open] [data-ndb-query-actions]").open === false')
        ->assertNoJavaScriptErrors();
});

it('keeps repeated SQL on one shared syntax-highlighted surface in :dataset mode', function (string $theme) {
    $preferences = json_encode([
        'theme' => $theme,
        'favorites' => [],
    ], JSON_THROW_ON_ERROR);

    visit('/profiled-rich')
        ->assertScript(<<<JS
            (() => {
                localStorage.setItem('newdebugbar.preferences.v1', '{$preferences}');

                return true;
            })()
            JS)
        ->refresh()
        ->assertAttribute('#newdebugbar', 'data-theme', $theme)
        ->click('[data-ndb-toolbar="queries"]')
        ->assertScript(<<<'JS'
            (() => {
                const sharedSql = document.querySelectorAll(
                    '[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-pattern] code[data-ndb-language="sql"][data-highlighted]',
                );
                const duplicateItems = document.querySelectorAll('[data-ndb-query-item]:not([hidden])');

                return sharedSql.length === 1 && duplicateItems.length === 0;
            })()
            JS)
        ->assertNoJavaScriptErrors();
})->with(['light', 'dark']);

it('filters searches sorts and shows repeated query evidence without another disclosure', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-toolbar="queries"]')
        ->waitForText('Needs attention')
        ->assertMissing('[data-ndb-findings]')
        ->assertScript(<<<'JS'
            (() => {
                const buttons = Array.from(document.querySelectorAll('[data-ndb-query-filter]'));

                return buttons.filter((button) => button.getAttribute('aria-pressed') === 'true').length === 1
                    && buttons.every((button) => {
                        const style = getComputedStyle(button);

                        return parseFloat(style.borderBottomLeftRadius) > 0
                            && style.borderTopColor === style.borderBottomColor;
                    });
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const items = Array.from(document.querySelectorAll('[data-ndb-query-item]'));
                const groups = Array.from(document.querySelectorAll('[data-ndb-query-group]'));
                const expected = {
                    all: items.length,
                    attention: groups.reduce((count, group) => count + Number(group.dataset.resultCount), 0)
                        + items.filter((item) => item.dataset.repeated !== 'true' && item.dataset.slow === 'true').length,
                    read: items.filter((item) => item.dataset.type === 'read').length,
                    write: items.filter((item) => item.dataset.type === 'write').length,
                };

                return Object.entries(expected).every(([filter, count]) =>
                    Number(document.querySelector(`[data-ndb-query-filter-count="${filter}"]`).textContent.trim()) === count
                );
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const tabs = document.querySelector('[aria-label="Filter queries"]').getBoundingClientRect();
                const count = document.querySelector('[data-ndb-query-result-count]').getBoundingClientRect();
                const search = document.querySelector('[data-ndb-query-search]').getBoundingClientRect();

                return tabs.bottom <= count.top
                    && tabs.bottom <= search.top
                    && count.right < search.left;
            })()
            JS)
        ->assertScript('document.querySelectorAll("[data-ndb-query-item]:not([hidden])").length', 0)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden])").length', 1)
        ->assertScript('document.querySelector("[data-ndb-query-result-count]").textContent.replace(/\\s+/g, " ").trim() === "3 results"')
        ->click('[data-ndb-query-filter="attention"]')
        ->assertAttribute('[data-ndb-query-filter="attention"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelectorAll("[data-ndb-query-item]:not([hidden])").length', 0)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden])").length', 1)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-pattern] code[data-ndb-language=sql]").length', 1)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-executions] > details").length', 3)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-executions] > details[open]").length', 1)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden]) [data-ndb-query-connection]").length', 3)
        ->assertScript(<<<'JS'
            document.querySelector('[data-ndb-query-group]:not([hidden])').getBoundingClientRect().top
                >= document.querySelector('[data-ndb-section-heading]').parentElement.getBoundingClientRect().bottom - 1
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-executions] > details'))
                .every((article) => article.querySelector(':scope > pre code[data-ndb-language="sql"]') === null)
            JS)
        ->assertSee('Likely N+1 pattern')
        ->click('[data-ndb-query-filter="read"]')
        ->assertScript('document.querySelectorAll("[data-ndb-query-filter][aria-pressed=true]").length', 1)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden])").length', 1)
        ->select('[data-ndb-query-sort]', 'duration')
        ->assertValue('[data-ndb-query-sort]', 'duration')
        ->assertScript(<<<'JS'
            (() => {
                const durations = Array.from(document.querySelectorAll('[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-execution]'))
                    .map((query) => Number(query.dataset.duration));

                return durations.every((duration, index) => index === 0 || durations[index - 1] >= duration);
            })()
            JS)
        ->type('[data-ndb-query-search]', 'no query can match this')
        ->assertScript('document.querySelectorAll("[data-ndb-query-item]:not([hidden])").length', 0)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden])").length', 0)
        ->assertSee('No queries match these filters.')
        ->assertNoJavaScriptErrors();
});

it('filters retained history and compares the current path', function () {
    $page = visit('/profiled')
        ->refresh()
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="history"]');

    assertDebugSectionSelected($page, 'history');

    $page
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-history-warning]')).every((button) => {
                const style = getComputedStyle(button);

                return parseFloat(style.borderBottomLeftRadius) > 0
                    && style.borderTopColor === style.borderBottomColor;
            })
            JS)
        ->assertScript('document.querySelectorAll("[data-ndb-history-profile]:not([hidden])").length >= 2')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-history-profile][data-runtime="true"]'))
                .every((profile) => getComputedStyle(profile).display === 'none')
            JS)
        ->click('[data-ndb-history-filters] summary')
        ->type('[data-ndb-history-method]', 'POST')
        ->wait(0.2)
        ->assertScript('document.querySelectorAll("[data-ndb-history-profile]:not([hidden])").length', 0)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-history-profile]'))
                .every((profile) => getComputedStyle(profile).display === 'none')
            JS)
        ->clear('[data-ndb-history-method]')
        ->wait(0.2)
        ->click('[data-ndb-compare-profile]')
        ->waitForText('Compare requests')
        ->assertPresent('[data-ndb-comparison]')
        ->assertPresent('[data-ndb-comparison-baseline]')
        ->assertPresent('[data-ndb-comparison-current]')
        ->assertMissing('[data-ndb-comparison] [data-ndb-history-profile]')
        ->assertScript('!document.querySelector("[data-ndb-comparison]").textContent.includes("#")')
        ->assertVisible('[data-ndb-section-panel="history"]')
        ->assertScript('document.querySelector("[data-ndb-section-heading]").textContent.trim() === "History"')
        ->click('[data-ndb-open-profile]')
        ->waitForText('Back to current request')
        ->assertVisible('[data-ndb-section-panel="history"]')
        ->assertScript('document.querySelector("[data-ndb-section-heading]").textContent.trim() === "History"')
        ->click('[data-ndb-return-current]')
        ->wait(0.3)
        ->assertMissing('[data-ndb-return-current]')
        ->assertVisible('[data-ndb-section-panel="history"]')
        ->assertScript('document.querySelector("[data-ndb-section-heading]").textContent.trim() === "History"')
        ->assertNoJavaScriptErrors();
});

it('keeps the main interactions usable on a phone viewport', function () {
    $page = visit('/profiled')
        ->on()->iPhone14Pro()
        ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]')
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const box = toolbar.getBoundingClientRect();

                return Math.abs(box.width - (window.innerWidth - 24)) <= 1
                    && Math.abs(box.left - 12) <= 1
                    && Math.abs(window.innerWidth - box.right - 12) <= 1;
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const toolbar = document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]');
                const request = document.querySelector('[data-ndb-toolbar="request"]');
                const facts = document.querySelector('[data-ndb-toolbar-facts]');
                const factButtons = Array.from(facts.querySelectorAll('[data-ndb-toolbar]'));
                const actions = document.querySelector('[data-ndb-toolbar-actions]');
                const close = document.querySelector('[data-ndb-window-controls="compact"] [data-ndb-window-action="close"]');
                const toolbarBox = toolbar.getBoundingClientRect();
                const requestBox = request.getBoundingClientRect();
                const factsBox = facts.getBoundingClientRect();
                const actionsBox = actions.getBoundingClientRect();
                const toolbarStyles = getComputedStyle(toolbar);

                return requestBox.width <= 113
                    && requestBox.width < toolbarBox.width / 3
                    && factsBox.width > 60
                    && getComputedStyle(facts).overflowX === 'auto'
                    && facts.scrollWidth > facts.clientWidth
                    && factButtons.length === 4
                    && factButtons.every((button) => {
                        const styles = getComputedStyle(button);

                        return styles.display === 'flex'
                            && styles.flexShrink === '0'
                            && button.getBoundingClientRect().width > 0;
                    })
                    && Math.abs(toolbarBox.right - actionsBox.right - Number.parseFloat(toolbarStyles.paddingRight)) <= 1
                    && Math.abs(actionsBox.right - close.getBoundingClientRect().right) <= 1
                    && actionsBox.left >= factsBox.right;
            })()
            JS)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->assertVisible('[data-ndb-header-memory]')
        ->assertAttribute('[data-ndb-mobile-sections-toggle]', 'aria-expanded', 'false')
        ->assertAttribute('[data-ndb-mobile-sections-toggle]', 'aria-label', 'Open sections')
        ->assertScript(<<<'JS'
            (() => {
                const toggle = document.querySelector('[data-ndb-mobile-sections-toggle]');
                const box = toggle.getBoundingClientRect();
                const styles = getComputedStyle(toggle);

                return box.width >= 44
                    && box.height >= 44
                    && Number.parseFloat(styles.borderTopWidth) === 0
                    && styles.boxShadow === 'none'
                    && styles.backgroundColor === 'rgba(0, 0, 0, 0)';
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const facts = document.querySelector('[data-ndb-header-facts]');
                const cards = Array.from(facts.querySelectorAll('[data-ndb-header-fact]'));
                const firstTop = cards[0].getBoundingClientRect().top;
                const styles = getComputedStyle(facts);

                return styles.overflowX === 'auto'
                    && Number.parseFloat(styles.columnGap) >= 8
                    && facts.scrollWidth > facts.clientWidth
                    && facts.getBoundingClientRect().height < 55
                    && cards.length === 4
                    && cards.every((card) => {
                        const box = card.getBoundingClientRect();

                        return box.width > 0
                            && Math.abs(box.top - firstTop) <= 1
                            && getComputedStyle(card).flexShrink === '0';
                    });
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const navigation = document.querySelector('#newdebugbar-section-navigation');
                const styles = getComputedStyle(navigation);
                const transitionProperties = styles.transitionProperty.split(',').map((property) => property.trim());
                const transitionDurations = styles.transitionDuration.split(',').map((duration) => duration.trim());
                const transitionDelays = styles.transitionDelay.split(',').map((delay) => delay.trim());
                const transformIndex = transitionProperties.indexOf('transform');
                const visibilityIndex = transitionProperties.indexOf('visibility');
                const transformDuration = Number.parseFloat(transitionDurations[transformIndex] ?? transitionDurations[0]);
                const visibilityDelay = Number.parseFloat(transitionDelays[visibilityIndex] ?? transitionDelays[0]);

                return styles.visibility === 'hidden'
                    && navigation.getBoundingClientRect().right <= 1
                    && transformIndex >= 0
                    && visibilityIndex >= 0
                    && transformDuration > 0
                    && visibilityDelay >= transformDuration;
            })()
            JS)
        ->click('[data-ndb-mobile-sections-toggle]')
        ->assertAttribute('[data-ndb-mobile-sections-toggle]', 'aria-expanded', 'true')
        ->assertAttribute('[data-ndb-mobile-sections-toggle]', 'aria-label', 'Close sections')
        ->assertVisible('#newdebugbar-section-navigation')
        ->assertVisible('[data-ndb-mobile-sections-backdrop]')
        ->assertScript(<<<'JS'
            (() => {
                const styles = getComputedStyle(document.querySelector('[data-ndb-mobile-sections-toggle]'));

                return Number.parseFloat(styles.borderTopWidth) === 0
                    && styles.boxShadow === 'none'
                    && styles.backgroundColor === 'rgba(0, 0, 0, 0)';
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const navigation = document.querySelector('#newdebugbar-section-navigation');
                const box = navigation.getBoundingClientRect();

                return getComputedStyle(navigation).position === 'absolute'
                    && box.left >= 0
                    && box.right <= window.innerWidth
                    && box.width <= 281
                    && document.activeElement === navigation.querySelector('[data-ndb-select-section][aria-current="page"]');
            })()
            JS)
        ->click('[data-ndb-mobile-sections-toggle]')
        ->assertAttribute('[data-ndb-mobile-sections-toggle]', 'aria-expanded', 'false')
        ->assertAttribute('[data-ndb-mobile-sections-toggle]', 'aria-label', 'Open sections')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-mobile-sections-toggle]")')
        ->assertScript('getComputedStyle(document.querySelector("#newdebugbar-section-navigation")).visibility === "visible"')
        ->wait(0.25)
        ->assertScript('getComputedStyle(document.querySelector("#newdebugbar-section-navigation")).visibility === "hidden"')
        ->click('[data-ndb-mobile-sections-toggle]')
        ->click('[data-ndb-select-section="queries"]')
        ->assertAttribute('[data-ndb-mobile-sections-toggle]', 'aria-expanded', 'false')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-section-heading]")')
        ->wait(0.25)
        ->assertScript('getComputedStyle(document.querySelector("#newdebugbar-section-navigation")).visibility === "hidden"');

    assertDebugSectionSelected($page, 'queries');

    $page
        ->click('[data-ndb-mobile-sections-toggle]')
        ->click('[data-ndb-toggle-favorite="queries"]')
        ->assertAttribute('[data-ndb-toggle-favorite="queries"]', 'aria-pressed', 'true')
        ->keys('[data-ndb-toggle-favorite="queries"]', 'Escape')
        ->assertAttribute('[data-ndb-mobile-sections-toggle]', 'aria-expanded', 'false')
        ->assertVisible('[role="dialog"][aria-label="Request inspector"]')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-mobile-sections-toggle]")')
        ->wait(0.25)
        ->assertScript('getComputedStyle(document.querySelector("#newdebugbar-section-navigation")).visibility === "hidden"')
        ->click('[data-ndb-mobile-sections-toggle]')
        ->click('[data-ndb-mobile-sections-backdrop]')
        ->assertAttribute('[data-ndb-mobile-sections-toggle]', 'aria-expanded', 'false')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-mobile-sections-toggle]")')
        ->wait(0.25)
        ->assertScript('getComputedStyle(document.querySelector("#newdebugbar-section-navigation")).visibility === "hidden"')
        ->resize(1440, 900)
        ->assertScript(<<<'JS'
            (() => {
                const toggle = document.querySelector('[data-ndb-mobile-sections-toggle]');
                const navigation = document.querySelector('#newdebugbar-section-navigation');

                return getComputedStyle(toggle).display === 'none'
                    && getComputedStyle(navigation).position === 'static'
                    && getComputedStyle(navigation).visibility === 'visible';
            })()
            JS)
        ->assertNoJavaScriptErrors();
});
