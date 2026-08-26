<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('explains and structures application checkpoints on desktop and mobile', function () {
    $page = visit('/profiled-checkpoints')
        ->resize(1280, 720)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="messages"]');

    DebugBarBrowser::waitForVisibleElement($page, '[data-ndb-checkpoint-workspace]');

    $page
        ->assertScript('document.querySelector("[data-ndb-section-heading]").textContent.trim() === "Checkpoints"')
        ->assertSee('What are checkpoints?')
        ->assertSee('NewDebugBar\\Debug::message()')
        ->assertSee("This does not write to Laravel's logs.")
        ->assertAttribute(
            '[data-ndb-checkpoint-logging-link]',
            'href',
            'https://laravel.com/docs/logging#writing-log-messages',
        )
        ->assertAttribute('[data-ndb-checkpoint-logging-link]', 'target', '_blank')
        ->assertCount('[data-ndb-checkpoint-item]', 2)
        ->assertCount('[data-ndb-checkpoint-dot]', 2)
        ->assertCount('[data-ndb-checkpoint-connector]', 1)
        ->assertCount('[data-ndb-checkpoint-source]', 2)
        ->assertCount('[data-ndb-checkpoint-context]', 1)
        ->assertCount('[data-ndb-checkpoint-context-list]', 1)
        ->assertSee('Checkout started')
        ->assertSee('Checkout view ready')
        ->assertSee('cart_id')
        ->assertSee('[redacted]')
        ->assertMissing('private-checkpoint-token')
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-checkpoint-workspace]');
                const list = document.querySelector('[data-ndb-checkpoint-list]');
                const items = [...document.querySelectorAll('[data-ndb-checkpoint-item]')];
                const times = [...document.querySelectorAll('[data-ndb-checkpoint-time]')];
                const sources = [...document.querySelectorAll('[data-ndb-checkpoint-source]')];
                const contexts = items.map((item) => item.querySelector('[data-ndb-checkpoint-context]'));

                return list.tagName === 'OL'
                    && list.getAttribute('aria-label') === 'Application checkpoint timeline'
                    && workspace.querySelector('[data-ndb-inspector-list-controls]') === null
                    && items.length === 2
                    && times.every((time) => /^\+\d+\.\d{3} ms$/.test(time.textContent.trim()))
                    && new Set(times.map((time) => Math.round(time.getBoundingClientRect().right))).size === 1
                    && sources.every((source) => source.textContent.trim().startsWith('tests/Support/DefinesTestApplication.php:'))
                    && contexts[0] !== null
                    && contexts[0].querySelector('[data-ndb-language="json"]') === null
                    && contexts[1] === null
                    && workspace.scrollWidth <= workspace.clientWidth + 1;
            })()
            JS)
        ->assertNoJavaScriptErrors();

    DebugBarBrowser::assertSectionSelected($page, 'messages');

    $page
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));
                state.setTheme('dark');

                return document.getElementById('newdebugbar').dataset.ndbTheme === 'dark';
            })()
            JS)
        ->resize(390, 844)
        ->assertAttribute('#newdebugbar', 'data-ndb-theme', 'dark')
        ->assertScript(<<<'JS'
            (() => {
                const panel = document.querySelector('[data-ndb-section-panel="messages"]');
                const workspace = document.querySelector('[data-ndb-checkpoint-workspace]');
                const items = [...document.querySelectorAll('[data-ndb-checkpoint-item]')];
                const sources = [...document.querySelectorAll('[data-ndb-checkpoint-source]')];

                return panel.scrollWidth <= panel.clientWidth + 1
                    && workspace.scrollWidth <= workspace.clientWidth + 1
                    && items.every((item) => item.scrollWidth <= item.clientWidth + 1)
                    && sources.every((source) => source.getBoundingClientRect().right <= workspace.getBoundingClientRect().right);
            })()
            JS)
        ->assertNoJavaScriptErrors();
});
