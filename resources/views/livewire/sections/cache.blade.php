{{-- Renders cache operations as a compact list with structured diagnostic detail. --}}
@php
    $cacheItems = array_values($section['payload']['items'] ?? []);
    $cacheSummary = $section['summary'];
    $cacheCount = (int) ($cacheSummary['retained_count'] ?? count($cacheItems));
    $cacheReads = (int) ($cacheSummary['reads'] ?? 0);
    $cacheHits = (int) ($cacheSummary['hits'] ?? 0);
    $cacheMisses = (int) ($cacheSummary['misses'] ?? 0);
    $cacheWrites = (int) ($cacheSummary['writes'] ?? 0);
    $cacheDeletes = (int) ($cacheSummary['forgets'] ?? 0) + (int) ($cacheSummary['flushes'] ?? 0);
    $cacheFailures = (int) ($cacheSummary['failures'] ?? 0);
    $cacheFlushes = (int) ($cacheSummary['flushes'] ?? 0);
    $cacheRepeatedMisses = (int) ($cacheSummary['repeated_miss_count'] ?? 0);
    $cacheHitRate = (float) ($cacheSummary['hit_rate'] ?? 0);
    $cacheDuration = (float) ($cacheSummary['duration_ms'] ?? 0);
    $cacheFilters = [
        'all' => ['All', $cacheCount],
        'reads' => ['Reads', $cacheReads],
        'writes' => ['Writes', (int) ($cacheSummary['filter_counts']['writes'] ?? $cacheWrites)],
        'deletes' => ['Deletes', (int) ($cacheSummary['filter_counts']['deletes'] ?? $cacheDeletes)],
        'failed' => ['Failed', (int) ($cacheSummary['filter_counts']['failed'] ?? $cacheFailures)],
    ];
    $cacheFilters = array_filter(
        $cacheFilters,
        fn (array $filter, string $key): bool => $key === 'all' || $filter[1] > 0,
        ARRAY_FILTER_USE_BOTH,
    );
    $cacheNeedsAttention = $cacheFailures > 0
        || $cacheFlushes > 0
        || $cacheRepeatedMisses > 0;
    $cacheAttentionParts = [];

    if ($cacheFailures > 0) {
        $cacheAttentionParts[] = number_format($cacheFailures).' failed '.\Illuminate\Support\Str::plural('operation', $cacheFailures);
    }

    if ($cacheRepeatedMisses > 0) {
        $cacheAttentionParts[] = number_format($cacheRepeatedMisses).' repeatedly missed '.\Illuminate\Support\Str::plural('key', $cacheRepeatedMisses);
    }

    if ($cacheFlushes > 0) {
        $cacheAttentionParts[] = number_format($cacheFlushes).' store '.\Illuminate\Support\Str::plural('flush', $cacheFlushes);
    }

@endphp

<div
    data-ndb-cache
    x-init="initializeCache(JSON.parse(atob($el.querySelector('[data-ndb-cache-payload]').textContent.trim())))"
    class="ndb:lg:flex ndb:lg:min-h-0 ndb:lg:flex-1 ndb:lg:flex-col"
>
    <script type="application/json" data-ndb-cache-payload>
        {{ base64_encode(json_encode($cacheItems, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE)) }}
    </script>

    @if ($cacheItems !== [])
        <x-newdebugbar::inspector-workspace data-ndb-cache-workspace>
            <div
                :class="cacheDetailOpen ? 'ndb:hidden ndb:lg:flex' : 'ndb:flex'"
                class="ndb:min-h-0 ndb:flex-col ndb:border-b ndb:border-zinc-200/90 ndb:lg:border-r ndb:lg:border-b-0 ndb:dark:border-zinc-800"
            >
                <div class="ndb:space-y-3 ndb:border-b ndb:border-zinc-200/90 ndb:p-3 ndb:dark:border-zinc-800">
                    <div data-ndb-cache-summary class="ndb:flex ndb:items-start ndb:justify-between ndb:gap-4">
                        <div class="ndb:min-w-0">
                            <p class="ndb:text-xs ndb:font-bold ndb:text-zinc-700 ndb:dark:text-zinc-200">
                                {{ number_format($cacheCount) }} {{ \Illuminate\Support\Str::plural('operation', $cacheCount) }}
                                <span
                                    x-show.important="visibleCacheCount !== cacheOperations.length"
                                    class="ndb:ml-1 ndb:text-[11px] ndb:font-medium ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                >
                                    <span data-ndb-cache-visible-count x-text="visibleCacheCount"></span>
                                    shown
                                </span>
                            </p>
                            <p class="ndb:mt-0.5 ndb:text-[11px] ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                {{ number_format($cacheDuration, $cacheDuration < 1 ? 3 : 2) }} ms total
                            </p>
                        </div>
                        @if ($cacheReads > 0)
                            <div class="ndb:shrink-0 ndb:text-right">
                                <p @class([
                                    'ndb:text-xs ndb:font-bold ndb:tabular-nums',
                                    'ndb:text-amber-700 ndb:dark:text-amber-300' => (bool) ($cacheSummary['high_miss_rate'] ?? false),
                                    'ndb:text-zinc-700 ndb:dark:text-zinc-200' => ! (bool) ($cacheSummary['high_miss_rate'] ?? false),
                                ])>
                                    {{ number_format($cacheHitRate, 1) }}% hit rate
                                </p>
                                <p class="ndb:mt-0.5 ndb:text-[11px] ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                    {{ number_format($cacheHits) }} {{ \Illuminate\Support\Str::plural('hit', $cacheHits) }}, {{ number_format($cacheMisses) }} {{ \Illuminate\Support\Str::plural('miss', $cacheMisses) }}
                                </p>
                            </div>
                        @endif
                    </div>

                    @if ($cacheNeedsAttention)
                        <p
                            data-ndb-cache-attention
                            role="status"
                            @class([
                                'ndb:flex ndb:items-start ndb:gap-2 ndb:text-[11px] ndb:font-medium ndb:leading-4',
                                'ndb:text-red-700 ndb:dark:text-red-300' => $cacheFailures > 0,
                                'ndb:text-amber-700 ndb:dark:text-amber-300' => $cacheFailures === 0,
                            ])
                        >
                            <x-newdebugbar::icon name="warning" class="ndb:mt-px ndb:size-3.5 ndb:shrink-0" />
                            <span>
                                <strong class="ndb:font-bold">Cache needs attention.</strong>
                                {{ ucfirst(implode(', ', $cacheAttentionParts)) }}.
                            </span>
                        </p>
                    @endif

                    @if ($cacheCount >= 5)
                        <label class="ndb:relative ndb:block ndb:min-w-0">
                            <span class="ndb:sr-only">Search cache operations</span>
                            <input
                                data-ndb-cache-search
                                x-model="cacheSearch"
                                @input.debounce.100ms="applyCacheView()"
                                type="search"
                                placeholder="Search keys or stores"
                                class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-9 ndb:pl-3 ndb:text-xs ndb:outline-none ndb:transition ndb:placeholder:text-zinc-400 ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                            />
                            <x-newdebugbar::icon
                                name="search"
                                class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-3 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
                            />
                        </label>
                    @endif

                    <div class="ndb:grid ndb:grid-cols-2 ndb:gap-2">
                        <label class="ndb:relative ndb:block">
                            <span class="ndb:sr-only">Filter cache operations</span>
                            <select
                                data-ndb-cache-filter
                                x-model="cacheFilter"
                                @change="setCacheFilter($event.target.value)"
                                class="ndb:h-9 ndb:w-full ndb:appearance-none ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-8 ndb:pl-3 ndb:text-xs ndb:font-semibold ndb:outline-none ndb:transition ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                            >
                                @foreach ($cacheFilters as $filter => [$label, $count])
                                    <option value="{{ $filter }}">{{ $label }} ({{ $count }})</option>
                                @endforeach
                            </select>
                            <x-newdebugbar::icon
                                name="chevron-down"
                                class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-2.5 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
                            />
                        </label>
                        <label class="ndb:relative ndb:block">
                            <span class="ndb:sr-only">Sort cache operations</span>
                            <select
                                data-ndb-cache-sort
                                x-model="cacheSort"
                                @change="setCacheSort($event.target.value)"
                                class="ndb:h-9 ndb:w-full ndb:appearance-none ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-8 ndb:pl-3 ndb:text-xs ndb:font-semibold ndb:outline-none ndb:transition ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                            >
                                <option value="execution">Oldest</option>
                                <option value="duration">Slowest</option>
                                <option value="key">Key</option>
                            </select>
                            <x-newdebugbar::icon
                                name="chevron-down"
                                class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-2.5 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
                            />
                        </label>
                    </div>
                </div>

                <div
                    x-ref="cacheList"
                    data-ndb-cache-list
                    class="ndb-scrollbar ndb:min-h-0 ndb:flex-1 ndb:divide-y ndb:divide-zinc-200/80 ndb:overflow-y-auto ndb:dark:divide-zinc-800"
                >
                    @foreach ($cacheItems as $item)
                        <button
                            type="button"
                            data-ndb-cache-item="{{ $item['execution'] }}"
                            data-ndb-cache-execution="{{ $item['execution'] }}"
                            data-ndb-cache-duration="{{ $item['duration_ms'] ?? 0 }}"
                            data-ndb-cache-timed="{{ is_numeric($item['duration_ms'] ?? null) ? 'true' : 'false' }}"
                            data-ndb-cache-category="{{ $item['category'] }}"
                            data-ndb-cache-failed="{{ ($item['failed'] ?? false) ? 'true' : 'false' }}"
                            data-ndb-cache-key="{{ mb_strtolower($item['key_label']) }}"
                            data-ndb-cache-search-text="{{ $item['search'] }}"
                            @click="selectCacheOperation({{ $item['execution'] }})"
                            :aria-pressed="cacheSelected === {{ $item['execution'] }}"
                            :class="cacheSelected === {{ $item['execution'] }}
                                ? 'ndb:bg-indigo-50/65 ndb:dark:bg-indigo-950/20'
                                : 'ndb:hover:bg-zinc-50/80 ndb:dark:hover:bg-zinc-900/60'"
                            class="ndb:grid ndb:h-auto ndb:w-full ndb:grid-cols-[3.5rem_minmax(0,1fr)_4.75rem] ndb:items-center ndb:gap-x-2 ndb:gap-y-0.5 ndb:px-3 ndb:py-2.5 ndb:text-left ndb:transition-colors ndb:focus-visible:relative ndb:focus-visible:z-10 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
                        >
                            <span class="ndb:row-span-2 ndb:flex ndb:min-w-0 ndb:flex-col ndb:self-center">
                                <span
                                    data-ndb-cache-operation
                                    class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                >{{ $item['operation_label'] }}</span>
                                <span class="ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400 ndb:dark:text-zinc-500">
                                    #{{ $item['execution'] }}
                                </span>
                            </span>
                            <code
                                data-ndb-cache-key
                                :title="{{ \Illuminate\Support\Js::from($item['key_label']) }}"
                                class="ndb:min-w-0 ndb:truncate ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:text-zinc-800 ndb:dark:text-zinc-200"
                            >{{ $item['key_label'] }}</code>
                            <span
                                data-ndb-cache-result
                                @class([
                                    'ndb:w-full ndb:text-right ndb:text-[11px] ndb:font-bold',
                                    'ndb:text-red-600 ndb:dark:text-red-300' => $item['failed'] ?? false,
                                    'ndb:text-amber-600 ndb:dark:text-amber-300' => ! ($item['failed'] ?? false) && in_array($item['result'], ['miss', 'flushed'], true),
                                    'ndb:text-zinc-500 ndb:dark:text-zinc-400' => ! ($item['failed'] ?? false) && ! in_array($item['result'], ['miss', 'flushed'], true),
                                ])
                            >{{ $item['result_label'] }}</span>
                            <span class="ndb:col-start-2 ndb:min-w-0 ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                {{ $item['store_label'] }}
                            </span>
                            <span
                                data-ndb-cache-list-duration
                                class="ndb:col-start-3 ndb:text-right ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400"
                            >{{ $item['duration_label'] }}</span>
                        </button>
                    @endforeach
                </div>

                <div x-show.important="visibleCacheCount === 0" class="ndb:p-3">
                    <x-newdebugbar::empty-state label="No cache operations match these controls." />
                </div>
            </div>

            <section
                x-ref="cacheDetail"
                data-ndb-cache-detail
                aria-live="polite"
                aria-label="Selected cache operation details"
                tabindex="0"
                :class="cacheDetailOpen ? 'ndb:flex' : 'ndb:hidden ndb:lg:flex'"
                class="ndb-scrollbar ndb:min-h-[32rem] ndb:min-w-0 ndb:flex-col ndb:scroll-mt-20 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:lg:min-h-0 ndb:lg:overflow-y-auto"
            >
                <x-newdebugbar::inspector-detail-back
                    data-ndb-cache-detail-back
                    @click="cacheDetailOpen = false"
                    label="Operations"
                />

                <template x-if="selectedCacheOperation">
                    <div class="ndb:flex ndb:flex-col">
                        <x-newdebugbar::cache-header />

                        <div class="ndb:border-b ndb:border-zinc-200/90 ndb:px-4 ndb:py-2.5 ndb:dark:border-zinc-800">
                            <x-newdebugbar::filter-tabs label="Cache operation detail">
                                @foreach (['overview' => ['Overview', 'eye'], 'source' => ['Source', 'activity'], 'raw' => ['Raw', 'code']] as $tab => [$label, $icon])
                                    <x-newdebugbar::filter-tab
                                        data-ndb-cache-detail-tab="{{ $tab }}"
                                        @click="setCacheDetailTab({{ \Illuminate\Support\Js::from($tab) }})"
                                        ::aria-pressed="cacheDetailTab === {{ \Illuminate\Support\Js::from($tab) }}"
                                        aria-label="{{ $label }}"
                                        class="ndb:h-auto"
                                    >
                                        <x-newdebugbar::icon name="{{ $icon }}" size="3.5" class="ndb:sm:hidden" />
                                        <span class="ndb:hidden ndb:sm:inline">{{ $label }}</span>
                                    </x-newdebugbar::filter-tab>
                                @endforeach
                            </x-newdebugbar::filter-tabs>
                        </div>

                        <div class="ndb:p-4">
                            <div
                                data-ndb-cache-detail-panel="overview"
                                x-show.important="cacheDetailTab === 'overview'"
                            >
                                <x-newdebugbar::cache-overview-facts />

                                <dl
                                    x-show.important="
                                        selectedCacheOperation.has_value ||
                                        ['write', 'write_failed'].includes(selectedCacheOperation.operation) ||
                                        selectedCacheOperation.duration_scope === 'batch' ||
                                        selectedCacheOperation.related_count > 1 ||
                                        (selectedCacheOperation.failed && selectedCacheOperation.exception_message)
                                    "
                                    class="ndb:mt-4 ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800"
                                >
                                    <div
                                        x-show.important="selectedCacheOperation.has_value"
                                        class="ndb:grid ndb:gap-1 ndb:py-3 ndb:first:pt-0 ndb:sm:grid-cols-[8rem_minmax(0,1fr)] ndb:sm:gap-4"
                                    >
                                        <dt class="ndb:text-xs ndb:font-bold">Value</dt>
                                        <dd class="ndb:min-w-0 ndb:text-xs ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300">
                                            <pre
                                                class="ndb:whitespace-pre-wrap ndb:break-words ndb:font-mono"
                                                x-text="selectedCacheOperation.value_display"
                                            ></pre>
                                        </dd>
                                    </div>
                                    <div
                                        x-show.important="
                                            ['write', 'write_failed'].includes(selectedCacheOperation.operation)
                                        "
                                        class="ndb:grid ndb:gap-1 ndb:py-3 ndb:sm:grid-cols-[8rem_minmax(0,1fr)] ndb:sm:gap-4"
                                    >
                                        <dt class="ndb:text-xs ndb:font-bold">Lifetime</dt>
                                        <dd
                                            class="ndb:text-xs ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                            x-text="selectedCacheOperation.lifetime_label"
                                        ></dd>
                                    </div>
                                    <div
                                        x-show.important="selectedCacheOperation.duration_scope === 'batch'"
                                        class="ndb:grid ndb:gap-1 ndb:py-3 ndb:sm:grid-cols-[8rem_minmax(0,1fr)] ndb:sm:gap-4"
                                    >
                                        <dt class="ndb:text-xs ndb:font-bold">Timing context</dt>
                                        <dd
                                            class="ndb:text-xs ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                            x-text="
                                                'Shared across a batch of ' +
                                                selectedCacheOperation.batch_size +
                                                ' operations.'
                                            "
                                        ></dd>
                                    </div>
                                    <div
                                        x-show.important="selectedCacheOperation.related_count > 1"
                                        class="ndb:grid ndb:gap-1 ndb:py-3 ndb:sm:grid-cols-[8rem_minmax(0,1fr)] ndb:sm:gap-4"
                                    >
                                        <dt class="ndb:text-xs ndb:font-bold">Related uses</dt>
                                        <dd class="ndb:text-xs ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300">
                                            <span
                                                x-text="
                                                    selectedCacheOperation.related_count +
                                                    ' operations for this key in this store:'
                                                "
                                            ></span>
                                            <span class="ndb:ml-1 ndb:inline-flex ndb:flex-wrap ndb:gap-x-2">
                                                <template
                                                    x-for="execution in selectedCacheOperation.related_executions"
                                                    :key="execution"
                                                >
                                                    <button
                                                        type="button"
                                                        @click="selectRelatedCacheOperation(execution)"
                                                        :aria-label="'Open cache execution ' + execution"
                                                        :aria-current="cacheSelected === execution ? 'true' : null"
                                                        class="ndb:rounded-sm ndb:font-mono ndb:font-semibold ndb:text-indigo-600 ndb:underline ndb:decoration-indigo-300 ndb:underline-offset-2 ndb:hover:text-indigo-800 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:decoration-indigo-700 ndb:dark:hover:text-indigo-200"
                                                        x-text="'#' + execution"
                                                    ></button>
                                                </template>
                                            </span>
                                        </dd>
                                    </div>
                                    <div
                                        x-show.important="
                                            selectedCacheOperation.failed && selectedCacheOperation.exception_message
                                        "
                                        class="ndb:grid ndb:gap-1 ndb:py-3 ndb:sm:grid-cols-[8rem_minmax(0,1fr)] ndb:sm:gap-4"
                                    >
                                        <dt class="ndb:text-xs ndb:font-bold ndb:text-red-700 ndb:dark:text-red-300">
                                            Failure
                                        </dt>
                                        <dd
                                            class="ndb:text-xs ndb:leading-5 ndb:text-red-700 ndb:dark:text-red-300"
                                            x-text="selectedCacheOperation.exception_message"
                                        ></dd>
                                    </div>
                                </dl>

                                <div data-ndb-cache-guidance class="ndb:mt-6 ndb:space-y-5">
                                    <section>
                                        <p class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                            What happened
                                        </p>
                                        <p
                                            class="ndb:mt-1 ndb:max-w-3xl ndb:text-xs ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                            x-text="
                                                selectedCacheOperation.what_happened +
                                                ' ' +
                                                selectedCacheOperation.why_it_matters
                                            "
                                        ></p>
                                    </section>
                                    <section>
                                        <p
                                            :class="selectedCacheOperation.failed
                                                ? 'ndb:text-red-600 ndb:dark:text-red-300'
                                                : selectedCacheOperation.attention
                                                  ? 'ndb:text-amber-600 ndb:dark:text-amber-300'
                                                  : 'ndb:text-zinc-400'"
                                            class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider"
                                        >
                                            Check next
                                        </p>
                                        <p
                                            class="ndb:mt-1 ndb:max-w-3xl ndb:text-xs ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                            x-text="selectedCacheOperation.check_next"
                                        ></p>
                                    </section>
                                </div>
                            </div>

                            <div data-ndb-cache-detail-panel="source" x-show.important="cacheDetailTab === 'source'">
                                <template x-if="(selectedCacheOperation.stack ?? []).length === 0">
                                    <div class="ndb:rounded-xl ndb:border ndb:border-dashed ndb:border-zinc-300 ndb:p-5 ndb:text-center ndb:dark:border-zinc-700">
                                        <p class="ndb:text-xs ndb:font-semibold">No application stack was captured.</p>
                                        <p class="ndb:mt-1 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                            This can happen when call-site collection is off or the framework only
                                            emitted a final event.
                                        </p>
                                    </div>
                                </template>
                                <div class="ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800">
                                    <template x-for="(frame, index) in selectedCacheOperation.stack ?? []" :key="index">
                                        <div class="ndb:py-3 ndb:first:pt-0">
                                            <code
                                                class="ndb:block ndb:break-all ndb:text-[11px] ndb:font-semibold ndb:text-indigo-600 ndb:dark:text-indigo-300"
                                                x-text="frame.file + ':' + frame.line"
                                            ></code>
                                            <p
                                                class="ndb:mt-1 ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                                x-text="frame.function || 'Application call'"
                                            ></p>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div data-ndb-cache-detail-panel="raw" x-show.important="cacheDetailTab === 'raw'">
                                <p class="ndb:mb-2 ndb:max-w-xl ndb:text-[11px] ndb:leading-4 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                    Captured collector fields only. Values are bounded and sensitive fields are
                                    redacted.
                                </p>
                                <pre class="ndb-scrollbar ndb:max-w-full ndb:overflow-x-auto ndb:rounded-lg ndb:bg-zinc-100/75 ndb:p-3 ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-zinc-700 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"><code x-text="formatCachePayload(selectedCacheOperation.raw)"></code></pre>
                            </div>
                        </div>
                    </div>
                </template>

                <div
                    x-show.important="! selectedCacheOperation"
                    class="ndb:grid ndb:min-h-[32rem] ndb:place-items-center ndb:p-6 ndb:lg:min-h-0"
                >
                    <p class="ndb:text-xs ndb:font-semibold ndb:text-zinc-400">
                        Choose an operation to inspect its evidence.
                    </p>
                </div>
            </section>
        </x-newdebugbar::inspector-workspace>
    @else
        <div
            data-ndb-cache-empty
            class="ndb:lg:flex ndb:lg:min-h-0 ndb:lg:flex-1 ndb:lg:items-center ndb:lg:justify-center"
        >
            <div class="ndb:w-full ndb:max-w-lg">
                <x-newdebugbar::empty-state label="No cache operations were captured for this request." />
                <p class="ndb:mt-3 ndb:text-center ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                    Reads, writes, deletes, and store flushes will appear here when Laravel emits them.
                </p>
            </div>
        </div>
    @endif
</div>
