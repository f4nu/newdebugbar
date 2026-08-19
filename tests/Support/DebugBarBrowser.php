<?php

namespace NewDebugBar\Tests\Support;

final class DebugBarBrowser
{
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
