<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\Attributes\Version;
use NewDebugBar\Mcp\NewDebugBarServer;
use NewDebugBar\Mcp\Tools\GetDebugFindings;
use NewDebugBar\Mcp\Tools\GetDebugProfileSection;
use NewDebugBar\Mcp\Tools\InspectDebugQueries;
use NewDebugBar\Mcp\Tools\ListDebugProfiles;
use NewDebugBar\Presentation\McpProfilePresenter;
use NewDebugBar\Storage\ProfileStore;

function captureStructuredContent($response): array
{
    $content = [];

    $response->assertStructuredContent(function (AssertableJson $json) use (&$content): void {
        $content = $json->toArray();
        $json->etc();
    });

    return $content;
}

it('registers one local read only server with four schema backed tools', function () {
    $version = (new ReflectionClass(NewDebugBarServer::class))->getAttributes(Version::class)[0]->newInstance();

    expect(Mcp::getLocalServer('new-debug-bar'))->toBeCallable()
        ->and(Mcp::getWebServer('new-debug-bar'))->toBeNull()
        ->and(Mcp::servers())->toHaveKey('new-debug-bar')
        ->and($version->value)->toBe('0.1.0');

    foreach ([
        ListDebugProfiles::class => 'list-debug-profiles',
        GetDebugProfileSection::class => 'get-debug-profile-section',
        InspectDebugQueries::class => 'inspect-debug-queries',
        GetDebugFindings::class => 'get-debug-findings',
    ] as $toolClass => $name) {
        $tool = app($toolClass)->toArray();

        expect($tool['name'])->toBe($name)
            ->and($tool['inputSchema']['type'])->toBe('object')
            ->and($tool['outputSchema']['required'])->toContain('version', 'status', 'data')
            ->and($tool['annotations'])->toBe([
                'readOnlyHint' => true,
                'openWorldHint' => false,
            ]);
    }
});

it('correlates the exact response profile while unrelated profiles exist', function () {
    $first = $this->get('/profiled?patient=private-marker', ['Accept' => 'text/html'])
        ->assertOk()
        ->headers->get('X-New-Debug-Bar-Profile');
    $this->get('/profiled-next', ['Accept' => 'text/html'])->assertOk();
    $profileCount = count(File::files(config('new-debug-bar.storage.path')));

    $queries = captureStructuredContent(NewDebugBarServer::tool(InspectDebugQueries::class, [
        'profile_id' => $first,
        'filter' => 'repeated',
        'limit' => 1,
    ])->assertOk());
    $findings = captureStructuredContent(NewDebugBarServer::tool(GetDebugFindings::class, [
        'profile_id' => $first,
    ])->assertOk());

    expect($queries)
        ->version->toBe(1)
        ->status->toBe('ok')
        ->data->profile_id->toBe($first)
        ->data->summary->repeated_pattern_count->toBe(1)
        ->data->repeated_groups->toHaveCount(1)
        ->data->repeated_groups->{0}->count->toBe(3)
        ->and(array_column($findings['data']['findings'], 'rule_id'))
        ->toContain('query.repeated', 'query.n_plus_one')
        ->and(count(File::files(config('new-debug-bar.storage.path'))))->toBe($profileCount);
});

it('lists and filters bounded profile summaries', function () {
    $this->get('/profiled', ['Accept' => 'text/html'])->assertOk();
    $this->get('/failed-html', ['Accept' => 'text/html'])->assertUnprocessable();

    $all = captureStructuredContent(NewDebugBarServer::tool(ListDebugProfiles::class, ['limit' => 1])->assertOk());
    $failed = captureStructuredContent(NewDebugBarServer::tool(ListDebugProfiles::class, [
        'method' => 'get',
        'path' => 'failed',
        'status' => 422,
        'warning' => true,
    ])->assertOk());

    expect($all['data']['profiles'])->toHaveCount(1)
        ->and($all['data'])
        ->count->toBe(1)
        ->total->toBe(1)
        ->truncated->toBeFalse()
        ->and($failed['data']['profiles'])->toHaveCount(1)
        ->and($failed['data']['profiles'][0])
        ->path->toBe('/failed-html')
        ->status->toBe(422)
        ->warning->toBeTrue();
});

it('paginates one section and hides private request values', function () {
    $response = $this->post('/profiled-input?name=query-secret', [
        'clinic' => ['name' => 'patient-secret'],
        'token' => 'token-secret',
    ], ['Accept' => 'text/html'])->assertOk();
    $profileId = $response->headers->get('X-New-Debug-Bar-Profile');

    $request = NewDebugBarServer::tool(GetDebugProfileSection::class, [
        'profile_id' => $profileId,
        'section' => 'request',
        'cursor' => 0,
        'limit' => 1,
    ])->assertOk()
        ->assertDontSee(['query-secret', 'patient-secret', 'token-secret', 'authorization']);
    $content = captureStructuredContent($request);

    expect($content['data']['payload'])
        ->not->toHaveKeys(['input', 'query', 'headers', 'response_headers', 'url'])
        ->and($content['data']['payload']['input_keys'])->toBe(['clinic', 'token', 'name'])
        ->and($content['data']['payload']['query_keys'])->toBe(['name'])
        ->and($content['data']['payload']['request_size_bytes'])->toBeGreaterThan(0)
        ->and($content['data']['payload']['response_size_bytes'])->toBeGreaterThan(0)
        ->and($content['data']['payload']['session_present'])->toBeFalse()
        ->and($content['data']['payload']['authenticated'])->toBeFalse()
        ->and($content['data']['pagination'])->toMatchArray([
            'cursor' => 0,
            'returned' => 0,
            'total' => 0,
            'truncated' => false,
            'next_cursor' => null,
        ]);
});

it('exposes relative exception evidence without messages or source code', function () {
    $response = $this->get('/profiled-exception', ['Accept' => 'text/html'])
        ->assertInternalServerError();

    $content = captureStructuredContent(NewDebugBarServer::tool(GetDebugProfileSection::class, [
        'profile_id' => $response->headers->get('X-New-Debug-Bar-Profile'),
        'section' => 'exceptions',
    ])->assertOk());
    $item = $content['data']['payload']['items'][0];

    expect($item)
        ->message->toBe('[message hidden]')
        ->file->toBe('tests/TestCase.php')
        ->not->toHaveKeys(['source', 'frames'])
        ->and($item['application_frames'])->not->toBeEmpty()
        ->and(json_encode($content))->not->toContain(base_path().'/');
});

it('returns stable not found results and validation errors', function () {
    $missing = (string) Str::uuid();
    $content = captureStructuredContent(NewDebugBarServer::tool(GetDebugFindings::class, [
        'profile_id' => $missing,
    ])->assertOk());

    expect($content)->toBe([
        'version' => 1,
        'status' => 'not_found',
        'data' => ['profile_id' => $missing],
    ]);

    NewDebugBarServer::tool(GetDebugFindings::class, ['profile_id' => '../bad'])
        ->assertHasErrors(['profile id']);
    NewDebugBarServer::tool(InspectDebugQueries::class, [
        'profile_id' => $missing,
        'filter' => 'unsafe',
    ])->assertHasErrors(['filter']);
});

it('enforces byte depth and item limits without exposing corrupt profiles', function () {
    config()->set('new-debug-bar.mcp.max_items', 2);
    config()->set('new-debug-bar.mcp.max_bytes', 700);
    app()->forgetInstance(McpProfilePresenter::class);

    $response = $this->get('/profiled', ['Accept' => 'text/html'])->assertOk();
    $profileId = $response->headers->get('X-New-Debug-Bar-Profile');
    $this->get('/failed-html', ['Accept' => 'text/html'])->assertUnprocessable();
    $corruptId = (string) Str::uuid();
    File::put(config('new-debug-bar.storage.path').'/'.$corruptId.'.json', '{broken');

    $events = captureStructuredContent(NewDebugBarServer::tool(GetDebugProfileSection::class, [
        'profile_id' => $profileId,
        'section' => 'events',
        'limit' => 2,
    ])->assertOk());
    $profiles = captureStructuredContent(NewDebugBarServer::tool(ListDebugProfiles::class)->assertOk());
    $models = captureStructuredContent(NewDebugBarServer::tool(GetDebugProfileSection::class, [
        'profile_id' => $profileId,
        'section' => 'models',
        'limit' => 2,
    ])->assertOk());

    expect(strlen(json_encode($events)))->toBeLessThanOrEqual(700)
        ->and(strlen(json_encode($profiles)))->toBeLessThanOrEqual(700)
        ->and(strlen(json_encode($models)))->toBeLessThanOrEqual(700)
        ->and($events['data']['pagination']['returned'])->toBeLessThanOrEqual(2)
        ->and($events['data']['pagination']['truncated'])->toBeTrue()
        ->and($profiles['data']['truncated'])->toBeTrue()
        ->and($models['data']['payload'])->not->toHaveKeys(['groups', 'repeated_groups', 'repeated_misses'])
        ->and(array_column($profiles['data']['profiles'], 'id'))->not->toContain($corruptId);
});

it('bounds deeply nested MCP values and treats malformed profiles as missing', function () {
    $profileId = (string) Str::uuid();
    app(ProfileStore::class)->put([
        'schema_version' => 1,
        'id' => $profileId,
        'metrics' => ['duration_ms' => 1],
        'sections' => [
            'events' => [
                'label' => 'Events',
                'summary' => ['count' => 1],
                'payload' => ['items' => [[
                    'name' => 'nested.event',
                    'nested' => ['one' => ['two' => ['three' => ['four' => ['five' => 'private-deep-value']]]]],
                ]], 'dropped' => 0],
            ],
        ],
    ]);

    NewDebugBarServer::tool(GetDebugProfileSection::class, [
        'profile_id' => $profileId,
        'section' => 'events',
    ])->assertOk()
        ->assertSee('[maximum depth reached]')
        ->assertDontSee('private-deep-value');

    $malformedId = (string) Str::uuid();
    File::put(config('new-debug-bar.storage.path').'/'.$malformedId.'.json', json_encode([
        'id' => $malformedId,
        'sections' => ['queries' => ['payload' => ['items' => ['not-an-item']]]],
    ]));
    $missing = captureStructuredContent(NewDebugBarServer::tool(GetDebugFindings::class, [
        'profile_id' => $malformedId,
    ])->assertOk());

    expect($missing['status'])->toBe('not_found');
});
