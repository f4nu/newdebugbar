<x-newdebugbar::inspector-detail-header data-ndb-http-client-header>
    <x-slot:title>
        <h3
            data-ndb-http-client-detail-host
            class="ndb:break-words ndb:text-base ndb:font-bold ndb:leading-6"
            x-text="selectedHttpClientRequest.host"
        ></h3>
    </x-slot:title>

    <x-slot:aside>
        <span
            data-ndb-http-client-detail-status
            :class="selectedHttpClientRequest.failed
                ? 'ndb:bg-red-100 ndb:text-red-700 ndb:dark:bg-red-950 ndb:dark:text-red-300'
                : selectedHttpClientRequest.slow
                  ? 'ndb:bg-amber-100 ndb:text-amber-700 ndb:dark:bg-amber-950 ndb:dark:text-amber-300'
                  : 'ndb:bg-emerald-100 ndb:text-emerald-700 ndb:dark:bg-emerald-950 ndb:dark:text-emerald-300'"
            class="ndb:inline-flex ndb:shrink-0 ndb:items-center ndb:gap-1.5 ndb:rounded-md ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-bold"
        >
            <span
                data-ndb-http-client-detail-status-code
                class="ndb:tabular-nums"
                x-text="selectedHttpClientRequest.status ?? 'Error'"
            ></span>
            <span x-text="selectedHttpClientRequest.status_reason"></span>
        </span>
    </x-slot:aside>

    <x-slot:identity data-ndb-http-client-identity>
        <dl>
            <div class="ndb:grid ndb:grid-cols-[4.75rem_minmax(0,1fr)] ndb:items-baseline ndb:gap-2">
                <dt class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">Request</dt>
                <dd class="ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:items-center ndb:justify-between ndb:gap-2">
                    <span class="ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-2">
                        <span
                            class="ndb:flex ndb:w-12 ndb:shrink-0 ndb:items-center ndb:justify-center ndb:rounded-md ndb:bg-white/90 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-700 ndb:ring-1 ndb:ring-inset ndb:ring-zinc-200/80 ndb:dark:bg-zinc-950/60 ndb:dark:text-zinc-200 ndb:dark:ring-zinc-700"
                            x-text="selectedHttpClientRequest.method"
                        ></span>
                        <code
                            :title="selectedHttpClientRequest.url"
                            class="ndb:block ndb:min-w-0 ndb:truncate ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                            x-text="
                                selectedHttpClientRequest.path +
                                (selectedHttpClientRequest.query ? '?' + selectedHttpClientRequest.query : '')
                            "
                        ></code>
                    </span>
                    <span data-ndb-http-client-actions class="ndb:flex ndb:shrink-0 ndb:items-center ndb:gap-1">
                        <button
                            type="button"
                            data-ndb-http-client-copy-curl
                            @click="copyText(selectedHttpClientRequest.curl)"
                            class="ndb:inline-flex ndb:min-h-9 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:px-2 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:transition ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/50"
                        >
                            <x-newdebugbar::icon name="code" class="ndb:size-3.5" />
                            Copy safe cURL
                        </button>
                        <button
                            type="button"
                            data-ndb-http-client-copy-url
                            @click="copyText(selectedHttpClientRequest.url)"
                            class="ndb:inline-flex ndb:min-h-9 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:px-2 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:transition ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/50"
                        >
                            <x-newdebugbar::icon name="copy" class="ndb:size-3.5" />
                            Copy URL
                        </button>
                    </span>
                </dd>
            </div>
        </dl>
    </x-slot:identity>

    <x-slot:metadata data-ndb-http-client-metadata>
        <div>
            <dt class="ndb:sr-only">Runtime</dt>
            <dd class="ndb:font-semibold ndb:tabular-nums" x-text="selectedHttpClientRequest.duration_label"></dd>
        </div>
        <div x-show.important="selectedHttpClientRequest.slow">
            <dt class="ndb:sr-only">Performance</dt>
            <dd class="ndb:font-bold ndb:text-amber-600 ndb:dark:text-amber-300">Slow request</dd>
        </div>
        <div class="ndb:min-w-0">
            <dt class="ndb:sr-only">Source</dt>
            <dd
                :title="selectedHttpClientRequest.callsite_label"
                class="ndb:truncate ndb:font-mono ndb:font-medium"
                x-text="selectedHttpClientRequest.callsite_short_label"
            ></dd>
        </div>
    </x-slot:metadata>
</x-newdebugbar::inspector-detail-header>
