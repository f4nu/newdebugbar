<?php

it('filters authorization decisions with the keyboard', function () {
    $page = visit('/profiled-context')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->wait(0.2)
        ->click('[data-ndb-select-section="authorization"]');

    $page
        ->assertScript(<<<'JS'
            (() => {
                const button = document.querySelector('[data-ndb-authorization-filter="denied"]');
                button.focus();

                return document.activeElement === button;
            })()
            JS)
        ->keys('[data-ndb-authorization-filter="denied"]', 'Enter')
        ->assertAttribute('[data-ndb-authorization-filter="denied"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelectorAll("[data-ndb-authorization-item]:not([hidden])").length', 1)
        ->assertScript('document.querySelector("[data-ndb-authorization-item]:not([hidden])").dataset.result === "denied"')
        ->assertScript(<<<'JS'
            (() => {
                const button = document.querySelector('[data-ndb-authorization-filter="allowed"]');
                button.focus();

                return document.activeElement === button;
            })()
            JS)
        ->keys('[data-ndb-authorization-filter="allowed"]', 'Enter')
        ->assertAttribute('[data-ndb-authorization-filter="allowed"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelectorAll("[data-ndb-authorization-item]:not([hidden])").length', 1)
        ->assertScript('document.querySelector("[data-ndb-authorization-item]:not([hidden])").dataset.result === "allowed"')
        ->assertNoJavaScriptErrors();
});
