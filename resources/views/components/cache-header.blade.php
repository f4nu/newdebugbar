<x-newdebugbar::inspector-detail-header data-ndb-cache-header>
    <x-slot:title>
        <div class="ndb:min-w-0">
            <p class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Cache operation</p>
            <h3
                data-ndb-cache-detail-operation
                class="ndb:mt-0.5 ndb:text-base ndb:font-bold ndb:leading-6"
                x-text="selectedCacheOperation.operation_label"
            ></h3>
        </div>
    </x-slot:title>

    <x-slot:aside>
        <span
            data-ndb-cache-detail-result
            :class="selectedCacheOperation.failed
                ? 'ndb:bg-red-100 ndb:text-red-700 ndb:dark:bg-red-950 ndb:dark:text-red-300'
                : selectedCacheOperation.result === 'miss' || selectedCacheOperation.result === 'flushed'
                  ? 'ndb:bg-amber-100 ndb:text-amber-700 ndb:dark:bg-amber-950 ndb:dark:text-amber-300'
                  : selectedCacheOperation.result === 'hit' || selectedCacheOperation.result === 'stored'
                    ? 'ndb:bg-emerald-100 ndb:text-emerald-700 ndb:dark:bg-emerald-950 ndb:dark:text-emerald-300'
                    : 'ndb:bg-zinc-100 ndb:text-zinc-700 ndb:dark:bg-zinc-800 ndb:dark:text-zinc-200'"
            class="ndb:inline-flex ndb:shrink-0 ndb:items-center ndb:rounded-md ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-bold"
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
                <button
                    type="button"
                    data-ndb-cache-copy-raw
                    @click="copyText(formatCachePayload(selectedCacheOperation.raw))"
                    class="ndb:inline-flex ndb:h-auto ndb:min-h-8 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:px-2 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:transition ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/50"
                >
                    <x-newdebugbar::icon name="code" size="3.5" />
                    Copy JSON
                </button>
            </div>
        </div>
    </x-slot:identity>

    <x-slot:metadata data-ndb-cache-metadata>
        <div class="ndb:min-w-0">
            <dt class="ndb:sr-only">Store</dt>
            <dd class="ndb:truncate ndb:font-semibold">
                <span x-text="selectedCacheOperation.store_label"></span>
                <span
                    x-show.important="
                        selectedCacheOperation.driver_label &&
                        selectedCacheOperation.driver_label !== selectedCacheOperation.store_label
                    "
                    class="ndb:ml-1 ndb:text-zinc-400"
                    x-text="'Driver ' + selectedCacheOperation.driver_label"
                ></span>
            </dd>
        </div>
        <div>
            <dt class="ndb:sr-only">Timing</dt>
            <dd class="ndb:font-semibold ndb:tabular-nums" x-text="selectedCacheOperation.duration_label"></dd>
        </div>
        <div class="ndb:min-w-0">
            <dt class="ndb:sr-only">Source</dt>
            <dd
                :title="selectedCacheOperation.source_label"
                class="ndb:truncate ndb:font-mono ndb:font-medium"
                x-text="selectedCacheOperation.source_short_label"
            ></dd>
        </div>
    </x-slot:metadata>
</x-newdebugbar::inspector-detail-header>
