{{-- Renders outbound HTTP requests as a compact request list and evidence detail. --}}
@php
    $httpItems = collect($section['payload']['items'] ?? [])
        ->map(function (array $item): array {
            $callsite = is_array($item['callsite'] ?? null) ? $item['callsite'] : null;
            $status = is_numeric($item['status'] ?? null) ? (string) $item['status'] : null;
            $statusLabel = (string) ($item['status_label'] ?? 'Connection error');

            $item['callsite_label'] = $callsite === null
                ? 'Source unavailable'
                : $callsite['file'].':'.$callsite['line'];
            $item['callsite_short_label'] = $callsite === null
                ? 'Source unavailable'
                : basename(str_replace('\\', '/', $callsite['file'])).':'.$callsite['line'];
            $item['status_reason'] = $status === null
                ? $statusLabel
                : trim(\Illuminate\Support\Str::after($statusLabel, $status));

            return $item;
        })
        ->values()
        ->all();
    $httpSummary = $section['summary'];
    $httpRetainedCount = (int) ($httpSummary['retained_count'] ?? count($httpItems));
    $httpFailedCount = (int) ($httpSummary['failed_count'] ?? 0);
    $httpSlowCount = (int) ($httpSummary['slow_count'] ?? 0);
    $httpFilters = [
        'all' => ['All', $httpRetainedCount],
        'failed' => ['Failed', $httpFailedCount],
        'slow' => ['Slow', $httpSlowCount],
    ];
@endphp

<div
    data-ndb-http-client
    x-init="initializeHttpClient({{ \Illuminate\Support\Js::encode($httpItems) }})"
    class="ndb:space-y-4 ndb:lg:flex ndb:lg:min-h-0 ndb:lg:flex-1 ndb:lg:flex-col ndb:lg:space-y-0"
>
    @if ($httpItems !== [])
        <x-newdebugbar::inspector-workspace data-ndb-http-client-workspace>
            <div
                :class="httpClientDetailOpen ? 'ndb:hidden ndb:lg:flex' : 'ndb:flex'"
                class="ndb:min-h-0 ndb:flex-col ndb:border-b ndb:border-zinc-200/90 ndb:lg:border-r ndb:lg:border-b-0 ndb:dark:border-zinc-800"
            >
                <div class="ndb:space-y-3 ndb:border-b ndb:border-zinc-200/90 ndb:p-3 ndb:dark:border-zinc-800">
                    <p
                        data-ndb-http-client-summary
                        class="ndb:min-w-0 ndb:text-xs ndb:font-semibold ndb:text-zinc-600 ndb:dark:text-zinc-300"
                    >
                        <span data-ndb-http-client-summary-count class="ndb:block">
                            {{ number_format($httpRetainedCount) }} {{ \Illuminate\Support\Str::plural('request', $httpRetainedCount) }}
                        </span>
                        <span
                            data-ndb-http-client-summary-runtime
                            class="ndb:mt-0.5 ndb:block ndb:text-[11px] ndb:font-medium ndb:tabular-nums ndb:text-zinc-400"
                        >
                            {{ number_format((float) ($httpSummary['duration_ms'] ?? 0), 2) }} ms cumulative
                        </span>
                    </p>

                    <x-newdebugbar::filter-tabs label="Filter outbound HTTP requests" class="ndb:w-full">
                        @foreach ($httpFilters as $filter => [$label, $count])
                            <x-newdebugbar::filter-tab
                                data-ndb-http-client-filter="{{ $filter }}"
                                @click="setHttpClientFilter({{ \Illuminate\Support\Js::from($filter) }})"
                                ::aria-pressed="httpClientFilter === {{ \Illuminate\Support\Js::from($filter) }}"
                                class="ndb:flex-1 ndb:justify-center ndb:px-2 ndb:py-1.5"
                            >
                                <span>{{ $label }}</span>
                                <span class="ndb:tabular-nums ndb:text-[11px] ndb:opacity-70">{{ $count }}</span>
                            </x-newdebugbar::filter-tab>
                        @endforeach
                    </x-newdebugbar::filter-tabs>

                    <div class="ndb:space-y-2">
                        @if (count($httpItems) > 5)
                            <label class="ndb:relative ndb:block ndb:min-w-0">
                                <span class="ndb:sr-only">Search outbound HTTP requests</span>
                                <input
                                    data-ndb-http-client-search
                                    x-model="httpClientSearch"
                                    @input.debounce.100ms="applyHttpClientView()"
                                    type="search"
                                    placeholder="Search requests"
                                    class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-9 ndb:pl-3 ndb:text-xs ndb:outline-none ndb:transition ndb:placeholder:text-zinc-400 ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                                />
                                <x-newdebugbar::icon
                                    name="search"
                                    class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-3 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
                                />
                            </label>
                        @endif
                        <label class="ndb:relative ndb:block">
                            <span class="ndb:sr-only">Sort outbound HTTP requests</span>
                            <select
                                data-ndb-http-client-sort
                                x-model="httpClientSort"
                                @change="setHttpClientSort($event.target.value)"
                                class="ndb:h-9 ndb:w-full ndb:appearance-none ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-8 ndb:pl-3 ndb:text-xs ndb:font-semibold ndb:outline-none ndb:transition ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                            >
                                <option value="execution">Oldest first</option>
                                <option value="duration">Slowest first</option>
                            </select>
                            <x-newdebugbar::icon
                                name="chevron-down"
                                class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-2.5 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
                            />
                        </label>
                    </div>
                </div>

                <div
                    x-ref="httpClientList"
                    class="ndb-scrollbar ndb:min-h-0 ndb:flex-1 ndb:divide-y ndb:divide-zinc-200/80 ndb:overflow-y-auto ndb:dark:divide-zinc-800"
                >
                    @foreach ($httpItems as $item)
                        <button
                            type="button"
                            data-ndb-http-client-item="{{ $item['execution'] }}"
                            data-ndb-execution="{{ $item['execution'] }}"
                            data-ndb-duration="{{ $item['duration_ms'] ?? 0 }}"
                            data-ndb-failed="{{ ($item['failed'] ?? false) ? 'true' : 'false' }}"
                            data-ndb-slow="{{ ($item['slow'] ?? false) ? 'true' : 'false' }}"
                            data-ndb-search="{{ $item['search'] }}"
                            @click="selectHttpClientRequest({{ $item['execution'] }})"
                            :aria-pressed="httpClientSelected === {{ $item['execution'] }}"
                            :class="httpClientSelected === {{ $item['execution'] }}
                                ? 'ndb:bg-indigo-50/65 ndb:dark:bg-indigo-950/20'
                                : 'ndb:hover:bg-zinc-50/80 ndb:dark:hover:bg-zinc-900/60'"
                            class="ndb:grid ndb:w-full ndb:grid-cols-[2rem_3rem_minmax(0,1fr)_auto] ndb:items-center ndb:gap-x-2 ndb:gap-y-0.5 ndb:px-3 ndb:py-2.5 ndb:text-left ndb:transition-colors ndb:focus-visible:relative ndb:focus-visible:z-10 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
                        >
                            <span class="ndb:self-center ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400">
                                #{{ str_pad((string) $item['execution'], 2, '0', STR_PAD_LEFT) }}
                            </span>
                            <span
                                data-ndb-http-client-method
                                class="ndb:flex ndb:w-12 ndb:shrink-0 ndb:self-center ndb:items-center ndb:justify-center ndb:rounded-md ndb:bg-zinc-100/70 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-600 ndb:dark:bg-white/10 ndb:dark:text-white"
                            >{{ $item['method'] }}</span>
                            <span
                                data-ndb-http-client-host
                                class="ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-bold"
                            >{{ $item['host'] }}</span>
                            <span
                                data-ndb-http-client-list-outcome
                                class="ndb:flex ndb:items-baseline ndb:justify-end ndb:gap-1.5 ndb:text-[11px] ndb:font-bold"
                            >
                                <span class="ndb:tabular-nums {{ ($item['failed'] ?? false) ? 'ndb:text-red-600 ndb:dark:text-red-300' : 'ndb:text-emerald-600 ndb:dark:text-emerald-300' }}">
                                    {{ $item['status'] ?? 'Error' }}
                                </span>
                                @if ($item['slow'] ?? false)
                                    <span class="ndb:text-amber-600 ndb:dark:text-amber-300">Slow</span>
                                @endif
                            </span>
                            <span class="ndb:col-start-3 ndb:min-w-0 ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $item['path'] }}{{ $item['query'] !== null ? '?'.$item['query'] : '' }}</span>
                            <span
                                data-ndb-http-client-list-duration
                                class="ndb:col-start-4 ndb:text-right ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400"
                            >{{ $item['duration_label'] }}</span>
                        </button>
                    @endforeach
                </div>

                <div x-show.important="visibleHttpClientCount === 0" class="ndb:p-3">
                    <x-newdebugbar::empty-state label="No outbound HTTP requests match these filters." />
                </div>
            </div>

            <section
                x-ref="httpClientDetail"
                data-ndb-http-client-detail
                aria-live="polite"
                aria-label="Selected outbound HTTP request details"
                tabindex="0"
                :class="httpClientDetailOpen ? 'ndb:flex' : 'ndb:hidden ndb:lg:flex'"
                class="ndb-scrollbar ndb:min-h-[32rem] ndb:min-w-0 ndb:flex-col ndb:scroll-mt-20 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:lg:min-h-0 ndb:lg:overflow-y-auto"
            >
                <x-newdebugbar::inspector-detail-back
                    data-ndb-http-client-detail-back
                    @click="httpClientDetailOpen = false"
                    label="Requests"
                />

                <template x-if="selectedHttpClientRequest">
                    <div class="ndb:flex ndb:flex-col">
                        <x-newdebugbar::http-client-header />

                        <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:justify-between ndb:gap-2 ndb:border-b ndb:border-zinc-200/90 ndb:px-4 ndb:py-2.5 ndb:dark:border-zinc-800">
                            <x-newdebugbar::filter-tabs label="Outbound HTTP request detail" class="ndb:min-w-0">
                                @foreach (['overview' => ['Overview', 'eye'], 'request' => ['Request', 'code'], 'response' => ['Response', 'server'], 'source' => ['Source', 'activity']] as $tab => [$label, $icon])
                                    <x-newdebugbar::filter-tab
                                        data-ndb-http-client-detail-tab="{{ $tab }}"
                                        @click="setHttpClientDetailTab({{ \Illuminate\Support\Js::from($tab) }})"
                                        ::aria-pressed="httpClientDetailTab === {{ \Illuminate\Support\Js::from($tab) }}"
                                        aria-label="{{ $label }}"
                                        class="ndb:h-auto"
                                    >
                                        <x-newdebugbar::icon
                                            name="{{ $icon }}"
                                            size="3.5"
                                            data-ndb-http-client-detail-tab-icon="{{ $tab }}"
                                            class="ndb:sm:hidden"
                                        />
                                        <span class="ndb:hidden ndb:sm:inline">{{ $label }}</span>
                                    </x-newdebugbar::filter-tab>
                                @endforeach
                            </x-newdebugbar::filter-tabs>
                        </div>

                        <div class="ndb:p-4">
                            <div
                                data-ndb-http-client-detail-panel="overview"
                                x-show.important="httpClientDetailTab === 'overview'"
                                class="ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800"
                            >
                                <div class="ndb:grid ndb:gap-1 ndb:py-3 ndb:first:pt-0 ndb:sm:grid-cols-[9rem_minmax(0,1fr)] ndb:sm:gap-4">
                                    <p class="ndb:text-xs ndb:font-bold">Outcome</p>
                                    <p
                                        :class="selectedHttpClientRequest.failed
                                            ? 'ndb:text-red-600 ndb:dark:text-red-300'
                                            : selectedHttpClientRequest.slow
                                              ? 'ndb:text-amber-600 ndb:dark:text-amber-300'
                                              : 'ndb:text-emerald-600 ndb:dark:text-emerald-300'"
                                        class="ndb:text-xs ndb:font-semibold ndb:leading-5"
                                        x-text="selectedHttpClientRequest.status_label"
                                    ></p>
                                </div>
                                <div class="ndb:grid ndb:gap-1 ndb:py-3 ndb:sm:grid-cols-[9rem_minmax(0,1fr)] ndb:sm:gap-4">
                                    <p class="ndb:text-xs ndb:font-bold">Response</p>
                                    <p
                                        class="ndb:text-xs ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                        x-text="selectedHttpClientRequest.response_summary"
                                    ></p>
                                </div>
                                <div class="ndb:grid ndb:gap-1 ndb:py-3 ndb:sm:grid-cols-[9rem_minmax(0,1fr)] ndb:sm:gap-4">
                                    <p class="ndb:text-xs ndb:font-bold">Timing</p>
                                    <p
                                        class="ndb:text-xs ndb:leading-5 ndb:tabular-nums ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                        x-text="selectedHttpClientRequest.timing_summary"
                                    ></p>
                                </div>
                                <div class="ndb:grid ndb:gap-1 ndb:py-3 ndb:sm:grid-cols-[9rem_minmax(0,1fr)] ndb:sm:gap-4">
                                    <p class="ndb:text-xs ndb:font-bold">Source</p>
                                    <code
                                        :title="selectedHttpClientRequest.callsite_label"
                                        class="ndb:truncate ndb:text-[11px] ndb:font-medium ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                        x-text="selectedHttpClientRequest.callsite_short_label"
                                    ></code>
                                </div>
                                <div class="ndb:grid ndb:gap-1 ndb:py-3 ndb:last:pb-0 ndb:sm:grid-cols-[9rem_minmax(0,1fr)] ndb:sm:gap-4">
                                    <p class="ndb:text-xs ndb:font-bold">Next</p>
                                    <p
                                        class="ndb:text-xs ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                        x-text="selectedHttpClientRequest.check_next"
                                    ></p>
                                </div>
                            </div>

                            <div
                                data-ndb-http-client-detail-panel="request"
                                x-show.important="httpClientDetailTab === 'request'"
                                class="ndb:space-y-4"
                            >
                                <div>
                                    <p class="ndb:mb-2 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                        Headers
                                    </p>
                                    <pre class="ndb-scrollbar ndb:overflow-x-auto ndb:rounded-lg ndb:bg-zinc-100/75 ndb:p-3 ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-zinc-700 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"><code x-text="formatHttpClientEvidence(selectedHttpClientRequest.request?.headers)"></code></pre>
                                </div>
                                <div>
                                    <p class="ndb:mb-2 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                        Body
                                    </p>
                                    <pre class="ndb-scrollbar ndb:overflow-x-auto ndb:rounded-lg ndb:bg-zinc-100/75 ndb:p-3 ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-zinc-700 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"><code x-text="formatHttpClientEvidence(selectedHttpClientRequest.request?.body)"></code></pre>
                                </div>
                            </div>

                            <div
                                data-ndb-http-client-detail-panel="response"
                                x-show.important="httpClientDetailTab === 'response'"
                                class="ndb:space-y-4"
                            >
                                <div>
                                    <p class="ndb:mb-2 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                        Headers
                                    </p>
                                    <pre class="ndb-scrollbar ndb:overflow-x-auto ndb:rounded-lg ndb:bg-zinc-100/75 ndb:p-3 ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-zinc-700 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"><code x-text="formatHttpClientEvidence(selectedHttpClientRequest.response?.headers)"></code></pre>
                                </div>
                                <div>
                                    <p class="ndb:mb-2 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                        Body
                                    </p>
                                    <pre class="ndb-scrollbar ndb:overflow-x-auto ndb:rounded-lg ndb:bg-zinc-100/75 ndb:p-3 ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-zinc-700 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"><code x-text="formatHttpClientEvidence(selectedHttpClientRequest.response?.body)"></code></pre>
                                </div>
                            </div>

                            <div
                                data-ndb-http-client-detail-panel="source"
                                x-show.important="httpClientDetailTab === 'source'"
                            >
                                <template x-if="(selectedHttpClientRequest.stack ?? []).length === 0">
                                    <p class="ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                        No application stack was captured.
                                    </p>
                                </template>
                                <div class="ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800">
                                    <template
                                        x-for="(frame, index) in selectedHttpClientRequest.stack ?? []"
                                        :key="index"
                                    >
                                        <div class="ndb:py-3 ndb:first:pt-0">
                                            <code
                                                class="ndb:block ndb:text-[11px] ndb:font-semibold ndb:text-indigo-600 ndb:dark:text-indigo-300"
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
                        </div>
                    </div>
                </template>

                <div
                    x-show.important="! selectedHttpClientRequest"
                    class="ndb:grid ndb:min-h-[32rem] ndb:place-items-center ndb:p-6 ndb:lg:min-h-0"
                >
                    <p class="ndb:text-xs ndb:font-semibold ndb:text-zinc-400">
                        Choose a request to inspect its evidence.
                    </p>
                </div>
            </section>
        </x-newdebugbar::inspector-workspace>
    @else
        <x-newdebugbar::empty-state label="No outbound HTTP requests were captured." />
    @endif
</div>
