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
                <template x-if="httpClientDetailTab === 'response'">
                    <x-newdebugbar::http-client-response-panel />
                </template>

                <template x-if="httpClientDetailTab === 'request'">
                    <x-newdebugbar::http-client-request-panel />
                </template>

                <template x-if="httpClientDetailTab === 'source'">
                    <x-newdebugbar::inspector-source-panel
                        frames="selectedHttpClientRequest.stack ?? []"
                        columns="1"
                        data-ndb-http-client-detail-panel="source"
                        data-ndb-http-client-source-facts
                    >
                        <template x-if="selectedHttpClientRequest.callsite_label">
                            <div data-ndb-http-client-primary-source class="ndb:min-w-0">
                                <dt class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                    Request initiated at
                                </dt>
                                <dd class="ndb:mt-1 ndb:min-w-0">
                                    <x-newdebugbar::inspector-source-link
                                        ::aria-label="'Copy source ' + selectedHttpClientRequest.callsite_label"
                                        @click="copyText(selectedHttpClientRequest.callsite_label)"
                                    >
                                        <x-slot:value
                                            data-ndb-http-client-detail-source
                                            ::title="selectedHttpClientRequest.callsite_label"
                                            x-text="selectedHttpClientRequest.callsite_label"
                                        ></x-slot:value>
                                    </x-newdebugbar::inspector-source-link>
                                </dd>
                            </div>
                        </template>
                    </x-newdebugbar::inspector-source-panel>
                </template>
            </div>
        </div>
    </template>

    <x-newdebugbar::inspector-detail-empty
        label="Choose a request to inspect its evidence."
        x-show.important="! selectedHttpClientRequest"
    />
</x-newdebugbar::inspector-detail-pane>
