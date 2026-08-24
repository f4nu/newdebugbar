<x-newdebugbar::inspector-detail-header data-ndb-cache-header>
    <x-slot:title>
        <h3
            data-ndb-cache-detail-operation
            class="ndb:text-base ndb:font-bold ndb:leading-6"
            x-text="selectedCacheOperation.operation_label"
        ></h3>
    </x-slot:title>

    <x-slot:aside>
        <span
            data-ndb-cache-detail-result
            :class="selectedCacheOperation.failed
                ? 'ndb:text-red-700 ndb:dark:text-red-300'
                : selectedCacheOperation.result === 'miss' || selectedCacheOperation.result === 'flushed'
                  ? 'ndb:text-amber-700 ndb:dark:text-amber-300'
                  : 'ndb:text-zinc-600 ndb:dark:text-zinc-300'"
            class="ndb:shrink-0 ndb:text-xs ndb:font-bold"
            x-text="selectedCacheOperation.result_label"
        ></span>
    </x-slot:aside>

    <x-slot:identity data-ndb-cache-identity>
        <div class="ndb:grid ndb:gap-2 ndb:sm:grid-cols-[minmax(0,1fr)_auto] ndb:sm:items-center">
            <div class="ndb:min-w-0">
                <p class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">Key</p>
                <code
                    data-ndb-cache-detail-key
                    :title="selectedCacheOperation.key_label"
                    class="ndb:mt-0.5 ndb:block ndb:break-all ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:leading-5 ndb:text-zinc-700 ndb:dark:text-zinc-200"
                    x-text="selectedCacheOperation.key_label"
                ></code>
            </div>
            <div data-ndb-cache-actions class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-1">
                <button
                    x-show.important="selectedCacheOperation.copy_key"
                    type="button"
                    data-ndb-cache-copy-key
                    @click="copyText(selectedCacheOperation.copy_key)"
                    class="ndb:inline-flex ndb:h-auto ndb:min-h-8 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:px-2 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:transition ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/50"
                >
                    <x-newdebugbar::icon name="copy" size="3.5" />
                    Copy key
                </button>
            </div>
        </div>
    </x-slot:identity>

    <x-slot:metadata data-ndb-cache-metadata class="ndb:w-full">
        <div class="ndb:grid ndb:w-full ndb:grid-cols-2 ndb:gap-x-4 ndb:gap-y-3 ndb:border-0 ndb:bg-transparent ndb:p-0 ndb:sm:grid-cols-4">
            <div class="ndb:min-w-0 ndb:bg-transparent">
                <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">Store</dt>
                <dd
                    :title="selectedCacheOperation.store_label"
                    class="ndb:mt-0.5 ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                    x-text="selectedCacheOperation.store_label"
                ></dd>
            </div>
            <div x-show.important="selectedCacheOperation.driver_label" class="ndb:min-w-0 ndb:bg-transparent">
                <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">Driver</dt>
                <dd
                    :title="selectedCacheOperation.driver_label"
                    class="ndb:mt-0.5 ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                    x-text="selectedCacheOperation.driver_label"
                ></dd>
            </div>
            <div class="ndb:min-w-0 ndb:bg-transparent">
                <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">Runtime</dt>
                <dd
                    class="ndb:mt-0.5 ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-700 ndb:dark:text-zinc-200"
                    x-text="selectedCacheOperation.duration_label"
                ></dd>
            </div>
            <div
                x-show.important="selectedCacheOperation.source_label !== 'Source unavailable'"
                class="ndb:min-w-0 ndb:bg-transparent"
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
        </div>
    </x-slot:metadata>
</x-newdebugbar::inspector-detail-header>
