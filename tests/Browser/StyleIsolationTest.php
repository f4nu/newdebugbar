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
                const host = document.querySelector('[data-testid="host-request"]');
                const tab = host.querySelector('[data-request-tab]');

                return getComputedStyle(host).backgroundColor === 'rgb(255, 0, 0)'
                    && getComputedStyle(host).borderLeftWidth === '20px'
                    && host.getBoundingClientRect().height === 91
                    && tab.getBoundingClientRect().height === 91;
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
                const workspace = document.querySelector('[data-ndb-request-workspace]');
                const panel = document.querySelector('[data-ndb-request-panel]');
                const tabs = [...document.querySelectorAll('[data-ndb-request-tab]')];

                return getComputedStyle(workspace).borderLeftWidth === '0px'
                    && getComputedStyle(panel).borderLeftWidth === '0px'
                    && tabs.length === 5
                    && tabs.every((tab) => tab.getBoundingClientRect().height < 91)
                    && document.querySelector('#newdebugbar [data-request-workspace], #newdebugbar [data-request-panel], #newdebugbar [data-request-tab], #newdebugbar [data-request-source]') === null;
            })()
            JS)
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
                const root = document.querySelector('[data-ndb-queries]');
                const workspace = root.querySelector('[data-ndb-query-workspace]');
                const row = root.querySelector('[data-ndb-query-item]');
                const detail = root.querySelector('[data-ndb-query-detail]');
                const facts = root.querySelector('[data-ndb-query-detail-panel="query"] [data-ndb-inspector-fact]')?.closest('dl');
                const code = root.querySelector('[data-ndb-query-sql][data-highlighted]');
                const surface = code?.closest('pre');
                const keyword = code?.querySelector('.hljs-keyword');
                const controls = [
                    root.querySelector('[data-ndb-query-filter]'),
                    root.querySelector('[data-ndb-query-sort]'),
                    root.querySelector('[data-ndb-query-search]'),
                    root.querySelector('[data-ndb-query-execution-select]'),
                    ...root.querySelectorAll('[data-ndb-query-detail-tab]'),
                ];

                if (! workspace || ! row || ! detail || ! facts || ! code || ! surface || ! keyword) return false;

                return getComputedStyle(workspace).borderLeftWidth === '0px'
                    && getComputedStyle(row).borderLeftWidth === '0px'
                    && row.getBoundingClientRect().height < 91
                    && getComputedStyle(detail).borderLeftWidth === '0px'
                    && getComputedStyle(facts).display === 'grid'
                    && [...facts.querySelectorAll('dl, dt, dd')]
                        .every((element) => getComputedStyle(element).backgroundColor === 'rgba(0, 0, 0, 0)')
                    && controls.every((control) => control.getBoundingClientRect().height < 91)
                    && getComputedStyle(surface).backgroundColor !== 'rgb(243, 243, 243)'
                    && getComputedStyle(code).color !== 'rgb(0, 0, 0)'
                    && getComputedStyle(keyword).color === 'rgb(196, 181, 253)';
            })()
            JS)
        ->click('[data-ndb-section="cache"]')
        ->assertVisible('[data-ndb-section-panel="cache"]')
        ->assertScript(<<<'JS'
            (() => {
                const root = document.querySelector('[data-ndb-cache]');
                const row = document.querySelector('[data-ndb-cache-item]');
                const result = document.querySelector('[data-ndb-cache-result]');
                const filter = document.querySelector('[data-ndb-cache-filter]');
                const search = document.querySelector('[data-ndb-cache-search]');
                const detail = document.querySelector('[data-ndb-cache-detail]');
                const summaryPrimary = document.querySelector('[data-ndb-cache-summary] p');
                const sourceLink = document.querySelector('[data-ndb-cache-metadata] [data-ndb-inspector-source-link]');

                return [
                    root.getAttribute('data-cache'),
                    row.getAttribute('data-cache-item'),
                    result.getAttribute('data-cache-result'),
                    filter.getAttribute('data-cache-filter'),
                    search.getAttribute('data-cache-search'),
                    row.getAttribute('data-cache-search-text'),
                    getComputedStyle(root).borderLeftWidth === '0px',
                    getComputedStyle(row).borderLeftWidth === '0px',
                    row.getBoundingClientRect().height < 91,
                    getComputedStyle(result).backgroundColor === 'rgba(0, 0, 0, 0)',
                    filter.getBoundingClientRect().height < 91,
                    getComputedStyle(detail).borderLeftWidth === '0px',
                    Number.parseFloat(getComputedStyle(summaryPrimary).fontSize) === 12,
                    getComputedStyle(summaryPrimary).backgroundColor === 'rgba(0, 0, 0, 0)',
                    getComputedStyle(summaryPrimary).color !== 'rgb(0, 128, 0)',
                    sourceLink.getBoundingClientRect().height < 91,
                    getComputedStyle(sourceLink).backgroundColor === 'rgba(0, 0, 0, 0)',
                    getComputedStyle(sourceLink).color !== 'rgb(0, 128, 0)',
                ];
            })()
            JS, [null, null, null, null, null, null, true, true, true, true, true, true, true, true, true, true, true, true])
        ->click('[data-ndb-section="http_client"]')
        ->assertVisible('[data-ndb-section-panel="http_client"]')
        ->assertScript(<<<'JS'
            (() => {
                const root = document.querySelector('[data-ndb-http-client]');
                const row = document.querySelector('[data-ndb-http-client-item]');
                const method = document.querySelector('[data-ndb-http-client-method]');
                const header = document.querySelector('[data-ndb-http-client-header]');
                const path = header.querySelector('[data-ndb-http-client-detail-path]');
                const facts = document.querySelector('[data-ndb-http-client-response-facts]');
                const factElements = [...facts.querySelectorAll('dl, dt, dd')];
                const detailStatus = document.querySelector('[data-ndb-http-client-detail-status]');
                const copyUrl = document.querySelector('[data-ndb-http-client-copy-url]');
                const listFilters = [...document.querySelectorAll('[data-ndb-http-client-filter]')];
                const detailTabs = [...document.querySelectorAll('[data-ndb-http-client-detail-tab]')];

                return [
                    root.getAttribute('data-http-client'),
                    row.getAttribute('data-http-client-item'),
                    method.getAttribute('data-method'),
                    getComputedStyle(root).borderLeftWidth === '0px',
                    getComputedStyle(row).borderLeftWidth === '0px',
                    row.getBoundingClientRect().height < 91,
                    Math.round(method.getBoundingClientRect().width) === 48,
                    getComputedStyle(method).backgroundColor !== 'rgb(255, 0, 0)',
                    getComputedStyle(path).backgroundColor === 'rgba(0, 0, 0, 0)',
                    getComputedStyle(path).color !== 'rgb(0, 0, 0)',
                    getComputedStyle(facts).display === 'grid',
                    getComputedStyle(facts).backgroundColor === 'rgba(0, 0, 0, 0)',
                    factElements.every((element) => getComputedStyle(element).backgroundColor === 'rgba(0, 0, 0, 0)'),
                    factElements.every((element) => getComputedStyle(element).color !== 'rgb(0, 128, 0)'),
                    copyUrl.getBoundingClientRect().height < 91,
                    getComputedStyle(copyUrl).backgroundColor !== 'rgb(255, 0, 0)',
                    getComputedStyle(detailStatus).color !== 'rgb(0, 128, 0)',
                    listFilters.every((filter) => filter.getBoundingClientRect().height < 91),
                    detailTabs.every((tab) => tab.getBoundingClientRect().height < 91),
                ];
            })()
            JS, [null, null, null, true, true, true, true, true, true, true, true, true, true, true, true, true, true, true, true])
        ->click('[data-ndb-http-client-detail-tab="request"]')
        ->assertScript(<<<'JS'
            (() => {
                const copyCurl = document.querySelector('[data-ndb-http-client-copy-curl]');
                const facts = document.querySelector('[data-ndb-http-client-request-facts]');
                const host = document.querySelector('[data-ndb-http-client-detail-host]');

                return copyCurl.getBoundingClientRect().height < 91
                    && getComputedStyle(copyCurl).backgroundColor !== 'rgb(255, 0, 0)'
                    && getComputedStyle(facts).display === 'grid'
                    && getComputedStyle(host).color !== 'rgb(0, 128, 0)';
            })()
            JS)
        ->click('[data-ndb-http-client-detail-tab="source"]')
        ->assertScript(<<<'JS'
            (() => {
                const sourcePanel = document.querySelector('[data-ndb-http-client-source-facts]');
                const facts = sourcePanel.querySelector('dl');
                const source = document.querySelector('[data-ndb-http-client-detail-source]');
                const fact = source.closest('[data-ndb-inspector-source-fact]');
                const stack = document.querySelector('[data-ndb-http-client-detail-panel="source"] [data-ndb-inspector-stack]');
                const functionCall = stack.querySelector('li code');
                const stackPath = stack.querySelector('li > span:last-child > span');

                return getComputedStyle(facts).display === 'grid'
                    && getComputedStyle(source).backgroundColor === 'rgba(0, 0, 0, 0)'
                    && getComputedStyle(source).color !== 'rgb(0, 0, 0)'
                    && !getComputedStyle(source).fontFamily.includes('JetBrains Mono Variable')
                    && getComputedStyle(functionCall).fontFamily.includes('JetBrains Mono Variable')
                    && !getComputedStyle(stackPath).fontFamily.includes('JetBrains Mono Variable')
                    && getComputedStyle(fact).backgroundColor !== 'rgb(255, 0, 0)'
                    && Number.parseFloat(getComputedStyle(fact).paddingLeft) === 12
                    && getComputedStyle(stack).backgroundColor === 'rgba(0, 0, 0, 0)'
                    && getComputedStyle(stack).borderLeftWidth === '0px'
                    && Number.parseFloat(getComputedStyle(stack).paddingLeft) === 0;
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
                const metadataGrid = metadata.querySelector('[data-ndb-mail-facts]');
                const metadataFacts = [...metadata.querySelectorAll('[data-ndb-mail-fact]')];
                const metadataLabel = metadata.querySelector('dt');
                const sourceLink = metadata.querySelector('[data-ndb-inspector-source-link]');
                const backIcon = document.querySelector('[data-ndb-mail-detail-back] svg');
                const tabIcons = [...document.querySelectorAll('[data-ndb-mail-detail-tab-icon]')];

                return getComputedStyle(row).borderLeftWidth === '0px'
                    && frame.getBoundingClientRect().width > 300
                    && getComputedStyle(frame).borderLeftWidth === '1px'
                    && getComputedStyle(actions).borderLeftWidth === '0px'
                    && getComputedStyle(actions).backgroundColor === 'rgba(0, 0, 0, 0)'
                    && getComputedStyle(metadata).backgroundColor !== 'rgb(255, 0, 0)'
                    && getComputedStyle(metadataGrid).display === 'grid'
                    && getComputedStyle(metadataGrid).borderTopWidth === '0px'
                    && Number.parseFloat(getComputedStyle(metadataGrid).paddingTop) === 0
                    && getComputedStyle(metadataGrid).backgroundColor === 'rgba(0, 0, 0, 0)'
                    && metadataFacts.every((fact) => getComputedStyle(fact).backgroundColor === 'rgba(0, 0, 0, 0)')
                    && Number.parseFloat(getComputedStyle(metadataLabel).fontSize) === 11
                    && getComputedStyle(metadataLabel).color !== 'rgb(0, 128, 0)'
                    && sourceLink.getBoundingClientRect().height < 91
                    && getComputedStyle(sourceLink).backgroundColor === 'rgba(0, 0, 0, 0)'
                    && getComputedStyle(sourceLink).color !== 'rgb(0, 128, 0)'
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
                const download = document.querySelector('[data-ndb-mail-attachment-download]');
                const style = getComputedStyle(summary);

                return style.fontSize === '12px'
                    && style.color !== 'rgb(255, 0, 0)'
                    && download.getBoundingClientRect().height < 91
                    && getComputedStyle(download).backgroundColor !== 'rgb(255, 0, 255)'
                    && getComputedStyle(download).textDecorationLine === 'none';
            })()
            JS)
        ->click('[data-ndb-mail-item="2"]')
        ->assertVisible('[data-ndb-mail-related-profile]')
        ->assertScript(<<<'JS'
            (() => {
                const status = document.querySelector('[data-ndb-mail-status]');
                const related = document.querySelector('[data-ndb-mail-related-profile]');

                return status === null
                    && related.getBoundingClientRect().height === 36
                    && getComputedStyle(related).borderRadius === '8px';
            })()
            JS)
        ->click('[data-ndb-mail-actions-trigger]')
        ->assertVisible('[data-ndb-mail-open-related]')
        ->assertScript("document.querySelector('[data-ndb-mail-open-related]').getBoundingClientRect().height < 91")
        ->keys('[data-ndb-mail-actions-trigger]', 'Escape')
        ->click('[data-ndb-section="queue"]')
        ->assertVisible('[data-ndb-section-panel="queue"]')
        ->assertVisible('[data-ndb-background-refresh]')
        ->assertScript(<<<'JS'
            (() => {
                const item = document.querySelector('[data-ndb-queue-item]');
                const badge = item.querySelector('span');
                const refresh = document.querySelector('[data-ndb-background-refresh]');
                const link = document.querySelector('[data-ndb-queue-profile-link]');
                const communications = [...document.querySelectorAll('[data-ndb-queue-communication]')];
                const communicationsAreStructured = communications.every((communication) => {
                    const terms = [...communication.querySelectorAll('dt')].map((term) => term.textContent.trim());
                    const values = [...communication.querySelectorAll('dd')].map((value) => value.textContent.trim());
                    const typeIndex = terms.indexOf('Type');
                    const channelIndex = terms.findIndex((term) => term.startsWith('Channel'));

                    return typeIndex !== -1
                        && terms.length >= 1
                        && terms.length === values.length
                        && !['•', '·'].some((separator) => communication.textContent.includes(separator))
                        && (channelIndex === -1 || values[typeIndex].toLowerCase() !== values[channelIndex].toLowerCase());
                });

                return getComputedStyle(item).borderLeftWidth !== '20px'
                    && Number.parseFloat(getComputedStyle(badge).fontSize) === 11
                    && getComputedStyle(item).backgroundColor !== 'rgb(255, 0, 0)'
                    && refresh.getBoundingClientRect().height < 91
                    && link.getBoundingClientRect().height < 91
                    && communications.length >= 1
                    && communicationsAreStructured;
            })()
            JS)
        ->click('[data-ndb-section="notifications"]')
        ->assertVisible('[data-ndb-section-panel="notifications"]')
        ->assertVisible('[data-ndb-notification-profile-link]')
        ->assertScript(<<<'JS'
            (() => {
                const root = document.querySelector('[data-ndb-notifications]');
                const row = document.querySelector('[data-ndb-notification-item]');
                const detail = document.querySelector('[data-ndb-notification-detail]');
                const metadata = document.querySelector('[data-ndb-notification-metadata]');
                const metadataGrid = metadata.querySelector('[data-ndb-notification-facts]');
                const metadataFacts = [...metadata.querySelectorAll('[data-ndb-notification-fact]')];
                const metadataTerms = [...metadata.querySelectorAll('dl, dt, dd')];
                const sourceLink = metadata.querySelector('[data-ndb-inspector-source-link]');
                const destinations = [...document.querySelectorAll('[data-ndb-notification-destination]')];
                const status = document.querySelector('[data-ndb-notification-status]');
                const link = document.querySelector('[data-ndb-notification-profile-link]');
                const backIcon = document.querySelector('[data-ndb-notification-detail-back] svg');
                const tabs = [...document.querySelectorAll('[data-ndb-notification-detail-tab]')];
                const tabIcons = [...document.querySelectorAll('[data-ndb-notification-detail-tab-icon]')];

                return root.getAttribute('data-notifications') === null
                    && getComputedStyle(root).borderLeftWidth === '0px'
                    && getComputedStyle(row).borderLeftWidth === '0px'
                    && row.getBoundingClientRect().height < 91
                    && getComputedStyle(detail).borderLeftWidth === '0px'
                    && getComputedStyle(metadata).backgroundColor !== 'rgb(255, 0, 0)'
                    && getComputedStyle(metadataGrid).display === 'grid'
                    && getComputedStyle(metadataGrid).borderTopWidth === '0px'
                    && Number.parseFloat(getComputedStyle(metadataGrid).paddingTop) === 0
                    && getComputedStyle(metadataGrid).backgroundColor === 'rgba(0, 0, 0, 0)'
                    && metadataFacts.every((fact) => getComputedStyle(fact).backgroundColor === 'rgba(0, 0, 0, 0)')
                    && metadataTerms.every((term) => getComputedStyle(term).backgroundColor === 'rgba(0, 0, 0, 0)')
                    && metadataTerms.every((term) => getComputedStyle(term).color !== 'rgb(0, 128, 0)')
                    && Number.parseFloat(getComputedStyle(metadata.querySelector('dt')).fontSize) === 11
                    && sourceLink.getBoundingClientRect().height < 91
                    && getComputedStyle(sourceLink).backgroundColor === 'rgba(0, 0, 0, 0)'
                    && getComputedStyle(sourceLink).color !== 'rgb(0, 128, 0)'
                    && destinations.length >= 1
                    && destinations.every((destination) => Number.parseFloat(getComputedStyle(destination).fontSize) === 11)
                    && destinations.every((destination) => !getComputedStyle(destination).fontFamily.includes('JetBrains Mono'))
                    && destinations.every((destination) => getComputedStyle(destination).backgroundColor !== 'rgb(255, 0, 0)')
                    && Number.parseFloat(getComputedStyle(status).fontSize) === 11
                    && getComputedStyle(status).backgroundColor !== 'rgb(255, 0, 0)'
                    && link.getBoundingClientRect().height < 91
                    && Number.parseFloat(getComputedStyle(backIcon).width) === 14
                    && tabs.every((tab) => tab.getBoundingClientRect().height < 91)
                    && tabIcons.length === 3
                    && tabIcons.every((icon) => Number.parseFloat(getComputedStyle(icon).width) === 14);
            })()
            JS)
        ->click('[data-ndb-section="authorization"]')
        ->assertVisible('[data-ndb-section-panel="authorization"]')
        ->assertScript(<<<'JS'
            (() => {
                const root = document.querySelector('[data-ndb-authorization]');
                const row = document.querySelector('[data-ndb-authorization-item]');

                return root.getAttribute('data-authorization') === null
                    && row.getAttribute('data-result') === null
                    && row.getAttribute('data-search') === null;
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const row = document.querySelector('[data-ndb-authorization-item]');
                const style = getComputedStyle(row);

                return style.borderLeftWidth === '0px'
                    && style.backgroundColor !== 'rgb(255, 0, 0)';
            })()
            JS)
        ->assertScript(<<<'JS'
            document.querySelector('[data-ndb-authorization-item]').getBoundingClientRect().height !== 91
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const result = document.querySelector('[data-ndb-authorization-result-label]');
                const detailResult = document.querySelector('[data-ndb-authorization-detail-result]');

                return Number.parseFloat(getComputedStyle(result).fontSize) === 11
                    && getComputedStyle(result).backgroundColor === 'rgba(0, 0, 0, 0)'
                    && Number.parseFloat(getComputedStyle(detailResult).fontSize) === 12
                    && getComputedStyle(detailResult).backgroundColor === 'rgba(0, 0, 0, 0)';
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const detail = document.querySelector('[data-ndb-authorization-detail]');
                const metadata = document.querySelector('[data-ndb-authorization-metadata]');
                const metadataTerms = [...metadata.querySelectorAll('dl, dt, dd')];
                const tabs = [...document.querySelectorAll('[data-ndb-authorization-detail-tab]')];

                return getComputedStyle(detail).borderLeftWidth === '0px'
                    && getComputedStyle(metadata).backgroundColor !== 'rgb(255, 0, 0)'
                    && metadataTerms.every((term) => getComputedStyle(term).backgroundColor !== 'rgb(255, 0, 0)')
                    && metadataTerms.every((term) => getComputedStyle(term).color !== 'rgb(0, 128, 0)')
                    && metadataTerms.every((term) => Number.parseFloat(getComputedStyle(term).fontSize) < 42)
                    && tabs.length === 2
                    && tabs.every((tab) => tab.getBoundingClientRect().height < 91);
            })()
            JS)
        ->click('[data-ndb-section="logs"]')
        ->assertVisible('[data-ndb-section-panel="logs"]')
        ->assertScript(<<<'JS'
            (() => {
                const entry = document.querySelector('[data-ndb-log-entry]');
                const severity = entry?.querySelector('[data-ndb-log-severity]');
                const levelSelect = document.querySelector('[data-ndb-log-level-select]');
                const summary = entry?.querySelector('[data-ndb-log-summary]');
                const trigger = entry?.querySelector('[data-ndb-log-details-trigger]');

                if (! entry || ! severity || ! levelSelect || ! summary || ! trigger) return false;

                return getComputedStyle(entry).borderLeftWidth !== '20px'
                    && getComputedStyle(entry).backgroundColor !== 'rgb(255, 0, 0)'
                    && getComputedStyle(entry).paddingLeft === '0px'
                    && Number.parseFloat(getComputedStyle(severity).fontSize) === 11
                    && getComputedStyle(severity).backgroundColor === 'rgba(0, 0, 0, 0)'
                    && getComputedStyle(severity).borderRadius === '0px'
                    && document.querySelector('[data-ndb-log-attention-label]') === null
                    && levelSelect.getBoundingClientRect().height === 36
                    && getComputedStyle(levelSelect).borderLeftWidth === '1px'
                    && getComputedStyle(levelSelect).backgroundColor !== 'rgb(255, 0, 0)'
                    && Number.parseFloat(getComputedStyle(summary).fontSize) === 12
                    && getComputedStyle(summary).color !== 'rgb(255, 0, 0)'
                    && trigger.getBoundingClientRect().height === 32
                    && getComputedStyle(trigger).backgroundColor !== 'rgb(255, 0, 255)'
                    && getComputedStyle(trigger).color !== 'rgb(0, 128, 0)';
            })()
            JS)
        ->click('[data-ndb-log-entry][data-ndb-log-level="error"] [data-ndb-log-details-trigger]')
        ->assertVisible('[data-ndb-log-details-popover] [data-ndb-log-related-exception]')
        ->assertScript(<<<'JS'
            (() => {
                const popover = document.querySelector('[data-ndb-log-details-popover]');
                const surface = popover.querySelector('[data-ndb-popover-surface]');
                const title = popover.querySelector('[data-ndb-log-details-title]');
                const actions = popover.querySelector('[data-ndb-log-actions]');
                const context = popover.querySelector('[data-ndb-log-context]');
                const contextTerm = context.querySelector('dt');
                const exception = popover.querySelector('[data-ndb-log-related-exception]');
                const exceptionLabel = exception.querySelector('h3');
                const review = popover.querySelector('[data-ndb-log-review-exception]');

                return actions === null
                    && getComputedStyle(popover).backgroundColor === 'rgba(0, 0, 0, 0)'
                    && getComputedStyle(popover).borderLeftWidth === '0px'
                    && getComputedStyle(popover).paddingLeft === '0px'
                    && getComputedStyle(surface).backgroundColor !== 'rgb(255, 0, 0)'
                    && Number.parseFloat(getComputedStyle(title).fontSize) === 13
                    && getComputedStyle(title).backgroundColor === 'rgba(0, 0, 0, 0)'
                    && getComputedStyle(title).color !== 'rgb(0, 128, 0)'
                    && getComputedStyle(context).backgroundColor !== 'rgb(255, 0, 0)'
                    && getComputedStyle(context).paddingLeft === '0px'
                    && Number.parseFloat(getComputedStyle(contextTerm).fontSize) === 11
                    && getComputedStyle(contextTerm).color !== 'rgb(0, 128, 0)'
                    && getComputedStyle(exception).backgroundColor === 'rgba(0, 0, 0, 0)'
                    && getComputedStyle(exception).borderRadius === '0px'
                    && review.getBoundingClientRect().height < 24
                    && Math.abs(review.getBoundingClientRect().top - exceptionLabel.getBoundingClientRect().top) <= 1
                    && getComputedStyle(review).backgroundColor === 'rgba(0, 0, 0, 0)'
                    && getComputedStyle(review).color !== 'rgb(0, 128, 0)';
            })()
            JS)
        ->click('[data-ndb-section="events"]')
        ->assertVisible('[data-ndb-section-panel="events"]')
        ->assertScript(<<<'JS'
            (() => {
                const root = document.querySelector('[data-ndb-events]');
                const row = document.querySelector('[data-ndb-event-item]:not([hidden])');
                const rowName = row.querySelector('[data-ndb-event-list-name]');
                const listSummary = document.querySelector('[data-ndb-event-visible-summary]');
                const search = document.querySelector('[data-ndb-event-search]');
                const source = document.querySelector('[data-ndb-event-source-control]');
                const overview = document.querySelector('[data-ndb-event-detail-panel="overview"]');
                const metadataGrid = overview.querySelector('[data-ndb-event-facts]');
                const metadataFacts = [...overview.querySelectorAll('[data-ndb-event-fact]')];
                const metadataTerms = [...metadataGrid.querySelectorAll('dt, dd')];
                const outcome = document.querySelector('[data-ndb-event-listener-outcome]');
                const nextStep = document.querySelector('[data-ndb-event-next-step]');
                const listenerRow = document.querySelector('[data-ndb-event-listener-row]');
                const timeline = document.querySelector('[data-ndb-event-timeline]');
                const tabs = [...document.querySelectorAll('[data-ndb-event-detail-tab]')];
                const tabIcons = [...document.querySelectorAll('[data-ndb-event-detail-tab-icon]')];
                const color = (value) => {
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');

                    canvas.width = 1;
                    canvas.height = 1;
                    context.fillStyle = value;
                    context.fillRect(0, 0, 1, 1);

                    return context.getImageData(0, 0, 1, 1).data;
                };
                const lightness = (element, property) => {
                    const [red, green, blue] = color(getComputedStyle(element)[property]);

                    return (red * 0.2126) + (green * 0.7152) + (blue * 0.0722);
                };
                const searchBox = search.getBoundingClientRect();
                const sourceBox = source.getBoundingClientRect();

                const checks = {
                    rootAttribute: root.getAttribute('data-events') === null,
                    rowBorder: getComputedStyle(row).borderLeftWidth === '0px',
                    rowHeight: row.getBoundingClientRect().height <= 64,
                    rowNameColor: getComputedStyle(rowName).color === getComputedStyle(root).color,
                    listControlAlignment: Math.abs(
                        (searchBox.top + (searchBox.height / 2)) - (sourceBox.top + (sourceBox.height / 2)),
                    ) <= 1,
                    darkControlSurfaces: [search, source].every(
                        (element) => lightness(element, 'backgroundColor') < 90,
                    ),
                    darkControlText: [search, source].every(
                        (element) => lightness(element, 'color') > 130,
                    ),
                    metadataDisplay: getComputedStyle(metadataGrid).display === 'grid',
                    metadataBorder: getComputedStyle(metadataGrid).borderTopWidth === '0px',
                    metadataPadding: Number.parseFloat(getComputedStyle(metadataGrid).paddingTop) === 0,
                    metadataBackground: getComputedStyle(metadataGrid).backgroundColor === 'rgba(0, 0, 0, 0)',
                    factBackgrounds: metadataFacts.every(
                        (fact) => getComputedStyle(fact).backgroundColor === 'rgba(0, 0, 0, 0)',
                    ),
                    termBackgrounds: metadataTerms.every(
                        (term) => getComputedStyle(term).backgroundColor === 'rgba(0, 0, 0, 0)',
                    ),
                    termColors: metadataTerms.every((term) => getComputedStyle(term).color !== 'rgb(0, 128, 0)'),
                    termSize: Number.parseFloat(getComputedStyle(metadataGrid.querySelector('dt')).fontSize) === 11,
                    outcomeSize: Number.parseFloat(getComputedStyle(outcome).fontSize) === 11,
                    outcomeBackground: getComputedStyle(outcome).backgroundColor === 'rgba(0, 0, 0, 0)',
                    nextStepBackground: getComputedStyle(nextStep).backgroundColor === 'rgba(0, 0, 0, 0)',
                    nextStepPadding: Number.parseFloat(getComputedStyle(nextStep).paddingTop) === 0,
                    nextStepColor: getComputedStyle(nextStep).color !== 'rgb(0, 128, 0)',
                    listenerBackground: getComputedStyle(listenerRow).backgroundColor === 'rgba(0, 0, 0, 0)',
                    listenerPadding: Number.parseFloat(getComputedStyle(listenerRow).paddingLeft) === 0,
                    timelineBackground: getComputedStyle(timeline).backgroundColor === 'rgba(0, 0, 0, 0)',
                    timelineBorder: getComputedStyle(timeline).borderLeftWidth === '0px',
                    timelinePadding: Number.parseFloat(getComputedStyle(timeline).paddingTop) === 0,
                    tabHeight: tabs.every((tab) => tab.getBoundingClientRect().height < 91),
                    tabIconCount: tabIcons.length === 3,
                    tabIconSize: tabIcons.every((icon) => Number.parseFloat(getComputedStyle(icon).width) === 14),
                };
                const failures = Object.entries(checks).filter(([, passed]) => !passed).map(([name]) => name);

                if (failures.length > 0) throw new Error('Event isolation failed: ' + failures.join(', '));

                return true;
            })()
            JS)
        ->click('[data-ndb-section="models"]')
        ->assertVisible('[data-ndb-section-panel="models"]')
        ->assertVisible('[data-ndb-model-workspace]')
        ->assertVisible('[data-ndb-model-detail-empty]')
        ->assertMissing('[data-ndb-model-detail]')
        ->assertScript(<<<'JS'
            (() => {
                const pane = document.querySelector('[data-ndb-model-detail-pane]');
                const empty = document.querySelector('[data-ndb-model-detail-empty]');
                const prompt = empty.querySelector('p');
                const search = document.querySelector('[data-ndb-model-search]');
                const searchIcon = search.nextElementSibling;
                const row = document.querySelector('[data-ndb-model-group]');

                return getComputedStyle(empty).backgroundColor !== 'rgb(255, 0, 0)'
                    && getComputedStyle(empty).borderLeftWidth !== '20px'
                    && getComputedStyle(empty).color !== 'rgb(0, 128, 0)'
                    && getComputedStyle(search).backgroundColor !== 'rgb(255, 0, 0)'
                    && Number.parseFloat(getComputedStyle(search).fontSize) < 42
                    && Number.parseFloat(getComputedStyle(search).paddingLeft) < 50
                    && searchIcon.getBoundingClientRect().left < search.getBoundingClientRect().left + search.getBoundingClientRect().width / 2
                    && getComputedStyle(row).display === 'grid'
                    && Math.abs(
                        prompt.getBoundingClientRect().left + prompt.getBoundingClientRect().width / 2
                        - pane.getBoundingClientRect().left - pane.getBoundingClientRect().width / 2
                    ) <= 1
                    && Math.abs(
                        prompt.getBoundingClientRect().top + prompt.getBoundingClientRect().height / 2
                        - pane.getBoundingClientRect().top - pane.getBoundingClientRect().height / 2
                    ) <= 1;
            })()
            JS)
        ->click('[data-ndb-model-group]:first-of-type')
        ->assertVisible('[data-ndb-model-detail]')
        ->assertVisible('[data-ndb-model-detail-panel="records"]')
        ->assertScript(<<<'JS'
            (() => {
                const selectors = [
                    '[data-ndb-model-workspace]',
                    '[data-ndb-model-summary]',
                    '[data-ndb-model-list]',
                    '[data-ndb-model-list-heading]',
                    '[data-ndb-model-group]',
                    '[data-ndb-model-detail-pane]',
                    '[data-ndb-model-detail]',
                    '[data-ndb-model-header]',
                    '[data-ndb-model-detail-panel="records"]',
                    '[data-ndb-model-write-table]',
                    '[data-ndb-model-write-operation]',
                    '[data-ndb-model-retrieved-column]',
                    '[data-ndb-model-write-column]',
                    '[data-ndb-model-extra-column]',
                ];
                const elements = selectors.map((selector) => document.querySelector(selector));
                const summaryCount = getComputedStyle(document.querySelector('[data-ndb-model-summary-count]'));
                const modelClass = document.querySelector('[data-ndb-model-class]');
                const tabs = [...document.querySelectorAll('[data-ndb-model-detail-tab]')];

                const checks = {
                    elements: elements.every(Boolean),
                    backgrounds: elements.every((element) => getComputedStyle(element).backgroundColor !== 'rgb(255, 0, 0)'),
                    borders: elements.every((element) => getComputedStyle(element).borderLeftWidth !== '20px'),
                    colors: elements.every((element) => getComputedStyle(element).color !== 'rgb(0, 128, 0)'),
                    typeSizes: elements.every((element) => Number.parseFloat(getComputedStyle(element).fontSize) < 42),
                    summaryBackground: summaryCount.backgroundColor === 'rgba(0, 0, 0, 0)',
                    summaryColor: summaryCount.color !== 'rgb(0, 128, 0)',
                    summarySize: Number.parseFloat(summaryCount.fontSize) === 12,
                    classBackground: getComputedStyle(modelClass).backgroundColor === 'rgba(0, 0, 0, 0)',
                    classFont: ! getComputedStyle(modelClass).fontFamily.includes('JetBrains Mono'),
                    tabs: tabs.length === 2,
                    tabHeight: tabs.every((tab) => tab.getBoundingClientRect().height < 91),
                    tabBackground: tabs.every((tab) => getComputedStyle(tab).backgroundColor !== 'rgb(255, 0, 255)'),
                    tabColor: tabs.every((tab) => getComputedStyle(tab).color !== 'rgb(0, 128, 0)'),
                    removedWriteEvidence: document.querySelector('[data-ndb-model-operation]') === null,
                    removedQueryActions: document.querySelector('[data-ndb-model-view-queries]') === null,
                };
                const failures = Object.entries(checks).filter(([, passed]) => ! passed).map(([name]) => name);

                if (failures.length > 0) throw new Error('Model isolation failed: ' + failures.join(', '));

                return true;
            })()
            JS)
        ->click('[data-ndb-model-detail-tab="records"]')
        ->assertVisible('[data-ndb-model-record]:first-of-type')
        ->assertScript(<<<'JS'
            (() => {
                const elements = [
                    document.querySelector('[data-ndb-model-detail-panel="records"]'),
                    document.querySelector('[data-ndb-model-records]'),
                    document.querySelector('[data-ndb-model-record]'),
                    document.querySelector('[data-ndb-model-write-table]'),
                    document.querySelector('[data-ndb-model-write-operation]'),
                ];

                return elements.every(Boolean)
                    && elements.every((element) => getComputedStyle(element).backgroundColor !== 'rgb(255, 0, 0)')
                    && elements.every((element) => getComputedStyle(element).borderLeftWidth !== '20px')
                    && elements.every((element) => Number.parseFloat(getComputedStyle(element).paddingLeft) < 50);
            })()
            JS)
        ->click('[data-ndb-model-detail-tab="source"]')
        ->assertVisible('[data-ndb-model-source]:first-of-type')
        ->assertScript(<<<'JS'
            (() => {
                const panel = document.querySelector('[data-ndb-model-detail-panel="source"]');
                const sources = document.querySelector('[data-ndb-model-sources]');
                const source = document.querySelector('[data-ndb-model-source]');
                const path = source.querySelector('[data-ndb-model-source-path]');
                const back = document.querySelector('[data-ndb-model-detail-back]');

                return [panel, sources, source, path].every(Boolean)
                    && [panel, sources, source, path].every(
                        (element) => getComputedStyle(element).backgroundColor !== 'rgb(255, 0, 0)',
                    )
                    && [panel, sources, source, path].every(
                        (element) => getComputedStyle(element).borderLeftWidth !== '20px',
                    )
                    && !getComputedStyle(path).fontFamily.includes('JetBrains Mono')
                    && back.getBoundingClientRect().height < 91
                    && getComputedStyle(back).backgroundColor !== 'rgb(255, 0, 255)'
                    && getComputedStyle(back).color !== 'rgb(0, 128, 0)';
            })()
            JS)
        ->click('[data-ndb-section="views"]')
        ->assertVisible('[data-ndb-section-panel="views"]')
        ->assertVisible('[data-ndb-view-workspace]')
        ->assertVisible('[data-ndb-view-detail-empty]')
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-view-workspace]');
                const list = document.querySelector('[data-ndb-view-list-panel]');
                const row = document.querySelector('[data-ndb-view-group]:not([hidden])');
                const name = row.querySelector('[data-ndb-view-list-name]');
                const search = document.querySelector('[data-ndb-view-search]');
                const filter = document.querySelector('[data-ndb-view-filter]');
                const detail = document.querySelector('[data-ndb-view-detail-pane]');

                const elements = [workspace, list, row, detail];
                const checks = {
                    backgrounds: elements.every(
                        (element) => getComputedStyle(element).backgroundColor !== 'rgb(255, 0, 0)',
                    ),
                    borders: elements.every((element) => getComputedStyle(element).borderLeftWidth !== '20px'),
                    rowDisplay: getComputedStyle(row).display === 'grid',
                    rowHeight: row.getBoundingClientRect().height < 91,
                    rowPadding: Number.parseFloat(getComputedStyle(row).paddingLeft) < 50,
                    nameSize: Number.parseFloat(getComputedStyle(name).fontSize) === 12,
                    nameFont: !getComputedStyle(name).fontFamily.includes('monospace'),
                    nameColor: getComputedStyle(name).color !== 'rgb(0, 128, 0)',
                    searchHeight: search.getBoundingClientRect().height < 91,
                    filterHeight: filter.getBoundingClientRect().height < 91,
                    searchPadding: Number.parseFloat(getComputedStyle(search).paddingLeft) < 50,
                    filterPadding: Number.parseFloat(getComputedStyle(filter).paddingLeft) < 50,
                };
                const failures = Object.entries(checks).filter(([, passed]) => ! passed).map(([name]) => name);

                if (failures.length > 0) throw new Error('View list isolation failed: ' + failures.join(', '));

                return true;
            })()
            JS)
        ->click('[data-ndb-view-group]:not([hidden])')
        ->assertVisible('[data-ndb-view-detail]')
        ->assertScript(<<<'JS'
            (() => {
                const detail = document.querySelector('[data-ndb-view-detail]');
                const name = document.querySelector('[data-ndb-view-detail-name]');
                const tabs = [...document.querySelectorAll('[data-ndb-view-detail-tab]')];
                const back = document.querySelector('[data-ndb-view-detail-back]');

                const checks = {
                    background: getComputedStyle(detail).backgroundColor !== 'rgb(255, 0, 0)',
                    border: getComputedStyle(detail).borderLeftWidth !== '20px',
                    padding: Number.parseFloat(getComputedStyle(detail).paddingLeft) < 50,
                    nameSize: Number.parseFloat(getComputedStyle(name).fontSize) === 16,
                    nameFont: !getComputedStyle(name).fontFamily.includes('monospace'),
                    nameColor: getComputedStyle(name).color !== 'rgb(0, 128, 0)',
                    tabCount: tabs.length === 3,
                    tabHeight: tabs.every((tab) => tab.getBoundingClientRect().height < 91),
                    tabBackground: tabs.every(
                        (tab) => getComputedStyle(tab).backgroundColor !== 'rgb(255, 0, 255)',
                    ),
                    backHeight: back.getBoundingClientRect().height < 91,
                };
                const failures = Object.entries(checks).filter(([, passed]) => ! passed).map(([name]) => name);

                if (failures.length > 0) throw new Error('View detail isolation failed: ' + failures.join(', '));

                return true;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});
