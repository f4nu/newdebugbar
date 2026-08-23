<?php

it('selects and inspects mail with a real in-panel preview', function () {
    visit('/profiled-mail-rich')
        ->resize(1440, 1200)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="mail"]')
        ->waitForText('Payment receipt #NS-1042')
        ->assertSee('3 messages')
        ->assertValue('[data-ndb-mail-filter]', 'all')
        ->assertAttribute('[data-ndb-mail-detail-tab="preview"]', 'aria-pressed', 'true')
        ->assertValue('[data-ndb-mail-preview-format]', 'html')
        ->assertAttribute('[data-ndb-mail-preview-viewport="desktop"]', 'aria-pressed', 'true')
        ->assertAttribute('[data-ndb-mail-actions-trigger]', 'aria-haspopup', 'menu')
        ->assertAttribute('[data-ndb-mail-actions-trigger]', 'aria-label', 'Mail actions')
        ->assertAttribute('[data-ndb-mail-actions-trigger]', 'aria-expanded', 'false')
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-mail-workspace]');
                const [list, detail] = workspace.children;
                const content = document.querySelector('[data-ndb-inspector-content]');
                const details = workspace.parentElement.parentElement.parentElement;
                const workspaceBox = workspace.getBoundingClientRect();
                const contentBox = content.getBoundingClientRect();
                const expectedBottom = contentBox.bottom - Number.parseFloat(getComputedStyle(details).paddingBottom);
                const listBox = list.getBoundingClientRect();
                const detailBox = detail.getBoundingClientRect();
                const selected = document.querySelector('[data-ndb-mail-item][aria-pressed="true"]');
                const tabs = [...document.querySelectorAll('[data-ndb-mail] [data-ndb-filter-tabs]')];
                const frame = document.querySelector('[data-ndb-mail-preview-frame]');
                const viewportButtons = [...document.querySelectorAll('[data-ndb-mail-preview-viewport]')];
                const viewportControl = document.querySelector('[data-ndb-mail-preview-viewport-control]');
                const formatControl = document.querySelector('[data-ndb-mail-preview-format]');
                const previewControls = document.querySelector('[data-ndb-mail-preview-controls]');
                const previewSurface = document.querySelector('[data-ndb-mail-preview-surface]');
                const attachmentBadge = document.querySelector('[data-ndb-mail-attachment-badge]');
                const summary = document.querySelector('[data-ndb-mail-summary]');
                const summaryCount = document.querySelector('[data-ndb-mail-summary-count]');
                const summaryRuntime = document.querySelector('[data-ndb-mail-summary-runtime]');
                const filter = document.querySelector('[data-ndb-mail-filter]');
                const header = detail.querySelector('header');
                const actions = header.querySelector('[data-ndb-mail-actions]');
                const metadata = header.querySelector('[data-ndb-mail-metadata]');
                const [addresses, technicalFacts] = metadata.children;
                const metadataFacts = [...technicalFacts.children];
                const addressGroups = [...addresses.children];
                const addressWidths = addressGroups.map((group) => group.getBoundingClientRect().width);
                const metadataFactWidths = metadataFacts.map((fact) => fact.getBoundingClientRect().width);
                const headerTop = header.getBoundingClientRect().top;

                detail.scrollTop = 96;
                const headerScrolled = header.getBoundingClientRect().top < headerTop - 80;
                detail.scrollTop = 0;

                return getComputedStyle(workspace).display === 'grid'
                    && workspaceBox.height > 576
                    && Math.abs(workspaceBox.bottom - expectedBottom) <= 1
                    && detailBox.width > listBox.width * 1.6
                    && Math.abs(listBox.top - detailBox.top) <= 1
                    && selected.dataset.ndbMailItem === '1'
                    && getComputedStyle(selected).borderLeftWidth === '0px'
                    && tabs.length === 1
                    && viewportButtons.length === 2
                    && viewportButtons.every((button) => button.querySelector('svg'))
                    && viewportButtons.every((button) => button.querySelector('svg').getBoundingClientRect().width <= 12.5)
                    && formatControl.getBoundingClientRect().left > viewportControl.getBoundingClientRect().right
                    && tabs[0].parentElement === previewControls.parentElement
                    && attachmentBadge.closest('header') === header
                    && actions.open === false
                    && metadataFacts.length === 3
                    && metadata.textContent.includes('Sent')
                    && metadata.textContent.includes('Delivery')
                    && metadata.textContent.includes('Source')
                    && metadata.querySelectorAll('svg').length === 3
                    && header.querySelector('[data-ndb-mail-actions-trigger]').textContent.trim() === ''
                    && metadata.scrollWidth <= metadata.clientWidth + 1
                    && /^To\s/.test(addressGroups[0].textContent.trim())
                    && /^From\s/.test(addressGroups[1].textContent.trim())
                    && ! addresses.textContent.includes('→')
                    && Math.abs(addressWidths[0] - addressWidths[1]) <= 1
                    && Math.max(...metadataFactWidths) - Math.min(...metadataFactWidths) <= 1
                    && summary.parentElement.contains(filter)
                    && summary.getBoundingClientRect().left < filter.getBoundingClientRect().left
                    && summaryRuntime.getBoundingClientRect().top > summaryCount.getBoundingClientRect().top
                    && detail.scrollHeight > detail.clientHeight
                    && getComputedStyle(detail).overflowY === 'auto'
                    && detail.tabIndex === 0
                    && getComputedStyle(previewSurface).overflowY === 'visible'
                    && headerScrolled
                    && frame.getAttribute('sandbox') === 'allow-scripts'
                    && frame.getAttribute('src').endsWith('/0/html')
                    && frame.getBoundingClientRect().width > 500
                    && frame.clientHeight > 320
                    && !document.querySelector('[data-ndb-mail]').textContent.includes('•');
            })()
            JS)
        ->click('[data-ndb-mail-actions-trigger]')
        ->assertAttribute('[data-ndb-mail-actions-trigger]', 'aria-expanded', 'true')
        ->assertVisible('[data-ndb-mail-actions-menu]')
        ->assertScript(<<<'JS'
            (() => {
                const actions = document.querySelector('[data-ndb-mail-actions]');
                const menu = document.querySelector('[data-ndb-mail-actions-menu]');
                const surface = menu.querySelector('[data-ndb-popover-surface]');
                const items = [...menu.querySelectorAll('[role="menuitem"]')];
                const preview = menu.querySelector('[data-ndb-mail-open-preview]');
                const download = menu.querySelector('[data-ndb-mail-download]');

                return actions.open
                    && menu.getAttribute('role') === 'menu'
                    && items.length === 2
                    && items.every((item) => Number.parseFloat(getComputedStyle(item).minHeight) >= 44)
                    && items.every((item) => item.querySelector('svg'))
                    && Number.parseFloat(getComputedStyle(surface).borderRadius) === 16
                    && preview.getAttribute('href').endsWith('/0/html')
                    && preview.getAttribute('target') === '_blank'
                    && download.getAttribute('href').endsWith('/0/eml');
            })()
            JS)
        ->keys('[data-ndb-mail-actions-trigger]', 'Escape')
        ->assertAttribute('[data-ndb-mail-actions-trigger]', 'aria-expanded', 'false')
        ->assertScript(<<<'JS'
            (() => {
                const actions = document.querySelector('[data-ndb-mail-actions]');
                const trigger = document.querySelector('[data-ndb-mail-actions-trigger]');

                return actions.open === false && document.activeElement === trigger;
            })()
            JS)
        ->select('[data-ndb-mail-preview-format]', 'text')
        ->assertValue('[data-ndb-mail-preview-format]', 'text')
        ->assertScript('document.querySelector("[data-ndb-mail-preview-frame]").getAttribute("src").endsWith("/0/text")')
        ->select('[data-ndb-mail-preview-format]', 'html')
        ->select('[data-ndb-mail-filter]', 'attachments')
        ->assertValue('[data-ndb-mail-filter]', 'attachments')
        ->assertScript('document.querySelectorAll("[data-ndb-mail-item]:not([hidden])").length', 1)
        ->select('[data-ndb-mail-filter]', 'all')
        ->assertValue('[data-ndb-mail-filter]', 'all')
        ->assertScript('document.querySelectorAll("[data-ndb-mail-item]:not([hidden])").length', 3)
        ->click('[data-ndb-mail-item="2"]')
        ->assertAttribute('[data-ndb-mail-item="2"]', 'aria-pressed', 'true')
        ->assertSee('Welcome to Northstar')
        ->click('[data-ndb-mail-item="3"]')
        ->assertAttribute('[data-ndb-mail-item="3"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelector("[data-ndb-mail-preview-frame]").getAttribute("src").endsWith("/2/text")')
        ->assertValue('[data-ndb-mail-preview-format]', 'text')
        ->click('[data-ndb-mail-item="1"]')
        ->click('[data-ndb-mail-preview-viewport="mobile"]')
        ->assertAttribute('[data-ndb-mail-preview-viewport="mobile"]', 'aria-pressed', 'true')
        ->assertScript('document.querySelector("[data-ndb-mail-preview-canvas]").getBoundingClientRect().width <= 376')
        ->click('[data-ndb-mail-detail-tab="message"]')
        ->assertAttribute('[data-ndb-mail-detail-tab="message"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-mail-detail-panel="message"]')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-mail-preview-controls]")).display === "none"')
        ->assertSee('receipt-NS-1042.pdf')
        ->assertSee('application/pdf')
        ->assertSee('Default mailer')
        ->click('[data-ndb-mail-headers] summary')
        ->assertSee('X-Northstar-Flow: profiled-mail')
        ->click('[data-ndb-mail-detail-tab="source"]')
        ->assertVisible('[data-ndb-mail-detail-panel="source"]')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-mail-preview-controls]")).display === "none"')
        ->assertSee('NewDebugBar\\Tests\\Fixtures\\Mail\\ProfiledMailable')
        ->assertSee('tests/Support/DefinesTestApplication.php')
        ->assertNoJavaScriptErrors();
});

it('drills into mail details with compact icon tabs on mobile', function () {
    $preferences = json_encode([
        'theme' => 'dark',
        'favorites' => [],
    ], JSON_THROW_ON_ERROR);

    visit('/profiled-mail-rich')
        ->resize(390, 844)
        ->assertScript(<<<JS
            (() => {
                localStorage.setItem('newdebugbar.preferences.v1', '{$preferences}');

                return true;
            })()
            JS)
        ->refresh()
        ->click('[data-ndb-mobile-toolbar-trigger="actions"]')
        ->click('[data-ndb-mobile-toolbar-action="inspector"]')
        ->click('[data-ndb-header-mobile-trigger="actions"]')
        ->click('[data-ndb-header-mobile-action="sections"]')
        ->click('[data-ndb-select-section="mail"]')
        ->waitForText('Payment receipt #NS-1042')
        ->assertAttribute('#newdebugbar', 'data-ndb-theme', 'dark')
        ->assertScript(<<<'JS'
            (() => {
                const dialog = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                const workspace = document.querySelector('[data-ndb-mail-workspace]');
                const [list, detail] = workspace.children;
                const listBox = list.getBoundingClientRect();
                const detailBox = detail.getBoundingClientRect();
                const rows = [...document.querySelectorAll('[data-ndb-mail-item]')];

                return dialog.scrollWidth <= dialog.clientWidth + 1
                    && workspace.scrollWidth <= workspace.clientWidth + 1
                    && getComputedStyle(workspace).display !== 'grid'
                    && getComputedStyle(list).display === 'flex'
                    && getComputedStyle(detail).display === 'none'
                    && listBox.width > 0
                    && detailBox.width === 0
                    && rows.every((row) => getComputedStyle(row).borderLeftWidth === '0px');
            })()
            JS)
        ->click('[data-ndb-mail-item="2"]')
        ->assertAttribute('[data-ndb-mail-item="2"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-mail-detail]')
        ->assertScript(<<<'JS'
            (() => {
                const detail = document.querySelector('[data-ndb-mail-detail]');
                const content = document.querySelector('[data-ndb-inspector-content]');
                const frame = document.querySelector('[data-ndb-mail-preview-frame]');
                const metadata = document.querySelector('[data-ndb-mail-metadata]');
                const technicalFacts = metadata.lastElementChild;
                const workspace = document.querySelector('[data-ndb-mail-workspace]');
                const list = workspace.firstElementChild;
                const back = document.querySelector('[data-ndb-mail-detail-back]');
                const tabs = [...document.querySelectorAll('[data-ndb-mail-detail-tab]')];
                const labels = tabs.map((tab) => tab.querySelector('span'));
                const icons = tabs.map((tab) => tab.querySelector('[data-ndb-mail-detail-tab-icon]'));

                return getComputedStyle(list).display === 'none'
                    && getComputedStyle(detail).display === 'flex'
                    && detail.getBoundingClientRect().top >= content.getBoundingClientRect().top
                    && detail.getBoundingClientRect().width >= workspace.getBoundingClientRect().width - 2
                    && frame.getBoundingClientRect().width <= detail.getBoundingClientRect().width
                    && metadata.scrollWidth <= metadata.clientWidth + 1
                    && getComputedStyle(technicalFacts).gridTemplateColumns.split(' ').length === 3
                    && back.getClientRects().length > 0
                    && back.textContent.trim() === 'Messages'
                    && tabs.length === 3
                    && icons.every((icon) => icon && icon.getClientRects().length > 0)
                    && labels.every((label) => getComputedStyle(label).display === 'none')
                    && tabs.map((tab) => tab.getAttribute('aria-label')).join('|') === 'Preview|Message|Source'
                    && content.scrollWidth <= content.clientWidth + 1;
            })()
            JS)
        ->click('[data-ndb-mail-detail-tab="message"]')
        ->assertVisible('[data-ndb-mail-detail-panel="message"]')
        ->click('[data-ndb-mail-detail-tab="preview"]')
        ->click('[data-ndb-mail-actions-trigger]')
        ->assertVisible('[data-ndb-mail-actions-menu]')
        ->assertScript(<<<'JS'
            (() => {
                const menu = document.querySelector('[data-ndb-mail-actions-menu]');
                const rect = menu.getBoundingClientRect();

                return rect.left >= 0 && rect.right <= window.innerWidth;
            })()
            JS)
        ->keys('[data-ndb-mail-actions-trigger]', 'Escape')
        ->click('[data-ndb-mail-detail-back]')
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-mail-workspace]');
                const [list, detail] = workspace.children;
                const selected = document.querySelector('[data-ndb-mail-item="2"]');

                return getComputedStyle(list).display === 'flex'
                    && getComputedStyle(detail).display === 'none'
                    && selected.getAttribute('aria-pressed') === 'true';
            })()
            JS)
        ->assertNoJavaScriptErrors();
});
