@props(['scope'])

@php
    $metrics = [
        [
            'key' => 'queries',
            'section' => 'queries',
            'label' => 'Queries',
            'shortLabel' => 'QRY',
            'value' => 'summary.query_count',
            'ariaLabel' => 'Open query details',
        ],
        [
            'key' => 'duration',
            'section' => 'request',
            'label' => 'Time',
            'shortLabel' => 'Time',
            'value' => 'summary.duration_label',
            'ariaLabel' => 'Open request timing',
        ],
        [
            'key' => 'memory',
            'section' => null,
            'label' => 'Peak MB',
            'shortLabel' => 'MB',
            'value' => 'summary.peak_memory_mb',
        ],
    ];
@endphp

<div
    data-ndb-mobile-request-metrics="{{ $scope }}"
    role="group"
    aria-label="Request metrics"
    {{ $attributes->class('ndb:mx-auto ndb:grid ndb:w-full ndb:max-w-sm ndb:min-w-0 ndb:flex-1 ndb:grid-cols-[1.75rem_minmax(0,1fr)_1.75rem] ndb:items-stretch ndb:min-[420px]:grid-cols-3') }}
>
    @foreach ($metrics as $metric)
        @if ($metric['section'])
            <button
                type="button"
                data-ndb-mobile-toolbar-metric="{{ $metric['key'] }}"
                data-ndb-mobile-toolbar-metric-scope="{{ $scope }}"
                @click="inspectorOpen ? selectSection(@js($metric['section'])) : openInspector(@js($metric['section']))"
                aria-label="{{ $metric['ariaLabel'] }}"
                class="ndb:relative ndb:flex ndb:min-h-11 ndb:min-w-0 ndb:flex-col ndb:items-center ndb:justify-center ndb:rounded-lg ndb:transition-colors ndb:hover:bg-zinc-100/80 ndb:focus-visible:z-10 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-white/10"
            >
        @else
            <div
                data-ndb-mobile-toolbar-metric="{{ $metric['key'] }}"
                data-ndb-mobile-toolbar-metric-scope="{{ $scope }}"
                class="ndb:relative ndb:flex ndb:min-h-11 ndb:min-w-0 ndb:flex-col ndb:items-center ndb:justify-center"
            >
        @endif
        <span
            data-ndb-mobile-toolbar-summary="{{ $metric['key'] }}"
            class="ndb:block ndb:max-w-full ndb:truncate ndb:text-[11px] ndb:font-bold ndb:leading-4 ndb:tabular-nums"
            x-text="{{ $metric['value'] }}"
        ></span>
        <span
            data-ndb-mobile-toolbar-metric-label="{{ $metric['key'] }}"
            class="ndb:block ndb:max-w-full ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:leading-[14px] ndb:uppercase ndb:tracking-normal ndb:text-zinc-400"
            ><span class="ndb:min-[420px]:hidden">{{ $metric['shortLabel'] }}</span
            ><span class="ndb:hidden ndb:min-[420px]:inline">{{ $metric['label'] }}</span></span>
        @if ($metric['section'])
        </button>
        @else
        </div>
        @endif
    @endforeach
</div>
