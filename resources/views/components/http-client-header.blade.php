<header data-ndb-http-client-header class="ndb:border-b ndb:border-zinc-200/90 ndb:p-4 ndb:dark:border-zinc-800">
    <div class="ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:items-start ndb:justify-between ndb:gap-3">
        <div class="ndb:grid ndb:min-w-0 ndb:flex-1 ndb:grid-cols-[3rem_minmax(0,1fr)] ndb:items-start ndb:gap-2">
            <span
                data-ndb-http-client-detail-method
                class="ndb:flex ndb:w-12 ndb:shrink-0 ndb:items-center ndb:justify-center ndb:rounded-md ndb:bg-zinc-100/70 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-600 ndb:ring-1 ndb:ring-inset ndb:ring-zinc-200/70 ndb:dark:bg-white/10 ndb:dark:text-zinc-200 ndb:dark:ring-zinc-700"
                x-text="selectedHttpClientRequest.method"
            ></span>
            <h3
                data-ndb-http-client-detail-path
                :title="selectedHttpClientRequest.url"
                class="ndb:min-w-0 ndb:break-all ndb:font-mono ndb:text-xs ndb:font-bold ndb:leading-5 ndb:text-zinc-700 ndb:dark:text-zinc-200"
                x-text="
                    selectedHttpClientRequest.path +
                    (selectedHttpClientRequest.query ? '?' + selectedHttpClientRequest.query : '')
                "
            ></h3>
        </div>

        <button
            type="button"
            data-ndb-http-client-copy-url
            @click="copyText(selectedHttpClientRequest.url)"
            class="ndb:inline-flex ndb:h-auto ndb:min-h-9 ndb:shrink-0 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:px-2 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:transition ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/50"
        >
            <x-newdebugbar::icon name="link" size="3.5" />
            Copy URL
        </button>
    </div>
</header>
