<?php

use NewDebugBar\Tests\Support\DebugBarBrowser;

it('opens and applies a component property edit from its popover', function () {
    $page = visit('/profiled-livewire')
        ->resize(1024, 900)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]');

    DebugBarBrowser::selectSectionViaPalette($page, 'livewire');

    $page
        ->click('[data-ndb-livewire-tab="components"]')
        ->assertVisible('[data-ndb-livewire-components]')
        ->assertVisible('[data-ndb-livewire-edit-key$=":count"]')
        ->click('[data-ndb-livewire-edit-key$=":count"]')
        ->assertVisible('[data-ndb-livewire-property-popover]')
        ->assertScript('document.activeElement.matches("[data-ndb-livewire-edit-control]")');

    $page->script(<<<'JS'
        document.querySelector('[data-ndb-livewire-property-table]').dispatchEvent(new Event('scroll'));
        JS);

    $page
        ->assertVisible('[data-ndb-livewire-property-popover]')
        ->type('[data-ndb-livewire-edit-control]', '5')
        ->click('[data-ndb-livewire-edit-apply]')
        ->assertSeeIn('[data-testid="host-counter-value"]', '5')
        ->assertMissing('[data-ndb-livewire-property-popover]')
        ->assertNoJavaScriptErrors();
});
