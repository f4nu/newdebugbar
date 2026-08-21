<?php

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
                const style = getComputedStyle(document.querySelector('[data-testid="host-code"]'));

                return style.backgroundColor === 'rgb(243, 243, 243)'
                    && style.color === 'rgb(0, 0, 0)';
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
        ->assertScript(<<<'JS'
            (() => {
                localStorage.setItem('newdebugbar.preferences.v1', JSON.stringify({
                    theme: 'dark',
                    favorites: [],
                }));

                return true;
            })()
            JS)
        ->refresh()
        ->assertAttribute('#newdebugbar', 'data-theme', 'dark')
        ->click('[data-ndb-toolbar="request"]')
        ->assertVisible('[data-ndb-section-panel="request"]')
        ->assertScript(<<<'JS'
            (() => {
                const code = Array.from(document.querySelectorAll('[data-ndb-section-panel="request"] code'));

                return code.length >= 2 && code.every((element) => {
                    const style = getComputedStyle(element);

                    return style.backgroundColor === 'rgba(0, 0, 0, 0)'
                        && style.color !== 'rgb(0, 0, 0)';
                });
            })()
            JS)
        ->click('[data-ndb-section="queries"]')
        ->assertVisible('[data-ndb-section-panel="queries"]')
        ->assertScript(<<<'JS'
            (() => {
                const code = document.querySelector('[data-ndb-query-group-pattern] code[data-highlighted]');
                const surface = code?.closest('pre');
                const keyword = code?.querySelector('.hljs-keyword');

                if (! code || ! surface || ! keyword) return false;

                return getComputedStyle(surface).backgroundColor === 'rgba(0, 0, 0, 0)'
                    && getComputedStyle(code).backgroundColor === 'rgba(0, 0, 0, 0)'
                    && getComputedStyle(code).color !== 'rgb(0, 0, 0)'
                    && getComputedStyle(keyword).color === 'rgb(196, 181, 253)';
            })()
            JS)
        ->assertNoJavaScriptErrors();
});
