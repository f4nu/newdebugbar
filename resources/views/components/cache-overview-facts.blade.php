<dl
    data-ndb-cache-metadata
    class="ndb:grid ndb:grid-cols-2 ndb:gap-x-5 ndb:gap-y-3 ndb:border-b ndb:border-zinc-200/90 ndb:pb-4 ndb:dark:border-zinc-800 ndb:sm:grid-cols-4"
>
    <div class="ndb:min-w-0">
        <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">Result</dt>
        <dd
            :class="selectedCacheOperation.failed
                ? 'ndb:text-red-700 ndb:dark:text-red-300'
                : selectedCacheOperation.result === 'miss' || selectedCacheOperation.result === 'flushed'
                  ? 'ndb:text-amber-700 ndb:dark:text-amber-300'
                  : 'ndb:text-zinc-700 ndb:dark:text-zinc-200'"
            class="ndb:mt-0.5 ndb:truncate ndb:text-[11px] ndb:font-bold"
            x-text="selectedCacheOperation.result_label"
        ></dd>
    </div>
    <div class="ndb:min-w-0">
        <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">Runtime</dt>
        <dd
            class="ndb:mt-0.5 ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-700 ndb:dark:text-zinc-200"
            x-text="selectedCacheOperation.duration_label"
        ></dd>
    </div>
    <div class="ndb:min-w-0">
        <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">Store</dt>
        <dd
            :title="selectedCacheOperation.store_label"
            class="ndb:mt-0.5 ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
            x-text="selectedCacheOperation.store_label"
        ></dd>
    </div>
    <div x-show.important="selectedCacheOperation.driver_label" class="ndb:min-w-0">
        <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">Driver</dt>
        <dd
            :title="selectedCacheOperation.driver_label"
            class="ndb:mt-0.5 ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
            x-text="selectedCacheOperation.driver_label"
        ></dd>
    </div>
    <div
        x-show.important="selectedCacheOperation.source_label !== 'Source unavailable'"
        class="ndb:col-span-2 ndb:min-w-0 ndb:sm:col-span-4"
    >
        <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">Source</dt>
        <dd class="ndb:mt-0.5 ndb:min-w-0">
            <button
                type="button"
                :title="selectedCacheOperation.source_label"
                @click="setCacheDetailTab('source')"
                class="ndb:block ndb:max-w-full ndb:truncate ndb:text-left ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:text-indigo-600 ndb:underline-offset-2 ndb:hover:underline ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                x-text="selectedCacheOperation.source_short_label"
            ></button>
        </dd>
    </div>
</dl>
