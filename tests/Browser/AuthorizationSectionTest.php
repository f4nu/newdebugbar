<?php

it('filters authorization decisions with the keyboard', function () {
    $page = visit('/profiled-context')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
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

it('uses the full width for authorization traces at every breakpoint', function () {
    $page = visit('/profiled-context')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="authorization"]');

    foreach ([320, 390, 639, 640, 1024] as $width) {
        $page
            ->resize($width, 844)
            ->assertScript(<<<'JS'
                (() => {
                    const chain = document.querySelector('[data-ndb-authorization-chain]');
                    const item = chain.closest('[data-ndb-authorization-item]');
                    const connectors = [...chain.querySelectorAll('[data-ndb-authorization-connector]')];
                    const chainRect = chain.getBoundingClientRect();
                    const itemRect = item.getBoundingClientRect();

                    return getComputedStyle(chain).display === 'grid'
                        && Math.abs(chainRect.left - itemRect.left) < 1
                        && Math.abs(chainRect.right - itemRect.right) < 1
                        && chain.scrollWidth <= chain.clientWidth
                        && connectors.every(connector => connector.getBoundingClientRect().width >= 24);
                })()
                JS)
            ->assertNoJavaScriptErrors();
    }
});
