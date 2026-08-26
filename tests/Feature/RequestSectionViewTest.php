<?php

use Illuminate\Support\Str;
use Livewire\Livewire;
use NewDebugBar\Livewire\DebugBar;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Tests\Fixtures\Http\ProfiledRequestController;

it('keeps useful HTTP route input session header response and source evidence', function () {
    $response = $this->get('/profiled-request/kyoto?month=november&filters%5Bweather%5D=clear', [
        'Accept' => 'text/html',
        'X-Request-Trace' => 'autumn-planning',
    ])->assertOk();
    $profileId = $response->headers->get('X-NewDebugBar-Profile');

    Livewire::test(DebugBar::class, ['profileId' => $profileId])
        ->call('loadSection', 'request')
        ->assertSeeHtml('data-ndb-request-workspace')
        ->assertSeeHtml('data-ndb-request-panel="route"')
        ->assertSeeHtml('data-ndb-request-panel="input"')
        ->assertSeeHtml('data-ndb-request-panel="headers"')
        ->assertSeeHtml('data-ndb-request-panel="session"')
        ->assertSeeHtml('data-ndb-request-panel="response"')
        ->assertSeeHtml('data-ndb-request-source')
        ->assertSee('profiled.request.show')
        ->assertSee(ProfiledRequestController::class.'@show')
        ->assertSee('kyoto')
        ->assertSee('november')
        ->assertSee('autumn-planning')
        ->assertSee('request-workspace')
        ->assertSee('workspace')
        ->assertDontSeeHtml('data-ndb-request-step')
        ->assertDontSee('Laravel received the request.')
        ->assertDontSee('Request details');
});

it('renders runtime identity and retained context without HTTP-only chrome', function () {
    $profileId = (string) Str::uuid();
    app(ProfileStore::class)->put([
        'id' => $profileId,
        'profile_type' => 'queue',
        'completion_state' => 'complete',
        'metrics' => ['duration_ms' => 18.42, 'peak_memory_mb' => 24.5],
        'sections' => [
            'request' => [
                'label' => 'Runtime',
                'summary' => ['method' => 'CLI', 'status' => 0, 'exit_code' => 0],
                'payload' => [
                    'method' => 'CLI',
                    'path' => 'queue:RefreshTripWorkspace',
                    'runtime_type' => 'queue',
                    'name' => 'RefreshTripWorkspace',
                    'status' => 0,
                    'exit_code' => 0,
                    'context' => [
                        'connection' => 'redis',
                        'queue' => 'trips',
                        'job_id' => 'job-42',
                        'attempt' => 2,
                        'communication_class' => 'App\\Jobs\\RefreshTripWorkspace',
                    ],
                ],
            ],
        ],
    ]);

    Livewire::test(DebugBar::class, ['profileId' => $profileId])
        ->call('loadSection', 'request')
        ->assertSeeHtml('data-ndb-request-tab="runtime"')
        ->assertSeeHtml('data-ndb-request-tab="context"')
        ->assertSeeHtml('data-ndb-request-panel="runtime"')
        ->assertSeeHtml('data-ndb-request-panel="context"')
        ->assertSee('RefreshTripWorkspace')
        ->assertSee('queue:RefreshTripWorkspace')
        ->assertSee('job-42')
        ->assertSee('communication_class')
        ->assertDontSeeHtml('data-ndb-request-tab="route"');
});
