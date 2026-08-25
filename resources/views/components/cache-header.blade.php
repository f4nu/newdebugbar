<x-newdebugbar::inspector-detail-header data-ndb-cache-header>
    <x-slot:title>
        <h3 class="ndb:flex ndb:min-w-0 ndb:flex-nowrap ndb:items-center ndb:gap-2 ndb:overflow-hidden">
            <x-newdebugbar::inspector-operation-badge
                outlined
                wide
                data-ndb-cache-detail-operation
                x-text="selectedCacheOperation.operation_label"
            ></x-newdebugbar::inspector-operation-badge>
            <span
                data-ndb-cache-detail-key
                :title="selectedCacheOperation.key_label"
                class="ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-bold ndb:leading-5 ndb:text-zinc-700 ndb:dark:text-zinc-200"
                x-text="selectedCacheOperation.key_label"
            ></span>
        </h3>
    </x-slot:title>
    <x-slot:aside></x-slot:aside>
</x-newdebugbar::inspector-detail-header>
