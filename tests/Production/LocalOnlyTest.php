<?php

use NewDebugBar\Http\Middleware\ProfileRequest;

it('registers no profiler or asset route outside an allowed environment', function () {
    expect(app()->environment())->toBe('testing')
        ->and(config('new-debug-bar.environments'))->toBe(['local'])
        ->and(app('router')->getMiddlewareGroups()['web'])->not->toContain(ProfileRequest::class)
        ->and(app('router')->getRoutes()->getByName('new-debug-bar.asset'))->toBeNull();

    $this->get('/production-page')
        ->assertOk()
        ->assertDontSee('new-debug-bar');

    $this->get('/__new-debug-bar/assets/new-debug-bar.css')->assertNotFound();
});
