<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\ResponseFactory;
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

    if (method_exists($response, 'assertStructuredContent')) {
        $response->assertStructuredContent(function (AssertableJson $json) use (&$content): void {
            $content = $json->toArray();
            $json->etc();
        });

        return $content;
    }

    $property = (new ReflectionClass($response))->getProperty('response');
    $payload = $property->getValue($response)->toArray();
    $content = $payload['result']['structuredContent']
        ?? json_decode($payload['result']['content'][0]['text'], true, flags: JSON_THROW_ON_ERROR);

    return $content;
}

beforeEach(function () {
    if (! class_exists(ResponseFactory::class)) {
        $this->markTestSkipped('These assertions cover Laravel MCP 0.2 and newer.');
    }
});

it('registers one local read only server with four schema backed tools', function () {
    $version = (new ReflectionClass(NewDebugBarServer::class))->getDefaultProperties()['version'];

    expect(Mcp::getLocalServer('newdebugbar'))->toBeCallable()
        ->and(Mcp::getWebServer('newdebugbar'))->toBeNull()
        ->and(Mcp::servers())->toHaveKey('newdebugbar')
        ->and($version)->toBe('1.0.0');

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
        ->headers->get('X-NewDebugBar-Profile');
    $this->get('/profiled-next', ['Accept' => 'text/html'])->assertOk();
    $profileCount = count(File::files(config('newdebugbar.storage.path')));

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
        ->not->toContain('query.repeated', 'query.n_plus_one')
        ->and(count(File::files(config('newdebugbar.storage.path'))))->toBe($profileCount);
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
        ->total->toBe(2)
        ->truncated->toBeTrue()
        ->and($failed['data']['profiles'])->toHaveCount(1)
        ->and($failed['data']['profiles'][0])
        ->path->toBe('/failed-html')
        ->status->toBe(422)
        ->warning->toBeTrue();
});

it('exposes every recorded context section through the bounded section tool', function () {
    $response = $this->get('/profiled-context', ['Accept' => 'text/html'])->assertOk();
    $profileId = $response->headers->get('X-NewDebugBar-Profile');

    foreach (['authorization', 'validation', 'lifecycle', 'messages'] as $section) {
        $content = captureStructuredContent(NewDebugBarServer::tool(GetDebugProfileSection::class, [
            'profile_id' => $profileId,
            'section' => $section,
        ])->assertOk());

        expect($content['status'])->toBe('ok')
            ->and($content['data']['section'])->toBe($section);
    }
});

it('keeps captured mail content out of MCP responses', function () {
    $response = $this->get('/profiled-messages', ['Accept' => 'text/html'])->assertOk();
    $profileId = $response->headers->get('X-NewDebugBar-Profile');

    $mail = NewDebugBarServer::tool(GetDebugProfileSection::class, [
        'profile_id' => $profileId,
        'section' => 'mail',
    ])->assertOk()
        ->assertDontSee([
            'private body',
            'private subject',
            'private-sender@example.test',
            'private-recipient@example.test',
            'private-copy@example.test',
        ]);
    $content = captureStructuredContent($mail);

    expect($content['data']['payload']['items'][0]['preview'])->toMatchArray([
        'available' => true,
        'html_available' => false,
        'text_available' => true,
        'eml_available' => true,
        'truncated' => false,
        'attachments_omitted' => 1,
        'addresses_omitted' => 0,
    ]);
});

it('masks full query bindings and log labels again at the MCP boundary', function () {
    $response = $this->get('/profiled-private-query', ['Accept' => 'text/html'])->assertOk();
    $profileId = $response->headers->get('X-NewDebugBar-Profile');

    expect(json_encode(app(ProfileStore::class)->get($profileId)['sections']['queries']))
        ->toContain('private-alpha', 'private-beta', 'private-gamma');

    $queries = captureStructuredContent(NewDebugBarServer::tool(InspectDebugQueries::class, [
        'profile_id' => $profileId,
        'filter' => 'repeated',
    ])->assertOk()
        ->assertDontSee(['private-alpha', 'private-beta', 'private-gamma']));
    $timeline = captureStructuredContent(NewDebugBarServer::tool(GetDebugProfileSection::class, [
        'profile_id' => $profileId,
        'section' => 'timeline',
    ])->assertOk()
        ->assertDontSee('private timeline log message'));

    expect($queries['data']['repeated_groups'][0]['executions'][0])
        ->bindings->toBe(['[string]'])
        ->binding_policy->toBe('safe')
        ->bindings_complete->toBeFalse()
        ->runnable_available->toBeFalse()
        ->not->toHaveKey('runnable_sql')
        ->and(collect($timeline['data']['payload']['items'])->firstWhere('section', 'logs')['label'])
        ->toBe('[log message hidden]');
});

it('masks captured view values at the MCP boundary', function () {
    $response = $this->get('/profiled-context', ['Accept' => 'text/html'])->assertOk();
    $profileId = $response->headers->get('X-NewDebugBar-Profile');

    expect(json_encode(app(ProfileStore::class)->get($profileId)['sections']['views']))
        ->toContain('view-data-value');

    $views = captureStructuredContent(NewDebugBarServer::tool(GetDebugProfileSection::class, [
        'profile_id' => $profileId,
        'section' => 'views',
    ])->assertOk()
        ->assertDontSee('view-data-value'));

    expect($views['data']['payload']['items'][0]['data'])
        ->label->toBe('[string]')
        ->private_value->toBe('[string]')
        ->rows->toBe('[array]');
});

it('paginates one section and hides private request values', function () {
    $response = $this->post('/profiled-input?name=query-secret', [
        'clinic' => ['name' => 'patient-secret'],
        'token' => 'token-secret',
    ], ['Accept' => 'text/html'])->assertOk();
    $profileId = $response->headers->get('X-NewDebugBar-Profile');

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
        'profile_id' => $response->headers->get('X-NewDebugBar-Profile'),
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
    $wrongVersion = '550e8400-e29b-11d4-a716-446655440000';
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
    NewDebugBarServer::tool(GetDebugFindings::class, ['profile_id' => $wrongVersion])
        ->assertHasErrors(['profile id']);
    NewDebugBarServer::tool(InspectDebugQueries::class, [
        'profile_id' => $missing,
        'filter' => 'unsafe',
    ])->assertHasErrors(['filter']);

    expect(app(McpProfilePresenter::class)->section($wrongVersion, 'overview', 0, 1))->toBe([
        'version' => 1,
        'status' => 'not_found',
        'data' => ['profile_id' => $wrongVersion],
    ]);
});

it('enforces byte depth and item limits without exposing corrupt profiles', function () {
    config()->set('newdebugbar.mcp.max_items', 2);
    config()->set('newdebugbar.mcp.max_bytes', 700);
    app()->forgetInstance(McpProfilePresenter::class);

    $response = $this->get('/profiled', ['Accept' => 'text/html'])->assertOk();
    $profileId = $response->headers->get('X-NewDebugBar-Profile');
    $this->get('/failed-html', ['Accept' => 'text/html'])->assertUnprocessable();
    $corruptId = (string) Str::uuid();
    File::put(config('newdebugbar.storage.path').'/'.$corruptId.'.json', '{broken');

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
        ->and($models['data']['payload'])->not->toHaveKeys(['groups', 'model_groups', 'repeated_groups', 'repeated_misses'])
        ->and(array_column($profiles['data']['profiles'], 'id'))->not->toContain($corruptId);
});

it('advances past an item that cannot fit within the MCP byte limit', function () {
    config()->set('newdebugbar.mcp.max_bytes', 700);
    app()->forgetInstance(McpProfilePresenter::class);
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
                    'name' => str_repeat('oversized-event-', 180),
                ]]],
            ],
        ],
    ]);

    $content = captureStructuredContent(NewDebugBarServer::tool(GetDebugProfileSection::class, [
        'profile_id' => $profileId,
        'section' => 'events',
        'limit' => 1,
    ])->assertOk());

    expect(strlen(json_encode($content)))->toBeLessThanOrEqual(700)
        ->and($content['data']['pagination'])
        ->returned->toBe(0)
        ->omitted_due_to_bytes->toBe(1)
        ->next_cursor->toBeNull();
});

it('falls back to bounded identity metadata when section metadata is oversized', function () {
    config()->set('newdebugbar.mcp.max_bytes', 700);
    app()->forgetInstance(McpProfilePresenter::class);
    $profileId = (string) Str::uuid();
    app(ProfileStore::class)->put([
        'schema_version' => 1,
        'id' => $profileId,
        'metrics' => ['duration_ms' => 1],
        'sections' => [
            'request' => [
                'label' => 'Request',
                'summary' => ['method' => 'GET', 'status' => 200],
                'payload' => [
                    'path' => '/oversized',
                    'middleware' => array_fill(0, 100, str_repeat('LongMiddlewareName', 120)),
                ],
            ],
        ],
    ]);

    $content = captureStructuredContent(NewDebugBarServer::tool(GetDebugProfileSection::class, [
        'profile_id' => $profileId,
        'section' => 'request',
    ])->assertOk());

    expect(strlen(json_encode($content)))->toBeLessThanOrEqual(700)
        ->and($content['data'])
        ->profile_id->toBe($profileId)
        ->section->toBe('request')
        ->content_omitted->toBeTrue()
        ->and($content['data']['pagination']['truncated'])->toBeTrue();
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
                ]]],
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
    File::put(config('newdebugbar.storage.path').'/'.$malformedId.'.json', json_encode([
        'id' => $malformedId,
        'sections' => ['queries' => ['payload' => ['items' => ['not-an-item']]]],
    ]));
    $missing = captureStructuredContent(NewDebugBarServer::tool(GetDebugFindings::class, [
        'profile_id' => $malformedId,
    ])->assertOk());

    expect($missing['status'])->toBe('not_found');
});
