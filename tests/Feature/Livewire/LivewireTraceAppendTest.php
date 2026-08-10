<?php

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Livewire;
use NewDebugBar\Livewire\BrowserTracePayload;
use NewDebugBar\Livewire\DebugBar;
use NewDebugBar\Livewire\LivewireTraceAppender;
use NewDebugBar\Livewire\LivewireTraceToken;
use NewDebugBar\Storage\ProfileStore;

/** @return array<string, mixed> */
function storedLivewireTraceProfile(): array
{
    $profileId = (string) Str::uuid();
    $messageId = (string) Str::uuid();
    $actionId = (string) Str::uuid();
    $componentId = 'trace-component-1';
    $profile = [
        'schema_version' => 1,
        'id' => $profileId,
        'recorded_at' => now()->toIso8601String(),
        'profile_type' => 'http',
        'environment' => 'testing',
        'metrics' => ['duration_ms' => 10.0, 'peak_memory_mb' => 16.0],
        'sections' => [
            'livewire' => [
                'schema_version' => 1,
                'profile_revision' => 1,
                'label' => 'Livewire',
                'summary' => [
                    'title' => 'Ran saveReview',
                    'message_count' => 1,
                    'action_count' => 1,
                    'component_count' => 1,
                    'state_change_count' => 1,
                    'result' => 'rendered',
                    'trace_status' => 'missing',
                    'truncated' => false,
                ],
                'payload' => [
                    'exchange' => [
                        'id' => (string) Str::uuid(),
                        'request_id' => $profileId,
                        'browser_clock' => ['type' => 'separate_monotonic', 'status' => 'missing'],
                    ],
                    'messages' => [[
                        'id' => $messageId,
                        'component_id' => $componentId,
                    ]],
                    'actions' => [[
                        'id' => $actionId,
                        'message_id' => $messageId,
                        'component_id' => $componentId,
                        'kind' => 'action',
                        'name' => 'saveReview',
                    ]],
                    'components' => [['id' => $componentId, 'name' => 'diagnostics-fixture']],
                    'state_changes' => [[
                        'id' => (string) Str::uuid(),
                        'action_id' => $actionId,
                        'component_id' => $componentId,
                        'path' => 'search',
                        'browser' => ['status' => 'unknown'],
                    ]],
                    'events' => [],
                    'server_spans' => [],
                    'browser_trace' => [
                        'status' => 'missing',
                        'appended_at' => null,
                        'spans' => [],
                        'failures' => [],
                    ],
                    'findings' => [],
                    'completeness' => ['browser_trace' => 'missing'],
                ],
            ],
        ],
    ];

    app(ProfileStore::class)->put($profile);

    return $profile;
}

/** @return array<string, mixed> */
function validBrowserTracePayload(string $url): array
{
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    return [
        'schema_version' => 1,
        'idempotency_key' => $query['nonce'],
        'request' => [
            'outcome' => 'success',
            'status' => 200,
            'wait_ms' => 12.5,
            'parse_ms' => 1.25,
            'total_ms' => 18.75,
        ],
        'messages' => [[
            'component_id' => 'trace-component-1',
            'outcome' => 'success',
            'phases' => [
                ['name' => 'send', 'at_ms' => 0.0],
                ['name' => 'sync', 'at_ms' => 14.0],
                ['name' => 'render', 'at_ms' => 18.5],
            ],
            'state' => [[
                'path' => 'search',
                'matches_server' => true,
                'browser_type' => 'string',
            ]],
        ]],
        'actions' => [[
            'component_id' => 'trace-component-1',
            'name' => 'saveReview',
            'source' => [
                'status' => 'observed',
                'directive' => 'wire:click',
                'element' => 'button',
                'contract' => 'livewire_action_origin_v1',
            ],
        ]],
        'failures' => [],
    ];
}

it('appends one signed value-free browser trace and advances its revision', function () {
    $profile = storedLivewireTraceProfile();
    $url = app(LivewireTraceToken::class)->issue($profile['id'], 1);
    $payload = validBrowserTracePayload($url);
    $response = $this->postJson($url, $payload);

    $response
        ->assertAccepted()
        ->assertHeaderMissing('X-NewDebugBar-Profile')
        ->assertJson(['status' => 'accepted', 'revision' => 2]);
    $stored = app(ProfileStore::class)->get($profile['id']);
    $livewire = $stored['sections']['livewire'];
    $trace = $livewire['payload']['browser_trace'];

    expect($livewire)
        ->profile_revision->toBe(2)
        ->summary->trace_status->toBe('complete')
        ->payload->exchange->browser_clock->type->toBe('performance_monotonic_offset')
        ->payload->exchange->browser_clock->status->toBe('complete')
        ->payload->completeness->browser_trace->toBe('complete')
        ->payload->state_changes->{0}->browser->status->toBe('observed')
        ->payload->state_changes->{0}->browser->matches_server->toBeTrue()
        ->payload->state_changes->{0}->browser->type->toBe('string')
        ->and($trace)
        ->status->toBe('complete')
        ->raw_values_stored->toBeFalse()
        ->request->outcome->toBe('success')
        ->messages->{0}->message_id->toBe($livewire['payload']['messages'][0]['id'])
        ->actions->{0}->action_id->toBe($livewire['payload']['actions'][0]['id'])
        ->actions->{0}->source->directive->toBe('wire:click')
        ->actions->{0}->source->element->toBe('button')
        ->spans->toHaveCount(6)
        ->failures->toBe([])
        ->and($trace['idempotency_hash'])->toHaveLength(16)
        ->and(json_encode($trace))->not->toContain('private', 'snapshot', 'html');
});

it('rejects repeated and stale appends without changing the accepted trace', function () {
    $profile = storedLivewireTraceProfile();
    $url = app(LivewireTraceToken::class)->issue($profile['id'], 1);
    $payload = validBrowserTracePayload($url);

    $this->postJson($url, $payload)->assertAccepted();
    $this->postJson($url, $payload)->assertStatus(409)->assertJson(['status' => 'repeated']);

    $staleUrl = app(LivewireTraceToken::class)->issue($profile['id'], 1);
    $this->postJson($staleUrl, validBrowserTracePayload($staleUrl))
        ->assertStatus(409)
        ->assertJson(['status' => 'conflict']);

    $stored = app(ProfileStore::class)->get($profile['id']);

    expect($stored['sections']['livewire']['profile_revision'])->toBe(2)
        ->and($stored['sections']['livewire']['payload']['browser_trace']['request']['wait_ms'])->toBe(12.5);
});

it('rejects malformed oversized expired wrong-profile and missing-profile appends', function () {
    $profile = storedLivewireTraceProfile();
    $url = app(LivewireTraceToken::class)->issue($profile['id'], 1);
    $malformed = validBrowserTracePayload($url);
    $malformed['raw_snapshot'] = 'not allowed';

    $this->postJson($url, $malformed)->assertStatus(422)->assertJson(['status' => 'malformed']);

    $this->call(
        'POST',
        $url,
        server: ['CONTENT_TYPE' => 'application/json'],
        content: str_repeat('x', BrowserTracePayload::MAX_BYTES + 1),
    )->assertStatus(413)->assertJson(['status' => 'too_large']);

    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
    $expired = URL::temporarySignedRoute('newdebugbar.livewire-trace', now()->subSecond(), [
        'profile' => $profile['id'],
        'revision' => 1,
        'nonce' => (string) Str::uuid(),
    ]);
    $this->postJson($expired, validBrowserTracePayload($expired))->assertForbidden();

    $wrongProfile = str_replace($profile['id'], (string) Str::uuid(), $url);
    $this->postJson($wrongProfile, validBrowserTracePayload($url))->assertForbidden();

    $missingId = (string) Str::uuid();
    $missing = app(LivewireTraceToken::class)->issue($missingId, 1);
    $this->postJson($missing, validBrowserTracePayload($missing))
        ->assertNotFound()
        ->assertJson(['status' => 'not_found']);
});

it('uses signed and web middleware for the append boundary', function () {
    $route = app('router')->getRoutes()->getByName('newdebugbar.livewire-trace');
    $middleware = app('router')->gatherRouteMiddleware($route);

    expect($middleware)->toContain('Illuminate\\Routing\\Middleware\\ValidateSignature')
        ->and(collect($middleware)->contains(
            fn (string $name): bool => str_ends_with($name, '\\VerifyCsrfToken')
                || str_ends_with($name, '\\PreventRequestForgery'),
        ))->toBeTrue();
});

it('refreshes an open toolbar from the revised stored profile', function () {
    $profile = storedLivewireTraceProfile();
    $component = Livewire::test(DebugBar::class, ['profileId' => $profile['id']])
        ->assertSet('detailsLoaded', false);
    $url = app(LivewireTraceToken::class)->issue($profile['id'], 1);

    $this->postJson($url, validBrowserTracePayload($url))->assertAccepted();

    $component
        ->call('refreshProfileTrace', $profile['id'])
        ->assertDispatched('newdebugbar-content-updated')
        ->assertSet('detailsLoaded', false);

    expect(app(ProfileStore::class)->get($profile['id']))
        ->sections->livewire->profile_revision->toBe(2)
        ->sections->livewire->payload->browser_trace->status->toBe('complete');
});

it('serializes concurrent appends so only one revision wins', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('The pcntl extension is required for the append race test.');
    }

    $profile = storedLivewireTraceProfile();
    $resultFiles = [
        tempnam(sys_get_temp_dir(), 'newdebugbar-trace-a-'),
        tempnam(sys_get_temp_dir(), 'newdebugbar-trace-b-'),
    ];
    $children = [];

    foreach ($resultFiles as $resultFile) {
        $nonce = (string) Str::uuid();
        $payload = validBrowserTracePayload(
            'https://viteclinic.test/trace?nonce='.$nonce,
        );
        $pid = pcntl_fork();

        if ($pid === 0) {
            $result = app(LivewireTraceAppender::class)->append($profile['id'], 1, $nonce, $payload);
            file_put_contents($resultFile, json_encode($result, JSON_THROW_ON_ERROR));
            exit(0);
        }

        expect($pid)->toBeGreaterThan(0);
        $children[] = $pid;
    }

    foreach ($children as $pid) {
        pcntl_waitpid($pid, $status);
        expect(pcntl_wexitstatus($status))->toBe(0);
    }

    $statuses = collect($resultFiles)
        ->map(fn (string $file): string => json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR)['status'])
        ->sort()
        ->values()
        ->all();

    foreach ($resultFiles as $resultFile) {
        unlink($resultFile);
    }

    expect($statuses)->toBe(['accepted', 'conflict'])
        ->and(app(ProfileStore::class)->get($profile['id'])['sections']['livewire']['profile_revision'])->toBe(2);
});
