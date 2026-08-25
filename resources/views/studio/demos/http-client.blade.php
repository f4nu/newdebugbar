@php
    $analysis = (new \NewDebugBar\Analysis\HttpClientAnalyzer(slowRequestMs: 250))->analyze([
        [
            'method' => 'GET',
            'url' => 'https://weather.morrow.test/v1/forecast?trip=kyoto-autumn',
            'status' => 200,
            'reason' => 'OK',
            'duration_ms' => 363.1,
            'request' => [
                'headers' => ['Accept' => ['application/json']],
                'body' => null,
                'body_size_bytes' => 0,
            ],
            'response' => [
                'headers' => ['Content-Type' => ['application/json']],
                'body' => ['forecast' => 'Clear', 'temperature' => 18],
                'body_size_bytes' => 48,
            ],
            'callsite_label' => 'app/Travel/LocalPartnerGateway.php:49',
            'stack' => [
                [
                    'function' => 'App\\Travel\\LocalPartnerGateway->forecast',
                    'file' => 'app/Travel/LocalPartnerGateway.php',
                    'line' => 49,
                ],
                [
                    'function' => 'App\\Actions\\Trips\\RefreshTripWorkspace->handle',
                    'file' => 'app/Actions/Trips/RefreshTripWorkspace.php',
                    'line' => 87,
                ],
            ],
        ],
        [
            'method' => 'GET',
            'url' => 'https://maps.morrow.test/v1/walks/gion-to-kiyomizu',
            'status' => 200,
            'reason' => 'OK',
            'duration_ms' => 0.41,
            'request' => ['body_size_bytes' => 0],
            'response' => ['body' => ['distance_km' => 2.1], 'body_size_bytes' => 29],
            'callsite_label' => 'app/Travel/WalkingRoute.php:31',
            'stack' => [],
        ],
        [
            'method' => 'PATCH',
            'url' => 'https://passport.morrow.test/v1/travelers/elise',
            'status' => 422,
            'reason' => 'Unprocessable Content',
            'duration_ms' => 38.72,
            'failed' => true,
            'request' => [
                'headers' => ['Content-Type' => ['application/json'], 'Authorization' => ['[redacted]']],
                'body' => ['passport_number' => '[redacted]'],
                'body_size_bytes' => 38,
            ],
            'response' => [
                'headers' => ['Content-Type' => ['application/json']],
                'body' => ['message' => 'The passport number is invalid.'],
                'body_size_bytes' => 53,
            ],
            'callsite_label' => 'app/Travel/PassportClient.php:74',
            'stack' => [],
        ],
        [
            'method' => 'POST',
            'url' => 'https://concierge.morrow.test/v1/requests',
            'status' => null,
            'duration_ms' => null,
            'failed' => true,
            'exception_class' => 'Illuminate\\Http\\Client\\ConnectionException',
            'exception_message' => 'Could not resolve concierge.morrow.test.',
            'request' => ['body' => ['request' => 'Dinner reservation'], 'body_size_bytes' => 32],
            'callsite_label' => 'app/Travel/ConciergeClient.php:28',
            'stack' => [],
        ],
        [
            'method' => 'GET',
            'url' => 'https://rail.morrow.test/v1/reservations/KYO-2048',
            'status' => 503,
            'reason' => 'Service Unavailable',
            'duration_ms' => 52.18,
            'failed' => true,
            'request' => ['body_size_bytes' => 0],
            'response' => ['body' => ['message' => 'Reservations are temporarily unavailable.'], 'body_size_bytes' => 64],
            'callsite_label' => 'app/Travel/RailClient.php:62',
            'stack' => [],
        ],
    ]);

    $items = $analysis['items'];
    $summary = [...$analysis['summary'], 'duration_ms' => 454.41];
    $selectedRequest = $items[0];
    $connectionFailure = $items[3];
@endphp

<div class="ndb:space-y-5" x-init="initializeHttpClient({{ \Illuminate\Support\Js::from($items) }})">
    @component('newdebugbar::studio.component', ['component' => 'http-client-controls', 'components' => $components])
        <div class="ndb:max-w-lg">
            <x-newdebugbar::http-client-controls :summary="$summary" :item-count="count($items)" />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'http-client-detail', 'components' => $components])
        <div
            x-data="{ httpClientDetailOpen: true }"
            class="ndb:h-[34rem] ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"
        >
            <x-newdebugbar::http-client-detail />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'http-client-detail-tabs', 'components' => $components])
        <div
            x-data="{
                httpClientDetailTab: 'response',
                setHttpClientDetailTab(tab) {
                    this.httpClientDetailTab = tab;
                },
            }"
            class="ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"
        >
            <x-newdebugbar::http-client-detail-tabs />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'http-client-empty', 'components' => $components])
        <x-newdebugbar::http-client-empty />
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'http-client-header', 'components' => $components])
        <div
            x-data="{ selectedHttpClientRequest: {{ \Illuminate\Support\Js::from($selectedRequest) }} }"
            class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"
        >
            <x-newdebugbar::http-client-header />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'http-client-list-item', 'components' => $components])
        <div class="ndb:max-w-xl ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950">
            <x-newdebugbar::http-client-list-item :item="$items[2]" />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'http-client-no-response', 'components' => $components])
        <div
            x-data="{ selectedHttpClientRequest: {{ \Illuminate\Support\Js::from($connectionFailure) }} }"
            class="ndb:max-w-xl ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white ndb:p-4 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"
        >
            <x-newdebugbar::http-client-no-response />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'http-client-request-panel', 'components' => $components])
        <div
            x-data="{
                selectedHttpClientRequest: {{ \Illuminate\Support\Js::from($selectedRequest) }},
                httpClientDetailTab: 'request',
            }"
            class="ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white ndb:p-4 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"
        >
            <x-newdebugbar::http-client-request-panel />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'http-client-response-panel', 'components' => $components])
        <div
            x-data="{
                selectedHttpClientRequest: {{ \Illuminate\Support\Js::from($selectedRequest) }},
                httpClientDetailTab: 'response',
            }"
            class="ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white ndb:p-4 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"
        >
            <x-newdebugbar::http-client-response-panel />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'http-client-source-panel', 'components' => $components])
        <div
            x-data="{
                selectedHttpClientRequest: {{ \Illuminate\Support\Js::from($selectedRequest) }},
                httpClientDetailTab: 'source',
            }"
            class="ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white ndb:p-4 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"
        >
            <x-newdebugbar::http-client-source-panel />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'http-client-workspace', 'components' => $components])
        <div class="ndb:flex ndb:h-[38rem] ndb:min-h-0 ndb:flex-col ndb:overflow-hidden ndb:bg-white ndb:dark:bg-zinc-950">
            <x-newdebugbar::http-client-workspace :items="$items" :summary="$summary" />
        </div>
    @endcomponent
</div>
