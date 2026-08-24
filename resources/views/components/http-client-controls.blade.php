@props([
    'summary',
    'itemCount',
])

@php
    $retainedCount = (int) ($summary['retained_count'] ?? $itemCount);
    $filters = [
        'all' => ['All', $retainedCount],
        'failed' => ['Failed', (int) ($summary['failed_count'] ?? 0)],
        'slow' => ['Slow', (int) ($summary['slow_count'] ?? 0)],
    ];
@endphp

<p
    data-ndb-http-client-summary
    class="ndb:min-w-0 ndb:text-xs ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
>
    <span data-ndb-http-client-summary-count>
        {{ number_format($retainedCount) }} {{ \Illuminate\Support\Str::plural('request', $retainedCount) }}
    </span>
    <span
        x-show.important="visibleHttpClientCount !== httpClientRequests.length"
        class="ndb:ml-1 ndb:text-[11px] ndb:font-medium ndb:text-zinc-500 ndb:dark:text-zinc-400"
    >
        <span data-ndb-http-client-visible-count x-text="visibleHttpClientCount"></span>
        shown
    </span>
    <span
        data-ndb-http-client-summary-runtime
        class="ndb:mt-0.5 ndb:block ndb:text-[11px] ndb:font-medium ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400"
    >
        {{ number_format((float) ($summary['duration_ms'] ?? 0), 2) }} ms total
    </span>
</p>

<div class="ndb:grid ndb:grid-cols-[minmax(0,1fr)_8.75rem] ndb:gap-2">
    @if ($itemCount >= 5)
        <x-newdebugbar::search-field
            label="Search outbound HTTP requests"
            placeholder="Search requests"
            data-ndb-http-client-search
            x-model="httpClientSearch"
            @input.debounce.100ms="applyHttpClientView()"
        />
    @endif

    <x-newdebugbar::select-field
        label="Sort outbound HTTP requests"
        :span="$itemCount < 5"
        data-ndb-http-client-sort
        x-model="httpClientSort"
        @change="setHttpClientSort($event.target.value)"
    >
        <option value="execution">Oldest</option>
        <option value="duration">Slowest</option>
    </x-newdebugbar::select-field>
</div>

<x-newdebugbar::filter-tabs label="Filter outbound HTTP requests" variant="segmented" class="ndb:w-full">
    @foreach ($filters as $filter => [$label, $count])
        <x-newdebugbar::filter-tab
            variant="segmented"
            data-ndb-http-client-filter="{{ $filter }}"
            @click="setHttpClientFilter({{ \Illuminate\Support\Js::from($filter) }})"
            ::aria-pressed="httpClientFilter === {{ \Illuminate\Support\Js::from($filter) }}"
            class="ndb:h-auto ndb:min-w-0 ndb:flex-1 ndb:justify-center ndb:px-2 ndb:py-1.5"
        >
            <span>{{ $label }}</span>
            <span class="ndb:tabular-nums ndb:text-[11px] ndb:opacity-70">{{ $count }}</span>
        </x-newdebugbar::filter-tab>
    @endforeach
</x-newdebugbar::filter-tabs>
