<dl
    data-ndb-http-client-facts
    class="ndb:grid ndb:grid-cols-2 ndb:gap-x-5 ndb:gap-y-3 ndb:border-b ndb:border-zinc-200/90 ndb:pb-4 ndb:dark:border-zinc-800 ndb:sm:grid-cols-4"
>
    <div class="ndb:min-w-0">
        <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">Status</dt>
        <dd
            data-ndb-http-client-detail-status
            :class="selectedHttpClientRequest.failed
                ? 'ndb:text-red-700 ndb:dark:text-red-300'
                : 'ndb:text-zinc-700 ndb:dark:text-zinc-200'"
            class="ndb:mt-0.5 ndb:truncate ndb:text-[11px] ndb:font-bold"
            x-text="selectedHttpClientRequest.status_label"
        ></dd>
    </div>
    <div class="ndb:min-w-0">
        <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">Runtime</dt>
        <dd
            :class="selectedHttpClientRequest.slow
                ? 'ndb:text-amber-700 ndb:dark:text-amber-300'
                : 'ndb:text-zinc-700 ndb:dark:text-zinc-200'"
            class="ndb:mt-0.5 ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:tabular-nums"
            x-text="selectedHttpClientRequest.duration_label"
        ></dd>
    </div>
    <div class="ndb:min-w-0">
        <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">Host</dt>
        <dd
            :title="selectedHttpClientRequest.host"
            class="ndb:mt-0.5 ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
            x-text="selectedHttpClientRequest.host"
        ></dd>
    </div>
    <div x-show.important="selectedHttpClientRequest.callsite" class="ndb:min-w-0">
        <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">Source</dt>
        <dd class="ndb:mt-0.5 ndb:min-w-0">
            <button
                type="button"
                data-ndb-http-client-source-link
                :title="selectedHttpClientRequest.callsite_label"
                @click="setHttpClientDetailTab('source')"
                class="ndb:block ndb:h-auto ndb:max-w-full ndb:truncate ndb:text-left ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:text-indigo-600 ndb:underline-offset-2 ndb:hover:underline ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                x-text="selectedHttpClientRequest.callsite_short_label"
            ></button>
        </dd>
    </div>
</dl>
