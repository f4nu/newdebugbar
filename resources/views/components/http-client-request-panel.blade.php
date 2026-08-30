<div data-ndb-http-client-detail-panel="request" class="ndb:p-3 ndb:sm:p-4">
    <div
        :class="selectedHttpClientRequest.request_has_headers || selectedHttpClientRequest.request_has_body
            ? 'ndb:border-b ndb:border-zinc-200/90 ndb:pb-3 ndb:sm:pb-4 ndb:dark:border-zinc-800'
            : ''"
        class="ndb:flex ndb:flex-col ndb:items-stretch ndb:justify-between ndb:gap-3 ndb:sm:flex-row ndb:sm:items-end"
    >
        <x-newdebugbar::inspector-facts
            :bordered="false"
            columns="2"
            data-ndb-http-client-request-facts
            class="ndb:min-w-0 ndb:flex-1"
        >
            <x-newdebugbar::inspector-fact label="Host">
                <x-slot:value
                    data-ndb-http-client-detail-host
                    ::title="selectedHttpClientRequest.host"
                    class="ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                    x-text="selectedHttpClientRequest.host"
                ></x-slot:value>
            </x-newdebugbar::inspector-fact>
            <x-newdebugbar::inspector-fact
                label="Request body"
                x-show.important="selectedHttpClientRequest.request_body_size_label !== '—'"
            >
                <x-slot:value
                    class="ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-700 ndb:dark:text-zinc-200"
                    x-text="selectedHttpClientRequest.request_body_size_label"
                ></x-slot:value>
            </x-newdebugbar::inspector-fact>
        </x-newdebugbar::inspector-facts>

        <x-newdebugbar::inspector-action
            icon="code"
            data-ndb-http-client-copy-curl
            @click="copyText(selectedHttpClientRequest.curl)"
            class="ndb:self-start ndb:sm:self-auto"
        >
            Copy safe cURL
        </x-newdebugbar::inspector-action>
    </div>

    <template x-if="selectedHttpClientRequest.request_has_headers || selectedHttpClientRequest.request_has_body">
        <div class="ndb:mt-3 ndb:space-y-3 ndb:sm:mt-5 ndb:sm:space-y-5">
            <template x-if="selectedHttpClientRequest.request_has_headers">
                <x-newdebugbar::inspector-evidence label="Headers">
                    <x-slot:value x-text="formatHttpClientEvidence(selectedHttpClientRequest.request?.headers)"></x-slot:value>
                </x-newdebugbar::inspector-evidence>
            </template>
            <template x-if="selectedHttpClientRequest.request_has_body">
                <x-newdebugbar::inspector-evidence label="Body">
                    <x-slot:value x-text="formatHttpClientEvidence(selectedHttpClientRequest.request?.body)"></x-slot:value>
                </x-newdebugbar::inspector-evidence>
            </template>
        </div>
    </template>
</div>
