@php
    $authorization = [
        'execution' => 7,
        'ability' => 'update-trip',
        'result' => 'allowed',
        'result_label' => 'Allowed',
        'result_code' => 'trip_owner',
        'result_status' => 200,
        'result_message' => 'The signed-in traveler owns this trip.',
        'check_next' => 'If this result is unexpected, check the TripPolicy update method and the actor passed to the gate.',
        'actor_label' => 'Elise Martin',
        'actor_type' => 'App\\Models\\Traveler',
        'actor_identifier_name' => 'ID',
        'actor_identifier' => 24,
        'handler_name' => 'App\\Policies\\TripPolicy->update',
        'handler_short_name' => 'TripPolicy->update',
        'handler_source_label' => 'app/Policies/TripPolicy.php:32',
        'callsite_label' => 'app/Http/Controllers/TripController.php:48',
        'callsite_short_label' => 'TripController.php:48',
        'arguments' => [
            ['position' => 1, 'role_label' => 'Actor', 'label' => 'Elise Martin', 'type' => 'App\\Models\\Traveler', 'identity_label' => 'ID 24'],
            ['position' => 2, 'role_label' => 'Subject', 'label' => 'Kyoto in autumn', 'type' => 'App\\Models\\Trip', 'identity_label' => 'ID 1'],
        ],
        'copy_evidence' => "Ability: update-trip\nResult: allowed\nHandler: App\\Policies\\TripPolicy->update",
        'stack' => [['file' => 'app/Http/Controllers/TripController.php', 'line' => 48, 'function' => 'TripController->update']],
    ];

    $event = [
        'name' => 'App\\Events\\TripWorkspaceRefreshed',
        'display_name' => 'TripWorkspaceRefreshed',
        'source' => 'application',
        'broadcast' => false,
        'first_sequence' => 18,
        'last_sequence' => 18,
        'first_at_ms' => 212.4,
        'occurrence_count' => 1,
        'occurrence_omitted_count' => 0,
        'occurrences' => [[
            'sequence' => 18,
            'at_ms' => 212.4,
            'lifecycle' => 'request',
            'callsite' => ['file' => 'app/Actions/Trips/RefreshTripWorkspace.php', 'line' => 81],
        ]],
        'listener_outcome_label' => '2 completed',
        'listener_summary' => 'Laravel completed both registered listeners during this request.',
        'duplicate_registration_count' => 0,
        'listeners' => [
            ['name' => 'App\\Listeners\\WarmTripCache', 'queued' => false, 'registrations' => 1, 'source' => ['file' => 'app/Listeners/WarmTripCache.php', 'line' => 14]],
            ['name' => 'App\\Listeners\\NotifyTravelers', 'queued' => false, 'registrations' => 1, 'source' => ['file' => 'app/Listeners/NotifyTravelers.php', 'line' => 18]],
        ],
        'next_step' => 'If a side effect is missing, inspect the listener that owns it and confirm that listener completed.',
        'related_section' => ['key' => 'notifications', 'label' => 'Notifications'],
        'payload_shape' => [[
            'position' => 1,
            'type' => 'App\\Models\\Trip',
            'fields' => ['id', 'title', 'status'],
            'field_count' => 3,
        ]],
        'dispatch_source_count' => 1,
        'dispatch_source_omitted_count' => 0,
        'dispatch_sources' => [[
            'file' => 'app/Actions/Trips/RefreshTripWorkspace.php',
            'line' => 81,
            'count' => 1,
        ]],
    ];

    $modelGroup = [
        'model' => 'App\\Models\\Trip',
        'connection' => 'sqlite',
        'table' => 'trips',
        'load_count' => 3,
        'change_count' => 1,
        'repeated_load_count' => 1,
        'record_count' => 2,
        'unidentified_load_count' => 0,
        'source_count' => 2,
        'hidden_record_count' => 0,
        'hidden_source_count' => 0,
        'hidden_change_operation_count' => 0,
        'records' => [
            ['key' => 1, 'loads' => 2, 'sources' => [['callsite' => ['file' => 'app/Http/Controllers/TripController.php', 'line' => 27]]]],
            ['key' => 2, 'loads' => 1, 'sources' => [['callsite' => ['file' => 'app/Actions/Trips/LoadRelatedTrip.php', 'line' => 19]]]],
        ],
        'change_operations' => [[
            'event' => 'updated',
            'key' => 1,
            'callsite' => ['file' => 'app/Actions/Trips/RefreshTripWorkspace.php', 'line' => 74],
        ]],
        'sources' => [
            ['callsite' => ['file' => 'app/Http/Controllers/TripController.php', 'line' => 27], 'retrieval_count' => 2, 'change_count' => 0],
            ['callsite' => ['file' => 'app/Actions/Trips/RefreshTripWorkspace.php', 'line' => 74], 'retrieval_count' => 1, 'change_count' => 1],
        ],
    ];

    $query = [
        'execution' => 12,
        'connection' => 'sqlite',
        'query_type' => 'select',
        'sql' => 'select * from "trip_days" where "trip_id" = ? order by "position" asc',
        'normalized_sql' => 'select * from "trip_days" where "trip_id" = ? order by "position" asc',
        'bindings' => [1],
        'duration_ms' => 3.82,
        'query_time_percent' => 18.4,
        'slow' => false,
        'repeated' => false,
        'runnable_available' => true,
        'runnable_sql' => 'select * from "trip_days" where "trip_id" = 1 order by "position" asc',
        'callsite' => ['file' => 'app/Actions/Trips/LoadTripDays.php', 'line' => 31],
        'stack' => [
            ['file' => 'app/Actions/Trips/LoadTripDays.php', 'line' => 31, 'function' => 'LoadTripDays->__invoke'],
        ],
    ];

    $querySection = [
        'summary' => ['total_count' => 1, 'read_count' => 1, 'write_count' => 0, 'total_time_ms' => 3.82],
        'payload' => ['items' => [$query], 'repeated_groups' => []],
    ];

    $logEntry = [
        'sequence' => 21,
        'first_sequence' => 21,
        'last_sequence' => 21,
        'first_at_ms' => 248.316,
        'last_at_ms' => 248.316,
        'first_occurred_at' => '2026-08-25T10:24:13.316+02:00',
        'level' => 'info',
        'level_label' => 'Info',
        'attention' => false,
        'repeat_count' => 1,
        'channel_filter' => 'application',
        'channel_label' => 'application',
        'message' => 'Trip workspace refreshed',
        'search' => 'trip workspace refreshed',
        'context_fields' => [
            ['key' => 'trip_id', 'structured' => false, 'preview' => '1', 'value' => 1],
        ],
        'callsite' => ['file' => 'app/Actions/Trips/RefreshTripWorkspace.php', 'line' => 96],
        'related_exception' => null,
        'stack' => [['file' => 'app/Actions/Trips/RefreshTripWorkspace.php', 'line' => 96, 'function' => 'RefreshTripWorkspace->handle']],
        'occurrences' => [],
    ];

    $livewireRow = [
        'componentId' => 'newdebugbar-studio-livewire',
        'path' => 'filters.search',
        'value' => 'Kyoto',
        'type' => 'String',
        'editable' => true,
        'hasChildren' => false,
    ];
@endphp

<div class="ndb:space-y-5">
    @component('newdebugbar::studio.component', ['component' => 'authorization-detail', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
        <div
            x-data="{
                selectedAuthorizationDecision: @js($authorization),
                authorizationDetailOpen: true,
                authorizationDetailTab: 'decision',
                closeAuthorizationDetail() { this.authorizationDetailOpen = false; },
                setAuthorizationDetailTab(tab) { this.authorizationDetailTab = tab; },
            }"
            class="ndb:min-h-[32rem] ndb:overflow-hidden ndb:border ndb:border-zinc-200/90 ndb:bg-white ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"
        >
            <x-newdebugbar::authorization-detail />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'event-detail', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
        <div
            x-data="{
                selectedEvent: @js($event),
                eventDetailOpen: true,
                eventDetailTab: 'overview',
                closeEventDetail() { this.eventDetailOpen = false; },
                setEventDetailTab(tab) { this.eventDetailTab = tab; },
                formatEventTime(value) { return '+' + Number(value).toFixed(2) + ' ms'; },
            }"
            class="ndb:min-h-[32rem] ndb:overflow-hidden ndb:border ndb:border-zinc-200/90 ndb:bg-white ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"
        >
            <x-newdebugbar::event-detail />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'livewire-property-editor', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
        <div x-data="{ row: @js($livewireRow) }" class="ndb:flex ndb:justify-end">
            <x-newdebugbar::livewire-property-editor />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'livewire-split-view', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
        <div x-data="{ livewireDetailOpen: false }">
            <x-newdebugbar::livewire-split-view>
                <x-slot:list>
                    <button
                        type="button"
                        class="ndb:w-full ndb:bg-indigo-50/65 ndb:px-3 ndb:py-3 ndb:text-left ndb:text-xs ndb:font-bold ndb:dark:bg-indigo-950/20"
                    >
                        TripPlanner
                    </button>
                </x-slot:list>
                <div class="ndb:p-4">
                    <p class="ndb:text-sm ndb:font-bold">TripPlanner</p>
                    <p class="ndb:mt-1 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        Selected component details stay on a stable second column.
                    </p>
                </div>
            </x-newdebugbar::livewire-split-view>
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'log-entry', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
        <div
            x-data="{ logDetailSequence: null }"
            class="ndb:overflow-hidden ndb:border-y ndb:border-zinc-200/90 ndb:bg-white ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"
        >
            <x-newdebugbar::log-entry :entry="$logEntry" />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'model-group', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
        <div
            x-data="{
                modelSelected: 0,
                selectModelGroup(index) {
                    this.modelSelected = index;
                },
            }"
            class="ndb:overflow-hidden ndb:border-y ndb:border-zinc-200/90 ndb:bg-white ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"
        >
            <x-newdebugbar::model-group :group="$modelGroup" :index="0" />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'model-group-detail', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
        <div
            x-data="{
                modelDetailTab: 'records',
                setModelDetailTab(tab) {
                    this.modelDetailTab = tab;
                },
            }"
            class="ndb:bg-white ndb:dark:bg-zinc-950"
        >
            <x-newdebugbar::model-group-detail :group="$modelGroup" />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'query-actions', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
        <div class="ndb:flex ndb:justify-end">
            <x-newdebugbar::query-actions :query="$query" identity="studio-actions" :sql="$query['sql']" />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'query-execution', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
        <x-newdebugbar::query-execution :query="$query" identity="studio-execution" expanded />
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'query-section', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
        <div
            x-data="{
                queryFilter: 'all',
                querySearch: '',
                querySort: 'execution',
                visibleQueryCount: 1,
                setQueryFilter(filter) {
                    this.queryFilter = filter;
                },
                setQuerySort(sort) {
                    this.querySort = sort;
                },
                applyQueryView() {
                    this.visibleQueryCount = 1;
                },
            }"
        >
            <x-newdebugbar::query-section :section="$querySection" />
        </div>
    @endcomponent
</div>
