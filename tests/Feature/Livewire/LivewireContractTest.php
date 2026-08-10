<?php

use Livewire\Component;
use Livewire\Drawer\Utils;
use Livewire\Livewire;
use NewDebugBar\Tests\Fixtures\Livewire\DiagnosticsChildFixture;
use NewDebugBar\Tests\Fixtures\Livewire\DiagnosticsFixture;
use NewDebugBar\Tests\Fixtures\Livewire\DiagnosticsParentFixture;

use function Livewire\on;

beforeEach(function () {
    Livewire::component('diagnostics-fixture', DiagnosticsFixture::class);
    Livewire::component('diagnostics-parent', DiagnosticsParentFixture::class);
    Livewire::component('diagnostics-child', DiagnosticsChildFixture::class);
});

/**
 * @param  array<string, mixed>  $params
 * @return array<string, mixed>
 */
function diagnosticsSnapshot(string $name, ?string $key = null, array $params = []): array
{
    $html = (string) app('livewire')->mount($name, $params, key: $key);

    return Utils::extractAttributeDataFromHtml($html, 'wire:snapshot');
}

/**
 * @param  array<string, mixed>  $snapshot
 * @param  array<string, mixed>  $updates
 * @param  list<array{method: string, params: array<array-key, mixed>}>  $calls
 * @return array{snapshot: string, updates: array<string, mixed>, calls: list<array{method: string, params: array<array-key, mixed>}>}
 */
function diagnosticsMessage(array $snapshot, array $updates = [], array $calls = []): array
{
    return [
        'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
        'updates' => $updates,
        'calls' => $calls,
    ];
}

it('exposes stable component lifecycle evidence for mounts and nested components', function () {
    config(['app.debug' => true]);
    $mounts = [];
    $profiles = [];
    $off = [
        on('mount', function (Component $component, array $params, mixed $key, mixed $parent) use (&$mounts): void {
            if (! str_starts_with($component->getName(), 'diagnostics-')) {
                return;
            }

            $observedParentId = $parent instanceof Component
                ? $parent->getId()
                : (is_string($parent) ? $parent : null);
            $knownIds = array_column($mounts, 'id');
            $mounts[] = [
                'id' => $component->getId(),
                'name' => $component->getName(),
                'class' => $component::class,
                'key' => $key,
                'parent_id' => in_array($observedParentId, $knownIds, true) ? $observedParentId : null,
            ];
        }),
        on('profile', function (string $phase, string $componentId, array $range) use (&$profiles): void {
            $profiles[] = compact('phase', 'componentId', 'range');
        }),
    ];

    try {
        diagnosticsSnapshot('diagnostics-parent', 'parent-fixture');
    } finally {
        foreach ($off as $unsubscribe) {
            $unsubscribe();
        }
    }

    $parent = collect($mounts)->firstWhere('name', 'diagnostics-parent');
    $child = collect($mounts)->firstWhere('name', 'diagnostics-child');

    expect($mounts)->toHaveCount(2)
        ->and($parent)->not->toBeNull()
        ->and($child)->not->toBeNull()
        ->and($parent['id'])->toBeString()->not->toBe('')
        ->and($parent['key'])->toBe('parent-fixture')
        ->and($parent['parent_id'])->toBeNull()
        ->and($child['parent_id'])->toBe($parent['id'])
        ->and($child['class'])->toBe(DiagnosticsChildFixture::class)
        ->and(array_column($profiles, 'phase'))->toContain('mount', 'render', 'dehydrate')
        ->and($profiles[0]['range'])->toHaveCount(2)
        ->and($profiles[0]['range'][0])->toBeFloat()
        ->and($profiles[0]['range'][1])->toBeFloat();
});

it('exposes before submitted server action render and response evidence for an update', function () {
    config(['app.debug' => true]);
    $snapshot = diagnosticsSnapshot('diagnostics-fixture');
    $evidence = [
        'hydrate' => [],
        'updates' => [],
        'calls' => [],
        'renders' => [],
        'dehydrate' => [],
        'profiles' => [],
    ];
    $off = [
        on('hydrate', function (Component $component) use (&$evidence): void {
            if ($component->getName() === 'diagnostics-fixture') {
                $evidence['hydrate'] = $component->all();
            }
        }),
        on('update', function (Component $component, string $path, mixed $value) use (&$evidence): ?callable {
            if ($component->getName() !== 'diagnostics-fixture') {
                return null;
            }

            $before = $component->getPropertyValue($path);

            return function () use (&$evidence, $component, $path, $value, $before): void {
                $evidence['updates'][] = [
                    'path' => $path,
                    'before' => $before,
                    'submitted' => $value,
                    'server' => $component->getPropertyValue($path),
                ];
            };
        }),
        on('call', function (Component $component, string $method, array $params) use (&$evidence): void {
            if ($component->getName() === 'diagnostics-fixture') {
                $evidence['calls'][] = compact('method', 'params');
            }
        }),
        on('render', function (Component $component, mixed $view) use (&$evidence): void {
            if ($component->getName() === 'diagnostics-fixture') {
                $evidence['renders'][] = is_object($view) && method_exists($view, 'getPath')
                    ? $view->getPath()
                    : null;
            }
        }),
        on('dehydrate', function (Component $component) use (&$evidence): void {
            if ($component->getName() === 'diagnostics-fixture') {
                $evidence['dehydrate'] = $component->all();
            }
        }),
        on('profile', function (string $phase, string $componentId, array $range) use (&$evidence, $snapshot): void {
            if ($componentId === $snapshot['memo']['id']) {
                $evidence['profiles'][] = ['phase' => $phase, 'range' => $range];
            }
        }),
    ];

    try {
        $response = $this->postJson(app('livewire')->getUpdateUri(), [
            'components' => [diagnosticsMessage(
                $snapshot,
                ['search' => 'northline'],
                [['method' => 'saveReview', 'params' => [5]]],
            )],
        ], ['X-Livewire' => '1']);
    } finally {
        foreach ($off as $unsubscribe) {
            $unsubscribe();
        }
    }

    $response->assertOk()->assertJsonCount(1, 'components');
    $responseSnapshot = json_decode($response->json('components.0.snapshot'), true, flags: JSON_THROW_ON_ERROR);

    expect($evidence['hydrate'])
        ->search->toBe('')
        ->password->toBe('initial-secret')
        ->and($evidence['updates'])->toBe([[
            'path' => 'search',
            'before' => '',
            'submitted' => 'northline',
            'server' => 'northline',
        ]])
        ->and($evidence['calls'])->toBe([['method' => 'saveReview', 'params' => [5]]])
        ->and($evidence['renders'])->toHaveCount(1)
        ->and($evidence['dehydrate'])
        ->search->toBe('northline')
        ->reviewScore->toBe(5)
        ->and($responseSnapshot['memo']['id'])->toBe($snapshot['memo']['id'])
        ->and($responseSnapshot['data']['search'])->toBe('northline')
        ->and($responseSnapshot['data']['reviewScore'])->toBe(5)
        ->and(array_column($evidence['profiles'], 'phase'))->toContain('hydrate', 'call0', 'render', 'dehydrate');
});

it('keeps one HTTP exchange distinct from its seventeen component messages', function () {
    $messages = [];

    foreach (range(1, 17) as $index) {
        $messages[] = diagnosticsMessage(
            diagnosticsSnapshot('diagnostics-fixture', 'batch-'.$index),
            calls: [['method' => 'saveReview', 'params' => [$index]]],
        );
    }

    $requestMessageCounts = [];
    $responseMessageCounts = [];
    $off = [
        on('request', function (array $payload) use (&$requestMessageCounts): void {
            $requestMessageCounts[] = count($payload);
        }),
        on('response', function (array $payload) use (&$responseMessageCounts): void {
            $responseMessageCounts[] = count($payload['components'] ?? []);
        }),
    ];

    try {
        $response = $this->postJson(app('livewire')->getUpdateUri(), [
            'components' => $messages,
        ], ['X-Livewire' => '1']);
    } finally {
        foreach ($off as $unsubscribe) {
            $unsubscribe();
        }
    }

    $response->assertOk()->assertJsonCount(17, 'components');
    $componentIds = collect($response->json('components'))
        ->map(fn (array $component): string => (string) data_get(
            json_decode($component['snapshot'], true, flags: JSON_THROW_ON_ERROR),
            'memo.id',
        ));

    expect($requestMessageCounts)->toBe([17])
        ->and($responseMessageCounts)->toBe([17])
        ->and($componentIds)->toHaveCount(17)
        ->and($componentIds->unique())->toHaveCount(17);
});

it('identifies an event recipient without claiming an unobserved source', function () {
    $snapshot = diagnosticsSnapshot('diagnostics-child');
    $calls = [];
    $off = on('call', function (Component $component, string $method, array $params) use (&$calls): void {
        if ($component->getName() === 'diagnostics-child') {
            $calls[] = [
                'recipient_id' => $component->getId(),
                'method' => $method,
                'params' => $params,
            ];
        }
    });

    try {
        $response = $this->postJson(app('livewire')->getUpdateUri(), [
            'components' => [diagnosticsMessage(
                $snapshot,
                calls: [[
                    'method' => '__dispatch',
                    'params' => ['vendor-checked-in', ['vendor' => 'Northline Ceramics']],
                ]],
            )],
        ], ['X-Livewire' => '1']);
    } finally {
        $off();
    }

    $response->assertOk();
    $responseSnapshot = json_decode($response->json('components.0.snapshot'), true, flags: JSON_THROW_ON_ERROR);

    expect($calls)->toBe([[
        'recipient_id' => $snapshot['memo']['id'],
        'method' => '__dispatch',
        'params' => ['vendor-checked-in', ['vendor' => 'Northline Ceramics']],
    ]])
        ->and($responseSnapshot['data']['checkIns'])->toBe(1);
});

it('keeps lifecycle evidence when debug-only server timings are unavailable', function () {
    config(['app.debug' => false]);
    $lifecycle = [];
    $profiles = [];
    $off = [
        on('mount', function (Component $component) use (&$lifecycle): void {
            if ($component->getName() === 'diagnostics-fixture') {
                $lifecycle[] = 'mount';
            }
        }),
        on('render', function (Component $component) use (&$lifecycle): void {
            if ($component->getName() === 'diagnostics-fixture') {
                $lifecycle[] = 'render';
            }
        }),
        on('dehydrate', function (Component $component) use (&$lifecycle): void {
            if ($component->getName() === 'diagnostics-fixture') {
                $lifecycle[] = 'dehydrate';
            }
        }),
        on('profile', function () use (&$profiles): void {
            $profiles[] = true;
        }),
    ];

    try {
        diagnosticsSnapshot('diagnostics-fixture');
    } finally {
        foreach ($off as $unsubscribe) {
            $unsubscribe();
        }
    }

    expect($lifecycle)->toBe(['mount', 'render', 'dehydrate'])
        ->and($profiles)->toBe([]);
});

it('exposes the package toolbar identity needed for strict self exclusion', function () {
    $snapshot = diagnosticsSnapshot('newdebugbar.toolbar', params: [
        'profileId' => '00000000-0000-4000-8000-000000000000',
    ]);

    expect($snapshot['memo']['name'])->toBe('newdebugbar.toolbar')
        ->and($snapshot['memo']['id'])->toBeString()->not->toBe('');
});
