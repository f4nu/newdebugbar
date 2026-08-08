<?php

use Laravel\Mcp\Facades\Mcp;
use NewDebugBar\Http\Middleware\ProfileRequest;

it('registers no profiler or asset route outside an allowed environment', function () {
    expect(app()->environment())->toBe('testing')
        ->and(config('newdebugbar.environments'))->toBe(['local'])
        ->and(app('router')->getMiddlewareGroups()['web'])->not->toContain(ProfileRequest::class)
        ->and(Mcp::getLocalServer('newdebugbar'))->toBeNull()
        ->and(app('router')->getRoutes()->getByName('newdebugbar.asset'))->toBeNull();

    $this->get('/production-page')
        ->assertOk()
        ->assertDontSee('newdebugbar');

    $this->get('/__newdebugbar/assets/newdebugbar.css')->assertNotFound();
});
