<?php

use Illuminate\Http\Request;
use NewDebugBar\Support\RequestEligibility;

function livewireEligibilityRequest(array $componentNames): Request
{
    $payload = [
        'components' => array_map(fn (string $name): array => [
            'snapshot' => json_encode(['memo' => ['name' => $name]], JSON_THROW_ON_ERROR),
            'updates' => [],
            'calls' => [],
        ], $componentNames),
    ];

    return Request::create('/livewire/update', 'POST', server: [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_LIVEWIRE' => 'true',
        'CONTENT_TYPE' => 'application/json',
    ], content: json_encode($payload, JSON_THROW_ON_ERROR));
}

it('profiles application requests and excludes package owned traffic', function (Request $request, bool $allowed) {
    expect(app(RequestEligibility::class)->allows($request))->toBe($allowed);
})->with([
    'html page' => [fn () => Request::create('/dashboard', server: ['HTTP_ACCEPT' => 'text/html']), true],
    'json response' => [fn () => Request::create('/dashboard', server: ['HTTP_ACCEPT' => 'application/json']), true],
    'application Livewire update' => [fn () => livewireEligibilityRequest(['clinic-dashboard']), true],
    'mixed Livewire update' => [fn () => livewireEligibilityRequest(['clinic-dashboard', 'newdebugbar.toolbar']), true],
    'internal Livewire update' => [fn () => livewireEligibilityRequest(['newdebugbar.toolbar']), false],
    'malformed Livewire update' => [fn () => livewireEligibilityRequest([]), false],
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
