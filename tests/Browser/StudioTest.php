<?php

$waitForStudioPreview = function (mixed $page, string $group, string $theme): void {
    $encodedGroup = json_encode($group, JSON_THROW_ON_ERROR);
    $encodedTheme = json_encode($theme, JSON_THROW_ON_ERROR);

    $page->script(<<<JS
        new Promise((resolve, reject) => {
            const group = {$encodedGroup};
            const theme = {$encodedTheme};
            const deadline = performance.now() + 10000;

            const check = () => {
                const frame = document.querySelector('[data-ndb-studio-frame]');
                const root = frame?.contentDocument?.querySelector('[data-ndb-studio-catalog]');

                if (root?.dataset.ndbStudioCatalog === group && root.dataset.ndbTheme === theme) {
                    resolve(true);

                    return;
                }

                if (performance.now() >= deadline) {
                    reject(new Error('Timed out waiting for Studio preview: ' + group + ' ' + theme));

                    return;
                }

                requestAnimationFrame(check);
            };

            check();
        })
        JS);
};

it('previews every family at exact desktop and mobile widths in both themes', function () use ($waitForStudioPreview) {
    $page = visit('/__newdebugbar/studio')
        ->resize(1600, 1000)
        ->assertVisible('[data-ndb-studio-frame]')
        ->assertAttribute('[data-ndb-studio-width="1024"]', 'aria-pressed', 'true');

    $waitForStudioPreview($page, 'foundations', 'light');

    $page
        ->assertScript('Math.round(document.querySelector("[data-ndb-studio-frame]").getBoundingClientRect().width)', 1024)
        ->assertScript('document.querySelector("[data-ndb-studio-frame]").contentWindow.innerWidth', 1024)
        ->assertScript(<<<'JS'
            (() => {
                const frame = document.querySelector('[data-ndb-studio-frame]');
                const documentElement = frame.contentDocument.documentElement;

                return documentElement.scrollHeight > documentElement.clientHeight
                    && documentElement.scrollWidth <= documentElement.clientWidth;
            })()
            JS)
        ->click('[data-ndb-studio-width="390"]')
        ->assertAttribute('[data-ndb-studio-width="390"]', 'aria-pressed', 'true')
        ->assertScript('Math.round(document.querySelector("[data-ndb-studio-frame]").getBoundingClientRect().width)', 390)
        ->assertScript('document.querySelector("[data-ndb-studio-frame]").contentWindow.innerWidth', 390)
        ->click('[data-ndb-studio-theme="dark"]');

    $waitForStudioPreview($page, 'foundations', 'dark');

    $page
        ->assertAttribute('[data-ndb-studio-theme="dark"]', 'aria-pressed', 'true')
        ->assertAttribute('[data-ndb-studio]', 'data-ndb-theme', 'dark')
        ->click('[data-ndb-studio-family="cache"]');

    $waitForStudioPreview($page, 'cache', 'dark');

    $page
        ->assertScript('new URL(window.location.href).searchParams.get("width")', '390')
        ->assertScript('new URL(window.location.href).searchParams.get("theme")', 'dark')
        ->assertScript('document.querySelector("[data-ndb-studio-frame]").contentWindow.innerWidth', 390)
        ->assertScript(<<<'JS'
            (() => {
                const frame = document.querySelector('[data-ndb-studio-frame]');
                const documentElement = frame.contentDocument.documentElement;
                const root = frame.contentDocument.querySelector('[data-ndb-studio-catalog="cache"]');

                return root !== null
                    && root.dataset.ndbTheme === 'dark'
                    && documentElement.scrollWidth <= documentElement.clientWidth;
            })()
            JS)
        ->click('[data-ndb-studio-component-link="cache-workspace"]');

    $page->script(<<<'JS'
        new Promise((resolve, reject) => {
            const frame = document.querySelector('[data-ndb-studio-frame]');
            const target = frame.contentDocument.getElementById('newdebugbar-studio-cache-workspace');
            const deadline = performance.now() + 3000;

            const check = () => {
                if (target.getBoundingClientRect().top < 120) {
                    resolve(true);

                    return;
                }

                if (performance.now() >= deadline) {
                    reject(new Error('Studio did not scroll to the selected component.'));

                    return;
                }

                requestAnimationFrame(check);
            };

            check();
        })
        JS);

    foreach (['inspector', 'shell', 'http-client', 'communications', 'framework-data'] as $family) {
        $page->click('[data-ndb-studio-family="'.$family.'"]');
        $waitForStudioPreview($page, $family, 'dark');

        $page
            ->assertScript('document.querySelector("[data-ndb-studio-frame]").contentWindow.innerWidth', 390)
            ->assertScript(<<<'JS'
                (() => {
                    const documentElement = document.querySelector('[data-ndb-studio-frame]').contentDocument.documentElement;

                    return documentElement.scrollWidth <= documentElement.clientWidth;
                })()
                JS)
            ->assertNoJavaScriptErrors();

        if ($family === 'communications') {
            $page->assertScript(<<<'JS'
                (() => {
                    const documentElement = document.querySelector('[data-ndb-studio-frame]').contentDocument;
                    const actions = documentElement.querySelector('[data-ndb-mail-actions]');
                    const menu = documentElement.querySelector('[data-ndb-mail-actions-menu]');

                    return actions.open === true && menu.getBoundingClientRect().height > 100;
                })()
                JS);
        }
    }

    $page
        ->click('[data-ndb-studio-theme="light"]')
        ->click('[data-ndb-studio-width="1024"]');
    $waitForStudioPreview($page, 'framework-data', 'light');

    $page
        ->assertScript('document.querySelector("[data-ndb-studio-frame]").contentWindow.innerWidth', 1024)
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth')
        ->assertNoJavaScriptErrors();
});
