<?php

use Laravel\Mcp\Client;
use Laravel\Mcp\Client\Transport\StdioTransport;
use Laravel\Mcp\Schema\Implementation;

test('the real stdio server advertises complete profile access', function () {
    $root = dirname(__DIR__, 2);
    $client = new Client(
        new StdioTransport('/usr/bin/env', [
            'APP_ENV=local',
            PHP_BINARY,
            $root.'/vendor/bin/testbench',
            'mcp:start',
            'newdebugbar',
        ]),
        new Implementation('newdebugbar-tests', '1.0.0'),
    );

    try {
        $client->withTimeout(10)->connect();
        $initialization = $client->initializeResult();
        $tools = $client->tools();
        $missing = $client->callTool('get-debug-profile-data', [
            'profile_id' => '00000000-0000-4000-8000-000000000000',
            'path' => '/sections',
        ]);

        expect($initialization?->serverInfo->name)->toBe('New Debug Bar')
            ->and($initialization?->serverInfo->version)->toBe('1.1.0')
            ->and($initialization?->instructions)->toContain('get-debug-profile-data', '/sections')
            ->and($tools->keys()->all())->toBe([
                'list-debug-profiles',
                'get-debug-profile-section',
                'get-debug-profile-data',
                'inspect-debug-queries',
                'get-debug-findings',
            ])
            ->and($tools['get-debug-profile-data']->inputSchema['properties']['path']['default'])->toBe('/sections')
            ->and($tools['get-debug-profile-data']->outputSchema['properties']['data']['properties'])
            ->toHaveKeys(['path', 'type', 'entries', 'value', 'chunks', 'pagination'])
            ->and($missing->isError)->toBeFalse()
            ->and($missing->structuredContent)->toMatchArray([
                'status' => 'not_found',
                'data' => [
                    'profile_id' => '00000000-0000-4000-8000-000000000000',
                    'path' => '/sections',
                ],
            ]);
    } finally {
        $client->disconnect();
    }
});
