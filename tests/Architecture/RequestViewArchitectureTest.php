<?php

it('builds Request from the shared stream workspace with active-only evidence', function () {
    $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/sections/request.blade.php');

    expect($view)
        ->toContain('<x-newdebugbar::inspector-workspace')
        ->toContain('mode="stream"')
        ->toContain('<x-newdebugbar::inspector-detail-tabs')
        ->toContain('<x-newdebugbar::filter-tab')
        ->toContain('<x-newdebugbar::inspector-facts')
        ->toContain('<x-newdebugbar::inspector-definition-list')
        ->toContain('<x-newdebugbar::inspector-evidence')
        ->toContain('x-ref="requestDetailBody"')
        ->toContain('data-ndb-request-source')
        ->and(substr_count($view, '<template x-if="requestDetailTab ==='))
        ->toBe(7)
        ->and($view)
        ->not->toContain('<details')
        ->not->toContain('data-ndb-request-step')
        ->not->toContain('data-ndb-request-line')
        ->not->toContain('data-ndb-request-dot')
        ->not->toContain('x-show.important="requestDetailTab')
        ->not->toContain('requestDetailGroups')
        ->not->toContain('requestSort')
        ->not->toContain('popover');
});
