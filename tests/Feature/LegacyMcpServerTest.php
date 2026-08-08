<?php

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Facades\Mcp;
use NewDebugBar\Mcp\Legacy\NewDebugBarServer;
use NewDebugBar\Mcp\Legacy\Tools\GetDebugFindings;
use NewDebugBar\Mcp\Legacy\Tools\GetDebugProfileSection;
use NewDebugBar\Mcp\Legacy\Tools\InspectDebugQueries;
use NewDebugBar\Mcp\Legacy\Tools\ListDebugProfiles;

beforeEach(function () {
    if (class_exists(ResponseFactory::class)) {
        $this->markTestSkipped('These assertions cover Laravel MCP 0.1.');
    }
});

it('rejects profile identifiers that storage cannot read', function () {
    $wrongVersion = '550e8400-e29b-11d4-a716-446655440000';

    foreach ([
        GetDebugFindings::class => [],
        GetDebugProfileSection::class => ['section' => 'overview'],
        InspectDebugQueries::class => [],
    ] as $tool => $arguments) {
        expect(fn () => app($tool)->handle([
            'profile_id' => $wrongVersion,
            ...$arguments,
        ]))->toThrow(ValidationException::class);
    }
});

it('keeps the local MCP server available on early Laravel 10', function () {
    $this->get('/profiled', ['Accept' => 'text/html'])->assertOk();

    $server = new NewDebugBarServer;
    $tool = app(ListDebugProfiles::class);
    $definition = $tool->toArray();
    $result = $tool->handle(['limit' => 1])->toArray();
    $content = json_decode($result['content'][0]['text'], true, flags: JSON_THROW_ON_ERROR);

    expect(Mcp::getLocalServer('newdebugbar'))->toBeCallable()
        ->and(Mcp::getWebServer('newdebugbar'))->toBeNull()
        ->and($server->serverName)->toBe('New Debug Bar')
        ->and($server->serverVersion)->toBe('1.0.0')
        ->and($server->tools)->toHaveCount(4)
        ->and($definition['name'])->toBe('list-debug-profiles')
        ->and($definition['inputSchema']['type'])->toBe('object')
        ->and($definition['annotations'])->toBe([
            'readOnlyHint' => true,
            'openWorldHint' => false,
        ])
        ->and($content['status'])->toBe('ok')
        ->and($content['data']['profiles'])->toHaveCount(1);
});
