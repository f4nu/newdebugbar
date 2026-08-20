<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('shows Livewire validation messages rules and source on desktop and mobile', function () {
    $page = visit('/profiled-livewire-validation')
        ->resize(1440, 900)
        ->click('[data-testid="host-validation-form"] button[type="submit"]')
        ->assertSee('The email field must be a valid email address.')
        ->assertSee('The name field is required.')
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));
                state.setTheme('light');

                return state.laterRequestCount === 1
                    && state.recentProfiles.some((profile) => /^\/livewire-[0-9a-f]{8}\/update$/i.test(profile.path))
                    && document.getElementById('newdebugbar').dataset.theme === 'light';
            })()
            JS)
        ->click('[data-ndb-request-picker-trigger="toolbar"]')
        ->assertVisible('#newdebugbar-request-list-toolbar')
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));
                const validation = state.recentProfiles.find((profile) => /^\/livewire-[0-9a-f]{8}\/update$/i.test(profile.path));
                const option = document.querySelector(`[data-ndb-request-option][data-profile-id="${validation?.id}"]`);

                option?.click();

                return option !== null;
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));

                return state.summary.section_counts.validation === 1
                    && state.inspectorOpen === true;
            })()
            JS)
        ->click('[data-ndb-select-section="validation"]')
        ->assertVisible('[data-ndb-validation-item="0"]')
        ->assertVisible('[data-ndb-validation-message="email"]')
        ->assertVisible('[data-ndb-validation-message="name"]')
        ->assertVisible('[data-ndb-validation-rules="email"]')
        ->assertVisible('[data-ndb-validation-callsite="0"]')
        ->assertSee('2 fields failed validation')
        ->assertSee('Validation 422')
        ->assertSee('Response 200')
        ->assertScript(<<<'JS'
            (() => {
                const message = document.querySelector('[data-ndb-validation-message="email"]');
                const source = document.querySelector('[data-ndb-validation-callsite="0"]');

                return message.closest('details') === null
                    && source.textContent.includes('tests/Fixtures/HostValidationForm.php');
            })()
            JS);

    DebugBarBrowser::assertSectionSelected($page, 'validation');

    $page
        ->keys('[data-ndb-validation-callsite="0"]', 'Enter')
        ->assertNoJavaScriptErrors()
        ->assertScript(<<<'JS'
            (() => {
                const state = Alpine.$data(document.getElementById('newdebugbar'));
                state.setTheme('dark');

                return document.getElementById('newdebugbar').dataset.theme === 'dark';
            })()
            JS)
        ->resize(390, 844)
        ->assertVisible('[data-ndb-validation-item="0"]')
        ->assertVisible('[data-ndb-validation-message="email"]')
        ->assertScript(<<<'JS'
            (() => {
                const panel = document.querySelector('[data-ndb-section-panel="validation"]');
                const item = document.querySelector('[data-ndb-validation-item="0"]');

                return item.scrollWidth <= item.clientWidth
                    && panel.scrollWidth <= panel.clientWidth;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});
