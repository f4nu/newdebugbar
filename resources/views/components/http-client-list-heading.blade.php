<div
    data-ndb-http-client-list-heading
    class="ndb:sticky ndb:top-0 ndb:z-10 ndb:grid ndb:h-auto ndb:grid-cols-[3rem_minmax(0,1fr)_3.5rem_4.75rem] ndb:items-center ndb:gap-x-2 ndb:border-b ndb:border-zinc-200/90 ndb:bg-white/95 ndb:px-3 ndb:py-2 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400 ndb:backdrop-blur-sm ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/95"
>
    <span>Method</span>
    <span>Request</span>
    <span class="ndb:text-right">Status</span>
    <span class="ndb:flex ndb:justify-end">
        <x-newdebugbar::inspector-sort-heading
            label="Time"
            align="right"
            active="httpClientSort === 'duration'"
            direction="httpClientSortDirection"
            data-ndb-http-client-sort-heading="duration"
            @click="toggleHttpClientSort('duration')"
        />
    </span>
</div>
