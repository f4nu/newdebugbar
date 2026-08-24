<div data-ndb-http-client-detail-panel="source" x-show.important="httpClientDetailTab === 'source'">
    <x-newdebugbar::inspector-facts columns="1" data-ndb-http-client-source-facts>
        <x-newdebugbar::inspector-fact label="Source">
            <code
                data-ndb-http-client-detail-source
                :title="selectedHttpClientRequest.callsite_label"
                class="ndb:block ndb:max-w-full ndb:truncate ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:text-indigo-600 ndb:dark:text-indigo-300"
                x-text="selectedHttpClientRequest.callsite_label"
            ></code>
        </x-newdebugbar::inspector-fact>
    </x-newdebugbar::inspector-facts>

    <x-newdebugbar::inspector-stack frames="selectedHttpClientRequest.stack ?? []" />
</div>
