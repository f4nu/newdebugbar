{{-- Renders request routing, retained inputs, response evidence, and runtime context. --}}
@php
    $requestPayload = is_array($section['payload'] ?? null) ? $section['payload'] : [];
    $isHttpRequest = ($profile['profile_type'] ?? 'http') === 'http';
    $requestStatus = (int) ($requestPayload['status'] ?? 0);
    $requestFailed = $requestStatus >= 400;
    $requestDuration = number_format((float) ($profile['metrics']['duration_ms'] ?? 0), 2, '.', '');
    $requestPeakMemory = number_format((float) ($profile['metrics']['peak_memory_mb'] ?? 0), 2, '.', '');
    $requestAfterResponseDuration = array_key_exists('after_response_duration_ms', $profile['metrics'] ?? [])
        ? number_format((float) $profile['metrics']['after_response_duration_ms'], 2, '.', '')
        : null;
    $requestQueryCount = (int) ($profile['sections']['queries']['summary']['total_count'] ?? $profile['sections']['queries']['summary']['count'] ?? 0);
    $requestHeaders = is_array($requestPayload['headers'] ?? null) ? $requestPayload['headers'] : [];
    $requestResponseHeaders = is_array($requestPayload['response_headers'] ?? null) ? $requestPayload['response_headers'] : [];
    $requestInput = is_array($requestPayload['input'] ?? null) ? $requestPayload['input'] : [];
    $requestQuery = is_array($requestPayload['query'] ?? null) ? $requestPayload['query'] : [];
    $requestRouteParameters = is_array($requestPayload['parameters'] ?? null) ? $requestPayload['parameters'] : [];
    $requestSession = is_array($requestPayload['session'] ?? null) ? $requestPayload['session'] : [];
    $requestAuthentication = is_array($requestPayload['authentication'] ?? null) ? $requestPayload['authentication'] : [];
    $requestMiddleware = array_values(array_filter(
        is_array($requestPayload['middleware'] ?? null) ? $requestPayload['middleware'] : [],
        static fn (mixed $middleware): bool => is_string($middleware) && $middleware !== '',
    ));
    $requestRuntimeContext = is_array($requestPayload['context'] ?? null) ? $requestPayload['context'] : [];
    $requestUrl = is_string($requestPayload['url'] ?? null) && $requestPayload['url'] !== ''
        ? $requestPayload['url']
        : null;
    $requestPath = is_string($requestPayload['path'] ?? null) && $requestPayload['path'] !== ''
        ? $requestPayload['path']
        : '—';
    $requestHost = $requestUrl === null ? '—' : (parse_url($requestUrl, PHP_URL_HOST) ?: '—');
    $requestRoute = is_string($requestPayload['route'] ?? null) && $requestPayload['route'] !== ''
        ? $requestPayload['route']
        : 'Unnamed route';
    $requestAction = is_string($requestPayload['action'] ?? null) && $requestPayload['action'] !== ''
        ? $requestPayload['action']
        : 'Closure';
    $requestType = str((string) ($requestPayload['request_type'] ?? 'full_page'))->replace('_', ' ')->title();
    $requestCompletion = str((string) ($profile['completion_state'] ?? 'complete'))->replace('_', ' ')->title();
    $requestRuntimeType = str((string) ($requestPayload['runtime_type'] ?? $profile['profile_type'] ?? 'runtime'))->replace('_', ' ')->title();
    $requestRuntimeName = is_string($requestPayload['name'] ?? null) && $requestPayload['name'] !== ''
        ? $requestPayload['name']
        : $requestPath;
    $requestRuntimeStatus = $requestPayload['exit_code'] ?? $requestPayload['status'] ?? '—';
    $formatRequestBytes = static fn (int $bytes): string => $bytes >= 1024
        ? number_format($bytes / 1024, 2).' KB'
        : number_format($bytes).' B';
    $formatRequestJson = static fn (array $value): string => json_encode(
        $value,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
    ) ?: '{}';
    $requestBodyInput = $requestInput;

    foreach ($requestQuery as $requestQueryKey => $requestQueryValue) {
        if (array_key_exists($requestQueryKey, $requestBodyInput) && $requestBodyInput[$requestQueryKey] === $requestQueryValue) {
            unset($requestBodyInput[$requestQueryKey]);
        }
    }
@endphp

<div
    data-ndb-request-workspace-root
    x-init="initializeRequestDetails({{ \Illuminate\Support\Js::from($isHttpRequest ? 'route' : 'runtime') }})"
    class="ndb:flex ndb:min-h-0 ndb:flex-1 ndb:flex-col"
>
    <x-newdebugbar::inspector-workspace
        mode="stream"
        frame="top"
        data-ndb-request-workspace
        class="ndb:min-h-[32rem] ndb:lg:min-h-0"
    >
        <x-slot:header class="ndb:border-b-0">
            <x-newdebugbar::inspector-detail-tabs :label="$isHttpRequest ? 'Request evidence' : 'Runtime evidence'">
                @foreach (
                    $isHttpRequest
                        ? ['route' => 'Route', 'input' => 'Input', 'headers' => 'Headers', 'session' => 'Session', 'response' => 'Response']
                        : ['runtime' => 'Runtime', 'context' => 'Context'] as $requestTab => $requestTabLabel
)
                    <x-newdebugbar::filter-tab
                        variant="segmented"
                        data-ndb-request-tab="{{ $requestTab }}"
                        @click="setRequestDetailTab({{ \Illuminate\Support\Js::from($requestTab) }})"
                        ::aria-pressed="requestDetailTab === {{ \Illuminate\Support\Js::from($requestTab) }}"
                        class="ndb:h-auto"
                    >
                        {{ $requestTabLabel }}
                    </x-newdebugbar::filter-tab>
                @endforeach
            </x-newdebugbar::inspector-detail-tabs>
        </x-slot:header>

        <x-slot:body x-ref="requestDetailBody" data-ndb-request-scroll>
            @if ($isHttpRequest)
                <template x-if="requestDetailTab === 'route'">
                    <div data-ndb-request-panel="route" class="ndb:p-4">
                        <x-newdebugbar::inspector-facts columns="4" data-ndb-request-route-facts>
                            <x-newdebugbar::inspector-fact label="Route">
                                <x-slot:value
                                    class="ndb:truncate ndb:text-xs ndb:font-semibold"
                                    title="{{ $requestRoute }}"
                                >
                                    {{ $requestRoute }}
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                            <x-newdebugbar::inspector-fact label="Host">
                                <x-slot:value
                                    class="ndb:truncate ndb:text-xs ndb:font-semibold"
                                    title="{{ $requestHost }}"
                                >
                                    {{ $requestHost }}
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                            <x-newdebugbar::inspector-fact label="Type">
                                <x-slot:value class="ndb:text-xs ndb:font-semibold">{{ $requestType }}</x-slot:value>
                            </x-newdebugbar::inspector-fact>
                            <x-newdebugbar::inspector-fact label="Middleware">
                                <x-slot:value class="ndb:text-xs ndb:font-semibold ndb:tabular-nums">
                                    {{ number_format(count($requestMiddleware)) }} {{ str('step')->plural(count($requestMiddleware)) }}
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                        </x-newdebugbar::inspector-facts>

                        <x-newdebugbar::inspector-definition-list class="ndb:mt-4">
                            <x-newdebugbar::inspector-definition-row label="URL">
                                <x-slot:value data-ndb-request-url class="ndb:break-all">
                                    {{ $requestUrl ?? $requestPath }}
                                </x-slot:value>
                            </x-newdebugbar::inspector-definition-row>
                        </x-newdebugbar::inspector-definition-list>

                        <div class="ndb:mt-5 ndb:space-y-5">
                            <x-newdebugbar::inspector-evidence
                                label="Controller"
                                language="php"
                                data-ndb-request-source
                            >
                                <x-slot:value>{{ $requestAction }}</x-slot:value>
                            </x-newdebugbar::inspector-evidence>

                            @if ($requestRouteParameters !== [])
                                <x-newdebugbar::inspector-evidence
                                    label="Route parameters"
                                    language="json"
                                    data-ndb-request-route-parameters
                                >
                                    <x-slot:value>{{ $formatRequestJson($requestRouteParameters) }}</x-slot:value>
                                </x-newdebugbar::inspector-evidence>
                            @endif

                            @if ($requestMiddleware !== [])
                                <x-newdebugbar::inspector-evidence
                                    label="Middleware pipeline"
                                    language="php"
                                    data-ndb-request-middleware
                                >
                                    <x-slot:value>{{ implode("\n", $requestMiddleware) }}</x-slot:value>
                                </x-newdebugbar::inspector-evidence>
                            @endif
                        </div>
                    </div>
                </template>

                <template x-if="requestDetailTab === 'input'">
                    <div data-ndb-request-panel="input" class="ndb:p-4">
                        <x-newdebugbar::inspector-facts columns="4" data-ndb-request-input-facts>
                            <x-newdebugbar::inspector-fact label="Request size">
                                <x-slot:value class="ndb:text-xs ndb:font-semibold ndb:tabular-nums">
                                    {{ $formatRequestBytes((int) ($requestPayload['request_size_bytes'] ?? 0)) }}
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                            <x-newdebugbar::inspector-fact label="Query values">
                                <x-slot:value class="ndb:text-xs ndb:font-semibold ndb:tabular-nums">
                                    {{ number_format(count($requestQuery)) }}
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                            <x-newdebugbar::inspector-fact label="Input values">
                                <x-slot:value class="ndb:text-xs ndb:font-semibold ndb:tabular-nums">
                                    {{ number_format(count($requestBodyInput)) }}
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                            <x-newdebugbar::inspector-fact label="Route values">
                                <x-slot:value class="ndb:text-xs ndb:font-semibold ndb:tabular-nums">
                                    {{ number_format(count($requestRouteParameters)) }}
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                        </x-newdebugbar::inspector-facts>

                        <div class="ndb:mt-5 ndb:space-y-5">
                            @if ($requestQuery !== [])
                                <x-newdebugbar::inspector-evidence
                                    label="Query parameters"
                                    language="json"
                                    data-ndb-request-query
                                >
                                    <x-slot:value>{{ $formatRequestJson($requestQuery) }}</x-slot:value>
                                </x-newdebugbar::inspector-evidence>
                            @endif

                            @if ($requestBodyInput !== [])
                                <x-newdebugbar::inspector-evidence
                                    label="Request input"
                                    language="json"
                                    data-ndb-request-input
                                >
                                    <x-slot:value>{{ $formatRequestJson($requestBodyInput) }}</x-slot:value>
                                </x-newdebugbar::inspector-evidence>
                            @endif

                            @if ($requestQuery === [] && $requestBodyInput === [])
                                <x-newdebugbar::empty-state label="This request has no query parameters or input values." />
                            @endif
                        </div>
                    </div>
                </template>

                <template x-if="requestDetailTab === 'headers'">
                    <div data-ndb-request-panel="headers" class="ndb:p-4">
                        <div class="ndb:space-y-5">
                            <x-newdebugbar::inspector-evidence label="Request" language="json" data-ndb-request-headers>
                                <x-slot:value>{{ $formatRequestJson($requestHeaders) }}</x-slot:value>
                            </x-newdebugbar::inspector-evidence>
                            <x-newdebugbar::inspector-evidence
                                label="Response"
                                language="json"
                                data-ndb-request-response-headers
                            >
                                <x-slot:value>{{ $formatRequestJson($requestResponseHeaders) }}</x-slot:value>
                            </x-newdebugbar::inspector-evidence>
                        </div>
                    </div>
                </template>

                <template x-if="requestDetailTab === 'session'">
                    <div data-ndb-request-panel="session" class="ndb:p-4">
                        <x-newdebugbar::inspector-facts columns="4" data-ndb-request-session-facts>
                            <x-newdebugbar::inspector-fact label="Authentication">
                                <x-slot:value class="ndb:text-xs ndb:font-semibold">
                                    {{ ($requestAuthentication['authenticated'] ?? $requestPayload['authenticated'] ?? false) ? 'Authenticated' : 'Guest' }}
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                            <x-newdebugbar::inspector-fact label="Guard">
                                <x-slot:value class="ndb:text-xs ndb:font-semibold">
                                    {{ $requestAuthentication['guard'] ?? 'unknown' }}
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                            <x-newdebugbar::inspector-fact label="Session">
                                <x-slot:value class="ndb:text-xs ndb:font-semibold">
                                    {{ ($requestSession['present'] ?? false) ? 'Started' : 'Not started' }}
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                            <x-newdebugbar::inspector-fact label="Session keys">
                                <x-slot:value class="ndb:text-xs ndb:font-semibold ndb:tabular-nums">
                                    {{ number_format((int) ($requestSession['key_count'] ?? 0)) }}
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                        </x-newdebugbar::inspector-facts>

                        <x-newdebugbar::inspector-definition-list class="ndb:mt-4" data-ndb-request-session-details>
                            @if (is_string($requestAuthentication['model'] ?? null) && $requestAuthentication['model'] !== '')
                                <x-newdebugbar::inspector-definition-row label="User model">
                                    <x-slot:value class="ndb:break-all">
                                        {{ $requestAuthentication['model'] }}
                                    </x-slot:value>
                                </x-newdebugbar::inspector-definition-row>
                            @endif
                            @if (is_string($requestAuthentication['identifier'] ?? null) && $requestAuthentication['identifier'] !== '')
                                <x-newdebugbar::inspector-definition-row label="User identifier">
                                    <x-slot:value class="ndb:break-all">
                                        {{ $requestAuthentication['identifier'] }}
                                    </x-slot:value>
                                </x-newdebugbar::inspector-definition-row>
                            @endif
                            @if ($requestSession['present'] ?? false)
                                <x-newdebugbar::inspector-definition-row label="Driver">
                                    <x-slot:value>{{ $requestSession['driver'] ?? 'unknown' }}</x-slot:value>
                                </x-newdebugbar::inspector-definition-row>
                            @endif
                            @if (($requestSession['keys'] ?? []) !== [])
                                <x-newdebugbar::inspector-definition-row label="Keys">
                                    <x-slot:value class="ndb:break-words">
                                        {{ implode(', ', (array) $requestSession['keys']) }}
                                    </x-slot:value>
                                </x-newdebugbar::inspector-definition-row>
                            @endif
                            @if (($requestSession['flash_keys'] ?? []) !== [])
                                <x-newdebugbar::inspector-definition-row label="Flash keys">
                                    <x-slot:value class="ndb:break-words">
                                        {{ implode(', ', (array) $requestSession['flash_keys']) }}
                                    </x-slot:value>
                                </x-newdebugbar::inspector-definition-row>
                            @endif
                            @if (($requestSession['error_bags'] ?? []) !== [])
                                <x-newdebugbar::inspector-definition-row label="Error bags">
                                    <x-slot:value class="ndb:break-words">
                                        {{ implode(', ', (array) $requestSession['error_bags']) }}
                                    </x-slot:value>
                                </x-newdebugbar::inspector-definition-row>
                            @endif
                            @if ((int) ($requestSession['keys_dropped'] ?? 0) > 0)
                                <x-newdebugbar::inspector-definition-row label="Keys omitted">
                                    <x-slot:value class="ndb:tabular-nums">
                                        {{ number_format((int) $requestSession['keys_dropped']) }}
                                    </x-slot:value>
                                </x-newdebugbar::inspector-definition-row>
                            @endif
                        </x-newdebugbar::inspector-definition-list>
                    </div>
                </template>

                <template x-if="requestDetailTab === 'response'">
                    <div data-ndb-request-panel="response" class="ndb:p-4">
                        <x-newdebugbar::inspector-facts columns="4" data-ndb-request-response-facts>
                            <x-newdebugbar::inspector-fact label="Status">
                                <x-slot:value
                                    data-ndb-request-status
                                    @class([
                                        'ndb:text-xs ndb:font-bold ndb:tabular-nums',
                                        'ndb:text-red-700 ndb:dark:text-red-300' => $requestFailed,
                                    ])
                                >
                                    {{ $requestStatus ?: '—' }}
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                            <x-newdebugbar::inspector-fact label="Duration">
                                <x-slot:value class="ndb:text-xs ndb:font-semibold ndb:tabular-nums">
                                    {{ $requestDuration }} ms
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                            <x-newdebugbar::inspector-fact label="Response size">
                                <x-slot:value class="ndb:text-xs ndb:font-semibold ndb:tabular-nums">
                                    {{ $formatRequestBytes((int) ($requestPayload['response_size_bytes'] ?? 0)) }}
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                            <x-newdebugbar::inspector-fact label="Queries">
                                <x-slot:value class="ndb:text-xs ndb:font-semibold ndb:tabular-nums">
                                    {{ number_format($requestQueryCount) }}
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                        </x-newdebugbar::inspector-facts>

                        <x-newdebugbar::inspector-definition-list class="ndb:mt-4" data-ndb-request-response-details>
                            <x-newdebugbar::inspector-definition-row label="Content type">
                                <x-slot:value class="ndb:break-all">
                                    {{ $requestPayload['content_type'] ?? '—' }}
                                </x-slot:value>
                            </x-newdebugbar::inspector-definition-row>
                            <x-newdebugbar::inspector-definition-row label="Peak memory">
                                <x-slot:value class="ndb:tabular-nums">{{ $requestPeakMemory }} MB</x-slot:value>
                            </x-newdebugbar::inspector-definition-row>
                            <x-newdebugbar::inspector-definition-row label="Capture">
                                <x-slot:value>{{ $requestCompletion }}</x-slot:value>
                            </x-newdebugbar::inspector-definition-row>
                            @if ($requestAfterResponseDuration !== null)
                                <x-newdebugbar::inspector-definition-row label="After response">
                                    <x-slot:value class="ndb:tabular-nums">
                                        {{ $requestAfterResponseDuration }} ms
                                    </x-slot:value>
                                </x-newdebugbar::inspector-definition-row>
                            @endif
                        </x-newdebugbar::inspector-definition-list>
                    </div>
                </template>
            @else
                <template x-if="requestDetailTab === 'runtime'">
                    <div data-ndb-request-panel="runtime" class="ndb:p-4">
                        <x-newdebugbar::inspector-facts columns="4" data-ndb-request-runtime-facts>
                            <x-newdebugbar::inspector-fact label="Type">
                                <x-slot:value class="ndb:text-xs ndb:font-semibold">
                                    {{ $requestRuntimeType }}
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                            <x-newdebugbar::inspector-fact label="Exit code">
                                <x-slot:value class="ndb:text-xs ndb:font-bold ndb:tabular-nums">
                                    {{ $requestRuntimeStatus }}
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                            <x-newdebugbar::inspector-fact label="Duration">
                                <x-slot:value class="ndb:text-xs ndb:font-semibold ndb:tabular-nums">
                                    {{ $requestDuration }} ms
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                            <x-newdebugbar::inspector-fact label="Peak memory">
                                <x-slot:value class="ndb:text-xs ndb:font-semibold ndb:tabular-nums">
                                    {{ $requestPeakMemory }} MB
                                </x-slot:value>
                            </x-newdebugbar::inspector-fact>
                        </x-newdebugbar::inspector-facts>

                        <x-newdebugbar::inspector-definition-list class="ndb:mt-4" data-ndb-request-runtime-details>
                            <x-newdebugbar::inspector-definition-row label="Name">
                                <x-slot:value class="ndb:break-all">{{ $requestRuntimeName }}</x-slot:value>
                            </x-newdebugbar::inspector-definition-row>
                            <x-newdebugbar::inspector-definition-row label="Runtime path">
                                <x-slot:value class="ndb:break-all">{{ $requestPath }}</x-slot:value>
                            </x-newdebugbar::inspector-definition-row>
                            <x-newdebugbar::inspector-definition-row label="Capture">
                                <x-slot:value>{{ $requestCompletion }}</x-slot:value>
                            </x-newdebugbar::inspector-definition-row>
                            @if ($requestAfterResponseDuration !== null)
                                <x-newdebugbar::inspector-definition-row label="After response">
                                    <x-slot:value class="ndb:tabular-nums">
                                        {{ $requestAfterResponseDuration }} ms
                                    </x-slot:value>
                                </x-newdebugbar::inspector-definition-row>
                            @endif
                        </x-newdebugbar::inspector-definition-list>
                    </div>
                </template>

                <template x-if="requestDetailTab === 'context'">
                    <div data-ndb-request-panel="context" class="ndb:p-4">
                        @if ($requestRuntimeContext !== [])
                            <x-newdebugbar::inspector-evidence language="json" data-ndb-request-runtime-context>
                                <x-slot:value>{{ $formatRequestJson($requestRuntimeContext) }}</x-slot:value>
                            </x-newdebugbar::inspector-evidence>
                        @else
                            <x-newdebugbar::empty-state label="No runtime context was captured." />
                        @endif
                    </div>
                </template>
            @endif
        </x-slot:body>
    </x-newdebugbar::inspector-workspace>
</div>
