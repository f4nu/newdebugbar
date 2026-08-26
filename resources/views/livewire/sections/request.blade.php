{{-- Renders the HTTP request trace and captured request data. --}}
@php($requestPayload = $section['payload'])
@php($isHttpRequest = ($profile['profile_type'] ?? 'http') === 'http')
@php($requestStatus = (int) ($requestPayload['status'] ?? 0))
@php($requestSucceeded = $requestStatus > 0 && $requestStatus < 400)
@php($requestDuration = number_format((float) ($profile['metrics']['duration_ms'] ?? 0), 2, '.', ''))
@php($requestQueryCount = (int) ($profile['sections']['queries']['summary']['total_count'] ?? $profile['sections']['queries']['summary']['count'] ?? 0))
@php($requestHeaders = is_array($requestPayload['headers'] ?? null) ? $requestPayload['headers'] : [])
@php($requestInput = is_array($requestPayload['input'] ?? null) ? $requestPayload['input'] : [])
@php($requestQuery = is_array($requestPayload['query'] ?? null) ? $requestPayload['query'] : [])
@php($requestSession = is_array($requestPayload['session'] ?? null) ? $requestPayload['session'] : [])
@php($requestAuthentication = is_array($requestPayload['authentication'] ?? null) ? $requestPayload['authentication'] : [])
@php($requestMiddleware = is_array($requestPayload['middleware'] ?? null) ? $requestPayload['middleware'] : [])
@php($requestPath = ($requestPayload['path'] ?? null) ?: ($requestPayload['url'] ?? null) ?: '—')
@php($requestHost = parse_url((string) ($requestPayload['url'] ?? ''), PHP_URL_HOST) ?: '—')
@php(
    $formatRequestBytes = static fn (int $bytes): string => $bytes >= 1024
        ? number_format($bytes / 1024, 2).' KB'
        : number_format($bytes).' B'
)
@php(
    $formatRequestValue = static function (mixed $value): string {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }
)
@php(
    $requestDetailGroups = [
        'headers' => [
            'label' => 'Headers',
            'count' => count($requestHeaders),
            'items' => $requestHeaders,
        ],
        'input' => [
            'label' => 'Input',
            'count' => count($requestInput),
            'items' => $requestInput,
        ],
        'query' => [
            'label' => 'Query',
            'count' => count($requestQuery),
            'items' => $requestQuery,
        ],
        'session' => [
            'label' => 'Session',
            'count' => (int) ($requestSession['key_count'] ?? 0),
            'items' => [
                'started' => (bool) ($requestSession['present'] ?? false),
                'driver' => $requestSession['driver'] ?? '—',
                'keys' => $requestSession['keys'] ?? [],
                'flash keys' => $requestSession['flash_keys'] ?? [],
                'error bags' => $requestSession['error_bags'] ?? [],
            ],
        ],
    ]
)

@if ($isHttpRequest)
    <div data-ndb-request-trace class="ndb:-mt-2">
        <div
            data-ndb-request-summary
            class="ndb:flex ndb:flex-col ndb:gap-3 ndb:border-y ndb:border-zinc-200/90 ndb:bg-white/55 ndb:px-4 ndb:py-3 ndb:sm:flex-row ndb:sm:items-center ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/35"
        >
            <div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3">
                <span class="ndb:shrink-0 ndb:rounded-md ndb:bg-emerald-50 ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-emerald-700 ndb:ring-1 ndb:ring-inset ndb:ring-emerald-200 ndb:dark:bg-emerald-950/60 ndb:dark:text-emerald-300 ndb:dark:ring-emerald-900">
                    {{ $requestPayload['method'] ?? 'HTTP' }}
                </span>
                <span class="ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-bold">{{ $requestPath }}</span>
            </div>
            <span class="ndb:hidden ndb:h-5 ndb:w-px ndb:shrink-0 ndb:bg-zinc-200 ndb:sm:block ndb:dark:bg-zinc-800"></span>
            <span
                data-ndb-request-status
                class="ndb:text-xs ndb:font-bold ndb:tabular-nums {{ $requestSucceeded ? 'ndb:text-emerald-600 ndb:dark:text-emerald-400' : 'ndb:text-red-600 ndb:dark:text-red-400' }}"
            >
                {{ $requestStatus ?: '—' }}
            </span>
            <span class="ndb:hidden ndb:h-5 ndb:w-px ndb:shrink-0 ndb:bg-zinc-200 ndb:sm:block ndb:dark:bg-zinc-800"></span>
            <p data-ndb-request-completion class="ndb:min-w-0 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                Completed in
                <span class="ndb:whitespace-nowrap ndb:tabular-nums">{{ $requestDuration }} ms</span>
            </p>
        </div>

        <ol data-ndb-request-timeline class="ndb:mt-5 ndb:list-none ndb:px-4 ndb:sm:px-6" aria-label="Request trace">
            <li data-ndb-request-step="received" class="ndb:grid ndb:grid-cols-[18px_minmax(0,1fr)] ndb:gap-x-4">
                <div aria-hidden="true" class="ndb:relative ndb:flex ndb:justify-center ndb:pt-0.5">
                    <span
                        data-ndb-request-line
                        class="ndb:absolute ndb:top-[18px] ndb:-bottom-0.5 ndb:left-1/2 ndb:w-0.5 ndb:-translate-x-1/2 ndb:bg-indigo-400 ndb:dark:bg-indigo-500"
                    ></span>
                    <span
                        data-ndb-request-dot
                        class="ndb:relative ndb:z-[1] ndb:size-4 ndb:rounded-full ndb:border-2 ndb:border-indigo-500 ndb:bg-white ndb:dark:border-indigo-400 ndb:dark:bg-zinc-950"
                    ></span>
                </div>
                <div class="ndb:pb-6">
                    <h3 class="ndb:text-sm ndb:font-bold ndb:leading-5">Received</h3>
                    <dl class="ndb:mt-3 ndb:grid ndb:grid-cols-2 ndb:gap-x-5 ndb:gap-y-3 ndb:sm:grid-cols-4">
                        @foreach ([
                            ['URL', ($requestPayload['url'] ?? null) ?: '—'],
                            ['Host', $requestHost],
                            ['Content type', ($requestPayload['content_type'] ?? null) ?: '—'],
                            ['Request size', $formatRequestBytes((int) ($requestPayload['request_size_bytes'] ?? 0))],
                        ] as [$label, $value])
                            <div class="ndb:min-w-0">
                                <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    {{ $label }}
                                </dt>
                                <dd class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-semibold" title="{{ $value }}">
                                    {{ $value }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </li>

            <li data-ndb-request-step="matched" class="ndb:grid ndb:grid-cols-[18px_minmax(0,1fr)] ndb:gap-x-4">
                <div aria-hidden="true" class="ndb:relative ndb:flex ndb:justify-center ndb:pt-0.5">
                    <span
                        data-ndb-request-line
                        class="ndb:absolute ndb:top-[18px] ndb:-bottom-0.5 ndb:left-1/2 ndb:w-0.5 ndb:-translate-x-1/2 ndb:bg-indigo-400 ndb:dark:bg-indigo-500"
                    ></span>
                    <span
                        data-ndb-request-dot
                        class="ndb:relative ndb:z-[1] ndb:size-4 ndb:rounded-full ndb:border-2 ndb:border-indigo-500 ndb:bg-white ndb:dark:border-indigo-400 ndb:dark:bg-zinc-950"
                    ></span>
                </div>
                <div class="ndb:pb-6">
                    <h3 class="ndb:text-sm ndb:font-bold ndb:leading-5">Matched</h3>
                    <dl class="ndb:mt-3 ndb:grid ndb:grid-cols-2 ndb:gap-x-5 ndb:gap-y-3 ndb:sm:grid-cols-4">
                        <div class="ndb:min-w-0">
                            <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                Route
                            </dt>
                            <dd class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-semibold">
                                {{ ($requestPayload['route'] ?? null) ?: 'Unnamed route' }}
                            </dd>
                        </div>
                        <div class="ndb:col-span-2 ndb:min-w-0">
                            <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                Controller
                            </dt>
                            <dd class="ndb:mt-1 ndb:min-w-0">
                                <code class="ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-semibold">{{ ($requestPayload['action'] ?? null) ?: 'Closure' }}</code>
                            </dd>
                        </div>
                        <div class="ndb:min-w-0">
                            <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                Middleware
                            </dt>
                            <dd class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-semibold">
                                {{ count($requestMiddleware) }} {{ str('step')->plural(count($requestMiddleware)) }}
                            </dd>
                        </div>
                        <div class="ndb:min-w-0">
                            <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                Guard
                            </dt>
                            <dd class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-semibold">
                                {{ $requestAuthentication['guard'] ?? 'unknown' }}
                            </dd>
                        </div>
                        <div class="ndb:col-span-2 ndb:min-w-0">
                            <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                Authentication
                            </dt>
                            <dd class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-semibold">
                                {{ ($requestPayload['authenticated'] ?? false) ? ($requestAuthentication['model'] ?? 'Authenticated') : 'Guest' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </li>

            <li data-ndb-request-step="responded" class="ndb:grid ndb:grid-cols-[18px_minmax(0,1fr)] ndb:gap-x-4">
                <div aria-hidden="true" class="ndb:relative ndb:flex ndb:justify-center ndb:pt-0.5">
                    <span
                        data-ndb-request-dot
                        class="ndb:relative ndb:z-[1] ndb:size-4 ndb:rounded-full ndb:border-2 ndb:border-indigo-500 ndb:bg-white ndb:dark:border-indigo-400 ndb:dark:bg-zinc-950"
                    ></span>
                </div>
                <div>
                    <h3 class="ndb:text-sm ndb:font-bold ndb:leading-5">Responded</h3>
                    <dl class="ndb:mt-3 ndb:grid ndb:grid-cols-2 ndb:gap-x-5 ndb:gap-y-3 ndb:sm:grid-cols-4">
                        @foreach ([
                            ['Status', $requestStatus ?: '—'],
                            ['Response size', $formatRequestBytes((int) ($requestPayload['response_size_bytes'] ?? 0))],
                            ['Duration', $requestDuration.' ms'],
                            ['Queries', $requestQueryCount],
                        ] as [$label, $value])
                            <div class="ndb:min-w-0">
                                <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    {{ $label }}
                                </dt>
                                <dd class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-bold ndb:tabular-nums {{ $label === 'Status' ? ($requestSucceeded ? 'ndb:text-emerald-600 ndb:dark:text-emerald-400' : 'ndb:text-red-600 ndb:dark:text-red-400') : '' }}">
                                    {{ $value }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </li>
        </ol>
    </div>

    <details
        data-ndb-request-details
        class="ndb:group ndb:mt-8 ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/25"
    >
        <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-4 ndb:py-3 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500">
            <span class="ndb:min-w-0 ndb:flex-1">
                <span class="ndb:block ndb:text-xs ndb:font-bold">Request details</span>
                <span class="ndb:mt-0.5 ndb:block ndb:text-[11px] ndb:text-zinc-400">Headers, input, query parameters, and session shape</span>
            </span>
            <x-newdebugbar::icon
                name="chevron-down"
                class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
            />
        </summary>
        <div
            x-data="{ requestDetail: 'headers' }"
            class="ndb:border-t ndb:border-zinc-200/90 ndb:sm:grid ndb:sm:grid-cols-[11rem_minmax(0,1fr)] ndb:dark:border-zinc-800"
        >
            <div class="ndb:grid ndb:grid-cols-2 ndb:gap-1 ndb:border-b ndb:border-zinc-200/90 ndb:bg-zinc-50/70 ndb:p-2 ndb:sm:block ndb:sm:border-r ndb:sm:border-b-0 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/50">
                @foreach ($requestDetailGroups as $requestDetailKey => $requestDetailGroup)
                    <button
                        type="button"
                        data-ndb-request-detail="{{ $requestDetailKey }}"
                        @click="requestDetail = @js($requestDetailKey)"
                        :aria-pressed="requestDetail === @js($requestDetailKey)"
                        :class="requestDetail === @js($requestDetailKey) ? 'ndb:bg-indigo-50 ndb:text-indigo-700 ndb:dark:bg-indigo-950/70 ndb:dark:text-indigo-300' : 'ndb:text-zinc-600 ndb:hover:bg-white ndb:hover:text-zinc-950 ndb:dark:text-zinc-400 ndb:dark:hover:bg-zinc-800 ndb:dark:hover:text-white'"
                        class="ndb:flex ndb:w-full ndb:min-w-0 ndb:items-center ndb:gap-2 ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-left ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-1 ndb:focus-visible:outline-indigo-500"
                    >
                        <span class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-bold">{{ $requestDetailGroup['label'] }}</span>
                        <span
                            data-ndb-request-detail-count
                            class="ndb:shrink-0 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
                        >{{ $requestDetailGroup['count'] }}</span>
                    </button>
                @endforeach
            </div>

            <div class="ndb:min-w-0 ndb:p-4">
                @foreach ($requestDetailGroups as $requestDetailKey => $requestDetailGroup)
                    <div
                        data-ndb-request-detail-panel="{{ $requestDetailKey }}"
                        x-show.important="requestDetail === @js($requestDetailKey)"
                    >
                        <div class="ndb:flex ndb:items-center ndb:justify-between ndb:gap-3">
                            <div class="ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-3">
                                <h3 class="ndb:text-xs ndb:font-bold">{{ $requestDetailGroup['label'] }}</h3>
                                <span
                                    data-ndb-request-detail-panel-count
                                    class="ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
                                >{{ $requestDetailGroup['count'] }}</span>
                            </div>
                            <button
                                type="button"
                                @click="copyText(@js(json_encode($requestDetailGroup['items'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)))"
                                class="ndb:shrink-0 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                            >
                                Copy all
                            </button>
                        </div>

                        <div class="ndb:mt-3 ndb:overflow-x-auto">
                            @if ($requestDetailGroup['items'] !== [])
                                <table class="ndb:w-full ndb:table-fixed ndb:border-collapse ndb:text-left">
                                    <thead>
                                        <tr class="ndb:border-b ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
                                            <th
                                                scope="col"
                                                class="ndb:w-2/5 ndb:pb-2 ndb:pr-4 ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                            >
                                                Name
                                            </th>
                                            <th
                                                scope="col"
                                                class="ndb:pb-2 ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                            >
                                                Value
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($requestDetailGroup['items'] as $requestDetailName => $requestDetailValue)
                                            <tr class="ndb:border-b ndb:border-zinc-200/70 ndb:last:border-b-0 ndb:dark:border-zinc-800/80">
                                                <th
                                                    scope="row"
                                                    class="ndb:py-2 ndb:pr-4 ndb:align-top ndb:text-[11px] ndb:font-medium ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                                >
                                                    {{ $requestDetailName }}
                                                </th>
                                                <td class="ndb:break-words ndb:py-2 ndb:align-top ndb:text-[11px] ndb:text-zinc-800 ndb:dark:text-zinc-200">
                                                    {{ $formatRequestValue($requestDetailValue) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="ndb:rounded-lg ndb:bg-zinc-50 ndb:px-3 ndb:py-4 ndb:text-xs ndb:text-zinc-500 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-400">
                                    No {{ strtolower($requestDetailGroup['label']) }} were captured.
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </details>
@else
    <div class="ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:p-4 ndb:dark:border-zinc-800">
        <h3 class="ndb:text-xs ndb:font-bold">Runtime summary</h3>
        <dl class="ndb:mt-4 ndb:grid ndb:grid-cols-2 ndb:gap-x-5 ndb:gap-y-3 ndb:sm:grid-cols-4">
            @foreach ([
                ['Type', str($profile['profile_type'] ?? 'runtime')->replace('_', ' ')->title()],
                ['Name', ($requestPayload['name'] ?? null) ?: $requestPath],
                ['Status', $requestPayload['exit_code'] ?? $requestPayload['status'] ?? '—'],
                ['Duration', $requestDuration.' ms'],
            ] as [$label, $value])
                <div class="ndb:min-w-0">
                    <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                        {{ $label }}
                    </dt>
                    <dd class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-semibold">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
@endif
