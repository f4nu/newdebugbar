<?php

it('sorts views from the column headers with clear direction feedback', function () {
    $groupNames = <<<'JS'
        Array.from(document.querySelectorAll('[data-ndb-view-group]'))
            .map((group) => group.querySelector('summary span').textContent.trim())
            .join('|')
        JS;

    $page = visit('/profiled-views');
    $page->script("localStorage.setItem('newdebugbar.preferences.v1', JSON.stringify({theme: 'dark', favorites: []}))");

    $page
        ->refresh()
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="views"]')
        ->assertMissing('select[data-ndb-view-sort]')
        ->assertAttribute('[data-ndb-view-sort="name"]', 'type', 'button')
        ->assertAttribute('[data-ndb-view-sort="name"]', 'data-ndb-view-sort', 'name')
        ->assertScript('!document.querySelector("[data-ndb-view-sort=\"name\"]").hasAttribute("aria-expanded")')
        ->assertScript('document.querySelector("[data-ndb-view-sort=\"name\"]").parentElement.getAttribute("aria-sort") === "ascending"')
        ->assertScript(<<<'JS'
            (() => {
                const buttons = Array.from(document.querySelectorAll('[data-ndb-view-sort]'));

                return buttons.every((button) => {
                    const styles = getComputedStyle(button);

                    return button.querySelector('svg') === null
                        && styles.paddingTop === '0px'
                        && styles.paddingRight === '0px'
                        && styles.paddingBottom === '0px'
                        && styles.paddingLeft === '0px'
                        && styles.backgroundColor === 'rgba(0, 0, 0, 0)';
                }) && getComputedStyle(document.querySelector('[data-ndb-view-sort="name"]')).color === 'rgb(255, 255, 255)';
            })()
            JS)
        ->hover('[data-ndb-view-sort="name"]')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-view-sort=\\"name\\"]")).backgroundColor === "rgba(0, 0, 0, 0)"')
        ->assertScript($groupNames, 'context|original-response')
        ->click('[data-ndb-view-sort="count"]')
        ->assertScript('document.querySelector("[data-ndb-view-sort=\"count\"]").parentElement.getAttribute("aria-sort") === "descending"')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-view-sort=\\"count\\"]")).color === "rgb(255, 255, 255)"')
        ->assertScript($groupNames, 'original-response|context')
        ->click('[data-ndb-view-sort="count"]')
        ->assertScript('document.querySelector("[data-ndb-view-sort=\"count\"]").parentElement.getAttribute("aria-sort") === "ascending"')
        ->assertScript($groupNames, 'context|original-response')
        ->keys('[data-ndb-view-sort="name"]', 'Enter')
        ->assertScript('document.querySelector("[data-ndb-view-sort=\"name\"]").parentElement.getAttribute("aria-sort") === "ascending"')
        ->keys('[data-ndb-view-sort="name"]', 'Enter')
        ->assertScript('document.querySelector("[data-ndb-view-sort=\"name\"]").parentElement.getAttribute("aria-sort") === "descending"')
        ->assertScript($groupNames, 'original-response|context')
        ->assertNoJavaScriptErrors();
});

it('loads one render data payload only when its popover opens', function () {
    $page = visit('/profiled-views')
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="views"]')
        ->assertMissing('[data-ndb-view-data]')
        ->assertDontSee('view-data-value')
        ->click('[data-ndb-view-group][data-name="context"] > summary')
        ->assertPresent('[data-ndb-view-data-loading]')
        ->click('[data-ndb-view-group][data-name="context"] [data-ndb-view-data-trigger]')
        ->waitForText('view-data-value')
        ->assertVisible('[data-ndb-view-data]')
        ->assertNoJavaScriptErrors();
});
