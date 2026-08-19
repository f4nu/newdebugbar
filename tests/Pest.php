<?php

use NewDebugBar\Tests\ProductionTestCase;
use NewDebugBar\Tests\TestCase;

uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Browser');
uses(ProductionTestCase::class)->in('Production');

function assertDebugSectionSelected($page, string $section): void
{
    $page
        ->assertCount('#newdebugbar [data-ndb-select-section][aria-current="page"]', 1)
        ->assertAttribute("#newdebugbar [data-ndb-select-section=\"{$section}\"]", 'aria-current', 'page')
        ->assertCount('#newdebugbar [data-ndb-section-panel]:not([hidden])', 1)
        ->assertVisible("#newdebugbar [data-ndb-section-panel=\"{$section}\"]");
}

function assertFavoriteOrder($page, string $order): void
{
    $page->assertScript(<<<'JS'
        Array.from(document.querySelectorAll('#newdebugbar [data-ndb-section][data-ndb-favorite="true"]'))
            .map((section) => section.dataset.ndbSection)
            .join(',')
        JS, $order);
}

function selectDebugSectionViaPalette($page, string $section): void
{
    $page
        ->click('[data-ndb-inspector-action="palette"]')
        ->assertVisible('[role="dialog"][aria-label="Command palette"]')
        ->click('[data-ndb-command="collectors:show"]')
        ->click("[data-ndb-command=\"section:{$section}\"]");
}
