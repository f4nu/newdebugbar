@props(['scope'])

@php
    $metrics = [
        [
            'key' => 'queries',
            'section' => 'queries',
            'label' => 'Queries',
            'shortLabel' => 'SQL',
            'value' => 'summary.query_count',
            'ariaLabel' => 'Open query details',
        ],
        [
            'key' => 'duration',
            'section' => 'request',
            'label' => 'Time ms',
            'shortLabel' => 'ms',
            'value' => 'summary.duration_ms',
            'ariaLabel' => 'Open request timing',
        ],
        [
            'key' => 'memory',
            'section' => 'overview',
            'label' => 'Peak MB',
            'shortLabel' => 'MB',
            'value' => 'summary.peak_memory_mb',
            'ariaLabel' => 'Open request overview',
        ],
    ];
@endphp

<div
    data-ndb-mobile-request-metrics="{{ $scope }}"
    role="group"
    aria-label="Request metrics"
    {{ $attributes->class('ndb:mx-auto ndb:grid ndb:w-full ndb:max-w-sm ndb:min-w-0 ndb:flex-1 ndb:grid-cols-3 ndb:items-stretch') }}
>
    @foreach ($metrics as $metric)
        <button
            type="button"
            data-ndb-mobile-toolbar-metric="{{ $metric['key'] }}"
            data-ndb-mobile-toolbar-metric-scope="{{ $scope }}"
            @click="inspectorOpen ? selectSection(@js($metric['section'])) : openInspector(@js($metric['section']))"
            aria-label="{{ $metric['ariaLabel'] }}"
            class="ndb:relative ndb:flex ndb:min-h-11 ndb:min-w-0 ndb:flex-col ndb:items-center ndb:justify-center ndb:rounded-lg ndb:px-0.5 ndb:transition-colors ndb:hover:bg-zinc-100/80 ndb:focus-visible:z-10 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-white/10"
        >
            @if (! $loop->first)
                <span
                    aria-hidden="true"
                    class="ndb:absolute ndb:bottom-2 ndb:left-0 ndb:top-2 ndb:w-px ndb:bg-zinc-200/80 ndb:dark:bg-zinc-700/80"
                ></span>
            @endif

            <span
                data-ndb-mobile-toolbar-summary="{{ $metric['key'] }}"
                class="ndb:block ndb:max-w-full ndb:truncate ndb:text-[10px] ndb:font-bold ndb:leading-4 ndb:tabular-nums ndb:min-[360px]:text-[11px]"
                x-text="{{ $metric['value'] }}"
            ></span>
            <span
                data-ndb-mobile-toolbar-metric-label="{{ $metric['key'] }}"
                class="ndb:block ndb:max-w-full ndb:truncate ndb:text-[8px] ndb:font-semibold ndb:leading-3 ndb:uppercase ndb:tracking-normal ndb:text-zinc-400 ndb:min-[360px]:text-[9px]"
                ><span class="ndb:min-[360px]:hidden">{{ $metric['shortLabel'] }}</span
                ><span class="ndb:hidden ndb:min-[360px]:inline">{{ $metric['label'] }}</span></span>
        </button>
    @endforeach
</div>
