<?php

use Illuminate\Http\Request;
use NewDebugBar\Support\RequestEligibility;

it('profiles only eligible html page requests', function (Request $request, bool $allowed) {
    expect(app(RequestEligibility::class)->allows($request))->toBe($allowed);
})->with([
    'html page' => [fn () => Request::create('/dashboard', server: ['HTTP_ACCEPT' => 'text/html']), true],
    'json response' => [fn () => Request::create('/dashboard', server: ['HTTP_ACCEPT' => 'application/json']), false],
    'Livewire update' => [fn () => Request::create('/dashboard', server: ['HTTP_ACCEPT' => 'text/html', 'HTTP_X_LIVEWIRE' => 'true']), false],
    'package asset' => [fn () => Request::create('/__new-debug-bar/assets/new-debug-bar.js', server: ['HTTP_ACCEPT' => 'text/html']), false],
    'Livewire route' => [fn () => Request::create('/livewire/update', server: ['HTTP_ACCEPT' => 'text/html']), false],
]);

it('stops profiling when the package is disabled', function () {
    config()->set('new-debug-bar.enabled', false);

    $request = Request::create('/dashboard', server: ['HTTP_ACCEPT' => 'text/html']);

    expect(app(RequestEligibility::class)->allows($request))->toBeFalse();
});
