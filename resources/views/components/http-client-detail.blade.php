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

            <div>
                <x-newdebugbar::http-client-request-panel />
                <x-newdebugbar::http-client-response-panel />
                <x-newdebugbar::inspector-source-panel
                    frames="selectedHttpClientRequest.stack ?? []"
                    columns="1"
                    data-ndb-http-client-detail-panel="source"
                    data-ndb-http-client-source-facts
                    x-show.important="httpClientDetailTab === 'source'"
                >
                    <x-newdebugbar::inspector-source-fact label="Request initiated at">
                        <x-slot:value
                            data-ndb-http-client-detail-source
                            ::title="selectedHttpClientRequest.callsite_label"
                            x-text="selectedHttpClientRequest.callsite_label"
                        ></x-slot:value>
                    </x-newdebugbar::inspector-source-fact>
                </x-newdebugbar::inspector-source-panel>
            </div>
        </div>
    </template>

    <x-newdebugbar::inspector-detail-empty
        label="Choose a request to inspect its evidence."
        x-show.important="! selectedHttpClientRequest"
    />
</x-newdebugbar::inspector-detail-pane>
