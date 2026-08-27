<x-newdebugbar::inspector-detail-tabs label="Outbound HTTP request detail">
    @foreach (['response' => 'Response', 'request' => 'Request'] as $tab => $label)
        <x-newdebugbar::filter-tab
            variant="segmented"
            data-ndb-http-client-detail-tab="{{ $tab }}"
            @click="setHttpClientDetailTab({{ \Illuminate\Support\Js::from($tab) }})"
            ::aria-pressed="httpClientDetailTab === {{ \Illuminate\Support\Js::from($tab) }}"
            class="ndb:h-auto"
        >
            {{ $label }}
        </x-newdebugbar::filter-tab>
    @endforeach

    <x-newdebugbar::filter-tab
        variant="segmented"
        data-ndb-http-client-detail-tab="source"
        @click="setHttpClientDetailTab('source')"
        ::aria-pressed="httpClientDetailTab === 'source'"
        x-show.important="selectedHttpClientRequest?.has_source"
        class="ndb:h-auto"
    >
        Source
    </x-newdebugbar::filter-tab>
</x-newdebugbar::inspector-detail-tabs>
