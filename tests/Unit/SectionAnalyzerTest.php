<?php

use NewDebugBar\Analysis\SectionAnalyzer;

it('groups models views cache behavior and event sources', function () {
    $profile = (new SectionAnalyzer)->analyze([
        'sections' => [
            'models' => ['summary' => ['count' => 3], 'payload' => ['items' => [
                ['model' => 'App\\Models\\User', 'event' => 'retrieved'],
                ['model' => 'App\\Models\\User', 'event' => 'retrieved'],
                ['model' => 'App\\Models\\Clinic', 'event' => 'created'],
            ]]],
            'cache' => ['summary' => ['count' => 5], 'payload' => ['items' => [
                ['operation' => 'hit', 'key_hash' => 'one'],
                ['operation' => 'miss', 'key_hash' => 'two'],
                ['operation' => 'miss', 'key_hash' => 'two'],
                ['operation' => 'write', 'key_hash' => 'three'],
                ['operation' => 'forget', 'key_hash' => 'three'],
            ]]],
            'views' => ['summary' => ['count' => 2], 'payload' => ['items' => [
                ['name' => 'clinics.index'],
                ['name' => 'clinics.index'],
            ]]],
            'events' => ['summary' => ['count' => 2], 'payload' => ['items' => [
                ['name' => 'Illuminate\\Auth\\Events\\Login'],
                ['name' => 'clinic.ready'],
            ]]],
        ],
    ]);

    expect($profile['sections']['models']['summary'])
        ->model_classes->toBe(2)
        ->lifecycle_events->toBe(['retrieved' => 2, 'created' => 1])
        ->retrieval_count->toBe(2)
        ->distinct_record_count->toBe(0)
        ->unidentified_load_count->toBe(2)
        ->repeated_load_count->toBe(0)
        ->model_change_count->toBe(1)
        ->model_change_events->toBe(['created' => 1])
        ->and($profile['sections']['models']['payload']['groups'][0])
        ->model->toBe('App\\Models\\User')
        ->event->toBe('retrieved')
        ->count->toBe(2)
        ->and($profile['sections']['cache']['summary'])
        ->reads->toBe(3)
        ->hit_rate->toBe(33.3)
        ->operations->toBe(['hit' => 1, 'miss' => 2, 'write' => 1, 'forget' => 1])
        ->repeated_miss_count->toBe(1)
        ->and($profile['sections']['cache']['payload']['repeated_misses'])
        ->toBe([['key_hash' => 'two', 'count' => 2]])
        ->and($profile['sections']['views']['summary']['unique_views'])->toBe(1)
        ->and($profile['sections']['views']['payload']['groups'][0]['count'])->toBe(2)
        ->and(array_column($profile['sections']['events']['payload']['items'], 'source'))
        ->toBe(['framework', 'application']);
});

it('sorts model groups by count then by model name', function () {
    $profile = (new SectionAnalyzer)->analyze([
        'sections' => [
            'models' => ['summary' => ['count' => 5], 'payload' => ['items' => [
                ['model' => 'App\\Models\\User', 'event' => 'retrieved'],
                ['model' => 'App\\Models\\Audit', 'event' => 'saved'],
                ['model' => 'App\\Models\\Clinic', 'event' => 'created'],
                ['model' => 'App\\Models\\User', 'event' => 'retrieved'],
                ['model' => 'App\\Models\\Clinic', 'event' => 'created'],
            ]]],
        ],
    ]);

    expect(array_map(
        fn (array $group): array => [$group['model'], $group['count']],
        $profile['sections']['models']['payload']['groups'],
    ))->toBe([
        ['App\\Models\\Clinic', 2],
        ['App\\Models\\User', 2],
        ['App\\Models\\Audit', 1],
    ]);
});

it('summarizes model loads by record and counts only extra identified retrievals as repeated', function () {
    $profile = (new SectionAnalyzer)->analyze([
        'sections' => [
            'models' => ['summary' => ['count' => 5], 'payload' => ['items' => [
                ['model' => 'App\\Models\\User', 'event' => 'retrieved', 'key' => 2, 'connection' => 'testing', 'table' => 'users', 'at_ms' => 7.25],
                ['model' => 'App\\Models\\User', 'event' => 'retrieved', 'key' => 1, 'connection' => 'testing', 'table' => 'users', 'at_ms' => 2.5],
                ['model' => 'App\\Models\\User', 'event' => 'retrieved', 'key' => 1, 'connection' => 'testing', 'table' => 'users', 'at_ms' => 5.75],
                ['model' => 'App\\Models\\User', 'event' => 'retrieved', 'key' => 1, 'connection' => 'testing', 'table' => 'users', 'at_ms' => 8.5],
                ['model' => 'App\\Models\\User', 'event' => 'retrieved', 'key' => null, 'connection' => 'testing', 'table' => 'users', 'at_ms' => 9],
            ]]],
        ],
    ]);

    expect($profile['sections']['models']['summary'])
        ->retrieval_count->toBe(5)
        ->distinct_record_count->toBe(2)
        ->unidentified_load_count->toBe(1)
        ->repeated_load_count->toBe(2)
        ->and($profile['sections']['models']['payload']['model_groups'][0])
        ->model->toBe('App\\Models\\User')
        ->connection->toBe('testing')
        ->table->toBe('users')
        ->load_count->toBe(5)
        ->record_count->toBe(2)
        ->unidentified_load_count->toBe(1)
        ->repeated_load_count->toBe(2)
        ->and($profile['sections']['models']['payload']['model_groups'][0]['records'][0])
        ->key->toBe(1)
        ->loads->toBe(3)
        ->first_seen_ms->toBe(2.5)
        ->last_seen_ms->toBe(8.5);
});

it('ranks changed models before repeated retrievals and keeps write events distinct', function () {
    $profile = (new SectionAnalyzer)->analyze([
        'sections' => [
            'models' => ['summary' => ['count' => 7], 'payload' => ['items' => [
                ['model' => 'App\\Models\\StudioJob', 'event' => 'retrieved', 'key' => 1],
                ['model' => 'App\\Models\\StudioJob', 'event' => 'retrieved', 'key' => 1],
                ['model' => 'App\\Models\\StudioJob', 'event' => 'retrieved', 'key' => 1],
                ['model' => 'App\\Models\\Client', 'event' => 'retrieved', 'key' => 4],
                ['model' => 'App\\Models\\Client', 'event' => 'updated', 'key' => 4],
                ['model' => 'App\\Models\\Client', 'event' => 'saved', 'key' => 4],
                ['model' => 'App\\Models\\Client', 'event' => 'updating', 'key' => 4],
            ]]],
        ],
    ]);

    expect(array_column($profile['sections']['models']['payload']['model_groups'], 'model'))
        ->toBe(['App\\Models\\Client', 'App\\Models\\StudioJob'])
        ->and($profile['sections']['models']['summary'])
        ->model_change_count->toBe(1)
        ->model_change_events->toBe(['updated' => 1])
        ->repeated_load_count->toBe(2)
        ->and($profile['sections']['models']['payload']['model_groups'][0])
        ->change_count->toBe(1)
        ->change_events->toBe(['updated' => 1])
        ->total_count->toBe(4);
});
