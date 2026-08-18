{{-- Renders the expanded inspector shell, navigation, and section content. --}}
<div
    x-cloak
    x-show.important="barVisible && inspectorOpen"
    class="ndb:pointer-events-auto ndb:fixed ndb:inset-0"
    role="presentation"
>
    <div
        data-ndb-backdrop
        x-show.important="barVisible && inspectorOpen"
        x-transition.opacity.duration.150ms
        @click="closeInspector()"
        class="ndb:absolute ndb:inset-0 ndb:bg-zinc-950/30 ndb:backdrop-blur-[1px] ndb:dark:bg-black/55"
    ></div>

    <aside
        x-show.important="barVisible && inspectorOpen"
        x-transition:enter="ndb:transition ndb:duration-200 ndb:ease-out"
        x-transition:enter-start="ndb:translate-y-full"
        x-transition:enter-end="ndb:translate-y-0"
        x-transition:leave="ndb:transition ndb:duration-150 ndb:ease-in"
        x-transition:leave-start="ndb:translate-y-0"
        x-transition:leave-end="ndb:translate-y-full"
        role="dialog"
        aria-modal="true"
        aria-label="Request inspector"
        @keydown="keepFocusWithin($event, mobileSectionsOpen ? $refs.mobileSectionsNav : $el)"
        class="ndb:absolute ndb:inset-x-0 ndb:bottom-0 ndb:mx-auto ndb:flex ndb:h-[min(82vh,780px)] ndb:w-full ndb:max-w-5xl ndb:max-h-[calc(100vh-12px)] ndb:flex-col ndb:overflow-hidden ndb:rounded-t-2xl ndb:border-x ndb:border-t ndb:border-white/70 ndb:bg-white/90 ndb:shadow-[0_-24px_80px_-28px_rgba(24,24,27,0.5)] ndb:backdrop-blur-2xl ndb:dark:border-zinc-800/80 ndb:dark:bg-zinc-950/90"
    >
        @include('newdebugbar::livewire.inspector-header')

        <div class="ndb:relative ndb:isolate ndb:flex ndb:min-h-0 ndb:flex-1 ndb:flex-col ndb:sm:flex-row">
            <div
                x-cloak
                x-show.important="mobileSectionsOpen"
                x-transition.opacity.duration.150ms
                data-ndb-mobile-sections-backdrop
                @click="closeMobileSections()"
                class="ndb:absolute ndb:inset-y-0 ndb:right-0 ndb:left-[min(82vw,280px)] ndb:z-20 ndb:bg-transparent ndb:sm:hidden"
                aria-hidden="true"
            ></div>

            <nav
                id="newdebugbar-section-navigation"
                x-ref="mobileSectionsNav"
                aria-label="Debug sections"
                :data-ndb-mobile-open="mobileSectionsOpen ? 'true' : 'false'"
                class="ndb-mobile-section-navigation ndb:absolute ndb:inset-y-0 ndb:left-0 ndb:z-30 ndb:flex ndb:w-[82vw] ndb:max-w-[280px] ndb:flex-col ndb:border-r ndb:border-zinc-200/80 ndb:bg-zinc-50/95 ndb:p-3 ndb:shadow-2xl ndb:backdrop-blur-2xl ndb:sm:static ndb:sm:z-auto ndb:sm:w-[210px] ndb:sm:max-w-none ndb:sm:shrink-0 ndb:sm:shadow-none ndb:dark:border-zinc-800/80 ndb:dark:bg-zinc-900/95 ndb:sm:dark:bg-zinc-900/60"
            >
                <div
                    id="newdebugbar-section-list"
                    class="ndb-scrollbar ndb:flex ndb:min-h-0 ndb:flex-1 ndb:flex-col ndb:gap-0.5 ndb:overflow-y-auto"
                >
                    <p
                        data-ndb-favorites-heading
                        x-show.important="favorites.length > 0"
                        class="ndb:px-2 ndb:pb-1.5 ndb:pt-1 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-[0.14em] ndb:text-zinc-400"
                    >
                        Favorites
                    </p>
                    <template x-for="section in orderedSections" :key="'section-' + section.key">
                        <div x-show.important="isSectionVisible(section)" class="ndb:contents">
                            <div
                                x-show.important="favorites.length > 0 && section.key === firstVisibleNonFavoriteKey"
                                class="ndb:my-2 ndb:h-px ndb:bg-zinc-200 ndb:dark:bg-zinc-800"
                            ></div>
                            <p
                                data-ndb-sections-heading
                                x-show.important="favorites.length > 0 && section.key === firstVisibleNonFavoriteKey"
                                class="ndb:px-2 ndb:pb-1.5 ndb:pt-1 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-[0.14em] ndb:text-zinc-400"
                            >
                                Sections
                            </p>
                            <div
                                :draggable="isFavorite(section.key)"
                                :data-ndb-section="section.key"
                                :data-ndb-section-visible="isSectionVisible(section) ? 'true' : 'false'"
                                :data-ndb-favorite="isFavorite(section.key) ? 'true' : 'false'"
                                data-ndb-dragging="false"
                                @dragstart="startFavoriteDrag(section.key, $event)"
                                @dragover.prevent="
                                    hoverFavorite(
                                        section.key,
                                        $event.clientY >
                                            $event.currentTarget.getBoundingClientRect().top +
                                                $event.currentTarget.offsetHeight / 2,
                                    )
                                "
                                @dragleave="leaveFavorite(section.key)"
                                @drop.prevent="dropFavorite(section.key, favoriteDropAfter)"
                                @dragend="endFavoriteDrag()"
                                class="ndb:group ndb:relative ndb:flex ndb:w-full ndb:items-center ndb:rounded-lg ndb:pr-1 ndb:transition ndb:hover:bg-zinc-200/60 ndb:dark:hover:bg-zinc-800/60"
                                :class="selected === section.key ? 'ndb-section-active' : ''"
                            >
                                <span
                                    :data-ndb-favorite-drop-before="section.key"
                                    hidden
                                    class="ndb:absolute ndb:inset-x-0.5 ndb:top-0 ndb:z-20 ndb:h-1 ndb:-translate-y-1/2 ndb:rounded-full ndb:bg-indigo-500 ndb:shadow-[0_0_0_2px_rgba(255,255,255,0.8)] ndb:dark:shadow-[0_0_0_2px_rgba(9,9,11,0.9)]"
                                ></span>
                                <span
                                    :data-ndb-favorite-drop-after="section.key"
                                    hidden
                                    class="ndb:absolute ndb:inset-x-0.5 ndb:bottom-0 ndb:z-20 ndb:h-1 ndb:translate-y-1/2 ndb:rounded-full ndb:bg-indigo-500 ndb:shadow-[0_0_0_2px_rgba(255,255,255,0.8)] ndb:dark:shadow-[0_0_0_2px_rgba(9,9,11,0.9)]"
                                ></span>
                                <button
                                    type="button"
                                    :data-ndb-select-section="section.key"
                                    :aria-current="selected === section.key ? 'page' : null"
                                    :aria-label="isFavorite(section.key)
                                        ? section.label + '. Drag to reorder. Shift and arrow keys also reorder.'
                                        : section.label"
                                    @click="selectSection(section.key)"
                                    @keydown.shift.arrow-up.prevent="moveFavorite(section.key, -1)"
                                    @keydown.shift.arrow-down.prevent="moveFavorite(section.key, 1)"
                                    class="ndb:flex ndb:h-9 ndb:min-w-0 ndb:flex-1 ndb:items-center ndb:gap-2 ndb:rounded-lg ndb:px-2.5 ndb:text-left ndb:text-xs ndb:font-semibold ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
                                    :class="(isFavorite(section.key)
                                        ? 'ndb:cursor-grab ndb:active:cursor-grabbing '
                                        : '') +
                                    (selected === section.key
                                        ? ''
                                        : 'ndb:text-zinc-600 ndb:hover:text-zinc-950 ndb:dark:text-zinc-400 ndb:dark:hover:text-white')"
                                >
                                    <span class="ndb-section-label ndb:truncate" x-text="section.label"></span>
                                    <span class="ndb:ml-auto ndb:flex ndb:h-7 ndb:shrink-0 ndb:items-center ndb:gap-1.5">
                                        <span
                                            x-show.important="section.count !== null"
                                            class="ndb-section-count ndb:inline-flex ndb:items-center ndb:text-[11px] ndb:leading-none ndb:tabular-nums"
                                            :class="selected === section.key ? '' : 'ndb:text-zinc-400'"
                                            x-text="section.count"
                                        ></span>
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    draggable="false"
                                    :data-ndb-toggle-favorite="section.key"
                                    :aria-label="(isFavorite(section.key) ? 'Remove ' : 'Add ') +
                                    section.label +
                                    (isFavorite(section.key) ? ' from favorites' : ' to favorites')"
                                    :aria-pressed="isFavorite(section.key)"
                                    :title="isFavorite(section.key) ? 'Remove from favorites' : 'Add to favorites'"
                                    @dragstart.prevent
                                    @click.stop="toggleFavorite(section.key)"
                                    class="ndb-star-button ndb:inline-flex ndb:size-7 ndb:items-center ndb:justify-center ndb:rounded-lg ndb:text-zinc-400 ndb:transition ndb:hover:scale-105 ndb:hover:text-blue-600 ndb:focus-visible:opacity-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-1 ndb:focus-visible:outline-blue-500 ndb:sm:opacity-0 ndb:sm:group-focus-within:opacity-100 ndb:sm:group-hover:opacity-100 ndb:dark:text-zinc-500 ndb:dark:hover:text-blue-300"
                                    :class="isFavorite(section.key) || selected === section.key
                                        ? 'ndb:sm:opacity-100'
                                        : ''"
                                >
                                    <span
                                        x-show.important="! isFavorite(section.key)"
                                        class="ndb-section-star-outline ndb:flex ndb:items-center ndb:justify-center ndb:leading-none"
                                        ><x-newdebugbar::icon name="star" class="ndb:size-3.5"
                                    /></span>
                                    <span
                                        x-show.important="isFavorite(section.key)"
                                        class="ndb:flex ndb:items-center ndb:justify-center ndb:leading-none"
                                        ><x-newdebugbar::icon name="star-filled" class="ndb-favorite-star ndb:size-3.5"
                                    /></span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </nav>

            <main
                x-ref="content"
                :inert="mobileSectionsOpen"
                class="ndb-scrollbar ndb:min-w-0 ndb:flex-1 ndb:overflow-y-auto ndb:bg-white/70 ndb:dark:bg-zinc-950/70"
            >
                <header data-ndb-section-header class="ndb:px-4 ndb:pt-4 ndb:sm:px-6 ndb:sm:pt-6">
                    <h2
                        data-ndb-section-heading
                        x-ref="sectionHeading"
                        tabindex="-1"
                        aria-describedby="newdebugbar-section-description"
                        class="ndb:text-sm ndb:font-bold ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500"
                        x-text="selectedSection.label"
                    ></h2>
                    <p
                        id="newdebugbar-section-description"
                        data-ndb-section-description
                        x-ref="sectionDescription"
                        class="ndb:mt-1 ndb:max-w-3xl ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400"
                        x-text="selectedSection.description"
                    ></p>
                </header>

                <div
                    wire:loading.flex
                    wire:target="loadDetails"
                    class="ndb:min-h-64 ndb:flex-col ndb:gap-4 ndb:p-4 ndb:sm:p-6"
                >
                    <div class="ndb:grid ndb:w-full ndb:grid-cols-3 ndb:divide-x ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:bg-white/55 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/35">
                        <div class="ndb:px-3 ndb:py-3">
                            <p class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                Status
                            </p>
                            <p class="ndb:mt-1 ndb:text-sm ndb:font-bold" x-text="summary.status"></p>
                        </div>
                        <div class="ndb:px-3 ndb:py-3">
                            <p class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                Duration
                            </p>
                            <p
                                class="ndb:mt-1 ndb:text-sm ndb:font-bold ndb:tabular-nums"
                                x-text="summary.duration_ms + ' ms'"
                            ></p>
                        </div>
                        <div class="ndb:px-3 ndb:py-3">
                            <p class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                Queries
                            </p>
                            <p
                                class="ndb:mt-1 ndb:text-sm ndb:font-bold ndb:tabular-nums"
                                x-text="summary.query_count"
                            ></p>
                        </div>
                    </div>
                    <div class="ndb:flex ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:bg-white/45 ndb:px-4 ndb:py-3 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30">
                        <span
                            class="ndb-loading-pulse ndb:grid ndb:size-8 ndb:shrink-0 ndb:place-items-center ndb:rounded-lg ndb:bg-indigo-50 ndb:text-indigo-600 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300"
                            ><x-newdebugbar::icon name="clock" class="ndb:size-4" /></span
                        ><span
                            ><span class="ndb:block ndb:text-sm ndb:font-semibold">Loading collector details…</span
                            ><span class="ndb:mt-0.5 ndb:block ndb:text-[11px] ndb:text-zinc-400"
                                >The request summary is ready. Larger sections will appear next.</span
                            ></span>
                    </div>
                </div>

                <div
                    x-cloak
                    x-show.important="detailsError"
                    role="alert"
                    class="ndb:m-4 ndb:rounded-xl ndb:border ndb:border-red-200 ndb:bg-red-50/70 ndb:p-4 ndb:dark:border-red-950 ndb:dark:bg-red-950/25 ndb:sm:m-6"
                >
                    <p class="ndb:text-sm ndb:font-bold ndb:text-red-800 ndb:dark:text-red-200">
                        Collector details could not be loaded.
                    </p>
                    <p class="ndb:mt-1 ndb:text-xs ndb:text-red-700/80 ndb:dark:text-red-300/80">
                        The request summary is still available. Retry, return to the current request, or reload the page
                        to capture a new request.
                    </p>
                    <div class="ndb:mt-3 ndb:flex ndb:flex-wrap ndb:gap-2">
                        <button
                            type="button"
                            @click="requestDetails()"
                            class="ndb:rounded-lg ndb:bg-red-700 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-bold ndb:text-white ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-red-500 ndb:dark:bg-red-300 ndb:dark:text-red-950"
                        >
                            Retry details</button
                        ><button
                            x-show.important="summary.is_current_profile === false"
                            type="button"
                            @click="returnToCurrentProfile()"
                            class="ndb:rounded-lg ndb:border ndb:border-red-300 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-bold ndb:text-red-800 ndb:focus-visible:outline-2 ndb:focus-visible:outline-red-500 ndb:dark:border-red-900 ndb:dark:text-red-200"
                        >
                            Back to current request</button
                        ><button
                            x-show.important="summary.is_current_profile !== false"
                            type="button"
                            @click="window.location.reload()"
                            class="ndb:rounded-lg ndb:border ndb:border-red-300 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-bold ndb:text-red-800 ndb:focus-visible:outline-2 ndb:focus-visible:outline-red-500 ndb:dark:border-red-900 ndb:dark:text-red-200"
                        >
                            Reload page
                        </button>
                    </div>
                </div>

                @if ($detailsLoaded && $profile !== [])
                    <div
                        wire:key="profile-details-{{ $profileId }}"
                        wire:loading.remove
                        wire:target="loadDetails"
                        class="ndb:p-4 ndb:sm:p-6"
                    >
                        @foreach ($profile['sections'] as $sectionKey => $section)
                            <section
                                data-ndb-section-panel="{{ $sectionKey }}"
                                @if ($sectionKey !== 'overview') hidden @endif
                                wire:key="section-{{ $sectionKey }}"
                                class="ndb:space-y-4"
                            >
                                @php($collectionDropped = (int) ($section['summary']['dropped_count'] ?? 0))
                                @php($collectionRetained = (int) ($section['summary']['retained_count'] ?? count($section['payload']['items'] ?? [])))
                                @php($collectionTotal = (int) ($section['summary']['count'] ?? ($collectionRetained + $collectionDropped)))
                                @if ($sectionKey !== 'overview' && $collectionDropped > 0)
                                    <div
                                        data-ndb-collection-status="{{ $sectionKey }}"
                                        role="status"
                                        class="ndb:rounded-lg ndb:border ndb:border-amber-200 ndb:bg-amber-50/60 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-semibold ndb:text-amber-800 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/25 ndb:dark:text-amber-300"
                                    >
                                        Showing {{ number_format($collectionRetained) }} of {{ number_format($collectionTotal) }} {{ strtolower($section['label']) }}.
                                    </div>
                                @endif
                                @if ($sectionKey === 'queries' && (int) ($section['summary']['transaction_dropped_count'] ?? 0) > 0)
                                    <div
                                        data-ndb-collection-status="query-transactions"
                                        role="status"
                                        class="ndb:rounded-lg ndb:border ndb:border-amber-200 ndb:bg-amber-50/60 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-semibold ndb:text-amber-800 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/25 ndb:dark:text-amber-300"
                                    >
                                        Showing {{ number_format((int) ($section['summary']['transaction_retained_count'] ?? count($section['payload']['transactions'] ?? []))) }} of {{ number_format((int) ($section['summary']['transaction_count'] ?? 0)) }} query
                                        transaction events.
                                    </div>
                                @endif
                                @if ($sectionKey === 'timeline' && ($section['payload']['incomplete'] ?? false))
                                    <div
                                        data-ndb-timeline-incomplete
                                        role="status"
                                        class="ndb:rounded-lg ndb:border ndb:border-amber-200 ndb:bg-amber-50/60 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-semibold ndb:text-amber-800 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/25 ndb:dark:text-amber-300"
                                    >
                                        Timeline incomplete: {{ number_format((int) ($section['payload']['omitted_count'] ?? 0)) }} source
                                        events were omitted.
                                    </div>
                                @endif
                                @includeFirst(['newdebugbar::livewire.sections.'.$sectionKey, 'newdebugbar::livewire.sections.default'], ['livewireSection' => $section])
                            </section>
                        @endforeach

                        @include('newdebugbar::livewire.history-panel')
                    </div>
                @elseif ($detailsLoaded)
                    <div class="ndb:p-8 ndb:text-center">
                        <p class="ndb:text-sm ndb:font-semibold">This saved request is no longer available.</p>
                        <p class="ndb:mt-1 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                            It may have expired or been cleared.
                        </p>
                        @if (! ($summary['is_current_profile'] ?? true))
                            <button
                                type="button"
                                wire:click="returnToCurrent"
                                wire:loading.attr="disabled"
                                class="ndb:mt-4 ndb:rounded-lg ndb:bg-indigo-600 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-bold ndb:text-white ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:opacity-50"
                            >
                                Back to current request
                            </button>
                        @else
                            <p class="ndb:mt-3 ndb:text-xs ndb:font-semibold">
                                Reload the page to capture a new request.
                            </p>
                        @endif
                    </div>
                @endif
            </main>
        </div>
    </aside>
</div>
