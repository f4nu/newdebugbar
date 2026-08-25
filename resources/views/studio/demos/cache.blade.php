@php
    $cacheItems = [
        [
            'execution' => 1,
            'operation' => 'hit',
            'operation_label' => 'Get',
            'category' => 'read',
            'key_label' => 'morrow:trip:1:options',
            'result' => 'hit',
            'result_label' => 'Hit',
            'store_label' => 'redis',
            'driver_label' => 'Redis',
            'duration_label' => '0.42 ms',
            'duration_scope' => 'operation',
            'source_label' => 'app/Actions/Trips/LoadTripOptions.php:38',
            'source_short_label' => 'LoadTripOptions.php:38',
            'has_value' => true,
            'value_display' => json_encode(['season' => 'autumn', 'published' => true], JSON_PRETTY_PRINT),
            'lifetime_label' => 'Not applicable',
            'batch_size' => 1,
            'failed' => false,
            'exception_message' => null,
            'raw' => ['event' => 'hit', 'key' => 'morrow:trip:1:options', 'store' => 'redis'],
            'stack' => [
                ['file' => 'app/Actions/Trips/LoadTripOptions.php', 'line' => 38, 'function' => 'LoadTripOptions->__invoke'],
                ['file' => 'app/Http/Controllers/TripController.php', 'line' => 27, 'function' => 'TripController->show'],
            ],
            'search' => 'morrow:trip:1:options redis get',
        ],
        [
            'execution' => 2,
            'operation' => 'miss',
            'operation_label' => 'Get',
            'category' => 'read',
            'key_label' => 'morrow:weather:kyoto',
            'result' => 'miss',
            'result_label' => 'Miss',
            'store_label' => 'redis',
            'driver_label' => 'Redis',
            'duration_label' => '0.68 ms',
            'duration_scope' => 'operation',
            'source_label' => 'app/Services/Weather/Forecast.php:54',
            'source_short_label' => 'Forecast.php:54',
            'has_value' => false,
            'value_display' => '',
            'lifetime_label' => 'Not applicable',
            'batch_size' => 1,
            'failed' => false,
            'exception_message' => null,
            'raw' => ['event' => 'miss', 'key' => 'morrow:weather:kyoto', 'store' => 'redis'],
            'stack' => [['file' => 'app/Services/Weather/Forecast.php', 'line' => 54, 'function' => 'Forecast->forTrip']],
            'search' => 'morrow:weather:kyoto redis get',
        ],
        [
            'execution' => 3,
            'operation' => 'write',
            'operation_label' => 'Put',
            'category' => 'write',
            'key_label' => 'morrow:trip:1:summary',
            'result' => 'stored',
            'result_label' => 'Stored',
            'store_label' => 'redis',
            'driver_label' => 'Redis',
            'duration_label' => '1.14 ms',
            'duration_scope' => 'operation',
            'source_label' => 'app/Actions/Trips/RefreshTripWorkspace.php:150',
            'source_short_label' => 'RefreshTripWorkspace.php:150',
            'has_value' => true,
            'value_display' => json_encode(['days' => 6, 'activities' => 18], JSON_PRETTY_PRINT),
            'lifetime_label' => '10 minutes',
            'batch_size' => 1,
            'failed' => false,
            'exception_message' => null,
            'raw' => ['event' => 'write', 'key' => 'morrow:trip:1:summary', 'ttl' => 600],
            'stack' => [['file' => 'app/Actions/Trips/RefreshTripWorkspace.php', 'line' => 150, 'function' => 'RefreshTripWorkspace->handle']],
            'search' => 'morrow:trip:1:summary redis put',
        ],
    ];

    $cacheSummary = [
        'retained_count' => 3,
        'reads' => 2,
        'hits' => 1,
        'misses' => 1,
        'writes' => 1,
        'forgets' => 0,
        'flushes' => 0,
        'failures' => 0,
        'repeated_miss_count' => 0,
        'hit_rate' => 50,
        'duration_ms' => 2.24,
        'filter_counts' => ['writes' => 1, 'deletes' => 0, 'failed' => 0],
    ];
@endphp

<div
    x-data="{
        cacheOperations: @js($cacheItems),
        cacheSelected: 1,
        cacheDetailTab: 'overview',
        cacheDetailOpen: true,
        cacheSearch: '',
        cacheFilter: 'all',
        visibleCacheCount: {{ count($cacheItems) }},
        get selectedCacheOperation() {
            return this.cacheOperations.find((operation) => operation.execution === this.cacheSelected) ?? null;
        },
        selectCacheOperation(execution) {
            this.cacheSelected = execution;
            this.cacheDetailOpen = true;
        },
        setCacheDetailTab(tab) { this.cacheDetailTab = tab; },
        setCacheFilter(filter) { this.cacheFilter = filter; },
        applyCacheView() { this.visibleCacheCount = this.cacheOperations.length; },
        formatCachePayload(value) { return JSON.stringify(value, null, 2); },
    }"
    class="ndb:space-y-5"
>
    @component('newdebugbar::studio.component', ['component' => 'cache-controls', 'components' => $components])
        <x-newdebugbar::cache-controls :summary="$cacheSummary" :item-count="6" />
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'cache-detail', 'components' => $components])
        <div class="ndb:min-h-[30rem] ndb:overflow-hidden ndb:border ndb:border-zinc-200/90 ndb:bg-white ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950">
            <x-newdebugbar::cache-detail />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'cache-detail-tabs', 'components' => $components])
        <x-newdebugbar::cache-detail-tabs />
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'cache-empty', 'components' => $components])
        <x-newdebugbar::cache-empty />
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'cache-header', 'components' => $components])
        <x-newdebugbar::cache-header />
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'cache-list-item', 'components' => $components])
        <div class="ndb:overflow-hidden ndb:border-y ndb:border-zinc-200/90 ndb:bg-white ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950">
            <x-newdebugbar::cache-list-item :item="$cacheItems[2]" />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'cache-overview-facts', 'components' => $components])
        <x-newdebugbar::cache-overview-facts />
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'cache-overview-panel', 'components' => $components])
        <x-newdebugbar::cache-overview-panel />
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'cache-raw-panel', 'components' => $components])
        <div x-data="{ cacheDetailTab: 'raw' }">
            <x-newdebugbar::cache-raw-panel />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'cache-source-panel', 'components' => $components])
        <div x-data="{ cacheDetailTab: 'source' }">
            <x-newdebugbar::cache-source-panel />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'cache-workspace', 'components' => $components])
        <div class="ndb:h-[34rem] ndb:overflow-hidden ndb:bg-white ndb:dark:bg-zinc-950">
            <x-newdebugbar::cache-workspace :items="$cacheItems" :summary="$cacheSummary" />
        </div>
    @endcomponent
</div>
