<?php

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

it('collects background requests in the split button without changing the host page', function () {
    $page = visit('/profiled')
        ->assertScript('document.querySelector(\'[data-ndb-request-picker-trigger="toolbar"]\').disabled === true')
        ->assertScript(<<<'JS'
            (() => {
                const control = document.querySelector('[data-ndb-request-switcher="toolbar"] [data-ndb-request-control]');
                const primary = document.querySelector('[data-ndb-toolbar="request"]');
                const picker = document.querySelector('[data-ndb-request-picker-trigger="toolbar"]');
                const controlStyle = getComputedStyle(control);
                const primaryStyle = getComputedStyle(primary);
                const pickerStyle = getComputedStyle(picker);

                return Number.parseFloat(controlStyle.borderTopWidth) === 1
                    && Number.parseFloat(primaryStyle.paddingLeft) === 10
                    && Number.parseFloat(primaryStyle.paddingRight) === 16
                    && Number.parseFloat(pickerStyle.borderLeftWidth) === 1;
            })()
            JS)
        ->assertNoJavaScriptErrors()
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
        ->assertNoJavaScriptErrors()
        ->assertScript(<<<'JS'
            (() => {
                fetch('/api/plain-json?sequence=third', { method: 'POST' });

                return true;
            })()
            JS)
        ->assertNoJavaScriptErrors()
        ->assertScript(<<<'JS'
            (() => {
                fetch('/api/plain-json?sequence=second', { method: 'PATCH' });

                return true;
            })()
            JS)
        ->assertNoJavaScriptErrors()
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));
                const discoveries = window.__newDebugBarDiscoveries;

                return window.__newDebugBarFetchSentinel === true
                    && state.summary.id === window.__newDebugBarActiveProfile
                    && discoveries.length === 3
                    && state.laterRequestCount === 3
                    && document.querySelector('[data-ndb-request-picker-trigger="toolbar"]').disabled === false
                    && location.pathname === '/profiled'
                    && document.querySelectorAll('#newdebugbar').length === 1;
            })()
            JS)
        ->assertNoJavaScriptErrors()
        ->assertVisible('[data-testid="host-page"]')
        ->assertSeeIn('[data-ndb-request-badge="toolbar"]', '3')
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));
                state.setTheme('dark');

                return document.getElementById('newdebugbar').dataset.theme === 'dark';
            })()
            JS)
        ->click('[data-ndb-request-picker-trigger="toolbar"]')
        ->assertVisible('#newdebugbar-request-list-toolbar')
        ->assertVisible('[data-ndb-request-popover="toolbar"] [data-ndb-popover-surface]')
        ->assertVisible('[data-ndb-request-popover="toolbar"] [data-ndb-popover-arrow]')
        ->assertSeeIn('[data-ndb-request-badge="toolbar"]', '3')
        ->assertScript('document.querySelector(\'[data-ndb-request-popover="toolbar"] [data-ndb-request-popover-heading]\').textContent.trim() === \'Requests\'')
        ->assertAttribute('[data-ndb-request-picker-trigger="toolbar"]', 'aria-expanded', 'true')
        ->assertScript(<<<'JS'
            (() => {
                const trigger = document.querySelector('[data-ndb-request-picker-trigger="toolbar"]');
                const arrow = document.querySelector('[data-ndb-request-popover="toolbar"] [data-ndb-popover-arrow]');
                const triggerBox = trigger?.getBoundingClientRect();
                const arrowBox = arrow?.getBoundingClientRect();

                if (! triggerBox || ! arrowBox) return false;

                return Math.abs(
                    (triggerBox.left + triggerBox.width / 2)
                    - (arrowBox.left + arrowBox.width / 2),
                ) <= 1;
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));
                const options = Array.from(document.querySelectorAll('#newdebugbar-request-list-toolbar [data-ndb-request-option]'));
                const groups = Array.from(document.querySelectorAll('#newdebugbar-request-list-toolbar [data-ndb-request-group]'));

                const methods = options.map((option) => option.querySelector('[data-ndb-request-method]'));
                const statuses = options.map((option) => option.querySelector('[data-ndb-request-status]'));
                const indicators = options.map((option) => option.querySelector('[data-ndb-request-current]'));
                const badge = document.querySelector('[data-ndb-request-badge="toolbar"]');
                const activeMethod = document.querySelector('[data-ndb-toolbar="request"] > span:first-child');

                return state.laterRequestCount === 3
                    && options.length >= 4
                    && groups.map((group) => group.dataset.ndbRequestGroup).join(',') === 'current,later'
                    && groups[0].querySelector('[data-ndb-request-option]').dataset.profileId === window.__newDebugBarActiveProfile
                    && groups[1].querySelectorAll('[data-ndb-request-option]').length === 3
                    && Number.parseFloat(getComputedStyle(groups[1]).borderTopWidth) === 0
                    && options.filter((option) => option.textContent.includes('/api/plain-json')).length >= 3
                    && methods.some((method) => method.textContent.trim() === 'POST')
                    && methods.some((method) => method.textContent.trim() === 'PATCH')
                    && document.activeElement.matches('[data-ndb-request-option]')
                    && getComputedStyle(badge).color === 'rgb(255, 255, 255)'
                    && getComputedStyle(badge).boxShadow === 'none'
                    && getComputedStyle(activeMethod).color === 'rgb(255, 255, 255)'
                    && methods.every((method) => getComputedStyle(method).color === 'rgb(255, 255, 255)')
                    && methods.every((method) => method.getBoundingClientRect().width >= 48)
                    && new Set(methods.map((method) => Math.round(method.getBoundingClientRect().width))).size === 1
                    && statuses.every((status, index) => {
                        const rowBox = options[index].getBoundingClientRect();
                        const statusBox = status.getBoundingClientRect();

                        return Math.abs(
                            (rowBox.top + rowBox.height / 2)
                            - (statusBox.top + statusBox.height / 2),
                        ) <= 1;
                    })
                    && new Set(statuses.map((status) => Math.round(status.getBoundingClientRect().left))).size === 1
                    && indicators.every((indicator) => Math.abs(indicator.getBoundingClientRect().width - 16) <= 0.5);
            })()
            JS)
        ->assertNoJavaScriptErrors()
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));
                state.setTheme('light');

                return document.getElementById('newdebugbar').dataset.theme === 'light';
            })()
            JS)
        ->hover('#newdebugbar-request-list-toolbar [data-ndb-request-group="later"] [data-ndb-request-option]:first-of-type')
        ->assertScript(<<<'JS'
            (() => {
                const option = document.querySelector(
                    '#newdebugbar-request-list-toolbar [data-ndb-request-group="later"] [data-ndb-request-option]:first-of-type:hover',
                );
                const background = getComputedStyle(option).backgroundColor;
                const alpha = Number(
                    background.match(/\/\s*([\d.]+)\s*\)$/)?.[1]
                        ?? background.match(/,\s*([\d.]+)\s*\)$/)?.[1]
                        ?? 1
                );

                return alpha > 0 && alpha < 1;
            })()
            JS)
        ->assertNoJavaScriptErrors()
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));
                const option = Array.from(document.querySelectorAll('#newdebugbar-request-list-toolbar [data-ndb-request-option]'))
                    .find((candidate) => candidate.dataset.profileId !== state.summary.id && candidate.textContent.includes('/api/plain-json'));

                option.click();

                return true;
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));

                return state.summary.path === '/api/plain-json'
                    && state.inspectorOpen === true
                    && state.selected === 'request'
                    && location.pathname === '/profiled'
                    && window.__newDebugBarFetchSentinel === true;
            })()
            JS)
        ->assertNoJavaScriptErrors()
        ->assertVisible('[data-ndb-section-panel="request"]')
        ->assertVisible('[data-ndb-request-picker-trigger="header"]')
        ->click('[data-ndb-request-picker-trigger="header"]')
        ->assertVisible('#newdebugbar-request-list-header')
        ->assertScript(<<<'JS'
            (() => {
                const current = document.querySelector(
                    '#newdebugbar-request-list-header [data-ndb-request-group="current"] [data-ndb-request-option]',
                );
                const selectedLater = document.querySelector(
                    '#newdebugbar-request-list-header [data-ndb-request-group="later"] [data-ndb-request-option][aria-selected="true"]',
                );

                return current?.dataset.profileId === window.__newDebugBarActiveProfile
                    && selectedLater !== null
                    && Alpine.$data(document.getElementById('newdebugbar')).selected === 'request';
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('keeps the bar working after host Livewire updates without a dedicated section', function () {
    $page = visit('/profiled-livewire')
        ->assertSeeIn('[data-testid="host-counter-value"]', '0')
        ->click('[data-testid="host-counter"] button')
        ->assertSeeIn('[data-testid="host-counter-value"]', '1')
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));

                return state.summary.path === '/profiled-livewire'
                    && state.laterRequestCount === 1
                    && state.recentProfiles.some((profile) =>
                        /^\/livewire-[0-9a-f]{8}\/update$/i.test(profile.path)
                    );
            })()
            JS)
        ->assertSeeIn('[data-ndb-request-badge="toolbar"]', '1')
        ->click('[data-ndb-request-picker-trigger="toolbar"]')
        ->assertVisible('#newdebugbar-request-list-toolbar')
        ->assertScript(<<<'JS'
            (() => {
                const option = Array.from(document.querySelectorAll(
                    '#newdebugbar-request-list-toolbar [data-ndb-request-option]',
                )).find((candidate) => /\/livewire-[0-9a-f]{8}\/update/i.test(candidate.textContent));

                option?.click();

                return option !== undefined;
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));

                return /^\/livewire-[0-9a-f]{8}\/update$/i.test(state.summary.path)
                    && state.inspectorOpen === true
                    && state.selected === 'request';
            })()
            JS)
        ->assertVisible('[data-ndb-section-panel="request"]')
        ->assertMissing('[data-ndb-select-section="livewire"]')
        ->assertMissing('[data-ndb-section-panel="livewire"]')
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
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    foreach (['request', 'timeline', 'queries', 'models', 'cache', 'views', 'events', 'logs', 'exceptions', 'overview', 'models'] as $section) {
        selectDebugSectionViaPalette($page, $section);

        assertDebugSectionSelected($page, $section);
    }

    $page->assertNoJavaScriptErrors();
});
