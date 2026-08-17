@props(['scope'])

<span
    data-ndb-mobile-request-metrics="{{ $scope }}"
    class="ndb:grid ndb:min-w-0 ndb:flex-1 ndb:grid-cols-3 ndb:items-center"
>
    <span class="ndb:flex ndb:min-w-0 ndb:flex-col ndb:items-center ndb:min-[360px]:px-0.5">
        <span
            data-ndb-mobile-toolbar-summary="queries"
            class="ndb:block ndb:max-w-full ndb:truncate ndb:text-[10px] ndb:font-bold ndb:leading-4 ndb:tabular-nums ndb:min-[360px]:text-[11px]"
            x-text="summary.query_count"
        ></span>
        <span
            data-ndb-mobile-toolbar-metric-label="queries"
            class="ndb:block ndb:max-w-full ndb:truncate ndb:text-[8px] ndb:font-semibold ndb:leading-3 ndb:uppercase ndb:tracking-normal ndb:text-zinc-400 ndb:min-[360px]:text-[9px]"
            ><span class="ndb:min-[360px]:hidden">SQL</span
            ><span class="ndb:hidden ndb:min-[360px]:inline">Queries</span></span>
    </span>
    <span class="ndb:flex ndb:min-w-0 ndb:flex-col ndb:items-center ndb:border-l ndb:border-zinc-200/80 ndb:min-[360px]:px-0.5 ndb:dark:border-zinc-700/80">
        <span
            data-ndb-mobile-toolbar-summary="duration"
            class="ndb:block ndb:max-w-full ndb:truncate ndb:text-[10px] ndb:font-bold ndb:leading-4 ndb:tabular-nums ndb:min-[360px]:text-[11px]"
            x-text="summary.duration_ms"
        ></span>
        <span
            data-ndb-mobile-toolbar-metric-label="duration"
            class="ndb:block ndb:max-w-full ndb:truncate ndb:text-[8px] ndb:font-semibold ndb:leading-3 ndb:uppercase ndb:tracking-normal ndb:text-zinc-400 ndb:min-[360px]:text-[9px]"
            ><span class="ndb:min-[360px]:hidden">ms</span
            ><span class="ndb:hidden ndb:min-[360px]:inline">Time ms</span></span>
    </span>
    <span class="ndb:flex ndb:min-w-0 ndb:flex-col ndb:items-center ndb:border-l ndb:border-zinc-200/80 ndb:min-[360px]:px-0.5 ndb:dark:border-zinc-700/80">
        <span
            data-ndb-mobile-toolbar-summary="memory"
            class="ndb:block ndb:max-w-full ndb:truncate ndb:text-[10px] ndb:font-bold ndb:leading-4 ndb:tabular-nums ndb:min-[360px]:text-[11px]"
            x-text="summary.peak_memory_mb"
        ></span>
        <span
            data-ndb-mobile-toolbar-metric-label="memory"
            class="ndb:block ndb:max-w-full ndb:truncate ndb:text-[8px] ndb:font-semibold ndb:leading-3 ndb:uppercase ndb:tracking-normal ndb:text-zinc-400 ndb:min-[360px]:text-[9px]"
            ><span class="ndb:min-[360px]:hidden">MB</span
            ><span class="ndb:hidden ndb:min-[360px]:inline">Peak MB</span></span>
    </span>
</span>
