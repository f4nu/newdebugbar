<div data-ndb-http-client-detail-panel="response" x-show.important="httpClientDetailTab === 'response'">
    <x-newdebugbar::inspector-facts columns="4" data-ndb-http-client-response-facts>
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
        <x-newdebugbar::inspector-fact label="Response body" x-show.important="selectedHttpClientRequest.response">
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

    <x-newdebugbar::inspector-definition-list
        data-ndb-http-client-failure
        x-show.important="selectedHttpClientRequest.failed"
        class="ndb:mt-4"
    >
        <x-newdebugbar::inspector-definition-row label="Failure" tone="danger">
            <x-slot:value x-text="selectedHttpClientRequest.response_summary"></x-slot:value>
        </x-newdebugbar::inspector-definition-row>
    </x-newdebugbar::inspector-definition-list>

    <template x-if="selectedHttpClientRequest.response">
        <div class="ndb:mt-5 ndb:space-y-5">
            <x-newdebugbar::inspector-evidence label="Headers">
                <x-slot:value x-text="formatHttpClientEvidence(selectedHttpClientRequest.response?.headers)"></x-slot:value>
            </x-newdebugbar::inspector-evidence>
            <x-newdebugbar::inspector-evidence label="Body">
                <x-slot:value x-text="formatHttpClientEvidence(selectedHttpClientRequest.response?.body)"></x-slot:value>
            </x-newdebugbar::inspector-evidence>
        </div>
    </template>

    <x-newdebugbar::http-client-no-response />
</div>
