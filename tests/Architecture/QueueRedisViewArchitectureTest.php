<?php

it('composes Queue from the shared inspector workspace grammar', function () {
    $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/sections/queue.blade.php');
    $attempts = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/sections/queue/attempts.blade.php');

    expect($view)
        ->toContain('<x-newdebugbar::inspector-workspace frame="top"')
        ->toContain('<x-newdebugbar::inspector-list-panel')
        ->toContain('<x-newdebugbar::inspector-list-controls')
        ->toContain('<x-newdebugbar::search-field')
        ->toContain('<x-newdebugbar::select-field')
        ->toContain('<x-newdebugbar::inspector-detail-pane')
        ->toContain('<x-newdebugbar::inspector-detail-tabs')
        ->toContain('variant="segmented"')
        ->toContain('<template x-if="selectedQueueActivity">')
        ->toContain('<template x-if="queueDetailTab === \'overview\'">')
        ->toContain("@include('newdebugbar::livewire.sections.queue.attempts')")
        ->toContain('data-ndb-queue-payload')
        ->toContain('data-ndb-queue-profile-link')
        ->not->toContain('<input')
        ->not->toContain('<select')
        ->not->toContain('queueSort')
        ->not->toContain('Oldest')
        ->not->toContain('Slowest');

    expect($attempts)
        ->toContain('<template x-if="queueDetailTab === \'attempts\'">')
        ->toContain('data-ndb-queue-attempt')
        ->toContain('<x-newdebugbar::inspector-action');
});

it('composes Redis from the shared inspector workspace grammar', function () {
    $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/sections/redis.blade.php');

    expect($view)
        ->toContain('<x-newdebugbar::inspector-workspace frame="top"')
        ->toContain('<x-newdebugbar::inspector-list-panel')
        ->toContain('<x-newdebugbar::inspector-list-controls')
        ->toContain('<x-newdebugbar::search-field')
        ->toContain('<x-newdebugbar::select-field')
        ->toContain('<x-newdebugbar::inspector-detail-pane')
        ->toContain('<x-newdebugbar::inspector-detail-tabs')
        ->toContain('variant="segmented"')
        ->toContain('<template x-if="selectedRedisCommand">')
        ->toContain('<template x-if="redisDetailTab === \'overview\'">')
        ->toContain('<template x-if="redisDetailTab === \'keys\'">')
        ->toContain('data-ndb-redis-payload')
        ->toContain('No key metadata was retained for this command.')
        ->not->toContain('<input')
        ->not->toContain('<select')
        ->not->toContain('redisSort')
        ->not->toContain('Succeeded')
        ->not->toContain('Oldest')
        ->not->toContain('Slowest');
});
