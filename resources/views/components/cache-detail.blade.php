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
            <x-newdebugbar::cache-overview-panel />
        </div>
    </template>

    <x-newdebugbar::inspector-detail-empty
        label="Choose an operation to inspect its evidence."
        x-show.important="! selectedCacheOperation"
    />
</x-newdebugbar::inspector-detail-pane>
