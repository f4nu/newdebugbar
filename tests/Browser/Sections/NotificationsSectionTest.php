<?php

it('groups notification attempts in a full-height delivery inspector', function () {
    visit('/profiled-notifications-rich')
        ->resize(1440, 1200)
        ->click('[data-ndb-window-controls="compact"] [data-ndb-window-action="expand"]')
        ->click('[data-ndb-select-section="notifications"]')
        ->waitForText('ProfiledNotification')
        ->assertSee('2 notifications')
        ->assertScript('document.querySelector("[data-ndb-select-section=notifications]").textContent.trim().endsWith("2")')
        ->assertSee('Partially sent')
        ->assertSee('Traveler phone number is not verified.')
        ->assertValue('[data-ndb-notification-filter]', 'all')
        ->assertAttribute('[data-ndb-notification-detail-tab="delivery"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-notification-view-mail]')
        ->assertScript(<<<'JS'
            (() => {
                const root = document.querySelector('[data-ndb-notifications]');
                const payload = root.querySelector('[data-ndb-notification-payload]');
                const notifications = JSON.parse(atob(payload.textContent));

                return !root.getAttribute('x-init').includes('ProfiledNotification')
                    && /^[A-Za-z0-9+/=]+$/.test(payload.textContent)
                    && notifications.length === 2
                    && notifications[0].notification.includes('\\ProfiledNotification');
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-notification-workspace]');
                const [list, detail] = workspace.children;
                const content = document.querySelector('[data-ndb-inspector-content]');
                const details = workspace.parentElement.parentElement.parentElement;
                const workspaceBox = workspace.getBoundingClientRect();
                const contentBox = content.getBoundingClientRect();
                const expectedBottom = contentBox.bottom - Number.parseFloat(getComputedStyle(details).paddingBottom);
                const listBox = list.getBoundingClientRect();
                const detailBox = detail.getBoundingClientRect();
                const selected = document.querySelector('[data-ndb-notification-item][aria-pressed="true"]');
                const summary = document.querySelector('[data-ndb-notification-summary]');
                const summaryCount = document.querySelector('[data-ndb-notification-summary-count]');
                const summaryRuntime = document.querySelector('[data-ndb-notification-summary-runtime]');
                const filter = document.querySelector('[data-ndb-notification-filter]');
                const metadata = document.querySelector('[data-ndb-notification-metadata]');
                const metadataFacts = [...metadata.lastElementChild.children];
                const factWidths = metadataFacts.map((fact) => fact.getBoundingClientRect().width);
                const channelControl = document.querySelector('[data-ndb-notification-channel-control]');
                const tabs = [...document.querySelectorAll('[data-ndb-notifications] [data-ndb-filter-tabs]')];

                return getComputedStyle(workspace).display === 'grid'
                    && workspaceBox.height > 576
                    && Math.abs(workspaceBox.bottom - expectedBottom) <= 1
                    && detailBox.width > listBox.width * 1.6
                    && Math.abs(listBox.top - detailBox.top) <= 1
                    && selected.dataset.ndbNotificationItem === '1'
                    && selected.getAttribute('aria-pressed') === 'true'
                    && getComputedStyle(selected).borderLeftWidth === '0px'
                    && summary.parentElement.contains(filter)
                    && summary.getBoundingClientRect().left < filter.getBoundingClientRect().left
                    && summaryRuntime.getBoundingClientRect().top > summaryCount.getBoundingClientRect().top
                    && filter.options[0].value === 'all'
                    && tabs.length === 1
                    && tabs[0].parentElement === channelControl.parentElement
                    && getComputedStyle(channelControl).display === 'none'
                    && metadata.querySelectorAll('svg').length === 3
                    && Math.max(...factWidths) - Math.min(...factWidths) <= 1
                    && metadata.scrollWidth <= metadata.clientWidth + 1
                    && getComputedStyle(detail).overflowY === 'auto'
                    && detail.tabIndex === 0
                    && !document.querySelector('[data-ndb-notifications]').textContent.includes('•');
            })()
            JS)
        ->click('[data-ndb-notification-detail-tab="data"]')
        ->assertVisible('[data-ndb-notification-detail-panel="data"]')
        ->assertVisible('[data-ndb-notification-channel-control]')
        ->assertValue('[data-ndb-notification-channel]', 'mail')
        ->select('[data-ndb-notification-channel]', 'profiled-sms')
        ->assertValue('[data-ndb-notification-channel]', 'profiled-sms')
        ->assertSee('RuntimeException')
        ->assertSee('No provider response was captured.')
        ->click('[data-ndb-notification-detail-tab="source"]')
        ->assertVisible('[data-ndb-notification-detail-panel="source"]')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-notification-channel-control]")).display === "none"')
        ->assertSee('NewDebugBar\\Tests\\Fixtures\\Notifications\\ProfiledNotification')
        ->assertSee('tests/Fixtures/Notifications/ProfiledNotification.php')
        ->assertSee('tests/Support/DefinesTestApplication.php')
        ->click('[data-ndb-notification-detail-tab="delivery"]')
        ->click('[data-ndb-notification-view-mail]')
        ->assertVisible('[data-ndb-section-panel="mail"]')
        ->assertSee('Your Kyoto journey is ready to review')
        ->assertAttribute('[data-ndb-mail-item="1"]', 'aria-pressed', 'true')
        ->click('[data-ndb-select-section="notifications"]')
        ->select('[data-ndb-notification-filter]', 'sent')
        ->assertValue('[data-ndb-notification-filter]', 'sent')
        ->assertScript('document.querySelectorAll("[data-ndb-notification-item]:not([hidden])").length', 1)
        ->assertAttribute('[data-ndb-notification-item="2"]', 'aria-pressed', 'true')
        ->select('[data-ndb-notification-filter]', 'all')
        ->assertScript('document.querySelectorAll("[data-ndb-notification-item]:not([hidden])").length', 2)
        ->click('[data-ndb-notification-item="2"]')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-notification-view-mail]")).display === "none"')
        ->assertNoJavaScriptErrors();
});

it('drills into notification details with icon tabs on mobile', function () {
    $preferences = json_encode([
        'theme' => 'dark',
        'favorites' => [],
    ], JSON_THROW_ON_ERROR);

    visit('/profiled-notifications-rich')
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
        ->click('[data-ndb-select-section="notifications"]')
        ->waitForText('ProfiledNotification')
        ->assertAttribute('#newdebugbar', 'data-ndb-theme', 'dark')
        ->assertScript(<<<'JS'
            (() => {
                const dialog = document.querySelector('[role="dialog"][aria-label="Request inspector"]');
                const workspace = document.querySelector('[data-ndb-notification-workspace]');
                const [list, detail] = workspace.children;
                const rows = [...document.querySelectorAll('[data-ndb-notification-item]')];

                return dialog.scrollWidth <= dialog.clientWidth + 1
                    && workspace.scrollWidth <= workspace.clientWidth + 1
                    && getComputedStyle(workspace).display !== 'grid'
                    && getComputedStyle(list).display === 'flex'
                    && getComputedStyle(detail).display === 'none'
                    && rows.every((row) => getComputedStyle(row).borderLeftWidth === '0px');
            })()
            JS)
        ->click('[data-ndb-notification-item="1"]')
        ->assertAttribute('[data-ndb-notification-item="1"]', 'aria-pressed', 'true')
        ->assertVisible('[data-ndb-notification-detail]')
        ->assertScript(<<<'JS'
            (() => {
                const detail = document.querySelector('[data-ndb-notification-detail]');
                const content = document.querySelector('[data-ndb-inspector-content]');
                const workspace = document.querySelector('[data-ndb-notification-workspace]');
                const list = workspace.firstElementChild;
                const back = document.querySelector('[data-ndb-notification-detail-back]');
                const metadata = document.querySelector('[data-ndb-notification-metadata]');
                const tabs = [...document.querySelectorAll('[data-ndb-notification-detail-tab]')];
                const labels = tabs.map((tab) => tab.querySelector('span'));
                const icons = tabs.map((tab) => tab.querySelector('[data-ndb-notification-detail-tab-icon]'));

                return getComputedStyle(list).display === 'none'
                    && getComputedStyle(detail).display === 'flex'
                    && detail.getBoundingClientRect().top >= content.getBoundingClientRect().top
                    && detail.getBoundingClientRect().width >= workspace.getBoundingClientRect().width - 2
                    && metadata.scrollWidth <= metadata.clientWidth + 1
                    && back.getClientRects().length > 0
                    && back.textContent.trim() === 'Notifications'
                    && tabs.length === 3
                    && icons.every((icon) => icon && icon.getClientRects().length > 0)
                    && labels.every((label) => getComputedStyle(label).display === 'none')
                    && tabs.map((tab) => tab.getAttribute('aria-label')).join('|') === 'Delivery|Data|Source'
                    && content.scrollWidth <= content.clientWidth + 1;
            })()
            JS)
        ->click('[data-ndb-notification-detail-tab="data"]')
        ->assertVisible('[data-ndb-notification-channel-control]')
        ->select('[data-ndb-notification-channel]', 'profiled-sms')
        ->assertSee('Traveler phone number is not verified.')
        ->click('[data-ndb-notification-detail-back]')
        ->click('[data-ndb-notification-item="2"]')
        ->assertScript('getComputedStyle(document.querySelector("[data-ndb-notification-view-mail]")).display === "none"')
        ->click('[data-ndb-notification-detail-back]')
        ->assertScript(<<<'JS'
            (() => {
                const workspace = document.querySelector('[data-ndb-notification-workspace]');
                const [list, detail] = workspace.children;
                const selected = document.querySelector('[data-ndb-notification-item="2"]');

                return getComputedStyle(list).display === 'flex'
                    && getComputedStyle(detail).display === 'none'
                    && selected.getAttribute('aria-pressed') === 'true';
            })()
            JS)
        ->assertNoJavaScriptErrors();
});
