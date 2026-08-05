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
