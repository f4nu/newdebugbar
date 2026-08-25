<?php

use Laravel\Mcp\Facades\Mcp;
use NewDebugBar\Http\Middleware\ProfileRequest;

it('registers no profiler or asset route outside an allowed environment', function () {
    $mcp = class_exists(Mcp::class)
        ? Mcp::class
        : Laravel\Mcp\Server\Facades\Mcp::class;

    expect(app()->environment())->toBe('testing')
        ->and(config('newdebugbar.environments'))->toBe(['local'])
        ->and(app('router')->getMiddlewareGroups()['web'])->not->toContain(ProfileRequest::class)
        ->and($mcp::getLocalServer('newdebugbar'))->toBeNull()
        ->and(app('router')->getRoutes()->getByName('newdebugbar.asset'))->toBeNull()
        ->and(app('router')->getRoutes()->getByName('newdebugbar.studio'))->toBeNull()
        ->and(app('router')->getRoutes()->getByName('newdebugbar.studio.component'))->toBeNull()
        ->and(app('router')->getRoutes()->getByName('newdebugbar.studio.preview'))->toBeNull();

    $this->get('/production-page')
        ->assertOk()
        ->assertDontSee('newdebugbar');

    $this->get('/__newdebugbar/assets/newdebugbar.css')->assertNotFound();
    $this->get('/__newdebugbar/studio')->assertNotFound();
    $this->get('/__newdebugbar/studio/search-field')->assertNotFound();
    $this->get('/__newdebugbar/studio/search-field/preview')->assertNotFound();
});
