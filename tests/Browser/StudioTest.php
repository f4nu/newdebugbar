<?php

$waitForStudioPreview = function (mixed $page, string $component, string $theme): void {
    $encodedComponent = json_encode($component, JSON_THROW_ON_ERROR);
    $encodedTheme = json_encode($theme, JSON_THROW_ON_ERROR);

    $page->script(<<<JS
        new Promise((resolve, reject) => {
            const component = {$encodedComponent};
            const theme = {$encodedTheme};
            const deadline = performance.now() + 10000;

            const check = () => {
                const frame = document.querySelector('[data-ndb-studio-frame]');
                const root = frame?.contentDocument?.querySelector('[data-ndb-studio-preview]');

                if (root?.dataset.ndbStudioPreview === component && root.dataset.ndbTheme === theme) {
                    resolve(true);

                    return;
                }

                if (performance.now() >= deadline) {
                    reject(new Error('Timed out waiting for Studio preview: ' + component + ' ' + theme));

                    return;
                }

                requestAnimationFrame(check);
            };

            check();
        })
        JS);
};

it('browses one focused component at a time with persistent preview controls', function () use ($waitForStudioPreview) {
    $page = visit('/__newdebugbar/studio')
        ->resize(1600, 1000)
        ->assertVisible('[data-ndb-studio-search]')
        ->assertVisible('[data-ndb-studio-component-link="search-field"]')
        ->assertAttribute('[data-ndb-studio-component-link="search-field"]', 'aria-current', 'page')
        ->assertAttribute('[data-ndb-studio-width="1024"]', 'aria-pressed', 'true');

    $waitForStudioPreview($page, 'search-field', 'light');

    $page
        ->assertScript('Math.round(document.querySelector("[data-ndb-studio-frame]").getBoundingClientRect().width)', 1024)
        ->assertScript('document.querySelector("[data-ndb-studio-frame]").contentWindow.innerWidth', 1024)
        ->assertScript('document.querySelector("[data-ndb-studio-frame]").contentDocument.querySelectorAll("[data-ndb-studio-demo]").length', 1)
        ->assertScript(<<<'JS'
            (() => {
                const frame = document.querySelector('[data-ndb-studio-frame]');
                const surface = frame?.contentDocument?.querySelector('[data-ndb-studio-demo-surface]');
                const content = surface?.firstElementChild;

                if (!surface || !content) return false;

                const surfaceBox = surface.getBoundingClientRect();
                const contentBox = content.getBoundingClientRect();
                const horizontalDifference = Math.abs(
                    surfaceBox.left + surfaceBox.width / 2 - contentBox.left - contentBox.width / 2,
                );
                const verticalDifference = Math.abs(
                    surfaceBox.top + surfaceBox.height / 2 - contentBox.top - contentBox.height / 2,
                );

                return horizontalDifference <= 1 && verticalDifference <= 1;
            })()
            JS)
        ->assertScript(<<<'JS'
            (() => {
                const root = document.querySelector('[data-ndb-studio]');
                const navigation = root?.querySelector('[data-ndb-studio-navigation-group]')?.parentElement;
                const frame = root?.querySelector('[data-ndb-studio-frame]');
                const frameShell = root?.querySelector('[data-ndb-studio-frame-shell]');

                if (!navigation || !frame || !frameShell) return false;

                const frameShellStyle = getComputedStyle(frameShell);

                return getComputedStyle(navigation).overflowY === 'visible'
                    && root.querySelector(':scope > header') === null
                    && frame.contentDocument.querySelector('#newdebugbar > header') === null
                    && frameShellStyle.borderWidth === '0px'
                    && frameShellStyle.borderRadius === '0px'
                    && frameShellStyle.boxShadow === 'none';
            })()
            JS)
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth')
        ->fill('[data-ndb-studio-search]', 'inspector-operation-badge')
        ->assertVisible('[data-ndb-studio-component-link="inspector-operation-badge"]')
        ->assertScript('document.querySelectorAll("[data-ndb-studio-component]:not([hidden])").length', 1)
        ->click('[data-ndb-studio-component-link="inspector-operation-badge"]')
        ->assertPathIs('/__newdebugbar/studio/inspector-operation-badge');

    $waitForStudioPreview($page, 'inspector-operation-badge', 'light');

    $page
        ->assertScript('new URL(window.location.href).searchParams.get("width")', '1024')
        ->assertScript('document.querySelector("[data-ndb-studio-frame]").contentDocument.querySelectorAll("[data-ndb-studio-demo]").length', 1)
        ->click('[data-ndb-studio-width="390"]')
        ->click('[data-ndb-studio-theme="dark"]');

    $waitForStudioPreview($page, 'inspector-operation-badge', 'dark');

    $page
        ->assertAttribute('[data-ndb-studio-theme="dark"]', 'aria-pressed', 'true')
        ->assertAttribute('[data-ndb-studio]', 'data-ndb-theme', 'dark')
        ->assertScript('document.querySelector("[data-ndb-studio-frame]").contentWindow.innerWidth', 390)
        ->fill('[data-ndb-studio-search]', 'cache-workspace')
        ->assertVisible('[data-ndb-studio-component-link="cache-workspace"]')
        ->click('[data-ndb-studio-component-link="cache-workspace"]')
        ->assertPathIs('/__newdebugbar/studio/cache-workspace');

    $waitForStudioPreview($page, 'cache-workspace', 'dark');

    $page
        ->assertScript('new URL(window.location.href).searchParams.get("width")', '390')
        ->assertScript('new URL(window.location.href).searchParams.get("theme")', 'dark')
        ->assertScript('document.querySelector("[data-ndb-studio-frame]").contentDocument.querySelectorAll("[data-ndb-studio-demo]").length', 1)
        ->assertScript('document.querySelector("[data-ndb-studio-frame]").contentDocument.documentElement.scrollWidth <= document.querySelector("[data-ndb-studio-frame]").contentDocument.documentElement.clientWidth')
        ->assertNoJavaScriptErrors();

    $page->script(<<<'JS'
        (() => {
            document.querySelector('[data-ndb-studio-frame-shell]').style.width = '714px';

            return new Promise((resolve) => window.setTimeout(resolve, 250));
        })()
        JS);

    $page
        ->assertScript('Math.round(document.querySelector("[data-ndb-studio-frame]").getBoundingClientRect().width)', 714)
        ->fill('[data-ndb-studio-search]', 'notification-header')
        ->assertVisible('[data-ndb-studio-component-link="notification-header"]')
        ->click('[data-ndb-studio-component-link="notification-header"]')
        ->assertPathIs('/__newdebugbar/studio/notification-header');

    $waitForStudioPreview($page, 'notification-header', 'dark');

    $page
        ->assertScript('new URL(window.location.href).searchParams.get("width")', '714')
        ->assertScript('document.querySelector("[data-ndb-studio-frame]").contentWindow.innerWidth', 714)
        ->resize(390, 900)
        ->assertVisible('[data-ndb-studio-component-select]')
        ->assertScript(<<<'JS'
            (() => {
                const root = document.querySelector('[data-ndb-studio]');

                return document.documentElement.scrollWidth <= document.documentElement.clientWidth
                    && document.body.scrollWidth <= document.documentElement.clientWidth
                    && root.scrollWidth <= root.clientWidth;
            })()
            JS)
        ->assertNoJavaScriptErrors();
});

it('keeps representative elements, patterns, and compositions bounded in both themes', function () use ($waitForStudioPreview) {
    $page = visit('/__newdebugbar/studio/icon-button?width=390&theme=dark')
        ->resize(1400, 760);

    foreach (['icon-button', 'inspector-facts', 'corner-toolbar', 'http-client-response-panel', 'mail-message-details', 'model-group-detail', 'query-section'] as $component) {
        if ($component !== 'icon-button') {
            $page
                ->fill('[data-ndb-studio-search]', $component)
                ->assertVisible('[data-ndb-studio-component-link="'.$component.'"]')
                ->click('[data-ndb-studio-component-link="'.$component.'"]');
        }

        $waitForStudioPreview($page, $component, 'dark');

        $page
            ->assertScript('document.querySelector("[data-ndb-studio-frame]").contentWindow.innerWidth', 390)
            ->assertScript('document.querySelector("[data-ndb-studio-frame]").contentDocument.querySelectorAll("[data-ndb-studio-demo]").length', 1)
            ->assertScript(<<<'JS'
                (() => {
                    const frame = document.querySelector('[data-ndb-studio-frame]');
                    const surface = frame?.contentDocument?.querySelector('[data-ndb-studio-demo-surface]');
                    const content = surface?.firstElementChild;

                    if (!surface || !content) return false;

                    const surfaceBox = surface.getBoundingClientRect();
                    const contentBox = content.getBoundingClientRect();

                    return Math.abs(
                        surfaceBox.left + surfaceBox.width / 2 - contentBox.left - contentBox.width / 2,
                    ) <= 1
                        && Math.abs(
                            surfaceBox.top + surfaceBox.height / 2 - contentBox.top - contentBox.height / 2,
                        ) <= 1;
                })()
                JS)
            ->assertScript('document.querySelector("[data-ndb-studio-frame]").contentDocument.documentElement.scrollWidth <= document.querySelector("[data-ndb-studio-frame]").contentDocument.documentElement.clientWidth')
            ->assertNoJavaScriptErrors();
    }

    $page
        ->click('[data-ndb-studio-theme="light"]')
        ->click('[data-ndb-studio-width="1024"]');

    $waitForStudioPreview($page, 'query-section', 'light');

    $page
        ->assertScript('document.querySelector("[data-ndb-studio-frame]").contentWindow.innerWidth', 1024)
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth')
        ->assertNoJavaScriptErrors();
});
