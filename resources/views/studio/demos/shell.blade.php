@php
    $currentRequest = [
        'id' => '550e8400-e29b-41d4-a716-446655440000',
        'environment' => 'local',
        'method' => 'GET',
        'path' => '/trips/kyoto-autumn',
        'status' => 200,
        'response_size' => '33.57 KB',
        'duration_ms' => 1453.51,
        'peak_memory_mb' => 8,
        'query_count' => 34,
        'query_time_ms' => 219.41,
        'request_type' => 'full_page',
        'recorded_at' => now()->subSeconds(12)->toIso8601String(),
        'recorded_time' => now()->subSeconds(12)->format('H:i:s'),
        'warning' => false,
        'sections' => [],
    ];
    $laterRequests = [
        [
            ...$currentRequest,
            'id' => '550e8400-e29b-41d4-a716-446655440001',
            'method' => 'POST',
            'path' => '/api/trips/kyoto-autumn/refresh',
            'status' => 202,
            'duration_ms' => 184.2,
            'query_count' => 7,
            'request_type' => 'json',
            'recorded_at' => now()->subSeconds(7)->toIso8601String(),
            'recorded_time' => now()->subSeconds(7)->format('H:i:s'),
        ],
        [
            ...$currentRequest,
            'id' => '550e8400-e29b-41d4-a716-446655440002',
            'method' => 'GET',
            'path' => '/api/trips/kyoto-autumn/weather',
            'status' => 503,
            'duration_ms' => 63.44,
            'query_count' => 2,
            'request_type' => 'ajax',
            'recorded_at' => now()->subSeconds(3)->toIso8601String(),
            'recorded_time' => now()->subSeconds(3)->format('H:i:s'),
            'warning' => true,
        ],
    ];
@endphp

<div
    class="ndb:space-y-5"
    x-init="
        summary = {{ \Illuminate\Support\Js::from($currentRequest) }};
        currentRequestId = summary.id;
        recentProfiles = [summary, ...{{ \Illuminate\Support\Js::from($laterRequests) }}];
    "
>
    @component('newdebugbar::studio.component', ['component' => 'corner-toolbar', 'components' => $components])
        <div
            x-data="{ toolbarIsCorner: true }"
            class="ndb:h-14 ndb:w-[196px] ndb:rounded-[18px] ndb:border ndb:border-white/70 ndb:bg-white/80 ndb:p-1.5 ndb:shadow-lg ndb:dark:border-white/10 ndb:dark:bg-zinc-950/90"
        >
            <x-newdebugbar::corner-toolbar />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'mobile-request-metrics', 'components' => $components])
        <div class="ndb:max-w-sm ndb:rounded-xl ndb:border ndb:border-zinc-200/80 ndb:bg-white ndb:px-2 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950">
            <x-newdebugbar::mobile-request-metrics scope="studio" />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'mobile-toolbar-popover', 'components' => $components])
        <div x-data="{ mobileToolbarMenu: 'studio-actions' }" class="ndb:relative ndb:h-52 ndb:max-w-sm">
            <x-newdebugbar::mobile-toolbar-popover
                id="newdebugbar-studio-mobile-actions"
                menu="studio-actions"
                label="Debug bar actions"
                direction="below"
            >
                <button
                    type="button"
                    role="menuitem"
                    class="ndb:flex ndb:min-h-11 ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-left ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-white/10"
                >
                    <x-newdebugbar::icon name="search" class="ndb:size-4 ndb:text-zinc-500" />
                    <span class="ndb:text-sm ndb:font-medium">Command palette</span>
                </button>
                <button
                    type="button"
                    role="menuitem"
                    class="ndb:flex ndb:min-h-11 ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-left ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-white/10"
                >
                    <x-newdebugbar::icon name="expand" class="ndb:size-4 ndb:text-zinc-500" />
                    <span class="ndb:text-sm ndb:font-medium">Open inspector</span>
                </button>
                <x-newdebugbar::theme-menu-item />
            </x-newdebugbar::mobile-toolbar-popover>
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'request-option', 'components' => $components])
        <div x-data="{ request: {{ \Illuminate\Support\Js::from($laterRequests[0]) }} }" class="ndb:max-w-sm">
            <x-newdebugbar::request-option />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'request-switcher', 'components' => $components])
        <div class="ndb:relative ndb:h-64 ndb:max-w-sm">
            <x-newdebugbar::request-switcher scope="studio" direction="below" class="ndb:w-full" />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'toolbar-anchor-preview', 'components' => $components])
        <div
            x-data="{
                toolbarDragging: true,
                toolbarDragTarget: 'top',
                toolbarCenterWidth: 560,
                toolbarCenterHeight: 60,
            }"
            class="ndb:relative ndb:h-24 ndb:overflow-hidden"
        >
            <x-newdebugbar::toolbar-anchor-preview
                placement="top"
                style="
                    position: absolute !important;
                    inset: 1rem !important;
                    width: auto !important;
                    height: auto !important;
                    transform: none !important;
                "
            />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'toolbar-button', 'components' => $components])
        <x-newdebugbar::toolbar-button
            section="queries"
            class="ndb:flex ndb:border ndb:border-zinc-200/80 ndb:bg-white ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"
        >
            <x-newdebugbar::icon name="database" class="ndb:size-3.5 ndb:text-indigo-500" />
            <span>
                <span class="ndb:block ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Queries</span>
                <span class="ndb:flex ndb:items-center ndb:gap-1.5 ndb:text-xs ndb:font-bold ndb:tabular-nums">
                    <span>34</span>
                    <span class="ndb:font-medium ndb:text-zinc-400">219.41 ms</span>
                </span>
            </span>
        </x-newdebugbar::toolbar-button>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'window-controls', 'components' => $components])
        <div class="ndb:inline-flex ndb:rounded-xl ndb:border ndb:border-zinc-200/80 ndb:bg-white ndb:px-1 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950">
            <x-newdebugbar::window-controls />
        </div>
    @endcomponent
</div>
