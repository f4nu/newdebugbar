<x-newdebugbar::inspector-detail-header data-ndb-cache-header>
    <x-slot:title>
        <h3 class="ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-2 ndb:overflow-hidden">
            <span
                data-ndb-cache-detail-operation
                class="ndb:shrink-0 ndb:text-base ndb:font-bold ndb:leading-6"
                x-text="selectedCacheOperation.operation_label"
            ></span>
            <span
                data-ndb-cache-detail-key
                :title="selectedCacheOperation.key_label"
                class="ndb:min-w-0 ndb:truncate ndb:text-sm ndb:font-semibold ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                x-text="selectedCacheOperation.key_label"
            ></span>
        </h3>
    </x-slot:title>
    <x-slot:aside></x-slot:aside>
</x-newdebugbar::inspector-detail-header>
