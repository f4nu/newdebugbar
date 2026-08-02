<?php

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

    foreach (['request', 'queries', 'models', 'cache', 'views', 'events', 'logs', 'exceptions', 'overview', 'models'] as $section) {
        $page->click("[data-ndb-select-section=\"{$section}\"]");

        assertDebugSectionSelected($page, $section);
    }

    $page->assertNoJavaScriptErrors();
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
