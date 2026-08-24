<?php

use NewDebugBar\Analysis\CacheAnalyzer;

it('builds diagnostic cache operations and summaries', function () {
    $weather = ['high' => 24, 'low' => 15, 'condition' => 'clear', 'updated' => true];
    $analysis = (new CacheAnalyzer(minimumReads: 3, highMissRate: 0.66))->analyze([
        [
            'operation' => 'miss',
            'key_hash' => 'trip-1',
            'key' => 'trip:kyoto',
            'store' => 'database',
            'driver' => 'database',
            'duration_ms' => 1.25,
            'duration_id' => 'read-1',
            'callsite' => ['file' => 'app/Trips/LoadTrip.php', 'line' => 24],
        ],
        [
            'operation' => 'miss',
            'key_hash' => 'trip-1',
            'key' => 'trip:kyoto',
            'store' => 'database',
            'driver' => 'database',
            'duration_ms' => 1.75,
            'duration_id' => 'read-2',
        ],
        [
            'operation' => 'hit',
            'key_hash' => 'weather-1',
            'key_policy' => 'hash',
            'store' => 'redis',
            'driver' => 'redis',
            'duration_ms' => 0.15,
            'duration_id' => 'read-3',
            'value' => $weather,
        ],
        [
            'operation' => 'write',
            'key_hash' => 'snapshot-1',
            'key' => 'trip:snapshot',
            'store' => 'redis',
            'driver' => 'redis',
            'duration_ms' => 2.5,
            'duration_id' => 'write-batch',
            'duration_scope' => 'batch',
            'seconds' => 3600,
            'value' => 'A compact autumn itinerary',
        ],
        [
            'operation' => 'write',
            'key_hash' => 'snapshot-2',
            'key' => 'trip:summary',
            'store' => 'redis',
            'driver' => 'redis',
            'duration_ms' => 2.5,
            'duration_id' => 'write-batch',
            'duration_scope' => 'batch',
            'seconds' => null,
        ],
        [
            'operation' => 'forget_failed',
            'key_hash' => 'stale-1',
            'key' => 'trip:stale',
            'store' => 'database',
            'driver' => 'database',
            'failed' => true,
        ],
        [
            'operation' => 'flush',
            'store' => 'array',
            'driver' => 'array',
        ],
    ]);

    expect($analysis['summary'])
        ->operations->toBe([
            'miss' => 2,
            'hit' => 1,
            'write' => 2,
            'forget_failed' => 1,
            'flush' => 1,
        ])
        ->reads->toBe(3)
        ->hits->toBe(1)
        ->misses->toBe(2)
        ->hit_rate->toBe(33.3)
        ->writes->toBe(2)
        ->forgets->toBe(0)
        ->flushes->toBe(1)
        ->failures->toBe(1)
        ->store_count->toBe(3)
        ->unique_key_count->toBe(5)
        ->timed_count->toBe(4)
        ->duration_ms->toBe(5.65)
        ->repeated_miss_count->toBe(1)
        ->high_miss_rate->toBeTrue()
        ->attention_count->toBe(4)
        ->filter_counts->toBe([
            'all' => 7,
            'reads' => 3,
            'writes' => 2,
            'deletes' => 2,
            'failed' => 1,
        ])
        ->and($analysis['repeated_misses'][0])
        ->key->toBe('trip:kyoto')
        ->store->toBe('database')
        ->count->toBe(2)
        ->and($analysis['items'][0])
        ->operation_label->toBe('Get')
        ->result_label->toBe('Miss')
        ->duration_label->toBe('1.25 ms')
        ->source_label->toBe('app/Trips/LoadTrip.php:24')
        ->related_count->toBe(2)
        ->related_executions->toBe([1, 2])
        ->repeat_miss_count->toBe(2)
        ->attention->toBeTrue()
        ->and($analysis['items'][2])
        ->key_label->toBe('Protected key weather-1')
        ->has_value->toBeTrue()
        ->value_display->toBe(json_encode(
            $weather,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
        ))
        ->and($analysis['items'][3])
        ->lifetime_label->toBe('3,600 seconds')
        ->has_value->toBeTrue()
        ->value_display->toBe('A compact autumn itinerary')
        ->and($analysis['items'][5])
        ->failed->toBeTrue()
        ->result_label->toBe('Failed')
        ->and($analysis['items'][6])
        ->key_label->toBe('No key')
        ->attention->toBeTrue();
});

it('handles empty and missing optional cache data', function () {
    $empty = (new CacheAnalyzer)->analyze([]);
    $missing = (new CacheAnalyzer)->analyze([
        ['operation' => 'unexpected'],
    ]);

    expect($empty['summary'])
        ->reads->toBe(0)
        ->hit_rate->toBe(0.0)
        ->duration_ms->toBe(0.0)
        ->attention_count->toBe(0)
        ->and($empty['items'])->toBe([])
        ->and($missing['items'][0])
        ->operation->toBe('unknown')
        ->key_label->toBe('No key')
        ->duration_label->toBe('—')
        ->source_label->toBe('Source unavailable')
        ->has_value->toBeFalse()
        ->value_display->toBeNull();
});

it('distinguishes null and false cache values from unavailable values', function () {
    $analysis = (new CacheAnalyzer)->analyze([
        ['operation' => 'hit', 'value' => null],
        ['operation' => 'hit', 'value' => false],
        ['operation' => 'miss'],
    ]);

    expect($analysis['items'][0])
        ->has_value->toBeTrue()
        ->value_display->toBe('null')
        ->and($analysis['items'][1])
        ->has_value->toBeTrue()
        ->value_display->toBe('false')
        ->and($analysis['items'][2])
        ->has_value->toBeFalse()
        ->value_display->toBeNull();
});
