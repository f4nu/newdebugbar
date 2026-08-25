@props(['item'])

<button
    type="button"
    data-ndb-http-client-item="{{ $item['execution'] }}"
    data-ndb-execution="{{ $item['execution'] }}"
    data-ndb-duration="{{ is_numeric($item['duration_ms'] ?? null) ? $item['duration_ms'] : -1 }}"
    data-ndb-failed="{{ ($item['failed'] ?? false) ? 'true' : 'false' }}"
    data-ndb-slow="{{ ($item['slow'] ?? false) ? 'true' : 'false' }}"
    data-ndb-search="{{ $item['search'] }}"
    @click="selectHttpClientRequest({{ $item['execution'] }})"
    :aria-pressed="httpClientSelected === {{ $item['execution'] }}"
    :class="httpClientSelected === {{ $item['execution'] }}
        ? 'ndb:bg-indigo-50/65 ndb:dark:bg-indigo-950/20'
        : 'ndb:hover:bg-zinc-50/80 ndb:dark:hover:bg-zinc-900/60'"
    class="ndb:grid ndb:h-auto ndb:w-full ndb:grid-cols-[3rem_minmax(0,1fr)_3.5rem_4.75rem] ndb:items-center ndb:gap-x-2 ndb:px-3 ndb:py-2.5 ndb:text-left ndb:transition-colors ndb:focus-visible:relative ndb:focus-visible:z-10 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
>
    <x-newdebugbar::http-client-method data-ndb-http-client-method>
        {{ $item['method'] }}
    </x-newdebugbar::http-client-method>
    <span :title="{{ \Illuminate\Support\Js::from($item['url']) }}" class="ndb:min-w-0">
        <span
            data-ndb-http-client-host
            class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold ndb:text-zinc-800 ndb:dark:text-zinc-100"
        >{{ $item['host'] }}</span>
        <span class="ndb:mt-0.5 ndb:block ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $item['path'] }}{{ $item['query'] !== null ? '?'.$item['query'] : '' }}</span>
    </span>
    <span
        data-ndb-http-client-list-status
        @class([
            'ndb:w-full ndb:text-right ndb:text-[11px] ndb:font-bold ndb:tabular-nums',
            'ndb:text-red-600 ndb:dark:text-red-300' => $item['failed'] ?? false,
            'ndb:text-zinc-500 ndb:dark:text-zinc-400' => ! ($item['failed'] ?? false),
        ])
    >{{ $item['list_status_label'] }}</span>
    <span data-ndb-http-client-list-duration class="ndb:flex ndb:min-w-0 ndb:items-center ndb:justify-end">
        <span
            @class([
                'ndb:whitespace-nowrap ndb:text-[11px] ndb:font-semibold ndb:tabular-nums',
                'ndb:text-amber-600 ndb:dark:text-amber-300' => $item['slow'] ?? false,
                'ndb:text-zinc-500 ndb:dark:text-zinc-400' => ! ($item['slow'] ?? false),
            ])
        >{{ $item['duration_label'] }}</span>
        @if ($item['slow'] ?? false)
            <span class="ndb:sr-only">Slow request</span>
        @endif
    </span>
</button>
