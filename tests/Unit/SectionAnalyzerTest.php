<?php

use NewDebugBar\Analysis\SectionAnalyzer;

it('groups models views and event sources', function () {
    $profile = (new SectionAnalyzer)->analyze([
        'sections' => [
            'models' => ['summary' => ['count' => 3], 'payload' => ['items' => [
                ['model' => 'App\\Models\\User', 'event' => 'retrieved'],
                ['model' => 'App\\Models\\User', 'event' => 'retrieved'],
                ['model' => 'App\\Models\\Clinic', 'event' => 'created'],
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
        ->and($profile['sections']['views']['summary']['unique_views'])->toBe(1)
        ->and($profile['sections']['views']['payload']['groups'][0]['count'])->toBe(2)
        ->and(array_column($profile['sections']['events']['payload']['items'], 'source'))
        ->toBe(['framework', 'application']);
});

it('groups repeated event signatures while preserving timing sources listeners and payload shape', function () {
    $profile = (new SectionAnalyzer)->analyze([
        'sections' => [
            'events' => ['summary' => ['count' => 5, 'retained_count' => 5], 'payload' => ['items' => [
                [
                    'name' => 'App\\Events\\TripWorkspaceRefreshed',
                    'broadcast' => true,
                    'at_ms' => 10.125,
                    'callsite' => ['file' => 'app/Actions/RefreshTrip.php', 'line' => 41],
                    'listeners' => [[
                        'name' => 'App\\Listeners\\RecordWorkspaceRefresh@handle',
                        'source' => ['file' => 'app/Listeners/RecordWorkspaceRefresh.php', 'line' => 12],
                        'queued' => false,
                        'registrations' => 2,
                    ]],
                    'payload_shape' => [[
                        'position' => 1,
                        'type' => 'App\\Events\\TripWorkspaceRefreshed',
                        'fields' => ['tripId'],
                        'field_count' => 1,
                        'truncated' => false,
                    ]],
                ],
                [
                    'name' => 'App\\Events\\TripWorkspaceRefreshed',
                    'broadcast' => true,
                    'at_ms' => 18.875,
                    'callsite' => ['file' => 'app/Jobs/RefreshTrip.php', 'line' => 24],
                    'listeners' => [[
                        'name' => 'App\\Listeners\\RecordWorkspaceRefresh@handle',
                        'source' => ['file' => 'app/Listeners/RecordWorkspaceRefresh.php', 'line' => 12],
                        'queued' => false,
                        'registrations' => 2,
                    ]],
                    'payload_shape' => [[
                        'position' => 1,
                        'type' => 'App\\Events\\TripWorkspaceRefreshed',
                        'fields' => ['tripId'],
                        'field_count' => 1,
                        'truncated' => false,
                    ]],
                ],
                ['name' => 'Illuminate\\Database\\Events\\StatementPrepared', 'at_ms' => 2.5],
                ['name' => 'Illuminate\\Database\\Events\\StatementPrepared', 'at_ms' => 4.75],
                ['name' => 'application.ready', 'payload_types' => ['array']],
            ]]],
        ],
    ]);

    $groups = collect($profile['sections']['events']['payload']['groups']);
    $application = $groups->firstWhere('name', 'App\\Events\\TripWorkspaceRefreshed');
    $framework = $groups->firstWhere('name', 'Illuminate\\Database\\Events\\StatementPrepared');
    $untimed = $groups->firstWhere('name', 'application.ready');

    expect($profile['sections']['events']['summary'])
        ->application_count->toBe(3)
        ->framework_count->toBe(2)
        ->group_count->toBe(3)
        ->application_group_count->toBe(2)
        ->framework_group_count->toBe(1)
        ->and(array_column($profile['sections']['events']['payload']['items'], 'sequence'))->toBe([1, 2, 3, 4, 5])
        ->and($application)
        ->source->toBe('application')
        ->display_name->toBe('TripWorkspaceRefreshed')
        ->namespace->toBe('App\\Events')
        ->occurrence_count->toBe(2)
        ->first_sequence->toBe(1)
        ->last_sequence->toBe(2)
        ->first_at_ms->toBe(10.125)
        ->last_at_ms->toBe(18.875)
        ->span_ms->toBe(8.75)
        ->listener_count->toBe(2)
        ->duplicate_registration_count->toBe(1)
        ->listener_outcome->toBe('completed')
        ->payload_field_count->toBe(1)
        ->and($application['payload_shape'][0]['fields'])->toBe(['tripId'])
        ->and($application['dispatch_sources'])->toHaveCount(2)
        ->and($application['next_step'])->toContain('registered more than once')
        ->and($framework)
        ->source->toBe('framework')
        ->occurrence_count->toBe(2)
        ->span_ms->toBe(2.25)
        ->and($framework['related_section'])->toBe(['key' => 'queries', 'label' => 'Queries'])
        ->and($untimed['first_at_ms'])->toBeNull()
        ->and($untimed['payload_shape'][0]['type'])->toBe('array');
});

it('bounds grouped event detail while preserving totals and timeline endpoints', function () {
    $items = array_map(fn (int $sequence): array => [
        'name' => 'App\\Events\\RepeatedSignal',
        'at_ms' => $sequence / 10,
        'callsite' => [
            'file' => 'app/Signals/Source'.(($sequence - 1) % 15).'.php',
            'line' => $sequence,
        ],
    ], range(1, 40));
    $profile = (new SectionAnalyzer)->analyze([
        'sections' => [
            'events' => [
                'summary' => ['count' => 40, 'retained_count' => 40],
                'payload' => ['items' => $items],
            ],
        ],
    ]);
    $group = $profile['sections']['events']['payload']['groups'][0];

    expect($group)
        ->occurrence_count->toBe(40)
        ->occurrence_omitted_count->toBe(15)
        ->dispatch_source_count->toBe(40)
        ->dispatch_source_omitted_count->toBe(30)
        ->and($group['occurrences'])->toHaveCount(25)
        ->and($group['occurrences'][0]['sequence'])->toBe(1)
        ->and($group['occurrences'][24]['sequence'])->toBe(40)
        ->and($group['dispatch_sources'])->toHaveCount(10)
        ->and($profile['sections']['events']['payload']['items'])->toHaveCount(40);
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
