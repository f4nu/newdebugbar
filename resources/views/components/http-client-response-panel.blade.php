<div data-ndb-http-client-detail-panel="response" class="ndb:p-4">
    <x-newdebugbar::inspector-facts
        :bordered="false"
        columns="4"
        data-ndb-http-client-response-facts
        ::class="selectedHttpClientRequest.response_has_headers
            || selectedHttpClientRequest.response_has_body
            || ! selectedHttpClientRequest.response
                ? 'ndb:border-b ndb:border-zinc-200/90 ndb:pb-4 ndb:dark:border-zinc-800'
                : ''"
    >
        <x-newdebugbar::inspector-fact label="Status">
            <x-slot:value
                data-ndb-http-client-detail-status
                ::class="selectedHttpClientRequest.failed
                    ? 'ndb:text-red-700 ndb:dark:text-red-300'
                    : 'ndb:text-zinc-700 ndb:dark:text-zinc-200'"
                class="ndb:truncate ndb:text-[11px] ndb:font-bold"
                x-text="selectedHttpClientRequest.status_label"
            ></x-slot:value>
        </x-newdebugbar::inspector-fact>
        <x-newdebugbar::inspector-fact label="Runtime">
            <x-slot:value
                data-ndb-http-client-detail-runtime
                ::class="selectedHttpClientRequest.slow
                    ? 'ndb:text-amber-700 ndb:dark:text-amber-300'
                    : 'ndb:text-zinc-700 ndb:dark:text-zinc-200'"
                class="ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:tabular-nums"
                x-text="selectedHttpClientRequest.duration_label"
            ></x-slot:value>
        </x-newdebugbar::inspector-fact>
        <x-newdebugbar::inspector-fact
            label="Response body"
            x-show.important="
                selectedHttpClientRequest.response && selectedHttpClientRequest.response_body_size_label !== '—'
            "
        >
            <x-slot:value
                class="ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-700 ndb:dark:text-zinc-200"
                x-text="selectedHttpClientRequest.response_body_size_label"
            ></x-slot:value>
        </x-newdebugbar::inspector-fact>
        <x-newdebugbar::inspector-fact
            label="Redirect to"
            x-show.important="selectedHttpClientRequest.redirect_location"
        >
            <x-slot:value
                ::title="selectedHttpClientRequest.redirect_location"
                class="ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                x-text="selectedHttpClientRequest.redirect_location"
            ></x-slot:value>
        </x-newdebugbar::inspector-fact>
    </x-newdebugbar::inspector-facts>

    <template x-if="selectedHttpClientRequest.response_has_headers || selectedHttpClientRequest.response_has_body">
        <div class="ndb:mt-5 ndb:space-y-5">
            <template x-if="selectedHttpClientRequest.response_has_headers">
                <x-newdebugbar::inspector-evidence label="Headers">
                    <x-slot:value x-text="formatHttpClientEvidence(selectedHttpClientRequest.response?.headers)"></x-slot:value>
                </x-newdebugbar::inspector-evidence>
            </template>
            <template x-if="selectedHttpClientRequest.response_has_body">
                <x-newdebugbar::inspector-evidence label="Body">
                    <x-slot:value x-text="formatHttpClientEvidence(selectedHttpClientRequest.response?.body)"></x-slot:value>
                </x-newdebugbar::inspector-evidence>
            </template>
        </div>
    </template>

    <x-newdebugbar::http-client-no-response />
</div>
