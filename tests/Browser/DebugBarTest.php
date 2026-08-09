<?php

use Illuminate\Support\Facades\File;

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
            $page->assertScript('document.querySelector("[data-ndb-header-memory]").textContent.includes("MB")');
        }

        $page
            ->click('[data-ndb-inspector-action="close"]')
            ->assertVisible('[role="toolbar"][aria-label="Debug toolbar"]');
    }

    $page->assertNoJavaScriptErrors();
});

it('pins overview before alphabetized active sections and keeps quiet sections in the palette', function () {
    $page = visit('/profiled-rich');
    $page->script("localStorage.setItem('newdebugbar.preferences.v1', JSON.stringify({theme: 'light', sectionMode: 'all', favorites: []}))");

    $page
        ->refresh()
        ->resize(1440, 900)
        ->click('[data-ndb-toolbar="expand"]')
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
        ->click('[data-ndb-toolbar="expand"]')
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
        ->assertMissing('[data-ndb-runtime-detail-count]')
        ->assertMissing('[data-ndb-runtime-detail-panel-count]')
        ->assertNoJavaScriptErrors();

    $page
        ->keys('[data-ndb-runtime-detail="drivers"]', 'Enter')
        ->assertVisible('[data-ndb-runtime-detail-panel="drivers"]')
        ->assertScript('document.querySelector(\'[data-ndb-runtime-detail="drivers"]\').getAttribute("aria-pressed") === "true"')
        ->resize(390, 844)
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

it('caps the expanded inspector at the large breakpoint', function () {
    visit('/profiled')
        ->resize(1440, 900)
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->assertScript(<<<'JS'
            (() => {
                const inspector = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                const box = inspector.getBoundingClientRect();

                return Math.abs(box.width - 1024) <= 1
                    && Math.abs(box.left - (window.innerWidth - box.width) / 2) <= 1
                    && Math.abs(window.innerWidth - box.right - box.left) <= 1;
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
        ->assertScript('Alpine.$data(document.getElementById("newdebugbar")).summary.path.includes("/livewire-")');
    $profiles = collect(File::files(config('newdebugbar.storage.path')))
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
        ->assertScript("getComputedStyle(document.getElementById('newdebugbar')).fontFamily.includes('Outfit Variable')")
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
        selectDebugSectionViaPalette($page, $section);

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
        ->assertScript(<<<'JS'
            (() => {
                const counts = Array.from(document.querySelectorAll('[data-ndb-model-group]'))
                    .map((group) => Number(group.dataset.count));

                return counts.every((count, index) => index === 0 || counts[index - 1] >= count);
            })()
            JS)
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
    $page->script("localStorage.setItem('newdebugbar.preferences.v1', JSON.stringify({theme: 'light', favorites: []}))");

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

it('shows an aligned request trace and switches request detail groups', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-toolbar="expand"]')
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
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
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
        ->assertScript('document.querySelectorAll("#newdebugbar code[data-ndb-language=php][data-highlighted]").length > 0')
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
        ->assertScript('document.querySelectorAll("#newdebugbar code[data-ndb-language=sql][data-highlighted]").length > 0')
        ->click('[data-ndb-query-bindings="item-1"] summary')
        ->assertAttribute('[data-ndb-query-bindings="item-1"]', 'open', '')
        ->assertNoJavaScriptErrors();
});

it('matches repeated query SQL to regular query surfaces in :dataset mode', function (string $theme) {
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
        ->click('[data-ndb-query-filter="repeated"]')
        ->assertScript(<<<'JS'
            (() => {
                const repeated = document.querySelector('[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-pattern] pre');
                const regular = document.querySelector('[data-ndb-query-item] > pre');
                const repeatedStyle = getComputedStyle(repeated);
                const regularStyle = getComputedStyle(regular);

                return repeatedStyle.backgroundColor === regularStyle.backgroundColor
                    && repeatedStyle.color === regularStyle.color;
            })()
            JS)
        ->assertNoJavaScriptErrors();
})->with(['light', 'dark']);

it('filters searches sorts and shows repeated query evidence without another disclosure', function () {
    $page = visit('/profiled')
        ->click('[data-ndb-toolbar="queries"]')
        ->waitForText('Extra runs')
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
                const search = document.querySelector('[data-ndb-query-search]');
                const searchLabel = search.parentElement.querySelector('span');
                const sort = document.querySelector('[role="group"][aria-label="Sort queries"]');
                const sortLabel = sort.parentElement.querySelector('p');

                return Math.abs(search.getBoundingClientRect().left - searchLabel.getBoundingClientRect().left) < 1
                    && Math.abs(sort.getBoundingClientRect().left - sortLabel.getBoundingClientRect().left) < 1;
            })()
            JS)
        ->assertScript('document.querySelectorAll("[data-ndb-query-item]:not([hidden])").length', 3)
        ->click('[data-ndb-query-filter="repeated"]')
        ->assertAttribute('[data-ndb-query-filter="repeated"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelectorAll("[data-ndb-query-item]:not([hidden])").length', 0)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden])").length', 1)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-pattern] code[data-ndb-language=sql]").length', 1)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-executions] > article").length', 3)
        ->assertScript('document.querySelectorAll("[data-ndb-query-group]:not([hidden]) [data-ndb-query-connection]").length', 3)
        ->assertScript(<<<'JS'
            document.querySelector('[data-ndb-query-group]:not([hidden])').getBoundingClientRect().top
                >= document.querySelector('[data-ndb-section-heading]').parentElement.getBoundingClientRect().bottom - 1
            JS)
        ->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('[data-ndb-query-group]:not([hidden]) [data-ndb-query-group-executions] > article'))
                .every((article) => article.querySelector(':scope > pre code[data-ndb-language="sql"]') === null)
            JS)
        ->assertSee('Likely N+1')
        ->click('[data-ndb-query-filter="read"]')
        ->assertScript('document.querySelectorAll("[data-ndb-query-filter][aria-pressed=true]").length', 1)
        ->assertScript('document.querySelectorAll("[data-ndb-query-item]:not([hidden])").length', 3)
        ->click('[data-ndb-query-sort="duration"]')
        ->assertAttribute('[data-ndb-query-sort="execution"]', 'aria-pressed', 'false')
        ->assertAttribute('[data-ndb-query-sort="duration"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelectorAll("[data-ndb-query-sort][aria-pressed=true]").length', 1)
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

it('filters retained history and compares the current path', function () {
    $page = visit('/profiled')
        ->refresh()
        ->click('[data-ndb-toolbar="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="history"]');

    assertDebugSectionSelected($page, 'history');

    $page
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
                const expand = document.querySelector('[data-ndb-toolbar="expand"]');
                const toolbarBox = toolbar.getBoundingClientRect();
                const requestBox = request.getBoundingClientRect();
                const factsBox = facts.getBoundingClientRect();
                const actionsBox = actions.getBoundingClientRect();
                const toolbarStyles = getComputedStyle(toolbar);

                return requestBox.width <= 113
                    && requestBox.width < toolbarBox.width / 3
                    && factsBox.width > 100
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
                    && Math.abs(actionsBox.right - expand.getBoundingClientRect().right) <= 1
                    && actionsBox.left >= factsBox.right;
            })()
            JS)
        ->click('[data-ndb-toolbar="expand"]')
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
