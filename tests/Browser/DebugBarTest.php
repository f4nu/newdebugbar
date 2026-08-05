<?php

use Illuminate\Support\Facades\File;

function assertDebugSectionSelected($page, string $section): void
{
    $page
        ->assertCount('#new-debug-bar [data-ndb-select-section][aria-current="page"]', 1)
        ->assertAttribute("#new-debug-bar [data-ndb-select-section=\"{$section}\"]", 'aria-current', 'page')
        ->assertCount('#new-debug-bar [data-ndb-section-panel]:not([hidden])', 1)
        ->assertVisible("#new-debug-bar [data-ndb-section-panel=\"{$section}\"]");
}

function assertFavoriteOrder($page, string $order): void
{
    $page->assertScript(<<<'JS'
        Array.from(document.querySelectorAll('#new-debug-bar [data-ndb-section][data-ndb-favorite="true"]'))
            .map((section) => section.dataset.ndbSection)
            .join(',')
        JS, $order);
}

it('opens every compact toolbar destination and closes cleanly', function () {
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
        $page
            ->click("[data-ndb-toolbar=\"{$toolbar}\"]")
            ->wait(0.2);

        assertDebugSectionSelected($page, $section);

        if ($toolbar === 'expand') {
            $page->assertScript('document.querySelector("[role=dialog][aria-label=\\"Request inspector\\"] > header").textContent.includes("MB peak")');
        }

        $page
            ->click('[data-ndb-inspector-action="close"]')
            ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]');
    }

    $page->assertNoJavaScriptErrors();
});

it('discloses active sections first without losing quiet section access', function () {
    $page = visit('/profiled-rich');
    $page->script("localStorage.setItem('new-debug-bar.preferences.v1', JSON.stringify({theme: 'light', sectionMode: 'active', favorites: []}))");

    $page
        ->refresh()
        ->resize(1440, 900)
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->assertAttribute('[data-ndb-section-mode="active"]', 'aria-pressed', 'true')
        ->assertScript(<<<'JS'
            document.querySelectorAll('[data-ndb-section-visible="true"]').length
                < Number(document.querySelector('[data-ndb-section-mode="all"] span:last-child').textContent)
            JS)
        ->assertScript(<<<'JS'
            Number(document.querySelector('[data-ndb-quiet-count] span:first-child').textContent)
                === Number(document.querySelector('[data-ndb-section-mode="all"] span:last-child').textContent)
                    - document.querySelectorAll('[data-ndb-section-visible="true"]').length
            JS)
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-section=\\"validation\\"]").parentElement).display === "none"')
        ->assertScript('document.querySelector("[data-ndb-overview-environment]").open === false')
        ->assertScript(<<<'JS'
            document.querySelector('[data-ndb-findings]').getBoundingClientRect().top
                < document.querySelector('[data-ndb-overview-activity]').getBoundingClientRect().top
            JS)
        ->click('[data-ndb-overview-environment] summary')
        ->assertAttribute('[data-ndb-overview-environment]', 'open', '')
        ->click('[data-ndb-section-mode="all"]')
        ->assertAttribute('[data-ndb-section-mode="all"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-section="validation"]')
        ->click('[data-ndb-select-section="validation"]')
        ->click('[data-ndb-section-mode="active"]')
        ->assertVisible('[data-ndb-section="validation"]');

    assertDebugSectionSelected($page, 'validation');

    $page
        ->click('[data-ndb-section-mode="all"]')
        ->refresh()
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->assertAttribute('[data-ndb-section-mode="all"]', 'aria-pressed', 'true')
        ->assertScript(<<<'JS'
            document.querySelectorAll('[data-ndb-section-visible="true"]').length
                === Number(document.querySelector('[data-ndb-section-mode="all"] span:last-child').textContent)
            JS)
        ->assertNoJavaScriptErrors();
});

it('moves focus into the inspector and returns it to its opener', function () {
    visit('/profiled')
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-inspector-action=close]")')
        ->click('[data-ndb-inspector-action="close"]')
        ->wait(0.2)
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-toolbar=expand]")')
        ->assertNoJavaScriptErrors();
});

it('profiles application Livewire updates without profiling itself', function () {
    $page = visit('/profiled-livewire')
        ->click('[data-testid="profiled-increment"]')
        ->waitForText('1')
        ->assertScript('Alpine.$data(document.getElementById("new-debug-bar")).summary.path.includes("/livewire-")');
    $profiles = collect(File::files(config('new-debug-bar.storage.path')))
        ->map(fn ($file) => json_decode(File::get($file->getPathname()), true, flags: JSON_THROW_ON_ERROR));
    $livewireProfile = $profiles->first(
        fn (array $profile): bool => str_contains($profile['sections']['request']['payload']['path'], '/livewire-'),
    );

    expect($livewireProfile)->not->toBeNull()
        ->and($livewireProfile['sections']['livewire']['summary']['count'])->toBe(1);

    $page
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="livewire"]');

    assertDebugSectionSelected($page, 'livewire');

    $page
        ->assertSee('profiled-counter')
        ->assertSee('increment')
        ->assertSee('Request')
        ->assertSee('Response')
        ->click('[data-ndb-inspector-action="close"]')
        ->click('[data-testid="profiled-save"]')
        ->wait(0.2)
        ->click('[data-ndb-toolbar="expand"]')
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
        ->assertScript('document.activeElement?.dataset.ndbCommand === "theme:dark"')
        ->keys('[data-ndb-command="theme:dark"]', 'Tab')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-palette-search]")')
        ->assertNoJavaScriptErrors();
});

it('uses one metric color and balanced glass toolbar spacing', function () {
    visit('/profiled')
        ->assertScript(<<<'JS'
            getComputedStyle(document.getElementById('new-debug-bar')).fontFamily.includes('Outfit Variable')
            JS)
        ->assertScript(<<<'JS'
            getComputedStyle(document.querySelector('[role="toolbar"][aria-label="Debug toolbar"]')).borderRadius
            JS, '18px')
        ->assertScript(<<<'JS'
            getComputedStyle(document.querySelector('[data-ndb-toolbar="expand"]')).borderRadius
            JS, '12px')
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
                const expand = document.querySelector('[data-ndb-toolbar="expand"]');
                const toolbarBox = toolbar.getBoundingClientRect();
                const expandBox = expand.getBoundingClientRect();
                const right = toolbarBox.right - expandBox.right;
                const top = expandBox.top - toolbarBox.top;
                const bottom = toolbarBox.bottom - expandBox.bottom;

                return Math.abs(right - top) <= 1 && Math.abs(right - bottom) <= 1;
            })()
            JS)
        ->assertScript('document.querySelectorAll(\'[role="toolbar"] > span\').length', 0)
        ->assertScript(<<<'JS'
            (() => {
                const metricColors = ['duration', 'memory', 'queries'].map((name) =>
                    getComputedStyle(document.querySelector(`[data-ndb-toolbar="${name}"] svg`)).color
                );
                const utilityColor = getComputedStyle(document.querySelector('[data-ndb-toolbar="expand"] svg')).color;

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
        const stylesheet = document.querySelector('link[href*="/__new-debug-bar/assets/new-debug-bar.css"]');
        stylesheet.href = stylesheet.href.replace(/id=[^&]+/, 'id=stale-test-build');
        JS);

    $page
        ->click('[data-testid="host-navigation"]')
        ->waitForText('Second request')
        ->assertScript('window.__newDebugBarNavigationSentinel === true')
        ->assertCount('#new-debug-bar', 1)
        ->assertNoJavaScriptErrors();
});

it('discovers background fetch profiles without switching reloading or flashing the host', function () {
    $page = visit('/profiled')
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('new-debug-bar'));
                window.__newDebugBarActiveProfile = state.summary.profile_id;
                window.__newDebugBarFetchSentinel = true;
                fetch('/api/plain-json');

                return true;
            })()
            JS)
        ->wait(0.3)
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('new-debug-bar'));

                return window.__newDebugBarFetchSentinel === true
                    && state.summary.profile_id === window.__newDebugBarActiveProfile
                    && location.pathname === '/profiled'
                    && document.querySelectorAll('#new-debug-bar').length === 1;
            })()
            JS)
        ->assertVisible('[data-testid="host-page"]')
        ->click('[data-ndb-toolbar="expand"]')
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
                const style = getComputedStyle(document.querySelector('[data-ndb-toolbar="expand"]'));

                return style.backgroundColor === 'rgba(0, 0, 0, 0)'
                    && style.borderRadius === '12px'
                    && style.height === '36px';
            })()
            JS)
        ->assertScript("getComputedStyle(document.getElementById('new-debug-bar')).fontFamily.includes('Outfit Variable')")
        ->assertNoJavaScriptErrors();
});

it('switches every section after Livewire navigation with one active state', function () {
    $page = visit('/profiled')
        ->click('[data-testid="host-navigation"]')
        ->waitForText('Second request')
        ->assertPathIs('/profiled-next')
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-section-mode="all"]');

    foreach (['request', 'timeline', 'queries', 'models', 'cache', 'views', 'events', 'logs', 'exceptions', 'history', 'overview', 'models'] as $section) {
        $page->click("[data-ndb-select-section=\"{$section}\"]");

        assertDebugSectionSelected($page, $section);
    }

    $page->assertNoJavaScriptErrors();
});

it('filters the timeline without inventing spans for point events', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="timeline"]')
        ->wait(0.2);

    assertDebugSectionSelected($page, 'timeline');

    $page
        ->assertPresent('[data-ndb-timeline-item="request-start"]')
        ->assertVisible('[data-ndb-timeline-waterfall]')
        ->assertScript('document.querySelector("[data-ndb-timeline-tick=\\"0\\"]").getBoundingClientRect().left > document.querySelector("[data-ndb-timeline-tick=\\"0\\"]").parentElement.parentElement.getBoundingClientRect().left + 4')
        ->assertScript('document.querySelectorAll("[data-ndb-timeline-item]:not([hidden])").length > 2')
        ->assertScript(<<<'JS'
            Number(document.querySelector('[data-ndb-section-panel="timeline"] [x-text="visibleTimelineCount"]').textContent)
                === document.querySelectorAll('[data-ndb-timeline-item]:not([hidden])').length
            JS)
        ->click('[data-ndb-timeline-filter="queries"]')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item]:not([hidden])'))
                .every((item) => item.dataset.section === 'queries')
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
        ->click('[data-ndb-timeline-filter="events"]')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item]:not([hidden])'))
                .every((item) => item.dataset.kind === 'point'
                    && item.querySelector('[data-ndb-timeline-mark]').getBoundingClientRect().width > 0)
            JS)
        ->type('[data-ndb-timeline-search]', 'nothing can match this')
        ->assertScript('document.querySelectorAll("[data-ndb-timeline-item]:not([hidden])").length', 0)
        ->assertSee('No timeline events match these filters.')
        ->assertNoJavaScriptErrors();
});

it('presents grouped Laravel activity with useful controls', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="models"]')
        ->assertSee('Model classes')
        ->assertSee('Lifecycle events')
        ->click('[data-ndb-select-section="cache"]')
        ->assertSee('Hit rate')
        ->assertSee('Misses')
        ->click('[data-ndb-select-section="events"]')
        ->click('[data-ndb-event-source="application"]')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-event-item]:not([hidden])'))
                .every((item) => item.dataset.source === 'application')
            JS)
        ->type('[data-ndb-event-search]', 'application.ready')
        ->assertScript('document.querySelectorAll("[data-ndb-event-item]:not([hidden])").length', 1)
        ->click('[data-ndb-select-section="logs"]')
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
    $page = visit('/profiled');
    $page->script("localStorage.setItem('new-debug-bar.preferences.v1', JSON.stringify({theme: 'light', favorites: []}))");

    $page
        ->refresh()
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="models"]')
        ->click('[data-ndb-section-panel="models"] details:first-of-type summary')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-section-panel=\\"models\\"] details pre")).borderTopColor === getComputedStyle(document.querySelector("[data-ndb-section-panel=\\"models\\"] details")).borderTopColor')
        ->click('[data-ndb-select-section="cache"]')
        ->click('[data-ndb-section-panel="cache"] details:first-of-type summary')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-section-panel=\\"cache\\"] details pre")).borderTopColor === getComputedStyle(document.querySelector("[data-ndb-section-panel=\\"cache\\"] details")).borderTopColor')
        ->assertNoJavaScriptErrors();
});

it('shows request sizes presence flags middleware and log call sites', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="request"]')
        ->assertSee('Request size')
        ->assertSee('Response size')
        ->assertSee('Authentication')
        ->assertSee('Configured middleware order')
        ->click('[data-ndb-select-section="logs"]')
        ->assertSee('tests/TestCase.php')
        ->click('[data-ndb-log-item] > summary')
        ->assertPresent('[data-ndb-copy-log-callsite="0"]')
        ->assertNoJavaScriptErrors();

    assertDebugSectionSelected($page, 'logs');
});

it('presents Laravel decisions lifecycle messages and editor links', function () {
    $page = visit('/profiled-context')
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="authorization"]')
        ->assertSee('inspect-profile')
        ->assertSee('allowed')
        ->click('[data-ndb-select-section="lifecycle"]')
        ->assertSee('Route matching')
        ->assertSee('Route response preparation')
        ->assertSee('Final response preparation')
        ->click('[data-ndb-select-section="messages"]')
        ->assertSee('Checkout checkpoint')
        ->click('[data-ndb-select-section="views"]')
        ->click('[data-ndb-section-panel="views"] details summary')
        ->assertPresent('[data-ndb-section-panel="views"] a[href^="vscode://file/"]')
        ->click('[data-ndb-select-section="events"]')
        ->click('[data-ndb-event-item]:first-child summary')
        ->assertPresent('[data-ndb-section-panel="events"] a[href^="vscode://file/"]')
        ->assertNoJavaScriptErrors();

    assertDebugSectionSelected($page, 'events');
});

it('shows relative exception frames and highlighted source context', function () {
    $page = visit('/profiled-reported-exception')
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="exceptions"]');

    assertDebugSectionSelected($page, 'exceptions');

    $page
        ->assertSee('Application frames')
        ->assertSee('Vendor frames')
        ->assertSee('tests/TestCase.php')
        ->assertDontSee('/Users/benjamin/Sites/new-debug-bar/tests/TestCase.php')
        ->assertPresent('[data-ndb-copy-exception-callsite="0"]')
        ->assertScript('document.querySelectorAll("#new-debug-bar code[data-ndb-language=php][data-highlighted]").length > 0')
        ->assertNoJavaScriptErrors();
});

it('keeps favoriting active and repeatable after Livewire navigation', function () {
    $page = visit('/profiled')
        ->click('[data-testid="host-navigation"]')
        ->waitForText('Second request')
        ->click('[data-ndb-toolbar="expand"]')
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
        ->click('[data-ndb-inspector-action="close"]')
        ->click('[data-testid="host-navigation"]')
        ->waitForText('First request')
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->assertAttribute($favorite, 'aria-pressed', 'true')
        ->assertNoJavaScriptErrors();
});

it('reorders favorites with the keyboard and drag and drop', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2);

    foreach (['request', 'overview', 'queries'] as $section) {
        $page->click("[data-ndb-toggle-favorite=\"{$section}\"]");
    }

    assertFavoriteOrder($page, 'request,overview,queries');

    $page->keys('[data-ndb-select-section="overview"]', 'Shift+ArrowUp');
    assertFavoriteOrder($page, 'overview,request,queries');

    $page->drag('[data-ndb-section="queries"]', '[data-ndb-section="overview"]');
    assertFavoriteOrder($page, 'queries,overview,request');

    $page
        ->refresh()
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2);

    assertFavoriteOrder($page, 'queries,overview,request');

    $page->assertNoJavaScriptErrors();
});

it('shows the favorite source and insertion point while dragging', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-toolbar="expand"]')
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
        ->assertAttribute('#new-debug-bar', 'data-theme', 'light')
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
        ->assertAttribute('#new-debug-bar', 'data-theme', 'dark')
        ->refresh()
        ->assertAttribute('#new-debug-bar', 'data-theme', 'dark')
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->keys('[data-ndb-inspector-action="palette"]', 'Meta+Shift+P')
        ->assertVisible('[role="dialog"][aria-label="Command palette"]')
        ->keys('[data-ndb-palette-search]', 'Escape')
        ->assertScript('getComputedStyle(document.querySelector("[role=dialog][aria-label=\\"Command palette\\"]")).display === "none"')
        ->assertVisible('[role="dialog"][aria-label="Request inspector"]')
        ->keys('[data-ndb-inspector-action="close"]', 'Escape')
        ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]')
        ->assertNoJavaScriptErrors();
});

it('highlights query code and expands custom binding details', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-toolbar="queries"]')
        ->waitForText('Bindings')
        ->assertSee('Repeated 3×')
        ->assertScript('document.querySelectorAll("#new-debug-bar code[data-ndb-language=sql][data-highlighted]").length > 0')
        ->click('[data-ndb-query-bindings="item-1"] summary')
        ->assertAttribute('[data-ndb-query-bindings="item-1"]', 'open', '')
        ->assertNoJavaScriptErrors();
});

it('filters searches sorts and shows repeated query evidence without another disclosure', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-toolbar="queries"]')
        ->waitForText('Extra runs')
        ->assertScript('document.querySelectorAll("[data-ndb-query-item]:not([hidden])").length', 3)
        ->click('[data-ndb-query-filter="repeated"]')
        ->assertScript('document.querySelectorAll("[data-ndb-query-item]:not([hidden])").length', 0)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden])").length', 1)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden]) > div:last-child > article").length', 3)
        ->assertSee('Likely N+1')
        ->click('[data-ndb-query-filter="read"]')
        ->assertScript('document.querySelectorAll("[data-ndb-query-item]:not([hidden])").length', 3)
        ->click('[data-ndb-query-sort="duration"]')
        ->assertScript(<<<'JS'
            (() => {
                const durations = Array.from(document.querySelectorAll('[data-ndb-query-item]:not([hidden])'))
                    .map((query) => Number(query.dataset.duration));

                return durations.every((duration, index) => index === 0 || durations[index - 1] >= duration);
            })()
            JS)
        ->type('[data-ndb-query-search]', 'no query can match this')
        ->assertScript('document.querySelectorAll("[data-ndb-query-item]:not([hidden])").length', 0)
        ->assertSee('No queries match these filters.')
        ->assertNoJavaScriptErrors();
});

it('shows shared findings in overview and the related section', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-toolbar="expand"]')
        ->waitForText('Findings')
        ->assertPresent('[data-ndb-finding="query.repeated"]')
        ->assertPresent('[data-ndb-finding="query.n_plus_one"]')
        ->click('[data-ndb-select-section="queries"]');

    assertDebugSectionSelected($page, 'queries');

    $page
        ->assertSee('Related findings')
        ->assertPresent('[data-ndb-section-panel="queries"] [data-ndb-finding="query.repeated"]')
        ->assertNoJavaScriptErrors();
});

it('filters retained history and compares the current path', function () {
    $page = visit('/profiled')
        ->refresh()
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="history"]');

    assertDebugSectionSelected($page, 'history');

    $page
        ->assertScript('document.querySelectorAll("[data-ndb-history-profile]:not([hidden])").length >= 2')
        ->type('[data-ndb-history-method]', 'POST')
        ->wait(0.2)
        ->assertScript('document.querySelectorAll("[data-ndb-history-profile]:not([hidden])").length', 0)
        ->clear('[data-ndb-history-method]')
        ->wait(0.2)
        ->click('[data-ndb-compare-profile]')
        ->waitForText('Comparison')
        ->assertPresent('[data-ndb-comparison]')
        ->assertVisible('[data-ndb-section-panel="history"]')
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
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->assertVisible('[data-ndb-header-memory]')
        ->assertScript(<<<'JS'
            (() => {
                const memory = document.querySelector('[data-ndb-header-memory]').getBoundingClientRect();

                return memory.left >= 0 && memory.right <= window.innerWidth;
            })()
            JS)
        ->click('[data-ndb-select-section="queries"]');

    assertDebugSectionSelected($page, 'queries');

    $page
        ->click('[data-ndb-toggle-favorite="queries"]')
        ->assertAttribute('[data-ndb-toggle-favorite="queries"]', 'aria-pressed', 'true')
        ->assertNoJavaScriptErrors();
});
