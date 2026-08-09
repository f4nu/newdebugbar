@props([
    'query',
    'identity',
    'filterable' => false,
    'grouped' => false,
    'expanded' => false,
    'explain' => null,
    'explainError' => null,
])

@php
    $bindings = is_array($query['bindings'] ?? null) ? $query['bindings'] : [];
    $stack = is_array($query['stack'] ?? null) ? $query['stack'] : [];
    $sql = (string) ($query['sql'] ?? '');
    $search = mb_strtolower($sql.' '.json_encode($bindings, JSON_UNESCAPED_SLASHES));
    $defaultTab = $stack !== [] ? 'stack' : 'bindings';
@endphp

<details
    wire:key="query-{{ $identity }}"
    data-execution="{{ $query['execution'] }}"
    data-duration="{{ $query['duration_ms'] }}"
    data-slow="{{ $query['slow'] ? 'true' : 'false' }}"
    @if ($filterable)
        data-ndb-query-item
        data-query-kind="item"
        data-result-count="1"
        data-type="{{ $query['query_type'] }}"
        data-repeated="{{ $query['repeated'] ? 'true' : 'false' }}"
        data-search="{{ $search }}"
    @endif
    @if ($grouped) data-ndb-query-group-execution @endif
    @if ($expanded) open @endif
    @if ($filterable && $query['repeated']) hidden @endif
    x-data="{ queryTab: @js($defaultTab) }"
    @class([
        'ndb:min-w-0',
        'ndb:scroll-mt-16 ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/55 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/40' => ! $grouped,
    ])
>
    <summary class="ndb:flex ndb:min-h-12 ndb:cursor-pointer ndb:list-none ndb:flex-wrap ndb:items-center ndb:gap-x-3 ndb:gap-y-1 ndb:px-4 ndb:py-3 ndb:transition ndb:hover:bg-zinc-50/70 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-zinc-900/60">
        <span data-ndb-query-execution-number class="ndb:text-[10px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-400"
            >#{{ $query['execution'] }}</span>
        <span
            data-ndb-query-connection
            title="{{ $query['connection'] }}"
            class="ndb:max-w-20 ndb:truncate ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-500 ndb:dark:text-zinc-400"
        >{{ $query['connection'] }}</span>
        <span class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $query['query_type'] }}</span>
        @if ($query['slow'])
            <span class="ndb:text-[10px] ndb:font-bold ndb:text-amber-700 ndb:dark:text-amber-300">Slow</span>
        @endif
        @unless ($grouped)
            <code
                title="{{ $query['normalized_sql'] ?? $sql }}"
                class="ndb:hidden ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-[10px] ndb:text-zinc-500 ndb:sm:block ndb:dark:text-zinc-400"
            >{{ $query['normalized_sql'] ?? $sql }}</code>
        @endunless
        <span
            data-ndb-query-percent
            class="ndb:ml-auto ndb:text-[10px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
        >{{ $query['query_time_percent'] }}% of query time</span>
        <span data-ndb-query-duration class="ndb:min-w-12 ndb:text-right ndb:text-xs ndb:font-bold ndb:tabular-nums"
            >{{ $query['duration_ms'] }} ms</span>
        <x-newdebugbar::icon
            name="chevron-down"
            class="ndb-details-chevron ndb:size-3.5 ndb:shrink-0 ndb:text-zinc-400 ndb:transition"
        />
    </summary>

    <div data-ndb-query-details class="ndb:border-t ndb:border-zinc-200/80 ndb:dark:border-zinc-800">
        @unless ($grouped)
            <pre class="ndb-code ndb-scrollbar ndb:rounded-none"><code data-ndb-language="sql">{{ $sql }}</code></pre>
        @endunless

        @if ($bindings !== [] || $stack !== [] || ($query['callsite'] ?? null) !== null)
            <div class="ndb:flex ndb:min-h-12 ndb:flex-wrap ndb:items-center ndb:gap-2 ndb:bg-zinc-50/60 ndb:px-3 ndb:py-2 ndb:dark:bg-zinc-900/35 {{ $grouped ? '' : 'ndb:border-t ndb:border-zinc-200/80 ndb:dark:border-zinc-800' }}">
                @if ($bindings !== [] || $stack !== [])
                    <div
                        role="tablist"
                        aria-label="Query evidence"
                        aria-orientation="horizontal"
                        data-ndb-query-tabs
                        class="ndb:flex ndb:max-w-full ndb:gap-1 ndb:overflow-x-auto ndb:rounded-lg ndb:bg-zinc-200/70 ndb:p-1 ndb:dark:bg-zinc-800/80"
                    >
                        @if ($bindings !== [])
                            <button
                                type="button"
                                role="tab"
                                id="ndb-query-{{ $identity }}-bindings-tab"
                                data-ndb-query-tab="bindings"
                                data-ndb-query-bindings="{{ $identity }}"
                                aria-controls="ndb-query-{{ $identity }}-bindings-panel"
                                :aria-selected="queryTab === 'bindings'"
                                :tabindex="queryTab === 'bindings' ? 0 : -1"
                                @click="queryTab = 'bindings'"
                                @keydown.right.prevent="queryTab = @js($stack !== [] ? 'stack' : 'bindings'); $nextTick(() => $el.parentElement.querySelector('[aria-selected=true]')?.focus())"
                                @keydown.left.prevent="queryTab = @js($stack !== [] ? 'stack' : 'bindings'); $nextTick(() => $el.parentElement.querySelector('[aria-selected=true]')?.focus())"
                                class="ndb:min-h-8 ndb:whitespace-nowrap ndb:rounded-md ndb:border ndb:border-transparent ndb:px-3 ndb:py-1.5 ndb:text-[10px] ndb:font-bold ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500"
                                :class="queryTab === 'bindings'
                                    ? 'ndb:border-zinc-200 ndb:bg-white ndb:text-indigo-700 ndb:shadow-sm ndb:dark:border-zinc-700 ndb:dark:bg-zinc-700 ndb:dark:text-indigo-200'
                                    : 'ndb:text-zinc-600 ndb:hover:bg-white/60 ndb:hover:text-zinc-950 ndb:dark:text-zinc-300 ndb:dark:hover:bg-zinc-700/70 ndb:dark:hover:text-white'"
                            >
                                Bindings
                                <span
                                    data-ndb-query-bindings-count
                                    class="ndb:ml-1.5 ndb:tabular-nums ndb:opacity-60"
                                >{{ count($bindings) }}</span>
                            </button>
                        @endif
                        @if ($stack !== [])
                            <button
                                type="button"
                                role="tab"
                                id="ndb-query-{{ $identity }}-stack-tab"
                                data-ndb-query-tab="stack"
                                aria-controls="ndb-query-{{ $identity }}-stack-panel"
                                :aria-selected="queryTab === 'stack'"
                                :tabindex="queryTab === 'stack' ? 0 : -1"
                                @click="queryTab = 'stack'"
                                @keydown.right.prevent="queryTab = @js($bindings !== [] ? 'bindings' : 'stack'); $nextTick(() => $el.parentElement.querySelector('[aria-selected=true]')?.focus())"
                                @keydown.left.prevent="queryTab = @js($bindings !== [] ? 'bindings' : 'stack'); $nextTick(() => $el.parentElement.querySelector('[aria-selected=true]')?.focus())"
                                class="ndb:min-h-8 ndb:whitespace-nowrap ndb:rounded-md ndb:border ndb:border-transparent ndb:px-3 ndb:py-1.5 ndb:text-[10px] ndb:font-bold ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500"
                                :class="queryTab === 'stack'
                                    ? 'ndb:border-zinc-200 ndb:bg-white ndb:text-indigo-700 ndb:shadow-sm ndb:dark:border-zinc-700 ndb:dark:bg-zinc-700 ndb:dark:text-indigo-200'
                                    : 'ndb:text-zinc-600 ndb:hover:bg-white/60 ndb:hover:text-zinc-950 ndb:dark:text-zinc-300 ndb:dark:hover:bg-zinc-700/70 ndb:dark:hover:text-white'"
                            >
                                Application stack
                                <span
                                    data-ndb-query-stack-count
                                    class="ndb:ml-1.5 ndb:tabular-nums ndb:opacity-60"
                                >{{ count($stack) }}</span>
                            </button>
                        @endif
                    </div>
                @endif

                <x-newdebugbar::query-actions
                    :query="$query"
                    :identity="$identity"
                    :sql="$sql"
                    class="ndb:ml-auto ndb:shrink-0"
                />
            </div>

            @if ($bindings !== [])
                <div
                    x-cloak
                    x-show.important="queryTab === 'bindings'"
                    role="tabpanel"
                    id="ndb-query-{{ $identity }}-bindings-panel"
                    aria-labelledby="ndb-query-{{ $identity }}-bindings-tab"
                >
                    <pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200/80 ndb:dark:border-zinc-800"><code data-ndb-language="json">{{ json_encode($bindings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                </div>
            @endif

            @if ($stack !== [])
                <div
                    x-cloak
                    x-show.important="queryTab === 'stack'"
                    role="tabpanel"
                    id="ndb-query-{{ $identity }}-stack-panel"
                    aria-labelledby="ndb-query-{{ $identity }}-stack-tab"
                    class="ndb:border-t ndb:border-zinc-200/80 ndb:px-4 ndb:dark:border-zinc-800"
                >
                    <ol class="ndb:divide-y ndb:divide-zinc-100 ndb:dark:divide-zinc-800/80">
                        @foreach ($stack as $frame)
                            <li class="ndb:grid ndb:min-w-0 ndb:gap-0.5 ndb:py-2.5 ndb:text-[10px] ndb:sm:grid-cols-[minmax(0,1fr)_minmax(0,0.8fr)] ndb:sm:gap-4">
                                <code
                                    title="{{ $frame['file'] }}:{{ $frame['line'] }}"
                                    class="ndb:min-w-0 ndb:truncate ndb:font-semibold"
                                >{{ $frame['file'] }}:{{ $frame['line'] }}</code>
                                @if (($frame['function'] ?? '') !== '')
                                    <span
                                        title="{{ $frame['function'] }}"
                                        class="ndb:min-w-0 ndb:truncate ndb:text-zinc-400 ndb:sm:text-right"
                                    >{{ $frame['function'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            @elseif (($query['callsite'] ?? null) !== null)
                <p class="ndb:border-t ndb:border-zinc-200/80 ndb:px-4 ndb:py-3 ndb:text-[10px] ndb:dark:border-zinc-800">
                    <span class="ndb:font-semibold ndb:text-zinc-400">Application call site</span>
                    <code class="ndb:ml-2 ndb:font-semibold">{{ $query['callsite']['file'] }}:{{ $query['callsite']['line'] }}</code>
                </p>
            @endif
        @else
            <div class="ndb:flex ndb:justify-end ndb:px-4 {{ $grouped ? '' : 'ndb:border-t ndb:border-zinc-200/80 ndb:dark:border-zinc-800' }}">
                <x-newdebugbar::query-actions :query="$query" :identity="$identity" :sql="$sql" />
            </div>
        @endif

        @if (is_array($explain))
            <div class="ndb:border-t ndb:border-zinc-200 ndb:bg-zinc-50/70 ndb:p-3 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/60">
                <p class="ndb:mb-2 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:gap-y-1 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                    <span>{{ $explain['mode'] }}</span><span>{{ $explain['driver'] }}</span>
                </p>
                <pre class="ndb-code ndb-scrollbar"><code data-ndb-language="json">{{ json_encode($explain['rows'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
            </div>
        @elseif (is_string($explainError))
            <p class="ndb:border-t ndb:border-amber-200 ndb:bg-amber-50/60 ndb:px-3 ndb:py-2 ndb:text-[10px] ndb:font-semibold ndb:text-amber-800 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/20 ndb:dark:text-amber-300">
                {{ $explainError }}
            </p>
        @endif
    </div>
</details>
