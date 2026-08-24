<div data-ndb-http-client-detail-panel="overview" x-show.important="httpClientDetailTab === 'overview'">
    <x-newdebugbar::http-client-overview-facts />

    <dl
        x-show.important="selectedHttpClientRequest.failed"
        class="ndb:mt-4 ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800"
    >
        <div class="ndb:grid ndb:gap-1 ndb:py-3 ndb:first:pt-0 ndb:sm:grid-cols-[8rem_minmax(0,1fr)] ndb:sm:gap-4">
            <dt class="ndb:text-xs ndb:font-bold ndb:text-red-700 ndb:dark:text-red-300">Failure</dt>
            <dd
                class="ndb:text-xs ndb:leading-5 ndb:text-red-700 ndb:dark:text-red-300"
                x-text="selectedHttpClientRequest.response_summary"
            ></dd>
        </div>
    </dl>

    <div data-ndb-http-client-guidance class="ndb:mt-6 ndb:space-y-5">
        <section>
            <p class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                What happened
            </p>
            <p
                class="ndb:mt-1 ndb:max-w-3xl ndb:text-xs ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                x-text="selectedHttpClientRequest.what_happened + ' ' + selectedHttpClientRequest.why_it_matters"
            ></p>
        </section>
        <section>
            <p
                :class="selectedHttpClientRequest.failed
                    ? 'ndb:text-red-600 ndb:dark:text-red-300'
                    : selectedHttpClientRequest.slow
                      ? 'ndb:text-amber-600 ndb:dark:text-amber-300'
                      : 'ndb:text-zinc-400'"
                class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider"
            >
                Check next
            </p>
            <p
                class="ndb:mt-1 ndb:max-w-3xl ndb:text-xs ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                x-text="selectedHttpClientRequest.check_next"
            ></p>
        </section>
    </div>
</div>
