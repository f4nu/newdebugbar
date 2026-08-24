<x-newdebugbar::inspector-detail-pane
    detail-open="httpClientDetailOpen"
    detail-ref="httpClientDetail"
    detail-label="Selected outbound HTTP request details"
    back-label="Requests"
    close-action="httpClientDetailOpen = false"
    data-ndb-http-client-detail
>
    <x-slot:back>
        <x-newdebugbar::inspector-detail-back
            data-ndb-http-client-detail-back
            @click="httpClientDetailOpen = false"
            label="Requests"
        />
    </x-slot:back>

    <template x-if="selectedHttpClientRequest">
        <div class="ndb:flex ndb:flex-col">
            <x-newdebugbar::http-client-header />
            <x-newdebugbar::http-client-detail-tabs />

            <div class="ndb:p-4">
                <x-newdebugbar::http-client-request-panel />
                <x-newdebugbar::http-client-response-panel />
                <x-newdebugbar::http-client-source-panel />
            </div>
        </div>
    </template>

    <x-newdebugbar::inspector-detail-empty
        label="Choose a request to inspect its evidence."
        x-show.important="! selectedHttpClientRequest"
    />
</x-newdebugbar::inspector-detail-pane>
