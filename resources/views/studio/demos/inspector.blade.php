@component('newdebugbar::studio.component', ['component' => 'inspector-definition-list', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
    <x-newdebugbar::inspector-definition-list class="ndb:max-w-xl">
        <x-newdebugbar::inspector-definition-row label="Connection">sqlite</x-newdebugbar::inspector-definition-row>
        <x-newdebugbar::inspector-definition-row label="Table">bookings</x-newdebugbar::inspector-definition-row>
        <x-newdebugbar::inspector-definition-row label="Result" tone="danger"
            >Denied</x-newdebugbar::inspector-definition-row>
    </x-newdebugbar::inspector-definition-list>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'inspector-definition-row', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
    <x-newdebugbar::inspector-definition-list class="ndb:max-w-xl">
        <x-newdebugbar::inspector-definition-row label="Cache store"> redis </x-newdebugbar::inspector-definition-row>
    </x-newdebugbar::inspector-definition-list>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'inspector-detail-back', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
    <x-newdebugbar::inspector-detail-back persistent label="Requests" data-ndb-studio-detail-back />
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'inspector-detail-empty', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
    <div class="ndb:max-h-[32rem] ndb:overflow-hidden">
        <x-newdebugbar::inspector-detail-empty label="Select a request to inspect its response." />
    </div>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'inspector-detail-header', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
    <x-newdebugbar::inspector-detail-header layout="wrap" class="ndb:bg-white ndb:dark:bg-zinc-950">
        <x-slot:title>
            <div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-2">
                <x-newdebugbar::inspector-operation-badge outlined>GET</x-newdebugbar::inspector-operation-badge>
                <strong class="ndb:min-w-0 ndb:truncate ndb:text-sm">/v1/forecast?trip=kyoto-autumn</strong>
            </div>
        </x-slot:title>
        <x-slot:aside>
            <x-newdebugbar::inspector-action icon="copy">Copy URL</x-newdebugbar::inspector-action>
        </x-slot:aside>
        <x-slot:identity>
            <p class="ndb:text-xs ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200">weather.morrow.test</p>
        </x-slot:identity>
        <x-slot:metadata>
            <div>
                <dt class="ndb:sr-only">Status</dt>
                <dd>200 OK</dd>
            </div>
            <div>
                <dt class="ndb:sr-only">Runtime</dt>
                <dd class="ndb:tabular-nums">363.1 ms</dd>
            </div>
        </x-slot:metadata>
    </x-newdebugbar::inspector-detail-header>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'inspector-detail-pane', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
    <div
        x-data="{ newdebugbarStudioDetailPaneOpen: true }"
        class="ndb:h-80 ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"
    >
        <x-newdebugbar::inspector-detail-pane
            detail-open="newdebugbarStudioDetailPaneOpen"
            detail-ref="newdebugbarStudioDetailPane"
            detail-label="Selected request details"
            back-label="Requests"
            close-action="newdebugbarStudioDetailPaneOpen = false"
            class="ndb:h-full"
        >
            <x-slot:back>
                <x-newdebugbar::inspector-detail-back
                    persistent
                    label="Requests"
                    @click="newdebugbarStudioDetailPaneOpen = false"
                />
            </x-slot:back>
            <div class="ndb:flex-1 ndb:p-4">
                <h3 class="ndb:text-sm ndb:font-bold">Response received</h3>
                <p class="ndb:mt-1 ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                    The request completed with a 200 response in 363.1 ms.
                </p>
            </div>
        </x-newdebugbar::inspector-detail-pane>
    </div>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'inspector-detail-tabs', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
    <div x-data="{ newdebugbarStudioDetailTab: 'response' }" class="ndb:bg-white ndb:dark:bg-zinc-950">
        <x-newdebugbar::inspector-detail-tabs label="Request detail">
            @foreach (['response', 'request', 'source'] as $tab)
                <x-newdebugbar::filter-tab
                    variant="segmented"
                    @click="newdebugbarStudioDetailTab = '{{ $tab }}'"
                    ::aria-pressed="newdebugbarStudioDetailTab === '{{ $tab }}'"
                >{{ ucfirst($tab) }}</x-newdebugbar::filter-tab>
            @endforeach
        </x-newdebugbar::inspector-detail-tabs>
    </div>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'inspector-evidence', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
    <x-newdebugbar::inspector-evidence label="Response body" language="json">
        <x-slot:value>{ "temperature": 18, "conditions": "clear" }</x-slot:value>
    </x-newdebugbar::inspector-evidence>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'inspector-explanation', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
    <x-newdebugbar::inspector-explanation
        title="Why was this record loaded more than once?"
        description="The same record was retrieved three times during this request. If that was not intentional, inspect the listed sources for work that can reuse an earlier result."
    />
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'inspector-fact', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
    <x-newdebugbar::inspector-facts columns="2" :bordered="false" class="ndb:max-w-md">
        <x-newdebugbar::inspector-fact label="Runtime">
            <span class="ndb:text-xs ndb:font-bold ndb:tabular-nums ndb:text-amber-600 ndb:dark:text-amber-400">363.1 ms</span>
        </x-newdebugbar::inspector-fact>
        <x-newdebugbar::inspector-fact label="Status">
            <span class="ndb:text-xs ndb:font-bold">200 OK</span>
        </x-newdebugbar::inspector-fact>
    </x-newdebugbar::inspector-facts>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'inspector-facts', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
    <x-newdebugbar::inspector-facts columns="4">
        <x-newdebugbar::inspector-fact label="Status"><span class="ndb:text-xs ndb:font-bold">200 OK</span></x-newdebugbar::inspector-fact>
        <x-newdebugbar::inspector-fact label="Runtime"><span class="ndb:text-xs ndb:font-bold ndb:tabular-nums">363.1 ms</span></x-newdebugbar::inspector-fact>
        <x-newdebugbar::inspector-fact label="Host"><span class="ndb:text-xs ndb:font-semibold">weather.morrow.test</span></x-newdebugbar::inspector-fact>
        <x-newdebugbar::inspector-fact label="Response body"><span class="ndb:text-xs ndb:font-semibold ndb:tabular-nums">1.8 KB</span></x-newdebugbar::inspector-fact>
    </x-newdebugbar::inspector-facts>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'inspector-list-panel', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
    <div
        x-data="{ newdebugbarStudioListDetailOpen: false }"
        class="ndb:h-80 ndb:max-w-xl ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"
    >
        <x-newdebugbar::inspector-list-panel
            detail-open="newdebugbarStudioListDetailOpen"
            list-ref="newdebugbarStudioList"
            class="ndb:h-full"
        >
            <x-slot:controls>
                <x-newdebugbar::search-field label="Search requests" placeholder="Search requests" />
            </x-slot:controls>
            <x-slot:list>
                @foreach ([['GET', 'weather.morrow.test', '363.1 ms'], ['POST', 'concierge.morrow.test', '8.4 ms'], ['GET', 'rail.morrow.test', '3.7 ms']] as [$method, $host, $runtime])
                    <button
                        type="button"
                        @click="newdebugbarStudioListDetailOpen = true"
                        class="ndb:flex ndb:w-full ndb:items-center ndb:gap-3 ndb:px-3 ndb:py-3 ndb:text-left ndb:hover:bg-indigo-50/70 ndb:dark:hover:bg-indigo-950/20"
                    >
                        <x-newdebugbar::inspector-operation-badge>{{ $method }}</x-newdebugbar::inspector-operation-badge>
                        <span class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-semibold">{{ $host }}</span>
                        <span class="ndb:shrink-0 ndb:text-[11px] ndb:tabular-nums ndb:text-zinc-500">{{ $runtime }}</span>
                    </button>
                @endforeach
            </x-slot:list>
        </x-newdebugbar::inspector-list-panel>
    </div>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'inspector-source-fact', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
    <dl class="ndb:grid ndb:max-w-2xl ndb:gap-3 ndb:sm:grid-cols-2">
        <x-newdebugbar::inspector-source-fact label="Request initiated at">
            app/Travel/LocalPartnerGateway.php:49
        </x-newdebugbar::inspector-source-fact>
        <x-newdebugbar::inspector-source-fact label="Notification class" :code="true">
            App\Notifications\TripReady
        </x-newdebugbar::inspector-source-fact>
    </dl>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'inspector-source-link', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
    <x-newdebugbar::inspector-source-link>
        app/Actions/Trips/RefreshTripWorkspace.php:150
    </x-newdebugbar::inspector-source-link>
@endcomponent

@php
    $newdebugbarStudioFrames = [
        ['function' => 'App\\Services\\WeatherClient->forecast', 'file' => 'app/Services/WeatherClient.php', 'line' => 42],
        ['function' => 'App\\Actions\\Trips\\RefreshTripWorkspace->handle', 'file' => 'app/Actions/Trips/RefreshTripWorkspace.php', 'line' => 150],
        ['function' => 'App\\Http\\Controllers\\TripController->show', 'file' => 'app/Http/Controllers/TripController.php', 'line' => 31],
    ];
@endphp
@component('newdebugbar::studio.component', ['component' => 'inspector-stack', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
    <div x-data="{ newdebugbarStudioFrames: @js($newdebugbarStudioFrames) }" class="ndb:max-w-2xl">
        <x-newdebugbar::inspector-stack frames="newdebugbarStudioFrames" />
    </div>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'inspector-workspace', 'components' => $components, 'selected' => $selected, 'kind' => $selectedComponent['kind']])
    <div x-data="{ newdebugbarStudioWorkspaceDetailOpen: false }" class="ndb:h-96 ndb:overflow-hidden">
        <x-newdebugbar::inspector-workspace detail-open="newdebugbarStudioWorkspaceDetailOpen" class="ndb:h-full">
            <x-newdebugbar::inspector-list-panel
                detail-open="newdebugbarStudioWorkspaceDetailOpen"
                list-ref="newdebugbarStudioWorkspaceList"
            >
                <x-slot:controls>
                    <x-newdebugbar::search-field label="Search operations" placeholder="Search operations" />
                </x-slot:controls>
                <x-slot:list>
                    @foreach ([['GET', 'weather.morrow.test'], ['POST', 'concierge.morrow.test'], ['GET', 'rail.morrow.test']] as [$method, $host])
                        <button
                            type="button"
                            @click="newdebugbarStudioWorkspaceDetailOpen = true"
                            class="ndb:flex ndb:w-full ndb:items-center ndb:gap-3 ndb:px-3 ndb:py-3 ndb:text-left ndb:hover:bg-indigo-50/70 ndb:dark:hover:bg-indigo-950/20"
                        >
                            <x-newdebugbar::inspector-operation-badge>{{ $method }}</x-newdebugbar::inspector-operation-badge>
                            <span class="ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-semibold">{{ $host }}</span>
                        </button>
                    @endforeach
                </x-slot:list>
            </x-newdebugbar::inspector-list-panel>

            <x-newdebugbar::inspector-detail-pane
                detail-open="newdebugbarStudioWorkspaceDetailOpen"
                detail-ref="newdebugbarStudioWorkspaceDetail"
                detail-label="Selected operation details"
                back-label="Operations"
                close-action="newdebugbarStudioWorkspaceDetailOpen = false"
            >
                <x-newdebugbar::inspector-detail-header layout="wrap">
                    <x-slot:title>
                        <div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-2">
                            <x-newdebugbar::inspector-operation-badge outlined>GET</x-newdebugbar::inspector-operation-badge>
                            <strong class="ndb:min-w-0 ndb:truncate ndb:text-sm">weather.morrow.test</strong>
                        </div>
                    </x-slot:title>
                    <x-slot:aside>
                        <x-newdebugbar::inspector-action icon="copy">Copy URL</x-newdebugbar::inspector-action>
                    </x-slot:aside>
                </x-newdebugbar::inspector-detail-header>
                <div class="ndb:flex-1 ndb:p-4">
                    <x-newdebugbar::inspector-facts columns="2" :bordered="false">
                        <x-newdebugbar::inspector-fact label="Status"><span class="ndb:text-xs ndb:font-bold">200 OK</span></x-newdebugbar::inspector-fact>
                        <x-newdebugbar::inspector-fact label="Runtime"
                            ><span class="ndb:text-xs ndb:font-bold ndb:tabular-nums"
                                >363.1 ms</span
                            ></x-newdebugbar::inspector-fact>
                    </x-newdebugbar::inspector-facts>
                </div>
            </x-newdebugbar::inspector-detail-pane>
        </x-newdebugbar::inspector-workspace>
    </div>
@endcomponent
