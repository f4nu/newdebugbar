<?php

namespace NewDebugBar\Tests\Fixtures\Http;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ProfiledRequestController
{
    public function show(Request $request, string $trip): Response
    {
        $request->session()->put('trip', $trip);
        $request->session()->put('workspace', ['season' => 'autumn', 'ready' => true]);
        $request->session()->flash('notice', 'Itinerary refreshed');

        return response(
            '<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1"><title>Request workspace</title></head><body><main><h1 data-testid="host-page">Request workspace</h1></main></body></html>',
            200,
            ['X-Request-Fixture' => 'request-workspace'],
        );
    }
}
