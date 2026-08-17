@props(['scope'])

<button
    type="button"
    role="menuitem"
    data-ndb-mobile-request-fact="environment"
    data-ndb-mobile-request-fact-scope="{{ $scope }}"
    @click="openInspector('overview')"
    class="ndb:flex ndb:min-h-11 ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-left ndb:transition-colors ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-white/10"
>
    <x-newdebugbar::icon name="server" class="ndb:size-4 ndb:text-zinc-500 ndb:dark:text-zinc-400" />
    <span class="ndb:min-w-0 ndb:flex-1 ndb:text-sm ndb:font-medium">Environment</span>
    <span
        class="ndb:max-w-24 ndb:truncate ndb:text-right ndb:text-sm ndb:font-semibold"
        x-text="summary.environment"
    ></span>
    <x-newdebugbar::icon name="chevron-down" class="ndb:size-3.5 ndb:-rotate-90 ndb:text-zinc-400" />
</button>
<button
    type="button"
    role="menuitem"
    data-ndb-mobile-request-fact="queries"
    data-ndb-mobile-request-fact-scope="{{ $scope }}"
    @click="openInspector('queries')"
    class="ndb:flex ndb:min-h-11 ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-left ndb:transition-colors ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-white/10"
>
    <x-newdebugbar::icon name="database" class="ndb:size-4 ndb:text-zinc-500 ndb:dark:text-zinc-400" />
    <span class="ndb:min-w-0 ndb:flex-1 ndb:text-sm ndb:font-medium">Queries</span>
    <span
        data-ndb-mobile-toolbar-fact-value="queries"
        class="ndb:text-sm ndb:font-semibold ndb:tabular-nums"
        x-text="summary.query_count"
    ></span>
    <x-newdebugbar::icon name="chevron-down" class="ndb:size-3.5 ndb:-rotate-90 ndb:text-zinc-400" />
</button>
<button
    type="button"
    role="menuitem"
    data-ndb-mobile-request-fact="duration"
    data-ndb-mobile-request-fact-scope="{{ $scope }}"
    @click="openInspector('request')"
    class="ndb:flex ndb:min-h-11 ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-left ndb:transition-colors ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-white/10"
>
    <x-newdebugbar::icon name="clock" class="ndb:size-4 ndb:text-zinc-500 ndb:dark:text-zinc-400" />
    <span class="ndb:min-w-0 ndb:flex-1 ndb:text-sm ndb:font-medium">Duration</span>
    <span
        data-ndb-mobile-toolbar-fact-value="duration"
        class="ndb:text-sm ndb:font-semibold ndb:tabular-nums"
        x-text="summary.duration_ms + ' ms'"
    ></span>
    <x-newdebugbar::icon name="chevron-down" class="ndb:size-3.5 ndb:-rotate-90 ndb:text-zinc-400" />
</button>
<button
    type="button"
    role="menuitem"
    data-ndb-mobile-request-fact="memory"
    data-ndb-mobile-request-fact-scope="{{ $scope }}"
    @click="openInspector('overview')"
    class="ndb:flex ndb:min-h-11 ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-left ndb:transition-colors ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-white/10"
>
    <x-newdebugbar::icon name="memory" class="ndb:size-4 ndb:text-zinc-500 ndb:dark:text-zinc-400" />
    <span class="ndb:min-w-0 ndb:flex-1 ndb:text-sm ndb:font-medium">Peak</span>
    <span
        data-ndb-mobile-toolbar-fact-value="memory"
        class="ndb:text-sm ndb:font-semibold ndb:tabular-nums"
        x-text="summary.peak_memory_mb + ' MB'"
    ></span>
    <x-newdebugbar::icon name="chevron-down" class="ndb:size-3.5 ndb:-rotate-90 ndb:text-zinc-400" />
</button>
