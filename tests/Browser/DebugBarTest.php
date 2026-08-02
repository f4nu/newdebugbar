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

        $page
            ->click('[data-ndb-inspector-action="close"]')
            ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]');
    }

    $page->assertNoJavaScriptErrors();
});

it('moves focus into the inspector and returns it to its opener', function () {
    visit('/profiled')
        ->click('[data-ndb-toolbar="expand"]')
        ->assertScript('document.activeElement === document.querySelector("[data-ndb-inspector-action=close]")')
        ->click('[data-ndb-inspector-action="close"]')
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

it('uses one metric color and concentric glass toolbar corners', function () {
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

                return filter.includes('brightness(1.5)') && filter.includes('saturate(1.5)');
            })()
            JS)
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

it('switches every section after Livewire navigation with one active state', function () {
    $page = visit('/profiled')
        ->click('[data-testid="host-navigation"]')
        ->waitForText('Second request')
        ->assertPathIs('/profiled-next')
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2);

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
                .every((item) => item.textContent.includes('span') && item.textContent.includes('→'))
            JS)
        ->click('[data-ndb-timeline-filter="events"]')
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-timeline-item]:not([hidden])'))
                .every((item) => item.textContent.includes('point') && !item.textContent.includes('→'))
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

it('filters searches sorts and expands repeated query evidence', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-toolbar="queries"]')
        ->waitForText('Extra runs')
        ->assertScript('document.querySelectorAll("[data-ndb-query-item]:not([hidden])").length', 3)
        ->click('[data-ndb-query-filter="repeated"]')
        ->assertScript('document.querySelectorAll("[data-ndb-query-item]:not([hidden])").length', 0)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden])").length', 1)
        ->click('[data-ndb-query-group] > summary')
        ->assertAttribute('[data-ndb-query-group]', 'open', '')
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
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="queries"]');

    assertDebugSectionSelected($page, 'queries');

    $page
        ->click('[data-ndb-toggle-favorite="queries"]')
        ->assertAttribute('[data-ndb-toggle-favorite="queries"]', 'aria-pressed', 'true')
        ->assertNoJavaScriptErrors();
});
