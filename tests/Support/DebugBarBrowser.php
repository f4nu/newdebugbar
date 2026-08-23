<?php

namespace NewDebugBar\Tests\Support;

final class DebugBarBrowser
{
    public static function waitForVisibleElement(mixed $page, string $selector): void
    {
        $encodedSelector = json_encode($selector, JSON_THROW_ON_ERROR);

        $page->script(<<<JS
            new Promise((resolve, reject) => {
                const selector = {$encodedSelector};
                const deadline = performance.now() + 10000;
                let visibleElement = null;
                let visibleFrames = 0;

                const check = () => {
                    const element = document.querySelector(selector);
                    const style = element === null ? null : getComputedStyle(element);
                    const box = element?.getBoundingClientRect();
                    const visible = element !== null
                        && element.isConnected
                        && style.display !== 'none'
                        && style.visibility !== 'hidden'
                        && Number(style.opacity) !== 0
                        && box.width > 0
                        && box.height > 0;

                    if (visible) {
                        if (element === visibleElement) visibleFrames += 1;
                        else {
                            visibleElement = element;
                            visibleFrames = 0;
                        }

                        if (visibleFrames >= 2) {
                            resolve(true);

                            return;
                        }
                    } else {
                        visibleElement = null;
                        visibleFrames = 0;
                    }

                    if (performance.now() >= deadline) {
                        reject(new Error('Timed out waiting for visible element: ' + selector));

                        return;
                    }

                    requestAnimationFrame(check);
                };

                check();
            })
            JS);
    }

    public static function waitForStableElement(mixed $page, string $selector): void
    {
        $encodedSelector = json_encode($selector, JSON_THROW_ON_ERROR);

        $page->script(<<<JS
            new Promise((resolve, reject) => {
                const selector = {$encodedSelector};
                const deadline = performance.now() + 10000;
                let stableElement = null;
                let stableFrames = 0;

                const check = () => {
                    const element = document.querySelector(selector);

                    if (element !== null && element.isConnected) {
                        if (element === stableElement) stableFrames += 1;
                        else {
                            stableElement = element;
                            stableFrames = 0;
                        }

                        if (stableFrames >= 2) {
                            resolve(true);

                            return;
                        }
                    } else {
                        stableElement = null;
                        stableFrames = 0;
                    }

                    if (performance.now() >= deadline) {
                        reject(new Error('Timed out waiting for stable element: ' + selector));

                        return;
                    }

                    requestAnimationFrame(check);
                };

                check();
            })
            JS);
    }

    public static function waitForDetails(mixed $page): void
    {
        $page->script(<<<'JS'
            new Promise((resolve, reject) => {
                const deadline = performance.now() + 10000;
                let stableDetails = null;
                let stableFrames = 0;

                const check = () => {
                    const root = document.getElementById('newdebugbar');
                    const details = root?.querySelector('[data-ndb-loaded-section]');
                    const loading = root?.querySelector('[data-ndb-section-loading]');
                    const loadingFinished = loading === null || getComputedStyle(loading).display === 'none';

                    if (details !== null && details !== undefined && loadingFinished) {
                        if (details === stableDetails) stableFrames += 1;
                        else {
                            stableDetails = details;
                            stableFrames = 0;
                        }

                        if (stableFrames >= 2) {
                            resolve(true);

                            return;
                        }
                    } else {
                        stableDetails = details ?? null;
                        stableFrames = 0;
                    }

                    if (performance.now() >= deadline) {
                        reject(new Error('Timed out waiting for deferred profile details.'));

                        return;
                    }

                    requestAnimationFrame(check);
                };

                check();
            })
            JS);
    }

    public static function assertSectionSelected(mixed $page, string $section): void
    {
        $page
            ->assertCount('#newdebugbar [data-ndb-select-section][aria-current="page"]', 1)
            ->assertAttribute("#newdebugbar [data-ndb-select-section=\"{$section}\"]", 'aria-current', 'page')
            ->assertCount('#newdebugbar [data-ndb-section-panel]:not([hidden])', 1)
            ->assertVisible("#newdebugbar [data-ndb-section-panel=\"{$section}\"]");
    }

    public static function assertFavoriteOrder(mixed $page, string $order): void
    {
        $page->assertScript(<<<'JS'
            Array.from(document.querySelectorAll('#newdebugbar [data-ndb-section][data-ndb-favorite="true"]'))
                .map((section) => section.dataset.ndbSection)
                .join(',')
            JS, $order);
    }

    public static function selectSectionViaPalette(mixed $page, string $section): void
    {
        $page
            ->click('[data-ndb-inspector-action="palette"]')
            ->assertVisible('[role="dialog"][aria-label="Command palette"]')
            ->click('[data-ndb-command="collectors:show"]')
            ->click("[data-ndb-command=\"section:{$section}\"]");
    }
}
