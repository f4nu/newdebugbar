@props([
    'section',
    'queryExplains' => [],
    'queryExplainErrors' => [],
])

@php($querySummary = $section['summary'])

<div data-ndb-queries class="ndb:space-y-4">
    <p class="ndb:max-w-3xl ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
        Find repeated work, slow SQL, and the application code that triggered it.
    </p>

    <div class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/55 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/35">
        <dl class="ndb:grid ndb:grid-cols-3 ndb:divide-x ndb:divide-zinc-200/80 ndb:dark:divide-zinc-800">
            @foreach ([
                ['Queries', $querySummary['total_count']],
                ['Query time', $querySummary['total_time_ms'].' ms'],
                ['Request share', $querySummary['request_time_percent'].'%'],
            ] as [$label, $value])
                <div class="ndb:min-w-0 ndb:px-2 ndb:py-3 ndb:text-center ndb:sm:px-4">
                    <dt class="ndb:text-[9px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        {{ $label }}
                    </dt>
                    <dd
                        data-ndb-query-summary-value="{{ str($label)->slug() }}"
                        class="ndb:mt-1 ndb:truncate ndb:text-sm ndb:font-bold ndb:tabular-nums"
                    >
                        {{ $value }}
                    </dd>
                </div>
            @endforeach
        </dl>
    </div>

    <div class="ndb:flex ndb:flex-col ndb:gap-3 ndb:sm:flex-row ndb:sm:items-center ndb:sm:justify-between">
        <div class="ndb:min-w-0">
            <div class="ndb:flex ndb:gap-1 ndb:overflow-x-auto" role="group" aria-label="Filter queries">
                @foreach (['all' => 'All', 'attention' => 'Needs attention', 'read' => 'Reads', 'write' => 'Writes'] as $filter => $label)
                    <button
                        type="button"
                        data-ndb-query-filter="{{ $filter }}"
                        @click="setQueryFilter(@js($filter))"
                        :aria-pressed="queryFilter === @js($filter)"
                        class="ndb:whitespace-nowrap ndb:rounded-lg ndb:border ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-semibold ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
                        :class="queryFilter === @js($filter) ? 'ndb:border-indigo-200 ndb:bg-indigo-50 ndb:text-indigo-700 ndb:dark:border-indigo-900 ndb:dark:bg-indigo-950/60 ndb:dark:text-indigo-300' : 'ndb:border-transparent ndb:bg-zinc-100/70 ndb:text-zinc-500 ndb:hover:bg-zinc-200/70 ndb:hover:text-zinc-950 ndb:dark:bg-zinc-900/70 ndb:dark:text-zinc-400 ndb:dark:hover:bg-zinc-800 ndb:dark:hover:text-white'"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
        <div class="ndb:grid ndb:min-w-0 ndb:grid-cols-[minmax(0,1fr)_auto] ndb:gap-2 ndb:sm:w-[25rem]">
            <label class="ndb:relative ndb:min-w-0">
                <span class="ndb:sr-only">Search queries</span>
                <input
                    data-ndb-query-search
                    x-model="querySearch"
                    @input.debounce.100ms="applyQueryView()"
                    type="search"
                    placeholder="Search SQL"
                    class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-9 ndb:pl-3 ndb:text-xs ndb:outline-none ndb:transition ndb:placeholder:text-zinc-400 ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                />
                <x-newdebugbar::icon
                    name="search"
                    class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-3 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
                />
            </label>
            <label class="ndb:relative">
                <span class="ndb:sr-only">Sort queries</span>
                <select
                    data-ndb-query-sort
                    x-model="querySort"
                    @change="setQuerySort($event.target.value)"
                    class="ndb:h-9 ndb:appearance-none ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-8 ndb:pl-3 ndb:text-xs ndb:font-semibold ndb:outline-none ndb:transition ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                >
                    <option value="execution">Execution order</option>
                    <option value="duration">Slowest first</option>
                </select>
                <x-newdebugbar::icon
                    name="chevron-down"
                    class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-2.5 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
                />
            </label>
        </div>
    </div>

    <p data-ndb-query-result-count class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400">
        <span x-text="visibleQueryCount"></span>
        <span x-text="visibleQueryCount === 1 ? 'result' : 'results'">results</span>
    </p>

    <div x-ref="queryResults" x-init="$nextTick(() => applyQueryView())" class="ndb:space-y-3">
        @foreach ($section['payload']['items'] as $query)
            <x-newdebugbar::query-execution
                :query="$query"
                :identity="'item-'.$query['execution']"
                :explain="$queryExplains[$query['execution']] ?? null"
                :explain-error="$queryExplainErrors[$query['execution']] ?? null"
                filterable
            />
        @endforeach

        @foreach ($section['payload']['repeated_groups'] as $group)
            @php($groupSearch = mb_strtolower($group['sql'].' '.json_encode(array_column($group['executions'], 'bindings'), JSON_UNESCAPED_SLASHES)))
            @php($groupSlow = collect($group['executions'])->contains(fn (array $execution): bool => $execution['slow']))
            <article
                data-ndb-query-group="{{ $group['fingerprint'] }}"
                data-query-kind="group"
                data-result-count="{{ $group['count'] }}"
                data-execution="{{ $group['executions'][0]['execution'] }}"
                data-duration="{{ $group['duration_ms'] }}"
                data-type="{{ $group['query_type'] }}"
                data-slow="{{ $groupSlow ? 'true' : 'false' }}"
                data-search="{{ $groupSearch }}"
                class="ndb:scroll-mt-16 ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/55 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/40"
            >
                <div class="ndb:flex ndb:flex-wrap ndb:items-start ndb:gap-x-6 ndb:gap-y-3 ndb:px-4 ndb:py-4">
                    <div class="ndb:min-w-0 ndb:flex-1">
                        <h3 class="ndb:text-sm ndb:font-bold">Repeated pattern</h3>
                        <p
                            data-ndb-query-group-count
                            class="ndb:mt-1 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400"
                        >
                            {{ $group['count'] }} executions,
                            <span data-ndb-query-group-extra>{{ $group['extra_executions'] }} extra {{ $group['extra_executions'] === 1 ? 'run' : 'runs' }}</span>
                        </p>
                        <pre
                            data-ndb-query-group-pattern
                            class="ndb-scrollbar ndb:mt-3 ndb:overflow-x-auto ndb:font-mono ndb:text-[10px] ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                        ><code data-ndb-language="sql">{{ $group['sql'] }}</code></pre>
                    </div>
                    <div class="ndb:shrink-0 ndb:text-right">
                        @if ($group['likely_n_plus_one'])
                            <p class="ndb:text-[10px] ndb:font-bold ndb:text-amber-700 ndb:dark:text-amber-300">
                                Likely N+1 pattern
                            </p>
                        @endif
                        <p
                            data-ndb-query-group-duration
                            class="ndb:mt-1 ndb:text-xs ndb:font-semibold ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400"
                        >
                            {{ $group['duration_ms'] }} ms total
                        </p>
                    </div>
                </div>
                <div
                    data-ndb-query-group-executions
                    class="ndb:divide-y ndb:divide-zinc-200/80 ndb:border-t ndb:border-zinc-200/80 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800"
                >
                    @foreach ($group['executions'] as $execution)
                        <x-newdebugbar::query-execution
                            :query="$execution"
                            :identity="'group-'.$group['fingerprint'].'-'.$execution['execution']"
                            :explain="$queryExplains[$execution['execution']] ?? null"
                            :explain-error="$queryExplainErrors[$execution['execution']] ?? null"
                            :expanded="$loop->first"
                            grouped
                        />
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>

    <div x-show.important="visibleQueryCount === 0">
        <x-newdebugbar::empty-state label="No queries match these filters." />
    </div>
</div>
