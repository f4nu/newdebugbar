<?php

it('keeps host styles and package styles isolated', function () {
    visit('/hostile-styles')
        ->assertScript("document.documentElement.getAttribute('data-theme') === 'dark'")
        ->assertAttribute('#newdebugbar', 'data-ndb-theme', 'light')
        ->assertMissing('#newdebugbar[data-theme]')
        ->assertScript("getComputedStyle(document.getElementById('newdebugbar')).backgroundColor === 'rgba(0, 0, 0, 0)'")
        ->assertScript(<<<'JS'
            (() => {
                const root = document.getElementById('newdebugbar');
                const probe = document.createElement('span');

                probe.style.color = 'var(--ndb-color-zinc-900)';
                root.append(probe);

                const usesLightTheme = getComputedStyle(root).color === getComputedStyle(probe).color;

                probe.remove();

                return usesLightTheme;
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const style = getComputedStyle(document.querySelector('[data-testid="host-button"]'));
                const icon = document.querySelector('[data-testid="host-icon-button"] svg').getBoundingClientRect();

                return style.backgroundColor === 'rgb(255, 0, 0)'
                    && style.borderRadius === '0px'
                    && style.color === 'rgb(0, 128, 0)'
                    && style.height === '91px'
                    && icon.width === 64
                    && icon.height === 64;
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
        ->assertAttribute('#newdebugbar', 'data-ndb-theme', 'dark')
        ->assertScript(<<<'JS'
            (() => {
                const root = document.getElementById('newdebugbar');
                const probe = document.createElement('span');

                probe.style.color = 'var(--ndb-color-zinc-100)';
                root.append(probe);

                const usesDarkTheme = getComputedStyle(root).color === getComputedStyle(probe).color;

                probe.remove();

                return usesDarkTheme;
            })()
            JS)
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
        ->click('[data-ndb-section="mail"]')
        ->assertVisible('[data-ndb-section-panel="mail"]')
        ->assertScript(<<<'JS'
            (() => {
                const row = document.querySelector('[data-ndb-mail-item]');
                const frame = document.querySelector('[data-ndb-mail-preview-frame]');
                const actions = document.querySelector('[data-ndb-mail-actions]');
                const metadata = document.querySelector('[data-ndb-mail-metadata]');
                const metadataLabel = metadata.querySelector('dt');
                const backIcon = document.querySelector('[data-ndb-mail-detail-back] svg');
                const tabIcons = [...document.querySelectorAll('[data-ndb-mail-detail-tab-icon]')];

                return getComputedStyle(row).borderLeftWidth === '0px'
                    && frame.getBoundingClientRect().width > 300
                    && getComputedStyle(frame).borderLeftWidth === '1px'
                    && getComputedStyle(actions).borderLeftWidth === '0px'
                    && getComputedStyle(actions).backgroundColor === 'rgba(0, 0, 0, 0)'
                    && getComputedStyle(metadata).backgroundColor !== 'rgb(255, 0, 0)'
                    && Number.parseFloat(getComputedStyle(metadataLabel).fontSize) === 11
                    && getComputedStyle(metadataLabel).color !== 'rgb(0, 128, 0)'
                    && Number.parseFloat(getComputedStyle(backIcon).width) === 14
                    && tabIcons.length === 3
                    && tabIcons.every((icon) => Number.parseFloat(getComputedStyle(icon).width) === 14);
            })()
            JS)
        ->click('[data-ndb-mail-actions-trigger]')
        ->assertScript(<<<'JS'
            (() => {
                const links = [...document.querySelectorAll('[data-ndb-mail-actions-menu] a')];

                return links.length === 2
                    && links.every((link) => link.getBoundingClientRect().height < 91)
                    && links.every((link) => getComputedStyle(link).backgroundColor !== 'rgb(255, 0, 255)')
                    && links.every((link) => getComputedStyle(link).textDecorationLine === 'none');
            })()
            JS)
        ->keys('[data-ndb-mail-actions-trigger]', 'Escape')
        ->click('[data-ndb-mail-detail-tab="message"]')
        ->assertScript(<<<'JS'
            (() => {
                const summary = document.querySelector('[data-ndb-mail-headers] summary');
                const style = getComputedStyle(summary);

                return style.fontSize === '12px' && style.color !== 'rgb(255, 0, 0)';
            })()
            JS)
        ->assertNoJavaScriptErrors();
});
