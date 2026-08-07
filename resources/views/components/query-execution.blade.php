@props([
    'query',
    'identity',
    'filterable' => false,
    'grouped' => false,
    'explain' => null,
    'explainError' => null,
])

@php
    $search = mb_strtolower(($query['sql'] ?? '').' '.json_encode($query['bindings'] ?? [], JSON_UNESCAPED_SLASHES));
@endphp

<article
    wire:key="query-{{ $identity }}"
    @if ($filterable)
        data-ndb-query-item
        data-execution="{{ $query['execution'] }}"
        data-duration="{{ $query['duration_ms'] }}"
        data-type="{{ $query['query_type'] }}"
        data-slow="{{ $query['slow'] ? 'true' : 'false' }}"
        data-search="{{ $search }}"
    @endif
    class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/60 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/40 {{ $filterable ? 'ndb:scroll-mt-16' : '' }}"
>
    <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-x-3 ndb:gap-y-1 ndb:bg-zinc-50/70 ndb:px-3 ndb:py-2 ndb:dark:bg-zinc-900/70 {{ $grouped ? '' : 'ndb:border-b ndb:border-zinc-200/80 ndb:dark:border-zinc-800' }}">
        <span data-ndb-query-execution-number class="ndb:text-[10px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-400">#{{ $query['execution'] }}</span>
        <span data-ndb-query-connection class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $query['connection'] }}</span>
        <span class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $query['query_type'] }}</span>
        @if ($query['repeated'] && ! $grouped)
            <span data-ndb-query-repeat-count class="ndb:text-[10px] ndb:font-bold ndb:text-indigo-600 ndb:dark:text-indigo-300">Repeated {{ $query['repeated_count'] }}×</span>
        @endif
        @if ($query['slow'])
            <span class="ndb:text-[10px] ndb:font-bold ndb:text-amber-700 ndb:dark:text-amber-300">Slow</span>
        @endif
        <div class="ndb:ml-auto ndb:flex ndb:items-center ndb:gap-3">
            <span data-ndb-query-percent class="ndb:text-[10px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400">{{ $query['query_time_percent'] }}% query time</span>
            <span data-ndb-query-duration class="ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $query['duration_ms'] }} ms</span>
            @unless ($grouped)<button type="button" @click="copyText(@js($query['sql']))" class="ndb:inline-flex ndb:size-7 ndb:items-center ndb:justify-center ndb:rounded-lg ndb:text-zinc-500 ndb:transition ndb:hover:bg-zinc-100 ndb:hover:text-zinc-950 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-zinc-400 ndb:dark:hover:bg-zinc-800 ndb:dark:hover:text-white" aria-label="Copy query {{ $query['execution'] }}" title="Copy query"><x-new-debug-bar::icon name="copy" class="ndb:size-3.5" /></button>@endunless
            @if (($query['runnable_available'] ?? false) && is_string($query['runnable_sql'] ?? null))
                <button type="button" @click="copyText(@js($query['runnable_sql']))" class="ndb:rounded-md ndb:px-2 ndb:py-1 ndb:text-[10px] ndb:font-bold ndb:text-indigo-600 ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950" aria-label="Copy runnable query {{ $query['execution'] }}">Copy runnable</button>
                <button type="button" wire:click="explainQuery({{ $query['execution'] }})" wire:loading.attr="disabled" wire:target="explainQuery({{ $query['execution'] }})" class="ndb:rounded-md ndb:px-2 ndb:py-1 ndb:text-[10px] ndb:font-bold ndb:text-indigo-600 ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:opacity-50 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950">Explain</button>
            @endif
        </div>
    </div>
    @unless ($grouped)<pre class="ndb-code ndb-scrollbar ndb:rounded-none"><code data-ndb-language="sql">{{ $query['sql'] }}</code></pre>@endunless
    @if (is_array($explain))
        <div class="ndb:border-t ndb:border-zinc-200 ndb:bg-zinc-50/70 ndb:p-3 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/60">
            <p class="ndb:mb-2 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:gap-y-1 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"><span>{{ $explain['mode'] }}</span><span>{{ $explain['driver'] }}</span></p>
            <pre class="ndb-code ndb-scrollbar"><code data-ndb-language="json">{{ json_encode($explain['rows'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
        </div>
    @elseif (is_string($explainError))
        <p class="ndb:border-t ndb:border-amber-200 ndb:bg-amber-50/60 ndb:px-3 ndb:py-2 ndb:text-[10px] ndb:font-semibold ndb:text-amber-800 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/20 ndb:dark:text-amber-300">{{ $explainError }}</p>
    @endif
    @if (($query['callsite'] ?? null) !== null)
        <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-x-3 ndb:gap-y-1 ndb:border-t ndb:border-zinc-200 ndb:bg-white/60 ndb:px-3 ndb:py-2 ndb:text-[10px] ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/40">
            <span class="ndb:font-semibold ndb:text-zinc-400">Application call site</span>
            <button type="button" @click="copyText(@js($query['callsite']['file'].':'.$query['callsite']['line']))" class="ndb:min-w-0 ndb:truncate ndb:font-mono ndb:font-semibold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300">{{ $query['callsite']['file'] }}:{{ $query['callsite']['line'] }}</button>
            @if (is_string($query['callsite']['editor_url'] ?? null))<a href="{{ $query['callsite']['editor_url'] }}" class="ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300">Open in editor</a>@endif
        </div>
    @endif
    @if (($query['bindings'] ?? []) !== [] || ($query['stack'] ?? []) !== [])
        <div class="ndb:grid ndb:border-t ndb:border-zinc-200 ndb:bg-zinc-100/70 ndb:text-zinc-700 ndb:sm:grid-cols-2 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/70 ndb:dark:text-zinc-300">
            @if (($query['bindings'] ?? []) !== [])
                <details data-ndb-query-bindings="{{ $identity }}" class="ndb:group ndb:border-zinc-200 ndb:sm:border-r ndb:dark:border-zinc-800">
                    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-2 ndb:px-3 ndb:py-2 ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-500 ndb:dark:text-zinc-400"><span>Bindings</span><span data-ndb-query-bindings-count class="ndb:text-[9px] ndb:font-bold ndb:tabular-nums">{{ count($query['bindings']) }}</span><x-new-debug-bar::icon name="chevron-down" class="ndb-details-chevron ndb:ml-auto ndb:size-3.5 ndb:transition" /></summary>
                    <pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="json">{{ json_encode($query['bindings'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                </details>
            @endif
            @if (($query['stack'] ?? []) !== [])
                <details class="ndb:group">
                    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-2 ndb:px-3 ndb:py-2 ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-500 ndb:dark:text-zinc-400"><span>Application stack</span><span data-ndb-query-stack-count class="ndb:text-[9px] ndb:font-bold ndb:tabular-nums">{{ count($query['stack']) }}</span><x-new-debug-bar::icon name="chevron-down" class="ndb-details-chevron ndb:ml-auto ndb:size-3.5 ndb:transition" /></summary>
                    <ol class="ndb:space-y-2 ndb:border-t ndb:border-zinc-200 ndb:bg-white/60 ndb:p-3 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/40">@foreach ($query['stack'] as $frame)<li class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-2 ndb:text-[10px]"><code class="ndb:min-w-0 ndb:flex-1 ndb:truncate">{{ $frame['file'] }}:{{ $frame['line'] }}</code>@if (is_string($frame['editor_url'] ?? null))<a href="{{ $frame['editor_url'] }}" class="ndb:shrink-0 ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300">Open</a>@endif</li>@endforeach</ol>
                </details>
            @endif
        </div>
    @endif
</article>
