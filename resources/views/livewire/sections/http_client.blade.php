{{-- Renders outbound HTTP requests as a stable request list and structured evidence detail. --}}
@php
    $httpItems = collect($section['payload']['items'] ?? [])
        ->map(function (array $item): array {
            $callsite = is_array($item['callsite'] ?? null) ? $item['callsite'] : null;

            $item['callsite_label'] = $callsite === null
                ? 'Source unavailable'
                : $callsite['file'].':'.$callsite['line'];
            $item['callsite_short_label'] = $callsite === null
                ? 'Source unavailable'
                : basename(str_replace('\\', '/', $callsite['file'])).':'.$callsite['line'];

            return $item;
        })
        ->values()
        ->all();
    $httpSummary = $section['summary'] ?? [];
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
    x-init="
        initializeHttpClient(JSON.parse(atob($el.querySelector('[data-ndb-http-client-payload]').textContent.trim())))
    "
    class="ndb:lg:flex ndb:lg:min-h-0 ndb:lg:flex-1 ndb:lg:flex-col"
>
    <script type="application/json" data-ndb-http-client-payload>
        {{ base64_encode(json_encode($httpItems, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE)) }}
    </script>

    @if ($httpItems !== [])
        <x-newdebugbar::inspector-workspace frame="top" data-ndb-http-client-workspace>
            <div
                :class="httpClientDetailOpen ? 'ndb:hidden ndb:lg:flex' : 'ndb:flex'"
                class="ndb:min-h-0 ndb:flex-col ndb:border-b ndb:border-zinc-200/90 ndb:lg:border-r ndb:lg:border-b-0 ndb:dark:border-zinc-800"
            >
                <div class="ndb:space-y-3 ndb:border-b ndb:border-zinc-200/90 ndb:p-3 ndb:dark:border-zinc-800">
                    <p
                        data-ndb-http-client-summary
                        class="ndb:min-w-0 ndb:text-xs ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                    >
                        <span data-ndb-http-client-summary-count>
                            {{ number_format($httpRetainedCount) }} {{ \Illuminate\Support\Str::plural('request', $httpRetainedCount) }}
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
                            {{ number_format((float) ($httpSummary['duration_ms'] ?? 0), 2) }} ms total
                        </span>
                    </p>

                    <div class="ndb:grid ndb:grid-cols-[minmax(0,1fr)_8.75rem] ndb:gap-2">
                        @if (count($httpItems) >= 5)
                            <label class="ndb:relative ndb:block ndb:min-w-0">
                                <span class="ndb:sr-only">Search outbound HTTP requests</span>
                                <input
                                    data-ndb-http-client-search
                                    x-model="httpClientSearch"
                                    @input.debounce.100ms="applyHttpClientView()"
                                    type="search"
                                    placeholder="Search requests"
                                    class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-3 ndb:pl-8 ndb:text-xs ndb:outline-none ndb:transition ndb:placeholder:text-zinc-400 ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                                />
                                <x-newdebugbar::icon
                                    name="search"
                                    size="4"
                                    class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:left-2.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
                                />
                            </label>
                        @endif

                        <label @class(['ndb:relative ndb:block', 'ndb:col-span-2' => count($httpItems) < 5])>
                            <span class="ndb:sr-only">Sort outbound HTTP requests</span>
                            <select
                                data-ndb-http-client-sort
                                x-model="httpClientSort"
                                @change="setHttpClientSort($event.target.value)"
                                class="ndb:h-9 ndb:w-full ndb:appearance-none ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-8 ndb:pl-3 ndb:text-xs ndb:font-semibold ndb:outline-none ndb:transition ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                            >
                                <option value="execution">Oldest</option>
                                <option value="duration">Slowest</option>
                            </select>
                            <x-newdebugbar::icon
                                name="chevron-down"
                                class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-2.5 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
                            />
                        </label>
                    </div>

                    <x-newdebugbar::filter-tabs
                        label="Filter outbound HTTP requests"
                        variant="segmented"
                        class="ndb:w-full"
                    >
                        @foreach ($httpFilters as $filter => [$label, $count])
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
                </div>

                <div
                    x-ref="httpClientList"
                    data-ndb-http-client-list
                    class="ndb-scrollbar ndb:min-h-0 ndb:flex-1 ndb:divide-y ndb:divide-zinc-200/80 ndb:overflow-y-auto ndb:dark:divide-zinc-800"
                >
                    @foreach ($httpItems as $item)
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
                            <span
                                data-ndb-http-client-method
                                class="ndb:flex ndb:w-12 ndb:shrink-0 ndb:items-center ndb:justify-center ndb:rounded-md ndb:bg-zinc-100/70 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-600 ndb:dark:bg-white/10 ndb:dark:text-zinc-200"
                            >{{ $item['method'] }}</span>
                            <span :title="{{ \Illuminate\Support\Js::from($item['url']) }}" class="ndb:min-w-0">
                                <span
                                    data-ndb-http-client-host
                                    class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold ndb:text-zinc-800 ndb:dark:text-zinc-100"
                                >{{ $item['host'] }}</span>
                                <code class="ndb:mt-0.5 ndb:block ndb:truncate ndb:font-mono ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $item['path'] }}{{ $item['query'] !== null ? '?'.$item['query'] : '' }}</code>
                            </span>
                            <span
                                data-ndb-http-client-list-status
                                @class([
                                    'ndb:w-full ndb:text-right ndb:text-[11px] ndb:font-bold ndb:tabular-nums',
                                    'ndb:text-red-600 ndb:dark:text-red-300' => $item['failed'] ?? false,
                                    'ndb:text-zinc-500 ndb:dark:text-zinc-400' => ! ($item['failed'] ?? false),
                                ])
                            >{{ $item['list_status_label'] }}</span>
                            <span
                                data-ndb-http-client-list-duration
                                class="ndb:flex ndb:min-w-0 ndb:items-center ndb:justify-end"
                            >
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
                    @endforeach
                </div>

                <div x-show.important="visibleHttpClientCount === 0" class="ndb:p-3">
                    <x-newdebugbar::empty-state label="No outbound HTTP requests match these controls." />
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

                        <div class="ndb:border-b ndb:border-zinc-200/90 ndb:px-4 ndb:py-2.5 ndb:dark:border-zinc-800">
                            <x-newdebugbar::filter-tabs label="Outbound HTTP request detail" class="ndb:min-w-0">
                                @foreach (['response' => 'Response', 'request' => 'Request', 'source' => 'Source'] as $tab => $label)
                                    <x-newdebugbar::filter-tab
                                        data-ndb-http-client-detail-tab="{{ $tab }}"
                                        @click="setHttpClientDetailTab({{ \Illuminate\Support\Js::from($tab) }})"
                                        ::aria-pressed="httpClientDetailTab === {{ \Illuminate\Support\Js::from($tab) }}"
                                        class="ndb:h-auto"
                                    >
                                        {{ $label }}
                                    </x-newdebugbar::filter-tab>
                                @endforeach
                            </x-newdebugbar::filter-tabs>
                        </div>

                        <div class="ndb:p-4">
                            <div
                                data-ndb-http-client-detail-panel="request"
                                x-show.important="httpClientDetailTab === 'request'"
                            >
                                <div class="ndb:flex ndb:flex-col ndb:items-stretch ndb:justify-between ndb:gap-3 ndb:border-b ndb:border-zinc-200/90 ndb:pb-4 ndb:sm:flex-row ndb:sm:items-end ndb:dark:border-zinc-800">
                                    <dl
                                        data-ndb-http-client-request-facts
                                        class="ndb:grid ndb:min-w-0 ndb:flex-1 ndb:grid-cols-2 ndb:gap-x-5 ndb:gap-y-3"
                                    >
                                        <div class="ndb:min-w-0">
                                            <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">
                                                Host
                                            </dt>
                                            <dd
                                                data-ndb-http-client-detail-host
                                                :title="selectedHttpClientRequest.host"
                                                class="ndb:mt-0.5 ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                                x-text="selectedHttpClientRequest.host"
                                            ></dd>
                                        </div>
                                        <div class="ndb:min-w-0">
                                            <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">
                                                Request body
                                            </dt>
                                            <dd
                                                class="ndb:mt-0.5 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                                x-text="selectedHttpClientRequest.request_body_size_label"
                                            ></dd>
                                        </div>
                                    </dl>
                                    <button
                                        type="button"
                                        data-ndb-http-client-copy-curl
                                        @click="copyText(selectedHttpClientRequest.curl)"
                                        class="ndb:inline-flex ndb:h-auto ndb:min-h-9 ndb:items-center ndb:self-start ndb:gap-1.5 ndb:rounded-lg ndb:px-2 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:transition ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:sm:self-auto ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/50"
                                    >
                                        <x-newdebugbar::icon name="code" size="3.5" />
                                        Copy safe cURL
                                    </button>
                                </div>

                                <div class="ndb:mt-5 ndb:space-y-5">
                                    <section>
                                        <h4 class="ndb:mb-2 ndb:text-xs ndb:font-bold">Headers</h4>
                                        <pre class="ndb-scrollbar ndb:overflow-x-auto ndb:rounded-lg ndb:bg-zinc-100/75 ndb:p-3 ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-zinc-700 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"><code x-text="formatHttpClientEvidence(selectedHttpClientRequest.request?.headers)"></code></pre>
                                    </section>
                                    <section>
                                        <h4 class="ndb:mb-2 ndb:text-xs ndb:font-bold">Body</h4>
                                        <pre class="ndb-scrollbar ndb:overflow-x-auto ndb:rounded-lg ndb:bg-zinc-100/75 ndb:p-3 ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-zinc-700 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"><code x-text="formatHttpClientEvidence(selectedHttpClientRequest.request?.body)"></code></pre>
                                    </section>
                                </div>
                            </div>

                            <div
                                data-ndb-http-client-detail-panel="response"
                                x-show.important="httpClientDetailTab === 'response'"
                            >
                                <dl
                                    data-ndb-http-client-response-facts
                                    class="ndb:grid ndb:grid-cols-2 ndb:gap-x-5 ndb:gap-y-3 ndb:border-b ndb:border-zinc-200/90 ndb:pb-4 ndb:dark:border-zinc-800 ndb:sm:grid-cols-4"
                                >
                                    <div class="ndb:min-w-0">
                                        <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">
                                            Status
                                        </dt>
                                        <dd
                                            data-ndb-http-client-detail-status
                                            :class="selectedHttpClientRequest.failed
                                                ? 'ndb:text-red-700 ndb:dark:text-red-300'
                                                : 'ndb:text-zinc-700 ndb:dark:text-zinc-200'"
                                            class="ndb:mt-0.5 ndb:truncate ndb:text-[11px] ndb:font-bold"
                                            x-text="selectedHttpClientRequest.status_label"
                                        ></dd>
                                    </div>
                                    <div class="ndb:min-w-0">
                                        <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">
                                            Runtime
                                        </dt>
                                        <dd
                                            data-ndb-http-client-detail-runtime
                                            :class="selectedHttpClientRequest.slow
                                                ? 'ndb:text-amber-700 ndb:dark:text-amber-300'
                                                : 'ndb:text-zinc-700 ndb:dark:text-zinc-200'"
                                            class="ndb:mt-0.5 ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:tabular-nums"
                                            x-text="selectedHttpClientRequest.duration_label"
                                        ></dd>
                                    </div>
                                    <div x-show.important="selectedHttpClientRequest.response" class="ndb:min-w-0">
                                        <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">
                                            Response body
                                        </dt>
                                        <dd
                                            class="ndb:mt-0.5 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                            x-text="selectedHttpClientRequest.response_body_size_label"
                                        ></dd>
                                    </div>
                                    <div
                                        x-show.important="selectedHttpClientRequest.redirect_location"
                                        class="ndb:min-w-0"
                                    >
                                        <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">
                                            Redirect to
                                        </dt>
                                        <dd
                                            :title="selectedHttpClientRequest.redirect_location"
                                            class="ndb:mt-0.5 ndb:truncate ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                            x-text="selectedHttpClientRequest.redirect_location"
                                        ></dd>
                                    </div>
                                </dl>

                                <dl
                                    data-ndb-http-client-failure
                                    x-show.important="selectedHttpClientRequest.failed"
                                    class="ndb:mt-4 ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800"
                                >
                                    <div class="ndb:grid ndb:gap-1 ndb:py-3 ndb:first:pt-0 ndb:sm:grid-cols-[8rem_minmax(0,1fr)] ndb:sm:gap-4">
                                        <dt class="ndb:text-xs ndb:font-bold ndb:text-red-700 ndb:dark:text-red-300">
                                            Failure
                                        </dt>
                                        <dd
                                            class="ndb:text-xs ndb:leading-5 ndb:text-red-700 ndb:dark:text-red-300"
                                            x-text="selectedHttpClientRequest.response_summary"
                                        ></dd>
                                    </div>
                                </dl>

                                <template x-if="selectedHttpClientRequest.response">
                                    <div class="ndb:mt-5 ndb:space-y-5">
                                        <section>
                                            <h4 class="ndb:mb-2 ndb:text-xs ndb:font-bold">Headers</h4>
                                            <pre class="ndb-scrollbar ndb:overflow-x-auto ndb:rounded-lg ndb:bg-zinc-100/75 ndb:p-3 ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-zinc-700 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"><code x-text="formatHttpClientEvidence(selectedHttpClientRequest.response?.headers)"></code></pre>
                                        </section>
                                        <section>
                                            <h4 class="ndb:mb-2 ndb:text-xs ndb:font-bold">Body</h4>
                                            <pre class="ndb-scrollbar ndb:overflow-x-auto ndb:rounded-lg ndb:bg-zinc-100/75 ndb:p-3 ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-zinc-700 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"><code x-text="formatHttpClientEvidence(selectedHttpClientRequest.response?.body)"></code></pre>
                                        </section>
                                    </div>
                                </template>

                                <template x-if="! selectedHttpClientRequest.response">
                                    <div class="ndb:mt-5">
                                        <p class="ndb:text-xs ndb:font-semibold">No HTTP response was received.</p>
                                        <dl
                                            x-show.important="
                                                selectedHttpClientRequest.exception_class ||
                                                selectedHttpClientRequest.exception_message
                                            "
                                            class="ndb:mt-4 ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800"
                                        >
                                            <div
                                                x-show.important="selectedHttpClientRequest.exception_class"
                                                class="ndb:grid ndb:gap-1 ndb:py-3 ndb:first:pt-0 ndb:sm:grid-cols-[8rem_minmax(0,1fr)] ndb:sm:gap-4"
                                            >
                                                <dt class="ndb:text-xs ndb:font-bold">Exception</dt>
                                                <dd
                                                    class="ndb:break-all ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                                    x-text="selectedHttpClientRequest.exception_class"
                                                ></dd>
                                            </div>
                                            <div
                                                x-show.important="selectedHttpClientRequest.exception_message"
                                                class="ndb:grid ndb:gap-1 ndb:py-3 ndb:sm:grid-cols-[8rem_minmax(0,1fr)] ndb:sm:gap-4"
                                            >
                                                <dt class="ndb:text-xs ndb:font-bold ndb:text-red-700 ndb:dark:text-red-300">
                                                    Message
                                                </dt>
                                                <dd
                                                    class="ndb:text-xs ndb:leading-5 ndb:text-red-700 ndb:dark:text-red-300"
                                                    x-text="selectedHttpClientRequest.exception_message"
                                                ></dd>
                                            </div>
                                        </dl>
                                    </div>
                                </template>
                            </div>

                            <div
                                data-ndb-http-client-detail-panel="source"
                                x-show.important="httpClientDetailTab === 'source'"
                            >
                                <dl
                                    data-ndb-http-client-source-facts
                                    class="ndb:grid ndb:grid-cols-1 ndb:border-b ndb:border-zinc-200/90 ndb:pb-4 ndb:dark:border-zinc-800"
                                >
                                    <div class="ndb:min-w-0">
                                        <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">
                                            Source
                                        </dt>
                                        <dd class="ndb:mt-0.5 ndb:min-w-0">
                                            <code
                                                data-ndb-http-client-detail-source
                                                :title="selectedHttpClientRequest.callsite_label"
                                                class="ndb:block ndb:max-w-full ndb:truncate ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:text-indigo-600 ndb:dark:text-indigo-300"
                                                x-text="selectedHttpClientRequest.callsite_label"
                                            ></code>
                                        </dd>
                                    </div>
                                </dl>

                                <template x-if="(selectedHttpClientRequest.stack ?? []).length === 0">
                                    <p class="ndb:mt-5 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                        No application stack was captured.
                                    </p>
                                </template>
                                <div class="ndb:mt-5 ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800">
                                    <template
                                        x-for="(frame, index) in selectedHttpClientRequest.stack ?? []"
                                        :key="frame.file + ':' + frame.line + ':' + index"
                                    >
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
        <div
            data-ndb-http-client-empty
            class="ndb:lg:flex ndb:lg:min-h-0 ndb:lg:flex-1 ndb:lg:items-center ndb:lg:justify-center"
        >
            <div class="ndb:w-full ndb:max-w-lg">
                <x-newdebugbar::empty-state label="No outbound HTTP requests were captured for this request." />
                <p class="ndb:mt-3 ndb:text-center ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                    Requests made through Laravel's HTTP client will appear here with their response, timing, and
                    source.
                </p>
            </div>
        </div>
    @endif
</div>
