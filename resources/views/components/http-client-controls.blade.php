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

<x-newdebugbar::inspector-list-controls :show-search="$itemCount >= 5">
    <x-slot:search>
        <x-newdebugbar::search-field
            label="Search outbound HTTP requests"
            placeholder="Search requests"
            data-ndb-http-client-search
            x-model="httpClientSearch"
            @input.debounce.100ms="applyHttpClientView()"
        />
    </x-slot:search>

    <x-slot:filter>
        <x-newdebugbar::select-field
            label="Filter outbound HTTP requests"
            data-ndb-http-client-filter
            x-model="httpClientFilter"
            @change="setHttpClientFilter($event.target.value)"
        >
            @foreach ($filters as $filter => [$label, $count])
                <option value="{{ $filter }}">{{ $label }} ({{ $count }})</option>
            @endforeach
        </x-newdebugbar::select-field>
    </x-slot:filter>
</x-newdebugbar::inspector-list-controls>
