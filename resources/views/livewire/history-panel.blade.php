{{-- Renders retained requests, filters, and request comparisons. --}}
<section data-ndb-section-panel="history" hidden wire:key="section-history" class="ndb:space-y-4">
    @if ($discoveredProfileId !== null)
        <div class="ndb:rounded-lg ndb:border ndb:border-indigo-200 ndb:bg-indigo-50/60 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-semibold ndb:text-indigo-800 ndb:dark:border-indigo-900 ndb:dark:bg-indigo-950/30 ndb:dark:text-indigo-200">
            A background request was added to History.
        </div>
    @endif
    @if (! ($summary['is_current_profile'] ?? true))
        <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:border-indigo-200 ndb:bg-indigo-50/50 ndb:px-3.5 ndb:py-3 ndb:dark:border-indigo-950 ndb:dark:bg-indigo-950/20">
            <div class="ndb:min-w-0 ndb:flex-1">
                <p class="ndb:text-xs ndb:font-bold">Inspecting an earlier request</p>
                <p class="ndb:mt-0.5 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                    The compact bar and sections show this selected request.
                </p>
            </div>
            <button
                type="button"
                data-ndb-return-current
                wire:click="returnToCurrent"
                wire:loading.attr="disabled"
                wire:loading.attr="aria-busy"
                class="ndb:rounded-lg ndb:bg-indigo-600 ndb:px-3 ndb:py-2 ndb:text-[11px] ndb:font-bold ndb:text-white ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="returnToCurrent">Back to current request</span
                ><span wire:loading wire:target="returnToCurrent">Returning…</span>
            </button>
        </div>
    @endif
    <details
        data-ndb-history-filters
        class="ndb:group ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/40 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/25"
    >
        <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-2 ndb:px-3.5 ndb:py-3 ndb:text-xs ndb:font-bold ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500">
            <span class="ndb:flex-1">Filters</span
            ><span
                x-show.important="historyPath !== '' || historyMethod !== '' || historyStatus !== ''"
                class="ndb:text-[11px] ndb:text-indigo-600 ndb:dark:text-indigo-300"
                >Active</span
            ><x-newdebugbar::icon
                name="chevron-down"
                class="ndb-details-chevron ndb:size-3.5 ndb:text-zinc-400 ndb:transition"
            />
        </summary>
        <div class="ndb:grid ndb:gap-3 ndb:border-t ndb:border-zinc-200/80 ndb:p-3.5 ndb:md:grid-cols-3 ndb:dark:border-zinc-800">
            <label
                ><span
                    class="ndb:mb-1.5 ndb:block ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                    >Path</span
                ><input
                    data-ndb-history-path
                    x-model="historyPath"
                    @input.debounce.100ms="applyHistoryFilters()"
                    type="search"
                    placeholder="Filter by path"
                    class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:outline-none ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
            /></label>
            <label
                ><span
                    class="ndb:mb-1.5 ndb:block ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                    >Method</span
                ><input
                    data-ndb-history-method
                    x-model="historyMethod"
                    @input.debounce.100ms="applyHistoryFilters()"
                    type="search"
                    placeholder="GET"
                    class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:uppercase ndb:outline-none ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
            /></label>
            <label
                ><span
                    class="ndb:mb-1.5 ndb:block ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                    >Status</span
                ><input
                    data-ndb-history-status
                    x-model="historyStatus"
                    @input.debounce.100ms="applyHistoryFilters()"
                    inputmode="numeric"
                    placeholder="200"
                    class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:outline-none ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
            /></label>
        </div>
    </details>
    <div class="ndb:flex ndb:items-center ndb:gap-1 ndb:overflow-x-auto">
        @foreach (['all' => 'All', 'warning' => 'Warnings', 'clean' => 'Clean'] as $filter => $label)
            <x-newdebugbar::filter-tab
                data-ndb-history-warning="{{ $filter }}"
                @click="setHistoryWarning({{ \Illuminate\Support\Js::from($filter) }})"
                ::aria-pressed="historyWarning === {{ \Illuminate\Support\Js::from($filter) }}"
            >
                {{ $label }}
            </x-newdebugbar::filter-tab>
        @endforeach
        <button
            type="button"
            data-ndb-history-runtime
            @click="toggleHistoryRuntime()"
            :aria-pressed="historyShowRuntime"
            class="ndb:ml-1 ndb:rounded-md ndb:px-2 ndb:py-1.5 ndb:text-[11px] ndb:font-bold ndb:text-zinc-500 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-zinc-400"
            x-text="historyShowRuntime ? 'Hide CLI' : 'Show CLI'"
        ></button>
        <span class="ndb:ml-auto ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400"><span x-text="visibleHistoryCount"></span>
            <span x-text="visibleHistoryCount === 1 ? 'request' : 'requests'"></span
        ></span>
    </div>

    @if ($comparison !== [])
        <div
            data-ndb-comparison
            class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-indigo-200/90 ndb:bg-indigo-50/30 ndb:dark:border-indigo-950 ndb:dark:bg-indigo-950/20"
        >
            <div class="ndb:flex ndb:items-center ndb:gap-3 ndb:border-b ndb:border-indigo-200/70 ndb:px-4 ndb:py-3 ndb:dark:border-indigo-950">
                <div class="ndb:min-w-0 ndb:flex-1">
                    <h3 class="ndb:text-xs ndb:font-bold">Compare requests</h3>
                    <p class="ndb:mt-0.5 ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        {{ $comparison['path'] }}
                    </p>
                    <p class="ndb:mt-1 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:gap-y-1 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
                        <span>Earlier request: {{ $comparison['baseline']['recorded_time'] ?? 'Unknown time' }}</span
                        ><span
                            >{{ ($summary['is_current_profile'] ?? true) ? 'Current request' : 'Selected request' }}: {{ $comparison['current']['recorded_time'] ?? 'Unknown time' }}</span>
                    </p>
                </div>
                <button
                    type="button"
                    wire:click="clearComparison"
                    class="ndb:text-[11px] ndb:font-bold ndb:text-indigo-700 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                >
                    Clear
                </button>
            </div>
            <div class="ndb:grid ndb:grid-cols-2 ndb:divide-x ndb:divide-y ndb:divide-indigo-200/70 ndb:lg:grid-cols-4 ndb:dark:divide-indigo-950">
                @foreach ($comparison['metrics'] as $metric)
                    <div data-ndb-comparison-metric="{{ $metric['key'] }}" class="ndb:px-3 ndb:py-2.5">
                        <p class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                            {{ $metric['label'] }}
                        </p>
                        <p class="ndb:mt-1 ndb:flex ndb:items-baseline ndb:gap-1.5 ndb:text-xs ndb:tabular-nums">
                            <span data-ndb-comparison-baseline class="ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                >{{ $metric['baseline'] }}{{ $metric['unit'] !== '' ? ' '.$metric['unit'] : '' }}</span
                            ><span aria-hidden="true" class="ndb:text-zinc-400">→</span
                            ><strong data-ndb-comparison-current
                                >{{ $metric['current'] }}{{ $metric['unit'] !== '' ? ' '.$metric['unit'] : '' }}</strong>
                        </p>
                        <p
                            data-ndb-comparison-change="{{ $metric['tone'] }}"
                            class="ndb:mt-0.5 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums {{ $metric['tone'] === 'regressed' ? 'ndb:text-amber-700 ndb:dark:text-amber-300' : ($metric['tone'] === 'improved' ? 'ndb:text-emerald-700 ndb:dark:text-emerald-300' : 'ndb:text-zinc-400') }}"
                        >
                            {{ $metric['delta'] === 0.0 ? 'No change' : (($metric['delta'] > 0 ? '+' : '').$metric['delta'].($metric['unit'] !== '' ? ' '.$metric['unit'] : '')) }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div x-ref="historyList" x-init="$nextTick(() => applyHistoryFilters())" class="ndb:space-y-2">
        @foreach ($history as $entry)
            <article
                data-ndb-history-profile="{{ $entry['id'] }}"
                data-path="{{ mb_strtolower($entry['path']) }}"
                data-method="{{ $entry['method'] }}"
                data-status="{{ $entry['status'] }}"
                data-warning="{{ $entry['warning'] ? 'true' : 'false' }}"
                data-runtime="{{ ($entry['profile_type'] ?? 'http') === 'http' ? 'false' : 'true' }}"
                @if ($entry['is_selected']) aria-current="true" @endif
                class="ndb:flex ndb:flex-col ndb:gap-3 ndb:rounded-xl ndb:border ndb:px-3.5 ndb:py-3 ndb:sm:flex-row ndb:sm:items-center {{ $entry['is_selected'] ? 'ndb:border-indigo-300 ndb:bg-indigo-50/55 ndb:ring-1 ndb:ring-indigo-200 ndb:dark:border-indigo-900 ndb:dark:bg-indigo-950/25 ndb:dark:ring-indigo-950' : ($entry['is_current'] ? 'ndb:border-sky-200 ndb:bg-sky-50/35 ndb:dark:border-sky-950 ndb:dark:bg-sky-950/15' : 'ndb:border-zinc-200/90 ndb:bg-white/50 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30') }}"
            >
                <div class="ndb:min-w-0 ndb:flex-1">
                    <div class="ndb:flex ndb:items-center ndb:gap-2">
                        <span class="ndb:rounded-md ndb:bg-indigo-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300">{{ $entry['method'] }}</span>
                        <p class="ndb:truncate ndb:text-xs ndb:font-bold">{{ $entry['path'] }}</p>
                        @if ($entry['is_current'])
                            <span class="ndb:text-[11px] ndb:font-bold ndb:text-sky-700 ndb:dark:text-sky-300">Current</span>
                        @elseif ($entry['is_selected'])
                            <span class="ndb:text-[11px] ndb:font-bold ndb:text-indigo-700 ndb:dark:text-indigo-300">Selected</span>
                        @endif
                    </div>
                    @if (is_string($entry['activity'] ?? null))
                        <p class="ndb:mt-1 ndb:truncate ndb:text-[11px] ndb:font-medium ndb:text-zinc-600 ndb:dark:text-zinc-300">
                            {{ $entry['activity'] }}
                        </p>
                    @endif
                    <div class="ndb:mt-1.5 ndb:flex ndb:flex-wrap ndb:gap-x-4 ndb:gap-y-1 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
                        <time
                            datetime="{{ $entry['recorded_at'] ?? '' }}"
                            >{{ $entry['recorded_time'] ?? 'Unknown time' }}</time
                        ><span>{{ $entry['status'] }}</span
                        ><span>{{ str($entry['request_type'])->replace('_', ' ')->title() }}</span
                        ><span>{{ $entry['duration_ms'] }} ms</span
                        ><span>{{ $entry['query_count'] }} {{ str('query')->plural($entry['query_count']) }}</span
                        ><span
                            >{{ $entry['finding_count'] }} {{ str('finding')->plural($entry['finding_count']) }}</span>
                    </div>
                </div>
                @if (! $entry['is_selected'])
                    <button
                        type="button"
                        data-ndb-open-profile="{{ $entry['id'] }}"
                        wire:click="selectProfile('{{ $entry['id'] }}')"
                        wire:loading.attr="disabled"
                        wire:loading.attr="aria-busy"
                        aria-label="Open {{ $entry['method'] }} {{ $entry['path'] }} request from {{ $entry['recorded_time'] ?? 'unknown time' }}"
                        class="ndb:self-start ndb:rounded-lg ndb:bg-zinc-900 ndb:px-3 ndb:py-2 ndb:text-[11px] ndb:font-bold ndb:text-white ndb:transition ndb:hover:bg-indigo-700 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:opacity-50 ndb:sm:self-center ndb:dark:bg-zinc-100 ndb:dark:text-zinc-900 ndb:dark:hover:bg-indigo-300"
                    >
                        <span wire:loading.remove wire:target="selectProfile">Open</span
                        ><span wire:loading wire:target="selectProfile">Opening…</span>
                    </button>
                @endif
                @if ($entry['comparable'])
                    <button
                        type="button"
                        data-ndb-compare-profile="{{ $entry['id'] }}"
                        wire:click="compareWith('{{ $entry['id'] }}')"
                        wire:loading.attr="disabled"
                        wire:loading.attr="aria-busy"
                        class="ndb:self-start ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:px-3 ndb:py-2 ndb:text-[11px] ndb:font-bold ndb:text-zinc-600 ndb:transition ndb:hover:border-indigo-300 ndb:hover:text-indigo-700 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:opacity-50 ndb:sm:self-center ndb:dark:border-zinc-700 ndb:dark:text-zinc-300 ndb:dark:hover:border-indigo-800 ndb:dark:hover:text-indigo-300"
                    >
                        <span wire:loading.remove wire:target="compareWith">Compare</span
                        ><span wire:loading wire:target="compareWith">Comparing…</span>
                    </button>
                @endif
            </article>
        @endforeach
    </div>
    <div x-show.important="visibleHistoryCount === 0">
        <x-newdebugbar::empty-state label="No requests match these filters." />
    </div>
</section>
