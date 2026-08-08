<?php

use Illuminate\Http\Request;
use NewDebugBar\ProfileManager;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Support\LivewireUpdateRecorder;
use Symfony\Component\HttpFoundation\Response;

it('records safe application Livewire facts and skips internal components', function () {
    $requestPayload = [
        'components' => [
            [
                'snapshot' => json_encode([
                    'data' => ['name' => 'private-value'],
                    'memo' => ['name' => 'profiled-counter'],
                ], JSON_THROW_ON_ERROR),
                'updates' => ['name' => 'another-private-value'],
                'calls' => [['method' => 'save', 'params' => ['private-parameter']]],
            ],
            [
                'snapshot' => json_encode(['memo' => ['name' => 'newdebugbar.toolbar']], JSON_THROW_ON_ERROR),
                'updates' => [],
                'calls' => [['method' => 'loadDetails', 'params' => []]],
            ],
        ],
    ];
    $content = json_encode($requestPayload, JSON_THROW_ON_ERROR);
    $request = Request::create('/livewire-test/update', 'POST', server: [
        'HTTP_X_LIVEWIRE' => 'true',
        'CONTENT_TYPE' => 'application/json',
        'CONTENT_LENGTH' => strlen($content),
    ], content: $content);
    app()->instance('request', $request);
    $manager = app(ProfileManager::class);
    $manager->begin($request);

    app(LivewireUpdateRecorder::class)->record(['components' => [
        [
            'snapshot' => json_encode(['memo' => ['errors' => ['name' => ['Required']]]], JSON_THROW_ON_ERROR),
            'effects' => [],
        ],
        ['snapshot' => json_encode(['memo' => ['errors' => []]], JSON_THROW_ON_ERROR)],
    ]]);

    $profile = $manager->finish($request, new Response('<html><body>Done</body></html>'));
    $items = $profile['sections']['livewire']['payload']['items'];

    expect($profile['sections']['livewire']['summary']['count'])->toBe(1)
        ->and($profile['sections']['livewire']['summary']['initial_render_count'])->toBe(0)
        ->and($profile['sections']['livewire']['summary']['update_count'])->toBe(1)
        ->and($profile['sections']['livewire']['summary']['component_count'])->toBe(1)
        ->and($items[0])
        ->kind->toBe('update')
        ->component->toBe('profiled-counter')
        ->actions->toBe(['save'])
        ->updated_properties->toBe(['name'])
        ->validation_failure_count->toBe(1)
        ->validation_fields->toBe(['name'])
        ->payload_size_bytes->toBe(strlen($content))
        ->and(json_encode($profile))->not->toContain('private-value', 'another-private-value', 'private-parameter', 'loadDetails');
});

it('resolves the active request manager for mounts after earlier requests', function () {
    $this->get('/profiled', ['Accept' => 'text/html'])->assertOk();
    $response = $this->get('/profiled-livewire', ['Accept' => 'text/html'])->assertOk();
    $profile = app(ProfileStore::class)->get($response->headers->get('X-NewDebugBar-Profile'));

    expect($profile['sections']['livewire']['summary'])
        ->count->toBe(1)
        ->initial_render_count->toBe(1)
        ->update_count->toBe(0);
});
