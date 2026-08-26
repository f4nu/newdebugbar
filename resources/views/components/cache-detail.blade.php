<x-newdebugbar::inspector-detail-pane
    detail-open="cacheDetailOpen"
    detail-ref="cacheDetail"
    detail-label="Selected cache operation details"
    back-label="Operations"
    close-action="cacheDetailOpen = false"
    data-ndb-cache-detail
>
    <x-slot:back>
        <x-newdebugbar::inspector-detail-back
            data-ndb-cache-detail-back
            @click="cacheDetailOpen = false"
            label="Operations"
        />
    </x-slot:back>

    <template x-if="selectedCacheOperation">
        <div class="ndb:flex ndb:flex-col">
            <x-newdebugbar::cache-header />
            <x-newdebugbar::cache-detail-tabs />

            <div>
                <x-newdebugbar::cache-overview-panel />
                <x-newdebugbar::cache-raw-panel />
                <x-newdebugbar::inspector-source-panel
                    frames="selectedCacheOperation.stack ?? []"
                    columns="1"
                    data-ndb-cache-detail-panel="source"
                    x-show.important="cacheDetailTab === 'source'"
                >
                    <x-newdebugbar::inspector-source-fact label="Triggered at">
                        <x-slot:value x-text="selectedCacheOperation.source_label"></x-slot:value>
                    </x-newdebugbar::inspector-source-fact>
                </x-newdebugbar::inspector-source-panel>
            </div>
        </div>
    </template>

    <x-newdebugbar::inspector-detail-empty
        label="Choose an operation to inspect its evidence."
        x-show.important="! selectedCacheOperation"
    />
</x-newdebugbar::inspector-detail-pane>
