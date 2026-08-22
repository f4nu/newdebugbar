{{-- Renders outbound HTTP requests as a compact request list and evidence detail. --}}
@php
    $httpItems = $section['payload']['items'] ?? [];
    $httpSummary = $section['summary'];
    $httpFilters = [
        'attention' => ['Needs attention', $httpSummary['attention_count'] ?? 0],
        'all' => ['All', $httpSummary['retained_count'] ?? count($httpItems)],
    ];
@endphp

<div data-ndb-http-client x-init="initializeHttpClient(@js($httpItems))" class="ndb:space-y-4">
    <p
        data-ndb-http-client-summary
        class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-x-2 ndb:gap-y-1 ndb:text-xs ndb:font-semibold ndb:text-zinc-600 ndb:dark:text-zinc-300"
    >
        <span>{{ number_format((int) ($httpSummary['retained_count'] ?? count($httpItems))) }} requests</span>
        <span aria-hidden="true" class="ndb:text-zinc-300 ndb:dark:text-zinc-700">•</span>
        <span class="{{ ($httpSummary['failed_count'] ?? 0) > 0 ? 'ndb:text-red-600 ndb:dark:text-red-300' : '' }}">{{ number_format((int) ($httpSummary['failed_count'] ?? 0)) }} failed</span>
        <span aria-hidden="true" class="ndb:text-zinc-300 ndb:dark:text-zinc-700">•</span>
        <span class="{{ ($httpSummary['slow_count'] ?? 0) > 0 ? 'ndb:text-amber-600 ndb:dark:text-amber-300' : '' }}">{{ number_format((int) ($httpSummary['slow_count'] ?? 0)) }} slow</span>
        <span aria-hidden="true" class="ndb:text-zinc-300 ndb:dark:text-zinc-700">•</span>
        <span class="ndb:tabular-nums ndb:text-zinc-400">{{ number_format((float) ($httpSummary['duration_ms'] ?? 0), 2) }} ms cumulative</span>
    </p>

    @if ($httpItems !== [])
        <div
            data-ndb-http-client-workspace
            class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/45 ndb:lg:grid ndb:lg:h-[34rem] ndb:lg:grid-cols-[minmax(22rem,0.82fr)_minmax(0,1.18fr)] ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/35"
        >
            <div class="ndb:flex ndb:min-h-0 ndb:flex-col ndb:border-b ndb:border-zinc-200/90 ndb:lg:border-r ndb:lg:border-b-0 ndb:dark:border-zinc-800">
                <div class="ndb:space-y-3 ndb:border-b ndb:border-zinc-200/90 ndb:p-3 ndb:dark:border-zinc-800">
                    <x-newdebugbar::filter-tabs label="Filter outbound HTTP requests">
                        @foreach ($httpFilters as $filter => [$label, $count])
                            <x-newdebugbar::filter-tab
                                data-ndb-http-client-filter="{{ $filter }}"
                                @click="setHttpClientFilter({{ \Illuminate\Support\Js::from($filter) }})"
                                ::aria-pressed="httpClientFilter === {{ \Illuminate\Support\Js::from($filter) }}"
                            >
                                <span>{{ $label }}</span>
                                <span
                                    data-ndb-http-client-filter-count="{{ $filter }}"
                                    class="ndb:text-[11px] ndb:font-bold ndb:tabular-nums ndb:opacity-65"
                                >{{ $count }}</span>
                            </x-newdebugbar::filter-tab>
                        @endforeach
                    </x-newdebugbar::filter-tabs>

                    <div class="ndb:grid ndb:grid-cols-[minmax(0,1fr)_auto] ndb:gap-2">
                        <label class="ndb:relative ndb:min-w-0">
                            <span class="ndb:sr-only">Search outbound HTTP requests</span>
                            <input
                                data-ndb-http-client-search
                                x-model="httpClientSearch"
                                @input.debounce.100ms="applyHttpClientView()"
                                type="search"
                                placeholder="Search URL or status"
                                class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-9 ndb:pl-3 ndb:text-xs ndb:outline-none ndb:transition ndb:placeholder:text-zinc-400 ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                            />
                            <x-newdebugbar::icon
                                name="search"
                                class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-3 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
                            />
                        </label>
                        <label class="ndb:relative">
                            <span class="ndb:sr-only">Sort outbound HTTP requests</span>
                            <select
                                data-ndb-http-client-sort
                                x-model="httpClientSort"
                                @change="setHttpClientSort($event.target.value)"
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

                    <p class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
                        <span data-ndb-http-client-visible-count x-text="visibleHttpClientCount"></span>
                        <span x-text="visibleHttpClientCount === 1 ? 'request' : 'requests'">requests</span>
                    </p>
                </div>

                <div
                    x-ref="httpClientList"
                    class="ndb-scrollbar ndb:min-h-0 ndb:flex-1 ndb:divide-y ndb:divide-zinc-200/80 ndb:overflow-y-auto ndb:dark:divide-zinc-800"
                >
                    @foreach ($httpItems as $item)
                        @php($location = is_array($item['callsite'] ?? null) ? $item['callsite']['file'].':'.$item['callsite']['line'] : 'Source unavailable')
                        <button
                            type="button"
                            data-ndb-http-client-item="{{ $item['execution'] }}"
                            data-execution="{{ $item['execution'] }}"
                            data-duration="{{ $item['duration_ms'] ?? 0 }}"
                            data-attention="{{ ($item['attention'] ?? false) ? 'true' : 'false' }}"
                            data-search="{{ $item['search'] }}"
                            @click="selectHttpClientRequest({{ $item['execution'] }}, true)"
                            :aria-pressed="httpClientSelected === {{ $item['execution'] }}"
                            :class="httpClientSelected === {{ $item['execution'] }}
                                ? 'ndb:border-l-indigo-500 ndb:bg-indigo-50/75 ndb:dark:bg-indigo-950/25'
                                : 'ndb:border-l-transparent ndb:hover:bg-zinc-50/80 ndb:dark:hover:bg-zinc-900/60'"
                            class="ndb:grid ndb:w-full ndb:grid-cols-[auto_minmax(0,1fr)_auto] ndb:items-start ndb:gap-3 ndb:border-l-2 ndb:px-3 ndb:py-3 ndb:text-left ndb:transition-colors ndb:focus-visible:relative ndb:focus-visible:z-10 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
                        >
                            <span class="ndb:mt-0.5 ndb:inline-flex ndb:w-14 ndb:justify-center ndb:rounded-md ndb:bg-zinc-100 ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:text-zinc-700 ndb:dark:bg-zinc-800 ndb:dark:text-zinc-200">{{ $item['method'] }}</span>
                            <span class="ndb:min-w-0">
                                <span class="ndb:block ndb:truncate ndb:text-xs ndb:font-semibold">{{ $item['host'] }}</span>
                                <span class="ndb:mt-0.5 ndb:block ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $item['path'] }}{{ $item['query'] !== null ? '?'.$item['query'] : '' }}</span>
                                <span class="ndb:mt-1 ndb:block ndb:truncate ndb:text-[11px] ndb:text-zinc-400">{{ $location }}</span>
                            </span>
                            <span class="ndb:text-right">
                                <span class="ndb:block ndb:text-xs ndb:font-bold ndb:tabular-nums {{ ($item['failed'] ?? false) ? 'ndb:text-red-600 ndb:dark:text-red-300' : 'ndb:text-emerald-600 ndb:dark:text-emerald-300' }}">{{ $item['status'] ?? 'Connection error' }}</span>
                                @if ($item['slow'] ?? false)
                                    <span class="ndb:mt-0.5 ndb:block ndb:text-[11px] ndb:font-bold ndb:text-amber-600 ndb:dark:text-amber-300">Slow</span>
                                @endif
                                <span class="ndb:mt-1 ndb:block ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $item['duration_ms'] === null ? '—' : $item['duration_ms'].' ms' }}</span>
                            </span>
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
                class="ndb:flex ndb:min-h-[25rem] ndb:min-w-0 ndb:flex-col ndb:scroll-mt-4 ndb:lg:min-h-0"
            >
                <template x-if="selectedHttpClientRequest">
                    <div class="ndb:flex ndb:min-h-0 ndb:flex-1 ndb:flex-col">
                        <header class="ndb:border-b ndb:border-zinc-200/90 ndb:p-4 ndb:dark:border-zinc-800">
                            <div class="ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:items-center ndb:gap-2">
                                <span
                                    data-ndb-http-client-detail-status-code
                                    :class="selectedHttpClientRequest.failed
                                        ? 'ndb:bg-red-100 ndb:text-red-700 ndb:dark:bg-red-950 ndb:dark:text-red-300'
                                        : selectedHttpClientRequest.slow
                                          ? 'ndb:bg-amber-100 ndb:text-amber-700 ndb:dark:bg-amber-950 ndb:dark:text-amber-300'
                                          : 'ndb:bg-emerald-100 ndb:text-emerald-700 ndb:dark:bg-emerald-950 ndb:dark:text-emerald-300'"
                                    class="ndb:rounded-md ndb:px-2 ndb:py-1 ndb:text-xs ndb:font-bold ndb:tabular-nums"
                                    x-text="selectedHttpClientRequest.status ?? 'Error'"
                                ></span>
                                <h3
                                    data-ndb-http-client-detail-status
                                    class="ndb:min-w-0 ndb:text-base ndb:font-bold ndb:leading-6"
                                    x-text="selectedHttpClientRequest.status_label"
                                ></h3>
                            </div>
                            <div class="ndb:mt-3 ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:items-center ndb:gap-x-2 ndb:gap-y-1 ndb:text-xs">
                                <span
                                    class="ndb:rounded-md ndb:bg-zinc-100 ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:text-zinc-700 ndb:dark:bg-zinc-800 ndb:dark:text-zinc-200"
                                    x-text="selectedHttpClientRequest.method"
                                ></span>
                                <span class="ndb:font-semibold" x-text="selectedHttpClientRequest.host"></span>
                                <span
                                    class="ndb:min-w-0 ndb:truncate ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                    x-text="
                                        selectedHttpClientRequest.path +
                                        (selectedHttpClientRequest.query ? '?' + selectedHttpClientRequest.query : '')
                                    "
                                ></span>
                            </div>
                            <p
                                class="ndb:mt-3 ndb:text-xs ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                x-text="selectedHttpClientRequest.meaning"
                            ></p>
                            <p class="ndb:mt-3 ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-x-2 ndb:gap-y-1 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
                                <span
                                    class="ndb:tabular-nums"
                                    x-text="
                                        selectedHttpClientRequest.duration_ms === null
                                            ? 'No duration'
                                            : selectedHttpClientRequest.duration_ms + ' ms'
                                    "
                                ></span>
                                <span aria-hidden="true">•</span>
                                <span
                                    x-text="
                                        selectedHttpClientRequest.callsite
                                            ? selectedHttpClientRequest.callsite.file +
                                              ':' +
                                              selectedHttpClientRequest.callsite.line
                                            : 'Source unavailable'
                                    "
                                ></span>
                            </p>
                        </header>

                        <div class="ndb:border-b ndb:border-zinc-200/90 ndb:px-4 ndb:py-3 ndb:dark:border-zinc-800">
                            <x-newdebugbar::filter-tabs label="Outbound HTTP request detail">
                                @foreach (['overview' => 'Overview', 'request' => 'Request', 'response' => 'Response', 'stack' => 'Stack'] as $tab => $label)
                                    <x-newdebugbar::filter-tab
                                        data-ndb-http-client-detail-tab="{{ $tab }}"
                                        @click="setHttpClientDetailTab({{ \Illuminate\Support\Js::from($tab) }})"
                                        ::aria-pressed="httpClientDetailTab === {{ \Illuminate\Support\Js::from($tab) }}"
                                    >
                                        {{ $label }}
                                    </x-newdebugbar::filter-tab>
                                @endforeach
                            </x-newdebugbar::filter-tabs>
                        </div>

                        <div class="ndb-scrollbar ndb:min-h-0 ndb:flex-1 ndb:overflow-y-auto ndb:p-4">
                            <div
                                data-ndb-http-client-detail-panel="overview"
                                x-show.important="httpClientDetailTab === 'overview'"
                                class="ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800"
                            >
                                @foreach (['what_happened' => 'What happened', 'why_it_matters' => 'Why it matters', 'check_next' => 'Check next'] as $key => $label)
                                    <div class="ndb:grid ndb:gap-1 ndb:py-3 ndb:first:pt-0 ndb:sm:grid-cols-[9rem_minmax(0,1fr)] ndb:sm:gap-4">
                                        <p class="ndb:text-xs ndb:font-bold">{{ $label }}</p>
                                        <p
                                            class="ndb:text-xs ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                            x-text="selectedHttpClientRequest.{{ $key }}"
                                        ></p>
                                    </div>
                                @endforeach
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
                                data-ndb-http-client-detail-panel="stack"
                                x-show.important="httpClientDetailTab === 'stack'"
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

                        <footer class="ndb:flex ndb:flex-wrap ndb:gap-2 ndb:border-t ndb:border-zinc-200/90 ndb:px-4 ndb:py-3 ndb:dark:border-zinc-800">
                            <button
                                type="button"
                                data-ndb-http-client-copy-curl
                                @click="copyText(selectedHttpClientRequest.curl)"
                                class="ndb:inline-flex ndb:min-h-9 ndb:items-center ndb:gap-2 ndb:rounded-lg ndb:px-2.5 ndb:text-xs ndb:font-bold ndb:text-indigo-600 ndb:transition ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/50"
                            >
                                <x-newdebugbar::icon name="code" class="ndb:size-3.5" />
                                Copy as cURL
                            </button>
                            <button
                                type="button"
                                data-ndb-http-client-copy-url
                                @click="copyText(selectedHttpClientRequest.url)"
                                class="ndb:inline-flex ndb:min-h-9 ndb:items-center ndb:gap-2 ndb:rounded-lg ndb:px-2.5 ndb:text-xs ndb:font-bold ndb:text-indigo-600 ndb:transition ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/50"
                            >
                                <x-newdebugbar::icon name="copy" class="ndb:size-3.5" />
                                Copy URL
                            </button>
                        </footer>
                    </div>
                </template>

                <div
                    x-show.important="! selectedHttpClientRequest"
                    class="ndb:grid ndb:min-h-[25rem] ndb:place-items-center ndb:p-6 ndb:lg:min-h-0"
                >
                    <p class="ndb:text-xs ndb:font-semibold ndb:text-zinc-400">
                        Choose a request to inspect its evidence.
                    </p>
                </div>
            </section>
        </div>
    @else
        <x-newdebugbar::empty-state label="No outbound HTTP requests were captured." />
    @endif
</div>
