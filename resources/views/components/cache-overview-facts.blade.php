<x-newdebugbar::inspector-facts columns="4" data-ndb-cache-metadata>
    <x-newdebugbar::inspector-fact label="Result">
        <x-slot:value
            ::class="selectedCacheOperation.failed
                ? 'ndb:text-red-700 ndb:dark:text-red-300'
                : selectedCacheOperation.result === 'miss' || selectedCacheOperation.result === 'flushed'
                  ? 'ndb:text-amber-700 ndb:dark:text-amber-300'
                  : 'ndb:text-zinc-700 ndb:dark:text-zinc-200'"
            class="ndb:truncate ndb:text-[11px] ndb:font-bold"
            x-text="selectedCacheOperation.result_label"
        ></x-slot:value>
    </x-newdebugbar::inspector-fact>
    <x-newdebugbar::inspector-fact label="Runtime">
        <x-slot:value
            class="ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-700 ndb:dark:text-zinc-200"
            x-text="selectedCacheOperation.duration_label"
        ></x-slot:value>
    </x-newdebugbar::inspector-fact>
    <x-newdebugbar::inspector-fact label="Store">
        <x-slot:value
            ::title="selectedCacheOperation.store_label"
            class="ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
            x-text="selectedCacheOperation.store_label"
        ></x-slot:value>
    </x-newdebugbar::inspector-fact>
    <x-newdebugbar::inspector-fact label="Driver" x-show.important="selectedCacheOperation.driver_label">
        <x-slot:value
            ::title="selectedCacheOperation.driver_label"
            class="ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
            x-text="selectedCacheOperation.driver_label"
        ></x-slot:value>
    </x-newdebugbar::inspector-fact>
    <x-newdebugbar::inspector-fact
        label="Source"
        x-show.important="selectedCacheOperation.source_label !== 'Source unavailable'"
        class="ndb:col-span-2 ndb:sm:col-span-4"
    >
        <x-newdebugbar::inspector-source-link
            ::title="selectedCacheOperation.source_label"
            @click="setCacheDetailTab('source')"
        >
            <x-slot:value x-text="selectedCacheOperation.source_short_label"></x-slot:value>
        </x-newdebugbar::inspector-source-link>
    </x-newdebugbar::inspector-fact>
</x-newdebugbar::inspector-facts>
