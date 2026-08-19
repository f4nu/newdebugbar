<?php

use Illuminate\Support\Facades\File;
use Livewire\Drawer\Utils;
use Livewire\Livewire;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Tests\Fixtures\HostCounter;

beforeEach(function () {
    Livewire::component('host-counter', HostCounter::class);
});

/** @return array<string, mixed> */
$hostCounterSnapshot = function (): array {
    $html = (string) app('livewire')->mount('host-counter');

    return Utils::extractAttributeDataFromHtml($html, 'wire:snapshot');
};

/** @param array<string, mixed> $snapshot @return array<string, mixed> */
$hostCounterMessage = function (array $snapshot): array {
    return [
        'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
        'updates' => [],
        'calls' => [['method' => 'increment', 'params' => []]],
    ];
};

it('profiles host Livewire requests without storing framework snapshots or a dedicated section', function () use ($hostCounterMessage, $hostCounterSnapshot) {
    $response = $this->postJson(app('livewire')->getUpdateUri(), [
        'components' => [$hostCounterMessage($hostCounterSnapshot())],
    ], ['X-Livewire' => '1']);

    $response->assertOk()->assertHeader('X-NewDebugBar-Profile');
    $profile = app(ProfileStore::class)->get($response->headers->get('X-NewDebugBar-Profile'));

    expect($profile['sections'])
        ->not->toHaveKey('livewire')
        ->and($profile['sections']['request']['payload']['input'])->toBe([
            'component_message_count' => 1,
            'snapshot_data_stored' => false,
        ])
        ->and(json_encode($profile))->not->toContain('wire:snapshot', 'checksum');
});

it('preserves host Livewire response bytes', function () use ($hostCounterMessage, $hostCounterSnapshot) {
    $payload = ['components' => [$hostCounterMessage($hostCounterSnapshot())]];
    $profiled = $this->postJson(app('livewire')->getUpdateUri(), $payload, ['X-Livewire' => '1']);

    config(['newdebugbar.enabled' => false]);
    $plain = $this->postJson(app('livewire')->getUpdateUri(), $payload, ['X-Livewire' => '1']);

    $profiled->assertOk()->assertHeader('X-NewDebugBar-Profile');
    $plain->assertOk()->assertHeaderMissing('X-NewDebugBar-Profile');

    expect($profiled->getContent())->toBe($plain->getContent())
        ->and($profiled->headers->get('Content-Type'))->toBe($plain->headers->get('Content-Type'));
});

it('excludes debug toolbar updates from profiling and storage', function () {
    $host = $this->get('/profiled')->assertOk()->assertHeader('X-NewDebugBar-Profile');
    $toolbar = (string) app('livewire')->mount('newdebugbar.toolbar', [
        'profileId' => $host->headers->get('X-NewDebugBar-Profile'),
    ]);
    $snapshot = Utils::extractAttributeDataFromHtml($toolbar, 'wire:snapshot');
    $storedBefore = count(File::files(config('newdebugbar.storage.path')));

    $response = $this->postJson(app('livewire')->getUpdateUri(), [
        'components' => [[
            'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
            'updates' => [],
            'calls' => [['method' => 'loadDetails', 'params' => []]],
        ]],
    ], ['X-Livewire' => '1']);

    $response->assertOk()->assertHeaderMissing('X-NewDebugBar-Profile');

    expect(count(File::files(config('newdebugbar.storage.path'))))->toBe($storedBefore);
});
