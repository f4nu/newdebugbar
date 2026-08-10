<?php

use Illuminate\Http\Request;
use NewDebugBar\Support\RequestEligibility;

it('profiles application requests and excludes package owned traffic', function (Request $request, bool $allowed) {
    expect(app(RequestEligibility::class)->allows($request))->toBe($allowed);
})->with([
    'html page' => [fn () => Request::create('/dashboard', server: ['HTTP_ACCEPT' => 'text/html']), true],
    'json response' => [fn () => Request::create('/dashboard', server: ['HTTP_ACCEPT' => 'application/json']), true],
    'Livewire update' => [fn () => Request::create('/livewire/update', 'POST', server: ['HTTP_X_LIVEWIRE' => 'true']), false],
    'package asset' => [fn () => Request::create('/__newdebugbar/assets/newdebugbar.js', server: ['HTTP_ACCEPT' => 'text/html']), false],
    'package Livewire runtime asset' => [fn () => Request::create('/livewire-95508dcc/livewire.js', server: ['HTTP_ACCEPT' => 'text/javascript']), false],
    'ordinary route named like Livewire' => [fn () => Request::create('/livewire/update', server: ['HTTP_ACCEPT' => 'text/html']), true],
    'ordinary similarly named script' => [fn () => Request::create('/livewire-example/app.js', server: ['HTTP_ACCEPT' => 'text/javascript']), true],
]);

it('stops profiling when the package is disabled', function () {
    config()->set('newdebugbar.enabled', false);

    $request = Request::create('/dashboard', server: ['HTTP_ACCEPT' => 'text/html']);

    expect(app(RequestEligibility::class)->allows($request))->toBeFalse();
});
