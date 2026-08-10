<?php

use Livewire\Drawer\Utils;
use Livewire\Livewire;
use NewDebugBar\Tests\Fixtures\Livewire\DiagnosticsFixture;
use NewDebugBar\Tests\TestCase;

uses(TestCase::class);

/** @return array{snapshot: string, updates: array<string, string>, calls: array<array-key, mixed>} */
function overheadMessage(string $search): array
{
    $html = (string) app('livewire')->mount('diagnostics-fixture');
    $snapshot = Utils::extractAttributeDataFromHtml($html, 'wire:snapshot');

    return [
        'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
        'updates' => ['search' => $search],
        'calls' => [],
    ];
}

/** @param list<float> $samples */
function overheadPercentile(array $samples, float $percentile): float
{
    sort($samples);
    $index = (int) floor((count($samples) - 1) * $percentile);

    return round($samples[$index], 3);
}

test('measures the local Livewire collection delta without setting an invented budget', function () {
    Livewire::component('diagnostics-fixture', DiagnosticsFixture::class);
    $enabled = [];
    $disabled = [];

    foreach (range(1, 4) as $iteration) {
        foreach ([false, true] as $collect) {
            config(['newdebugbar.enabled' => $collect]);
            $this->postJson(app('livewire')->getUpdateUri(), [
                'components' => [overheadMessage('warm-'.$iteration)],
            ], ['X-Livewire' => '1'])->assertOk();
        }
    }

    foreach (range(1, 30) as $iteration) {
        $order = $iteration % 2 === 0 ? [true, false] : [false, true];

        foreach ($order as $collect) {
            config(['newdebugbar.enabled' => $collect]);
            $message = overheadMessage('sample-'.$iteration);
            $started = hrtime(true);
            $response = $this->postJson(app('livewire')->getUpdateUri(), [
                'components' => [$message],
            ], ['X-Livewire' => '1'])->assertOk();
            $duration = (hrtime(true) - $started) / 1_000_000;

            if ($collect) {
                $response->assertHeader('X-NewDebugBar-Profile');
                $enabled[] = $duration;
            } else {
                $response->assertHeaderMissing('X-NewDebugBar-Profile');
                $disabled[] = $duration;
            }
        }
    }

    $disabledMedian = overheadPercentile($disabled, 0.5);
    $enabledMedian = overheadPercentile($enabled, 0.5);
    $result = [
        'samples_per_lane' => count($enabled),
        'disabled_median_ms' => $disabledMedian,
        'enabled_median_ms' => $enabledMedian,
        'median_delta_ms' => round($enabledMedian - $disabledMedian, 3),
        'disabled_p95_ms' => overheadPercentile($disabled, 0.95),
        'enabled_p95_ms' => overheadPercentile($enabled, 0.95),
        'scope' => 'local Testbench property-update HTTP requests',
    ];

    fwrite(STDOUT, PHP_EOL.'LIVEWIRE_OVERHEAD '.json_encode($result, JSON_THROW_ON_ERROR).PHP_EOL);

    expect($enabled)->toHaveCount(30)
        ->and($disabled)->toHaveCount(30)
        ->and($enabledMedian)->toBeGreaterThan(0)
        ->and($disabledMedian)->toBeGreaterThan(0);
});
