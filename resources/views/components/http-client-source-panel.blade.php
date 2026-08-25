<div data-ndb-http-client-detail-panel="source" x-show.important="httpClientDetailTab === 'source'">
    <dl class="ndb:grid ndb:gap-2" data-ndb-http-client-source-facts>
        <x-newdebugbar::inspector-source-fact label="Request initiated at">
            <x-slot:value
                data-ndb-http-client-detail-source
                ::title="selectedHttpClientRequest.callsite_label"
                x-text="selectedHttpClientRequest.callsite_label"
            ></x-slot:value>
        </x-newdebugbar::inspector-source-fact>
    </dl>

    <x-newdebugbar::inspector-stack frames="selectedHttpClientRequest.stack ?? []" />
</div>
