<header data-ndb-cache-header class="ndb:border-b ndb:border-zinc-200/90 ndb:p-4 ndb:dark:border-zinc-800">
    <h3
        data-ndb-cache-detail-operation
        class="ndb:text-base ndb:font-bold ndb:leading-6"
        x-text="selectedCacheOperation.operation_label"
    ></h3>
    <code
        data-ndb-cache-detail-key
        :title="selectedCacheOperation.key_label"
        class="ndb:mt-1 ndb:block ndb:break-all ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
        x-text="selectedCacheOperation.key_label"
    ></code>
</header>
