@php
    $profile = $detailsLoaded ? $this->profile : [];
@endphp

<div
    id="newdebugbar"
    wire:ignore.self
    x-data="newDebugBar(@js($summary))"
    :data-theme="resolvedTheme"
    @keydown.window="handleShortcut($event)"
    @newdebugbar-content-updated.window="
        $nextTick(() => {
            syncSectionHeading();
            syncSectionPanels();
            applyAuthorizationFilters();
            applyHistoryFilters();
            applyTimelineFilters();
            applyEventFilters();
            applyLogFilters();
            syncHostLock();
            window.newDebugBarHighlight?.($root);
        })
    "
    @newdebugbar-profile-switched.window="switchProfile($event.detail.summary)"
    class="ndb:pointer-events-none ndb:fixed ndb:inset-0 ndb:z-[2147483000] ndb:text-zinc-900 ndb:dark:text-zinc-100"
>
    <div
        x-cloak
        x-show.important="barVisible && ! inspectorOpen"
        x-transition.opacity.duration.150ms
        role="toolbar"
        aria-label="Debug toolbar"
        class="ndb:pointer-events-auto ndb:fixed ndb:bottom-3 ndb:left-1/2 ndb:flex ndb:w-[calc(100vw-24px)] ndb:max-w-[calc(100vw-24px)] ndb:-translate-x-1/2 ndb:items-stretch ndb:gap-1 ndb:rounded-[18px] ndb:border ndb:border-white/70 ndb:bg-white/80 ndb:py-1.5 ndb:pl-1.5 ndb:pr-2.5 ndb:shadow-[0_18px_60px_-18px_rgba(24,24,27,0.4)] ndb:backdrop-blur-xl ndb:backdrop-brightness-110 ndb:backdrop-saturate-125 ndb:sm:max-w-5xl ndb:dark:border-white/10 ndb:dark:bg-zinc-950/90 ndb:dark:shadow-[0_18px_60px_-18px_rgba(0,0,0,0.8)] ndb:dark:backdrop-brightness-75 ndb:dark:backdrop-saturate-100"
    >
        <x-newdebugbar::toolbar-button
            section="request"
            data-ndb-toolbar="request"
            class="ndb:flex ndb:w-28 ndb:min-w-0 ndb:flex-none ndb:sm:w-auto ndb:sm:max-w-64"
            aria-label="Open request details"
        >
            <span
                class="ndb:rounded-md ndb:bg-indigo-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300"
                x-text="summary.method"
            ></span>
            <span class="ndb:min-w-0">
                <span class="ndb:block ndb:truncate ndb:text-xs ndb:font-semibold" x-text="summary.path"></span>
                <span
                    class="ndb:flex ndb:items-center ndb:gap-1.5 ndb:whitespace-nowrap ndb:text-[10px] ndb:font-medium ndb:text-zinc-400"
                    ><span data-ndb-toolbar-status x-text="summary.status"></span
                    ><span
                        data-ndb-toolbar-status-meaning
                        class="ndb:hidden ndb:sm:inline"
                        x-text="summary.status_meaning"
                    ></span
                    ><span
                        data-ndb-toolbar-response-size
                        class="ndb:hidden ndb:font-semibold ndb:text-zinc-500 ndb:sm:inline ndb:dark:text-zinc-300"
                        x-show="summary.response_size"
                        x-text="summary.response_size"
                    ></span
                ></span>
            </span>
        </x-newdebugbar::toolbar-button>

        <div
            data-ndb-toolbar-facts
            class="ndb-toolbar-facts ndb:flex ndb:min-w-0 ndb:flex-1 ndb:items-stretch ndb:gap-1 ndb:overflow-x-auto ndb:overscroll-x-contain ndb:sm:ml-auto ndb:sm:flex-none ndb:sm:overflow-visible"
        >
            <x-newdebugbar::toolbar-button
                section="overview"
                data-ndb-toolbar="environment"
                class="ndb:order-1 ndb:flex ndb:min-w-max ndb:shrink-0"
            >
                <span
                    class="ndb:size-2 ndb:shrink-0 ndb:rounded-full"
                    :class="summary.warning ? 'ndb:bg-amber-500' : 'ndb:bg-emerald-500'"
                ></span>
                <span
                    ><span
                        class="ndb:hidden ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:sm:block"
                        >Environment</span
                    ><span
                        class="ndb:block ndb:max-w-24 ndb:truncate ndb:text-[10px] ndb:font-bold ndb:sm:text-xs"
                        x-text="summary.environment"
                    ></span
                ></span>
            </x-newdebugbar::toolbar-button>

            <x-newdebugbar::toolbar-button
                section="request"
                data-ndb-toolbar="duration"
                class="ndb:order-3 ndb:flex ndb:min-w-max ndb:shrink-0"
            >
                <x-newdebugbar::icon
                    name="clock"
                    class="ndb:size-3.5 ndb:shrink-0 ndb:text-indigo-500 ndb:dark:text-indigo-400"
                />
                <span
                    ><span
                        class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                        >Duration</span
                    ><span
                        class="ndb:block ndb:whitespace-nowrap ndb:text-xs ndb:font-bold ndb:tabular-nums"
                        x-text="summary.duration_ms + ' ms'"
                    ></span
                ></span>
            </x-newdebugbar::toolbar-button>

            <x-newdebugbar::toolbar-button
                section="overview"
                data-ndb-toolbar="memory"
                class="ndb:order-4 ndb:flex ndb:min-w-max ndb:shrink-0"
            >
                <x-newdebugbar::icon
                    name="memory"
                    class="ndb:size-3.5 ndb:shrink-0 ndb:text-indigo-500 ndb:dark:text-indigo-400"
                />
                <span
                    ><span
                        class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                        >Peak</span
                    ><span
                        class="ndb:block ndb:whitespace-nowrap ndb:text-xs ndb:font-bold ndb:tabular-nums"
                        x-text="summary.peak_memory_mb + ' MB'"
                    ></span
                ></span>
            </x-newdebugbar::toolbar-button>

            <x-newdebugbar::toolbar-button
                section="queries"
                data-ndb-toolbar="queries"
                class="ndb:order-2 ndb:flex ndb:min-w-max ndb:shrink-0"
            >
                <x-newdebugbar::icon
                    name="database"
                    class="ndb:size-3.5 ndb:shrink-0 ndb:text-indigo-500 ndb:dark:text-indigo-400"
                />
                <span
                    ><span
                        class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                        >Queries</span
                    ><span
                        class="ndb:flex ndb:items-center ndb:gap-1.5 ndb:whitespace-nowrap ndb:text-xs ndb:font-bold ndb:tabular-nums"
                        ><span x-text="summary.query_count"></span
                        ><span
                            class="ndb:hidden ndb:font-medium ndb:text-zinc-400 ndb:sm:inline"
                            x-text="summary.query_time_ms + ' ms'"
                        ></span></span
                ></span>
            </x-newdebugbar::toolbar-button>
        </div>

        <div data-ndb-toolbar-actions class="ndb:flex ndb:shrink-0 ndb:items-center ndb:gap-0.5">
            <div
                data-ndb-toolbar-utility-actions
                role="group"
                aria-label="Tools"
                class="ndb:flex ndb:items-center ndb:gap-0.5"
            >
                <x-newdebugbar::icon-button
                    name="search"
                    :dark-surface="true"
                    data-ndb-toolbar="palette"
                    @click="openPalette()"
                    class="ndb:size-9 ndb:rounded-xl"
                    aria-label="Open command palette"
                    title="Command palette (Command or Control + Shift + P)"
                />
            </div>
            <span
                data-ndb-window-controls-separator
                aria-hidden="true"
                class="ndb:mx-1 ndb:h-5 ndb:w-px ndb:shrink-0 ndb:bg-zinc-300/80 ndb:dark:bg-zinc-700"
            ></span>
            <x-newdebugbar::window-controls data-ndb-window-controls="compact" :dark-surface="true" />
        </div>
    </div>

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
            <header class="ndb:shrink-0 ndb:border-b ndb:border-zinc-200/80 ndb:bg-white ndb:p-1.5 ndb:dark:border-zinc-800/80 ndb:dark:bg-zinc-950">
                <div
                    data-ndb-header-toolbar
                    class="ndb:flex ndb:flex-wrap ndb:items-stretch ndb:gap-1 ndb:sm:flex-nowrap"
                >
                    <x-newdebugbar::toolbar-button
                        section="request"
                        data-ndb-header-request
                        class="ndb:flex ndb:min-w-0 ndb:flex-none ndb:max-w-64"
                        aria-label="Open request details"
                    >
                        <span
                            class="ndb:rounded-md ndb:bg-indigo-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300"
                            x-text="summary.method"
                        ></span>
                        <span class="ndb:min-w-0">
                            <span
                                class="ndb:block ndb:truncate ndb:text-xs ndb:font-semibold"
                                x-text="summary.path"
                            ></span>
                            <span
                                class="ndb:flex ndb:items-center ndb:gap-1.5 ndb:whitespace-nowrap ndb:text-[10px] ndb:font-medium ndb:text-zinc-400"
                                ><span data-ndb-header-status x-text="summary.status"></span
                                ><span
                                    data-ndb-header-status-meaning
                                    class="ndb:hidden ndb:sm:inline"
                                    x-text="summary.status_meaning"
                                ></span
                                ><span
                                    data-ndb-header-response-size
                                    class="ndb:hidden ndb:font-semibold ndb:text-zinc-500 ndb:sm:inline ndb:dark:text-zinc-300"
                                    x-show="summary.response_size"
                                    x-text="summary.response_size"
                                ></span
                                ><span x-text="summary.is_current_profile ? '' : 'History profile'"></span
                            ></span>
                        </span>
                    </x-newdebugbar::toolbar-button>

                    <div
                        data-ndb-header-mobile-row
                        class="ndb:order-3 ndb:flex ndb:w-full ndb:min-w-0 ndb:items-stretch ndb:gap-2 ndb:sm:contents"
                    >
                        <button
                            type="button"
                            data-ndb-mobile-sections-toggle
                            @click="toggleMobileSections()"
                            :aria-expanded="mobileSectionsOpen"
                            :aria-label="mobileSectionsOpen ? 'Close sections' : 'Open sections'"
                            :title="mobileSectionsOpen ? 'Close sections' : 'Open sections'"
                            aria-controls="newdebugbar-section-navigation"
                            class="ndb:flex ndb:size-11 ndb:shrink-0 ndb:items-center ndb:justify-center ndb:rounded-xl ndb:text-zinc-500 ndb:transition-colors ndb:hover:text-zinc-950 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:sm:hidden ndb:dark:text-zinc-400 ndb:dark:hover:text-white"
                        >
                            <span x-show.important="! mobileSectionsOpen"
                                ><x-newdebugbar::icon name="sidebar" class="ndb:size-4"
                            /></span>
                            <span x-cloak x-show.important="mobileSectionsOpen"
                                ><x-newdebugbar::icon name="close" class="ndb:size-4"
                            /></span>
                        </button>

                        <div
                            data-ndb-header-facts
                            class="ndb-scrollbar ndb:flex ndb:min-w-0 ndb:flex-1 ndb:gap-2 ndb:overflow-x-auto ndb:overscroll-x-contain ndb:pb-0.5 ndb:sm:order-none ndb:sm:ml-auto ndb:sm:w-auto ndb:sm:flex-none ndb:sm:gap-1 ndb:sm:overflow-visible ndb:sm:pb-0"
                        >
                            <x-newdebugbar::toolbar-button
                                section="overview"
                                data-ndb-header-fact="environment"
                                class="ndb:order-1 ndb:flex ndb:min-w-max ndb:shrink-0"
                            >
                                <span
                                    class="ndb:size-2 ndb:shrink-0 ndb:rounded-full"
                                    :class="summary.warning ? 'ndb:bg-amber-500' : 'ndb:bg-emerald-500'"
                                ></span>
                                <span class="ndb:min-w-0"
                                    ><span
                                        class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                        >Environment</span
                                    ><span
                                        data-ndb-header-environment
                                        class="ndb:block ndb:max-w-24 ndb:truncate ndb:text-xs ndb:font-bold"
                                        x-text="summary.environment"
                                    ></span
                                ></span>
                            </x-newdebugbar::toolbar-button>

                            <x-newdebugbar::toolbar-button
                                section="request"
                                data-ndb-header-fact="duration"
                                class="ndb:order-3 ndb:flex ndb:min-w-max ndb:shrink-0"
                            >
                                <x-newdebugbar::icon
                                    name="clock"
                                    class="ndb:size-3.5 ndb:shrink-0 ndb:text-indigo-500 ndb:dark:text-indigo-400"
                                />
                                <span
                                    ><span
                                        class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                        >Duration</span
                                    ><span
                                        class="ndb:block ndb:whitespace-nowrap ndb:text-xs ndb:font-bold ndb:tabular-nums"
                                        x-text="summary.duration_ms + ' ms'"
                                    ></span
                                ></span>
                            </x-newdebugbar::toolbar-button>

                            <x-newdebugbar::toolbar-button
                                section="overview"
                                data-ndb-header-fact="memory"
                                class="ndb:order-4 ndb:flex ndb:min-w-max ndb:shrink-0"
                            >
                                <x-newdebugbar::icon
                                    name="memory"
                                    class="ndb:size-3.5 ndb:shrink-0 ndb:text-indigo-500 ndb:dark:text-indigo-400"
                                />
                                <span
                                    ><span
                                        class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                        >Peak</span
                                    ><span
                                        data-ndb-header-memory
                                        class="ndb:block ndb:whitespace-nowrap ndb:text-xs ndb:font-bold ndb:tabular-nums"
                                        x-text="summary.peak_memory_mb + ' MB'"
                                    ></span
                                ></span>
                            </x-newdebugbar::toolbar-button>

                            <x-newdebugbar::toolbar-button
                                section="queries"
                                data-ndb-header-fact="queries"
                                class="ndb:order-2 ndb:flex ndb:min-w-max ndb:shrink-0"
                            >
                                <x-newdebugbar::icon
                                    name="database"
                                    class="ndb:size-3.5 ndb:shrink-0 ndb:text-indigo-500 ndb:dark:text-indigo-400"
                                />
                                <span
                                    ><span
                                        class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                        >Queries</span
                                    ><span
                                        class="ndb:flex ndb:items-center ndb:gap-1.5 ndb:whitespace-nowrap ndb:text-xs ndb:font-bold ndb:tabular-nums"
                                        ><span data-ndb-header-query-count x-text="summary.query_count"></span
                                        ><span
                                            data-ndb-header-query-duration
                                            class="ndb:hidden ndb:font-medium ndb:text-zinc-400 ndb:sm:inline"
                                            x-text="summary.query_time_ms + ' ms'"
                                        ></span></span
                                ></span>
                            </x-newdebugbar::toolbar-button>
                        </div>
                    </div>

                    <div data-ndb-inspector-actions class="ndb:flex ndb:items-center ndb:gap-0.5">
                        <div
                            data-ndb-inspector-utility-actions
                            role="group"
                            aria-label="Tools"
                            class="ndb:flex ndb:items-center ndb:gap-0.5"
                        >
                            <x-newdebugbar::icon-button
                                name="search"
                                data-ndb-inspector-action="palette"
                                @click="openPalette()"
                                class="ndb:size-9 ndb:rounded-xl"
                                aria-label="Open command palette"
                            />
                            <x-newdebugbar::icon-button
                                data-ndb-inspector-action="theme"
                                @click="toggleTheme()"
                                class="ndb:size-9 ndb:rounded-xl"
                                ::aria-label="resolvedTheme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'"
                                ::title="resolvedTheme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'"
                                ><span x-show.important="resolvedTheme !== 'dark'"
                                    ><x-newdebugbar::icon name="moon" class="ndb:size-4" /></span
                                ><span x-show.important="resolvedTheme === 'dark'"
                                    ><x-newdebugbar::icon name="sun" class="ndb:size-4" /></span
                            ></x-newdebugbar::icon-button>
                        </div>
                        <span
                            data-ndb-window-controls-separator
                            aria-hidden="true"
                            class="ndb:mx-1 ndb:h-5 ndb:w-px ndb:shrink-0 ndb:bg-zinc-300/80 ndb:dark:bg-zinc-700"
                        ></span>
                        <x-newdebugbar::window-controls data-ndb-window-controls="expanded" />
                    </div>
                </div>
            </header>

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
                            class="ndb:px-2 ndb:pb-1.5 ndb:pt-1 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-[0.14em] ndb:text-zinc-400"
                        >
                            Favorites
                        </p>
                        <template x-for="section in orderedSections" :key="'section-' + section.key">
                            <div x-show.important="isSectionVisible(section)" class="ndb:contents">
                                <div
                                    x-show.important="
                                        favorites.length > 0 && section.key === firstVisibleNonFavoriteKey
                                    "
                                    class="ndb:my-2 ndb:h-px ndb:bg-zinc-200 ndb:dark:bg-zinc-800"
                                ></div>
                                <p
                                    data-ndb-sections-heading
                                    x-show.important="
                                        favorites.length > 0 && section.key === firstVisibleNonFavoriteKey
                                    "
                                    class="ndb:px-2 ndb:pb-1.5 ndb:pt-1 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-[0.14em] ndb:text-zinc-400"
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
                                        <span class="ndb:ml-auto ndb:flex ndb:shrink-0 ndb:items-center ndb:gap-1.5">
                                            <span
                                                x-show.important="section.count !== null"
                                                class="ndb-section-count ndb:text-[10px] ndb:tabular-nums"
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
                                            class="ndb-section-star-outline"
                                            ><x-newdebugbar::icon name="star" class="ndb:size-3.5"
                                        /></span>
                                        <span x-show.important="isFavorite(section.key)"
                                            ><x-newdebugbar::icon
                                                name="star-filled"
                                                class="ndb-favorite-star ndb:size-3.5"
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
                    <div class="ndb:sticky ndb:top-0 ndb:z-10 ndb:flex ndb:h-12 ndb:items-center ndb:border-b ndb:border-zinc-100/80 ndb:bg-white/65 ndb:px-4 ndb:backdrop-blur-xl ndb:sm:px-6 ndb:dark:border-zinc-900/80 ndb:dark:bg-zinc-950/65">
                        <h2
                            data-ndb-section-heading
                            x-ref="sectionHeading"
                            tabindex="-1"
                            class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-sm ndb:font-bold ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500"
                            x-text="selectedSection.label"
                        ></h2>
                    </div>

                    <div
                        wire:loading.flex
                        wire:target="loadDetails"
                        class="ndb:min-h-64 ndb:flex-col ndb:gap-4 ndb:p-4 ndb:sm:p-6"
                    >
                        <div class="ndb:grid ndb:w-full ndb:grid-cols-3 ndb:divide-x ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:bg-white/55 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/35">
                            <div class="ndb:px-3 ndb:py-3">
                                <p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    Status
                                </p>
                                <p class="ndb:mt-1 ndb:text-sm ndb:font-bold" x-text="summary.status"></p>
                            </div>
                            <div class="ndb:px-3 ndb:py-3">
                                <p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    Duration
                                </p>
                                <p
                                    class="ndb:mt-1 ndb:text-sm ndb:font-bold ndb:tabular-nums"
                                    x-text="summary.duration_ms + ' ms'"
                                ></p>
                            </div>
                            <div class="ndb:px-3 ndb:py-3">
                                <p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
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
                                ><span class="ndb:mt-0.5 ndb:block ndb:text-[10px] ndb:text-zinc-400"
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
                            The request summary is still available. Retry, return to the current request, or reload the
                            page to capture a new request.
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
                                    @if ($sectionKey === 'overview')
                                        @php($overview = app(\NewDebugBar\Presentation\OverviewPresenter::class)->present($profile, $summary['sections'] ?? []))
                                        @php($activitySections = $overview['activity'])
                                        @php($runtimeDetailGroups = $overview['runtime'])
                                        @if ($activitySections !== [])
                                            <div data-ndb-overview-activity>
                                                <div class="ndb:mb-3">
                                                    <h3 class="ndb:text-xs ndb:font-bold">Relevant activity</h3>
                                                    <p class="ndb:mt-0.5 ndb:text-[10px] ndb:text-zinc-400">
                                                        Sorted by what may need attention
                                                    </p>
                                                </div>
                                                <div class="ndb:border-t ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
                                                    @foreach ($activitySections as $link)
                                                        <button
                                                            type="button"
                                                            data-ndb-overview-activity-section="{{ $link['key'] }}"
                                                            @click="navigateToSection(@js($link['key']))"
                                                            class="ndb:grid ndb:w-full ndb:grid-cols-[minmax(0,1fr)_auto] ndb:items-center ndb:gap-x-3 ndb:border-b ndb:border-zinc-200/90 ndb:py-3 ndb:text-left ndb:transition ndb:hover:bg-indigo-50/60 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500 ndb:sm:grid-cols-[9rem_minmax(0,1fr)_auto] ndb:dark:border-zinc-800 ndb:dark:hover:bg-indigo-950/30"
                                                        >
                                                            <span class="ndb:col-start-1 ndb:row-start-1 ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-bold">
                                                                {{ $link['label'] }}
                                                            </span>
                                                            <span class="ndb:col-start-1 ndb:row-start-2 ndb:min-w-0 ndb:text-[10px] ndb:leading-4 ndb:text-zinc-500 ndb:sm:col-start-2 ndb:sm:row-start-1 ndb:dark:text-zinc-400">
                                                                {{ $link['description'] }}
                                                            </span>
                                                            @if ($link['attention'] ?? false)
                                                                <span
                                                                    data-ndb-overview-activity-review
                                                                    class="ndb:col-start-2 ndb:row-span-2 ndb:row-start-1 ndb:self-center ndb:text-[10px] ndb:font-bold ndb:text-amber-600 ndb:sm:col-start-3 ndb:sm:row-span-1 ndb:dark:text-amber-400"
                                                                >Review</span>
                                                            @endif
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                        <details
                                            open
                                            data-ndb-overview-runtime
                                            class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/25"
                                        >
                                            <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-4 ndb:py-3 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500">
                                                <span class="ndb:min-w-0 ndb:flex-1">
                                                    <span class="ndb:block ndb:text-xs ndb:font-bold">Runtime details</span>
                                                    <span class="ndb:mt-0.5 ndb:block ndb:text-[10px] ndb:text-zinc-400">
                                                        Runtime, drivers, framework cache, and ecosystem
                                                    </span>
                                                </span>
                                                <x-newdebugbar::icon
                                                    name="chevron-down"
                                                    class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
                                                />
                                            </summary>
                                            <div
                                                x-data="{ runtimeDetail: 'runtime' }"
                                                class="ndb:border-t ndb:border-zinc-200/90 ndb:sm:grid ndb:sm:grid-cols-[11rem_minmax(0,1fr)] ndb:dark:border-zinc-800"
                                            >
                                                <div
                                                    data-ndb-runtime-detail-select-wrapper
                                                    class="ndb:border-b ndb:border-zinc-200/90 ndb:bg-zinc-50/70 ndb:p-3 ndb:sm:hidden ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/50"
                                                >
                                                    <label
                                                        for="ndb-runtime-detail-select"
                                                        class="ndb:mb-1.5 ndb:block ndb:text-[10px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                                    >
                                                        Show details for
                                                    </label>
                                                    <div class="ndb:relative">
                                                        <select
                                                            id="ndb-runtime-detail-select"
                                                            data-ndb-runtime-detail-select
                                                            x-model="runtimeDetail"
                                                            class="ndb:h-10 ndb:w-full ndb:appearance-none ndb:rounded-lg ndb:border ndb:border-zinc-300 ndb:bg-white ndb:px-3 ndb:pr-9 ndb:text-xs ndb:font-bold ndb:text-zinc-950 ndb:shadow-xs ndb:outline-none ndb:transition ndb:focus:border-indigo-500 ndb:focus:ring-2 ndb:focus:ring-indigo-500/20 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-950 ndb:dark:text-white ndb:dark:focus:border-indigo-400 ndb:dark:focus:ring-indigo-400/20"
                                                        >
                                                            @foreach ($runtimeDetailGroups as $runtimeDetailKey => $runtimeDetailGroup)
                                                                <option value="{{ $runtimeDetailKey }}">
                                                                    {{ $runtimeDetailGroup['label'] }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <x-newdebugbar::icon
                                                            name="chevron-down"
                                                            class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-3 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
                                                        />
                                                    </div>
                                                </div>

                                                <nav
                                                    data-ndb-runtime-detail-navigation
                                                    aria-label="Runtime detail category"
                                                    class="ndb:hidden ndb:border-r ndb:border-zinc-200/90 ndb:bg-zinc-50/70 ndb:p-2 ndb:sm:block ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/50"
                                                >
                                                    @foreach ($runtimeDetailGroups as $runtimeDetailKey => $runtimeDetailGroup)
                                                        <button
                                                            type="button"
                                                            data-ndb-runtime-detail="{{ $runtimeDetailKey }}"
                                                            @click="runtimeDetail = @js($runtimeDetailKey)"
                                                            :aria-pressed="runtimeDetail === @js($runtimeDetailKey)"
                                                            :class="runtimeDetail === @js($runtimeDetailKey) ? 'ndb:bg-indigo-50 ndb:text-indigo-700 ndb:dark:bg-indigo-950/70 ndb:dark:text-indigo-300' : 'ndb:text-zinc-600 ndb:hover:bg-white ndb:hover:text-zinc-950 ndb:dark:text-zinc-400 ndb:dark:hover:bg-zinc-800 ndb:dark:hover:text-white'"
                                                            class="ndb:flex ndb:w-full ndb:min-w-0 ndb:items-center ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-left ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-1 ndb:focus-visible:outline-indigo-500"
                                                        >
                                                            <span class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-bold">
                                                                {{ $runtimeDetailGroup['label'] }}
                                                            </span>
                                                        </button>
                                                    @endforeach
                                                </nav>

                                                <div class="ndb:min-w-0 ndb:p-4">
                                                    @foreach ($runtimeDetailGroups as $runtimeDetailKey => $runtimeDetailGroup)
                                                        <div
                                                            data-ndb-runtime-detail-panel="{{ $runtimeDetailKey }}"
                                                            x-show.important="runtimeDetail === @js($runtimeDetailKey)"
                                                        >
                                                            <div class="ndb:flex ndb:items-center ndb:justify-between ndb:gap-3">
                                                                <h3 class="ndb:text-xs ndb:font-bold">
                                                                    {{ $runtimeDetailGroup['label'] }}
                                                                </h3>
                                                                <button
                                                                    type="button"
                                                                    @click="copyText(@js(json_encode($runtimeDetailGroup['copy'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)))"
                                                                    class="ndb:shrink-0 ndb:text-[10px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                                                                >
                                                                    Copy all
                                                                </button>
                                                            </div>

                                                            <div class="ndb:mt-3 ndb:overflow-x-auto">
                                                                @if ($runtimeDetailGroup['items'] !== [])
                                                                    <table class="ndb:w-full ndb:table-fixed ndb:border-collapse ndb:text-left">
                                                                        <thead>
                                                                            <tr class="ndb:border-b ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
                                                                                <th
                                                                                    scope="col"
                                                                                    class="ndb:w-2/5 ndb:pb-2 ndb:pr-4 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                                                >
                                                                                    Name
                                                                                </th>
                                                                                <th
                                                                                    scope="col"
                                                                                    class="ndb:pb-2 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                                                >
                                                                                    Value
                                                                                </th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach ($runtimeDetailGroup['items'] as $runtimeDetailItem)
                                                                                <tr class="ndb:border-b ndb:border-zinc-200/70 ndb:last:border-b-0 ndb:dark:border-zinc-800/80">
                                                                                    <th
                                                                                        scope="row"
                                                                                        class="ndb:py-2 ndb:pr-4 ndb:align-top ndb:font-mono ndb:text-[10px] ndb:font-medium ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                                                                    >
                                                                                        {{ $runtimeDetailItem['name'] }}
                                                                                    </th>
                                                                                    <td class="ndb:break-words ndb:py-2 ndb:align-top ndb:font-mono ndb:text-[10px] ndb:text-zinc-800 ndb:dark:text-zinc-200">
                                                                                        {{ is_scalar($runtimeDetailItem['value']) || $runtimeDetailItem['value'] === null ? ($runtimeDetailItem['value'] ?? '—') : json_encode($runtimeDetailItem['value'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}
                                                                                    </td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                @else
                                                                    <p class="ndb:rounded-lg ndb:bg-zinc-50 ndb:px-3 ndb:py-4 ndb:text-xs ndb:text-zinc-500 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-400">
                                                                        No {{ strtolower($runtimeDetailGroup['label']) }} details
                                                                        were detected.
                                                                    </p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </details>
                                    @endif

                                    @if ($sectionKey === 'timeline')
                                        @php($timelineItems = $section['payload']['items'])
                                        @php($timelineSections = array_values(array_unique(array_column($timelineItems, 'section'))))
                                        @php($timelineSourceSections = array_values(array_filter($timelineSections, fn ($timelineSection) => $timelineSection !== 'request')))
                                        @php($timelineKeySections = ['request', 'lifecycle', 'queries', 'http_client', 'exceptions', 'authorization', 'validation', 'queue'])
                                        @php($timelineDuration = max(0.001, ...array_column($timelineItems, 'at_ms')))
                                        @php($timelineTicks = [0, 25, 50, 75, 100])
                                        <div data-ndb-timeline-results-header class="ndb:space-y-3">
                                            <div
                                                data-ndb-timeline-overview
                                                class="ndb:flex ndb:flex-wrap ndb:items-end ndb:justify-between ndb:gap-3"
                                            >
                                                <div>
                                                    <h3 class="ndb:text-xs ndb:font-bold">Waterfall</h3>
                                                    <p
                                                        data-ndb-timeline-summary
                                                        class="ndb:mt-0.5 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400"
                                                    >
                                                        <span x-text="visibleTimelineCount"></span> events across {{ number_format($timelineDuration, $timelineDuration < 10 ? 1 : 0) }} ms
                                                    </p>
                                                </div>
                                                <div
                                                    class="ndb:flex ndb:items-center ndb:gap-4 ndb:pb-0.5 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                                    aria-label="Timeline legend"
                                                >
                                                    <span class="ndb:flex ndb:items-center ndb:gap-1.5"
                                                        ><span
                                                            class="ndb:h-1.5 ndb:w-5 ndb:rounded-sm ndb:bg-indigo-500"
                                                        ></span
                                                        >Duration</span
                                                    ><span class="ndb:flex ndb:items-center ndb:gap-1.5"
                                                        ><span class="ndb:size-2 ndb:rounded-full ndb:bg-sky-500"></span
                                                        >Event</span>
                                                </div>
                                            </div>
                                            <div
                                                data-ndb-timeline-toolbar
                                                class="ndb:grid ndb:w-full ndb:min-w-0 ndb:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)] ndb:gap-2 ndb:sm:grid-cols-[12rem_16rem] ndb:sm:justify-between"
                                            >
                                                <label class="ndb:min-w-0"
                                                    ><span
                                                        class="ndb:mb-1.5 ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                        >Show activity</span
                                                    ><span class="ndb:relative ndb:block"
                                                        ><select
                                                            data-ndb-timeline-filter
                                                            x-model="timelineFilter"
                                                            @change="setTimelineFilter($event.target.value)"
                                                            class="ndb:h-9 ndb:w-full ndb:appearance-none ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-8 ndb:pl-3 ndb:text-xs ndb:font-semibold ndb:outline-none ndb:transition ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                                                        >
                                                            <optgroup label="View">
                                                                <option value="key">Key activity</option>
                                                                <option value="all">All activity</option>
                                                            </optgroup>
                                                            <optgroup label="Source">
                                                                <option value="request">Request</option>
                                                                @foreach ($timelineSourceSections as $timelineSection)
                                                                    <option value="{{ $timelineSection }}">
                                                                        {{ str($timelineSection)->replace('_', ' ')->title() }}
                                                                    </option>
                                                                @endforeach
                                                            </optgroup>
                                                        </select>
                                                        <x-newdebugbar::icon
                                                            name="chevron-down"
                                                            class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-2.5 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
                                                        /> </span
                                                ></label>
                                                <label class="ndb:min-w-0"
                                                    ><span
                                                        class="ndb:mb-1.5 ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                        >Search activity</span
                                                    ><input
                                                        data-ndb-timeline-search
                                                        x-model="timelineSearch"
                                                        @input.debounce.100ms="applyTimelineFilters()"
                                                        type="search"
                                                        placeholder="Event or section"
                                                        class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:outline-none ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                                                /></label>
                                            </div>
                                        </div>
                                        <div
                                            data-ndb-timeline-waterfall
                                            class="ndb-scrollbar ndb:overflow-x-auto ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/55 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30"
                                        >
                                            <div class="ndb:min-w-[760px]">
                                                <div class="ndb:grid ndb:grid-cols-[minmax(190px,0.8fr)_minmax(420px,2fr)_88px] ndb:border-b ndb:border-zinc-200/90 ndb:bg-zinc-50/80 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/70">
                                                    <div class="ndb:self-end ndb:px-3 ndb:pb-2 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                        Activity
                                                    </div>
                                                    <div
                                                        class="ndb:h-10 ndb:border-x ndb:border-zinc-200/90 ndb:dark:border-zinc-800"
                                                        aria-label="Timeline from zero to {{ number_format($timelineDuration, $timelineDuration < 10 ? 1 : 0) }} milliseconds"
                                                    >
                                                        <div class="ndb:relative ndb:mx-2 ndb:h-full">
                                                            @foreach ($timelineTicks as $tick)
                                                                @php($timelineTickMs = $timelineDuration * $tick / 100)
                                                                <span
                                                                    data-ndb-timeline-tick="{{ $tick }}"
                                                                    class="ndb:absolute ndb:bottom-2 ndb:whitespace-nowrap {{ $tick === 0 ? 'ndb:translate-x-0' : ($tick === 100 ? 'ndb:-translate-x-full' : 'ndb:-translate-x-1/2') }} ndb:text-[9px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
                                                                    style="left: {{ $tick }}%"
                                                                >{{ number_format($timelineTickMs, $timelineTickMs > 0 && $timelineTickMs < 10 ? 1 : 0) }} ms</span>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                    <div class="ndb:self-end ndb:px-3 ndb:pb-2 ndb:text-right ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                        Timing
                                                    </div>
                                                </div>
                                                <ol
                                                    x-ref="timelineList"
                                                    x-init="$nextTick(() => applyTimelineFilters())"
                                                    class="ndb:m-0 ndb:list-none ndb:divide-y ndb:divide-zinc-100 ndb:p-0 ndb:dark:divide-zinc-800/80"
                                                >
                                                    @foreach ($timelineItems as $item)
                                                        <li
                                                            data-ndb-timeline-item="{{ $item['id'] }}"
                                                            data-section="{{ $item['section'] }}"
                                                            data-kind="{{ $item['kind'] }}"
                                                            data-key="{{ in_array($item['section'], $timelineKeySections, true) ? 'true' : 'false' }}"
                                                            data-position="{{ $item['at_percent'] }}"
                                                            @if ($item['start_percent'] !== null) data-start="{{ $item['start_percent'] }}" @endif
                                                            @if ($item['duration_percent'] !== null) data-duration="{{ $item['duration_percent'] }}" @endif
                                                            data-search="{{ mb_strtolower($item['label'].' '.$item['section']) }}"
                                                            class="ndb:grid ndb:min-h-14 ndb:grid-cols-[minmax(190px,0.8fr)_minmax(420px,2fr)_88px]"
                                                            style="--ndb-timeline-at: {{ $item['at_percent'] }}%; --ndb-timeline-start: {{ $item['start_percent'] ?? $item['at_percent'] }}%; --ndb-timeline-width: {{ $item['duration_percent'] ?? 0 }}%;"
                                                        >
                                                            <div class="ndb:min-w-0 ndb:px-3 ndb:py-2.5">
                                                                <p
                                                                    class="ndb:truncate ndb:text-xs ndb:font-semibold"
                                                                    title="{{ $item['label'] }}"
                                                                >
                                                                    {{ $item['label'] }}
                                                                </p>
                                                                <button
                                                                    type="button"
                                                                    @click="selectSection(@js($item['section']))"
                                                                    class="ndb:mt-0.5 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:transition ndb:hover:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:text-indigo-300"
                                                                >
                                                                    {{ str($item['section'])->replace('_', ' ')->title() }}
                                                                </button>
                                                            </div>
                                                            <div
                                                                data-ndb-timeline-track
                                                                class="ndb:overflow-hidden ndb:border-x ndb:border-zinc-200/90 ndb:dark:border-zinc-800"
                                                            >
                                                                <div class="ndb:relative ndb:mx-2 ndb:h-full">
                                                                    @foreach ([25, 50, 75] as $tick)
                                                                        <span
                                                                            aria-hidden="true"
                                                                            class="ndb:absolute ndb:inset-y-0 ndb:border-l ndb:border-zinc-200/60 ndb:dark:border-zinc-800/80"
                                                                            style="left: {{ $tick }}%"
                                                                        ></span>
                                                                    @endforeach
                                                                    <span
                                                                        aria-hidden="true"
                                                                        class="ndb:absolute ndb:inset-x-0 ndb:top-1/2 ndb:border-t ndb:border-zinc-200/80 ndb:dark:border-zinc-700/80"
                                                                    ></span>
                                                                    @if ($item['kind'] === 'span')
                                                                        <span
                                                                            data-ndb-timeline-mark
                                                                            title="{{ $item['label'] }} — {{ number_format((float) $item['duration_ms'], $item['duration_ms'] < 10 ? 1 : 0) }} ms"
                                                                            class="ndb:absolute ndb:top-1/2 ndb:left-[var(--ndb-timeline-start)] ndb:h-2.5 ndb:w-[max(3px,var(--ndb-timeline-width))] ndb:-translate-y-1/2 ndb:rounded-sm ndb:bg-indigo-500 ndb:shadow-[0_0_0_1px_rgba(79,70,229,0.18)] ndb:dark:bg-indigo-400"
                                                                        ></span>
                                                                    @elseif ($item['kind'] === 'milestone')
                                                                        <span
                                                                            data-ndb-timeline-mark
                                                                            title="{{ $item['label'] }}"
                                                                            class="ndb:absolute ndb:top-1/2 ndb:left-[clamp(4px,var(--ndb-timeline-at),calc(100%-4px))] ndb:h-5 ndb:w-px ndb:-translate-x-1/2 ndb:-translate-y-1/2 ndb:bg-zinc-700 ndb:dark:bg-zinc-200"
                                                                        ></span>
                                                                    @else
                                                                        <span
                                                                            data-ndb-timeline-mark
                                                                            title="{{ $item['label'] }}"
                                                                            class="ndb:absolute ndb:top-1/2 ndb:left-[clamp(4px,var(--ndb-timeline-at),calc(100%-4px))] ndb:size-2.5 ndb:-translate-x-1/2 ndb:-translate-y-1/2 ndb:rounded-full ndb:border-2 ndb:border-white ndb:bg-sky-500 ndb:shadow-sm ndb:dark:border-zinc-900 ndb:dark:bg-sky-400"
                                                                        ></span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <div class="ndb:self-center ndb:px-3 ndb:text-right ndb:tabular-nums">
                                                                @if ($item['kind'] === 'span')
                                                                    <p class="ndb:text-[10px] ndb:font-bold">
                                                                        {{ number_format((float) $item['duration_ms'], $item['duration_ms'] < 10 ? 1 : 0) }} ms
                                                                    </p>
                                                                    <p class="ndb:text-[9px] ndb:font-semibold ndb:text-zinc-400">
                                                                        {{ $item['start_ms'] }}–{{ $item['at_ms'] }} ms
                                                                    </p>
                                                                @else
                                                                    <p class="ndb:text-[10px] ndb:font-bold">
                                                                        {{ number_format((float) $item['at_ms'], $item['at_ms'] > 0 && $item['at_ms'] < 10 ? 1 : 0) }} ms
                                                                    </p>
                                                                @endif
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ol>
                                            </div>
                                        </div>
                                        <div x-show.important="visibleTimelineCount === 0">
                                            <x-newdebugbar::empty-state label="No timeline events match these filters." />
                                        </div>
                                    @elseif ($sectionKey === 'request')
                                        @php($requestPayload = $section['payload'])
                                        @php($isHttpRequest = ($profile['profile_type'] ?? 'http') === 'http')
                                        @php($requestStatus = (int) ($requestPayload['status'] ?? 0))
                                        @php($requestSucceeded = $requestStatus > 0 && $requestStatus < 400)
                                        @php($requestDuration = number_format((float) ($profile['metrics']['duration_ms'] ?? 0), 2, '.', ''))
                                        @php($requestQueryCount = (int) ($profile['sections']['queries']['summary']['total_count'] ?? $profile['sections']['queries']['summary']['count'] ?? 0))
                                        @php($requestHeaders = is_array($requestPayload['headers'] ?? null) ? $requestPayload['headers'] : [])
                                        @php($requestInput = is_array($requestPayload['input'] ?? null) ? $requestPayload['input'] : [])
                                        @php($requestQuery = is_array($requestPayload['query'] ?? null) ? $requestPayload['query'] : [])
                                        @php($requestSession = is_array($requestPayload['session'] ?? null) ? $requestPayload['session'] : [])
                                        @php($requestAuthentication = is_array($requestPayload['authentication'] ?? null) ? $requestPayload['authentication'] : [])
                                        @php($requestMiddleware = is_array($requestPayload['middleware'] ?? null) ? $requestPayload['middleware'] : [])
                                        @php($requestPath = ($requestPayload['path'] ?? null) ?: ($requestPayload['url'] ?? null) ?: '—')
                                        @php($requestHost = parse_url((string) ($requestPayload['url'] ?? ''), PHP_URL_HOST) ?: '—')
                                        @php(
                                            $formatRequestBytes = static fn (int $bytes): string => $bytes >= 1024
                                                ? number_format($bytes / 1024, 2).' KB'
                                                : number_format($bytes).' B'
                                        )
                                        @php(
                                            $formatRequestValue = static function (mixed $value): string {
                                                if ($value === null) {
                                                    return 'null';
                                                }

                                                if (is_bool($value)) {
                                                    return $value ? 'true' : 'false';
                                                }

                                                if (is_array($value)) {
                                                    return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                                                }

                                                return (string) $value;
                                            }
                                        )
                                        @php(
                                            $requestDetailGroups = [
                                                'headers' => [
                                                    'label' => 'Headers',
                                                    'count' => count($requestHeaders),
                                                    'items' => $requestHeaders,
                                                ],
                                                'input' => [
                                                    'label' => 'Input',
                                                    'count' => count($requestInput),
                                                    'items' => $requestInput,
                                                ],
                                                'query' => [
                                                    'label' => 'Query',
                                                    'count' => count($requestQuery),
                                                    'items' => $requestQuery,
                                                ],
                                                'session' => [
                                                    'label' => 'Session',
                                                    'count' => (int) ($requestSession['key_count'] ?? 0),
                                                    'items' => [
                                                        'started' => (bool) ($requestSession['present'] ?? false),
                                                        'driver' => $requestSession['driver'] ?? '—',
                                                        'keys' => $requestSession['keys'] ?? [],
                                                        'flash keys' => $requestSession['flash_keys'] ?? [],
                                                        'error bags' => $requestSession['error_bags'] ?? [],
                                                    ],
                                                ],
                                            ]
                                        )

                                        @if ($isHttpRequest)
                                            <div data-ndb-request-trace>
                                                <div class="ndb:flex ndb:flex-col ndb:gap-3 ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/55 ndb:px-4 ndb:py-3 ndb:sm:flex-row ndb:sm:items-center ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/35">
                                                    <div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3">
                                                        <span class="ndb:shrink-0 ndb:rounded-md ndb:bg-emerald-50 ndb:px-2 ndb:py-1 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-emerald-700 ndb:ring-1 ndb:ring-inset ndb:ring-emerald-200 ndb:dark:bg-emerald-950/60 ndb:dark:text-emerald-300 ndb:dark:ring-emerald-900">
                                                            {{ $requestPayload['method'] ?? 'HTTP' }}
                                                        </span>
                                                        <code class="ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-bold">{{ $requestPath }}</code>
                                                    </div>
                                                    <span class="ndb:hidden ndb:h-5 ndb:w-px ndb:shrink-0 ndb:bg-zinc-200 ndb:sm:block ndb:dark:bg-zinc-800"></span>
                                                    <span class="ndb:text-xs ndb:font-bold ndb:tabular-nums {{ $requestSucceeded ? 'ndb:text-emerald-600 ndb:dark:text-emerald-400' : 'ndb:text-red-600 ndb:dark:text-red-400' }}">
                                                        {{ $requestStatus ?: '—' }} {{ $requestSucceeded ? 'Success' : 'Failed' }}
                                                    </span>
                                                    <span class="ndb:hidden ndb:h-5 ndb:w-px ndb:shrink-0 ndb:bg-zinc-200 ndb:sm:block ndb:dark:bg-zinc-800"></span>
                                                    <p class="ndb:min-w-0 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                                        {{ $requestSucceeded ? 'Completed successfully' : 'Completed with an error' }} in
                                                        <span class="ndb:whitespace-nowrap ndb:tabular-nums">{{ $requestDuration }} ms</span>
                                                    </p>
                                                </div>

                                                <ol class="ndb:mt-5 ndb:list-none" aria-label="Request trace">
                                                    <li
                                                        data-ndb-request-step="received"
                                                        class="ndb:grid ndb:grid-cols-[18px_minmax(0,1fr)] ndb:gap-x-4"
                                                    >
                                                        <div
                                                            aria-hidden="true"
                                                            class="ndb:relative ndb:flex ndb:justify-center ndb:pt-0.5"
                                                        >
                                                            <span
                                                                data-ndb-request-line
                                                                class="ndb:absolute ndb:top-[18px] ndb:-bottom-0.5 ndb:left-1/2 ndb:w-0.5 ndb:-translate-x-1/2 ndb:bg-indigo-400 ndb:dark:bg-indigo-500"
                                                            ></span>
                                                            <span
                                                                data-ndb-request-dot
                                                                class="ndb:relative ndb:z-[1] ndb:size-4 ndb:rounded-full ndb:border-2 ndb:border-indigo-500 ndb:bg-white ndb:dark:border-indigo-400 ndb:dark:bg-zinc-950"
                                                            ></span>
                                                        </div>
                                                        <div class="ndb:pb-6">
                                                            <h3 class="ndb:text-sm ndb:font-bold ndb:leading-5">
                                                                Received
                                                            </h3>
                                                            <p class="ndb:mt-0.5 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                                                Laravel received the request.
                                                            </p>
                                                            <dl class="ndb:mt-4 ndb:grid ndb:grid-cols-2 ndb:gap-x-5 ndb:gap-y-3 ndb:sm:grid-cols-4">
                                                                @foreach ([
                                                                    ['URL', ($requestPayload['url'] ?? null) ?: '—'],
                                                                    ['Host', $requestHost],
                                                                    ['Content type', ($requestPayload['content_type'] ?? null) ?: '—'],
                                                                    ['Request size', $formatRequestBytes((int) ($requestPayload['request_size_bytes'] ?? 0))],
                                                                ] as [$label, $value])
                                                                    <div class="ndb:min-w-0">
                                                                        <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                                            {{ $label }}
                                                                        </dt>
                                                                        <dd
                                                                            class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-semibold"
                                                                            title="{{ $value }}"
                                                                        >
                                                                            {{ $value }}
                                                                        </dd>
                                                                    </div>
                                                                @endforeach
                                                            </dl>
                                                        </div>
                                                    </li>

                                                    <li
                                                        data-ndb-request-step="matched"
                                                        class="ndb:grid ndb:grid-cols-[18px_minmax(0,1fr)] ndb:gap-x-4"
                                                    >
                                                        <div
                                                            aria-hidden="true"
                                                            class="ndb:relative ndb:flex ndb:justify-center ndb:pt-0.5"
                                                        >
                                                            <span
                                                                data-ndb-request-line
                                                                class="ndb:absolute ndb:top-[18px] ndb:-bottom-0.5 ndb:left-1/2 ndb:w-0.5 ndb:-translate-x-1/2 ndb:bg-indigo-400 ndb:dark:bg-indigo-500"
                                                            ></span>
                                                            <span
                                                                data-ndb-request-dot
                                                                class="ndb:relative ndb:z-[1] ndb:size-4 ndb:rounded-full ndb:border-2 ndb:border-indigo-500 ndb:bg-white ndb:dark:border-indigo-400 ndb:dark:bg-zinc-950"
                                                            ></span>
                                                        </div>
                                                        <div class="ndb:pb-6">
                                                            <h3 class="ndb:text-sm ndb:font-bold ndb:leading-5">
                                                                Matched
                                                            </h3>
                                                            <p class="ndb:mt-0.5 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                                                Laravel matched the route and middleware.
                                                            </p>
                                                            <dl class="ndb:mt-4 ndb:grid ndb:grid-cols-2 ndb:gap-x-5 ndb:gap-y-3 ndb:sm:grid-cols-4">
                                                                <div class="ndb:min-w-0">
                                                                    <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                                        Route
                                                                    </dt>
                                                                    <dd class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-semibold">
                                                                        {{ ($requestPayload['route'] ?? null) ?: 'Unnamed route' }}
                                                                    </dd>
                                                                </div>
                                                                <div class="ndb:col-span-2 ndb:min-w-0">
                                                                    <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                                        Controller
                                                                    </dt>
                                                                    <dd class="ndb:mt-1 ndb:min-w-0">
                                                                        <code class="ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-semibold">{{ ($requestPayload['action'] ?? null) ?: 'Closure' }}</code>
                                                                    </dd>
                                                                </div>
                                                                <div class="ndb:min-w-0">
                                                                    <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                                        Middleware
                                                                    </dt>
                                                                    <dd class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-semibold">
                                                                        {{ count($requestMiddleware) }} {{ str('step')->plural(count($requestMiddleware)) }}
                                                                    </dd>
                                                                </div>
                                                                <div class="ndb:min-w-0">
                                                                    <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                                        Guard
                                                                    </dt>
                                                                    <dd class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-semibold">
                                                                        {{ $requestAuthentication['guard'] ?? 'unknown' }}
                                                                    </dd>
                                                                </div>
                                                                <div class="ndb:col-span-2 ndb:min-w-0">
                                                                    <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                                        Authentication
                                                                    </dt>
                                                                    <dd class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-semibold">
                                                                        {{ ($requestPayload['authenticated'] ?? false) ? ($requestAuthentication['model'] ?? 'Authenticated') : 'Guest' }}
                                                                    </dd>
                                                                </div>
                                                            </dl>
                                                        </div>
                                                    </li>

                                                    <li
                                                        data-ndb-request-step="responded"
                                                        class="ndb:grid ndb:grid-cols-[18px_minmax(0,1fr)] ndb:gap-x-4"
                                                    >
                                                        <div
                                                            aria-hidden="true"
                                                            class="ndb:relative ndb:flex ndb:justify-center ndb:pt-0.5"
                                                        >
                                                            <span
                                                                data-ndb-request-dot
                                                                class="ndb:relative ndb:z-[1] ndb:size-4 ndb:rounded-full ndb:border-2 ndb:border-indigo-500 ndb:bg-white ndb:dark:border-indigo-400 ndb:dark:bg-zinc-950"
                                                            ></span>
                                                        </div>
                                                        <div>
                                                            <h3 class="ndb:text-sm ndb:font-bold ndb:leading-5">
                                                                Responded
                                                            </h3>
                                                            <p class="ndb:mt-0.5 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                                                Laravel sent the response to the client.
                                                            </p>
                                                            <dl class="ndb:mt-4 ndb:grid ndb:grid-cols-2 ndb:gap-x-5 ndb:gap-y-3 ndb:sm:grid-cols-4">
                                                                @foreach ([
                                                                    ['Status', $requestStatus ?: '—'],
                                                                    ['Response size', $formatRequestBytes((int) ($requestPayload['response_size_bytes'] ?? 0))],
                                                                    ['Duration', $requestDuration.' ms'],
                                                                    ['Queries', $requestQueryCount],
                                                                ] as [$label, $value])
                                                                    <div class="ndb:min-w-0">
                                                                        <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                                            {{ $label }}
                                                                        </dt>
                                                                        <dd class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-bold ndb:tabular-nums {{ $label === 'Status' ? ($requestSucceeded ? 'ndb:text-emerald-600 ndb:dark:text-emerald-400' : 'ndb:text-red-600 ndb:dark:text-red-400') : '' }}">
                                                                            {{ $value }}
                                                                        </dd>
                                                                    </div>
                                                                @endforeach
                                                            </dl>
                                                        </div>
                                                    </li>
                                                </ol>
                                            </div>

                                            <details
                                                data-ndb-request-details
                                                class="ndb:group ndb:mt-8 ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/25"
                                            >
                                                <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-4 ndb:py-3 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500">
                                                    <span class="ndb:min-w-0 ndb:flex-1">
                                                        <span class="ndb:block ndb:text-xs ndb:font-bold">Request details</span>
                                                        <span class="ndb:mt-0.5 ndb:block ndb:text-[10px] ndb:text-zinc-400">Headers, input, query parameters, and session shape</span>
                                                    </span>
                                                    <x-newdebugbar::icon
                                                        name="chevron-down"
                                                        class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
                                                    />
                                                </summary>
                                                <div
                                                    x-data="{ requestDetail: 'headers' }"
                                                    class="ndb:border-t ndb:border-zinc-200/90 ndb:sm:grid ndb:sm:grid-cols-[11rem_minmax(0,1fr)] ndb:dark:border-zinc-800"
                                                >
                                                    <div class="ndb:grid ndb:grid-cols-2 ndb:gap-1 ndb:border-b ndb:border-zinc-200/90 ndb:bg-zinc-50/70 ndb:p-2 ndb:sm:block ndb:sm:border-r ndb:sm:border-b-0 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/50">
                                                        @foreach ($requestDetailGroups as $requestDetailKey => $requestDetailGroup)
                                                            <button
                                                                type="button"
                                                                data-ndb-request-detail="{{ $requestDetailKey }}"
                                                                @click="requestDetail = @js($requestDetailKey)"
                                                                :aria-pressed="requestDetail === @js($requestDetailKey)"
                                                                :class="requestDetail === @js($requestDetailKey) ? 'ndb:bg-indigo-50 ndb:text-indigo-700 ndb:dark:bg-indigo-950/70 ndb:dark:text-indigo-300' : 'ndb:text-zinc-600 ndb:hover:bg-white ndb:hover:text-zinc-950 ndb:dark:text-zinc-400 ndb:dark:hover:bg-zinc-800 ndb:dark:hover:text-white'"
                                                                class="ndb:flex ndb:w-full ndb:min-w-0 ndb:items-center ndb:gap-2 ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-left ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-1 ndb:focus-visible:outline-indigo-500"
                                                            >
                                                                <span class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-bold">{{ $requestDetailGroup['label'] }}</span>
                                                                <span
                                                                    data-ndb-request-detail-count
                                                                    class="ndb:shrink-0 ndb:text-[10px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
                                                                >{{ $requestDetailGroup['count'] }}</span>
                                                            </button>
                                                        @endforeach
                                                    </div>

                                                    <div class="ndb:min-w-0 ndb:p-4">
                                                        @foreach ($requestDetailGroups as $requestDetailKey => $requestDetailGroup)
                                                            <div
                                                                data-ndb-request-detail-panel="{{ $requestDetailKey }}"
                                                                x-show.important="requestDetail === @js($requestDetailKey)"
                                                            >
                                                                <div class="ndb:flex ndb:items-center ndb:justify-between ndb:gap-3">
                                                                    <div class="ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-3">
                                                                        <h3 class="ndb:text-xs ndb:font-bold">
                                                                            {{ $requestDetailGroup['label'] }}
                                                                        </h3>
                                                                        <span
                                                                            data-ndb-request-detail-panel-count
                                                                            class="ndb:text-[10px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
                                                                        >{{ $requestDetailGroup['count'] }}</span>
                                                                    </div>
                                                                    <button
                                                                        type="button"
                                                                        @click="copyText(@js(json_encode($requestDetailGroup['items'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)))"
                                                                        class="ndb:shrink-0 ndb:text-[10px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                                                                    >
                                                                        Copy all
                                                                    </button>
                                                                </div>

                                                                <div class="ndb:mt-3 ndb:overflow-x-auto">
                                                                    @if ($requestDetailGroup['items'] !== [])
                                                                        <table class="ndb:w-full ndb:table-fixed ndb:border-collapse ndb:text-left">
                                                                            <thead>
                                                                                <tr class="ndb:border-b ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
                                                                                    <th
                                                                                        scope="col"
                                                                                        class="ndb:w-2/5 ndb:pb-2 ndb:pr-4 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                                                    >
                                                                                        Name
                                                                                    </th>
                                                                                    <th
                                                                                        scope="col"
                                                                                        class="ndb:pb-2 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                                                    >
                                                                                        Value
                                                                                    </th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach ($requestDetailGroup['items'] as $requestDetailName => $requestDetailValue)
                                                                                    <tr class="ndb:border-b ndb:border-zinc-200/70 ndb:last:border-b-0 ndb:dark:border-zinc-800/80">
                                                                                        <th
                                                                                            scope="row"
                                                                                            class="ndb:py-2 ndb:pr-4 ndb:align-top ndb:font-mono ndb:text-[10px] ndb:font-medium ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                                                                        >
                                                                                            {{ $requestDetailName }}
                                                                                        </th>
                                                                                        <td class="ndb:break-words ndb:py-2 ndb:align-top ndb:font-mono ndb:text-[10px] ndb:text-zinc-800 ndb:dark:text-zinc-200">
                                                                                            {{ $formatRequestValue($requestDetailValue) }}
                                                                                        </td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    @else
                                                                        <p class="ndb:rounded-lg ndb:bg-zinc-50 ndb:px-3 ndb:py-4 ndb:text-xs ndb:text-zinc-500 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-400">
                                                                            No {{ strtolower($requestDetailGroup['label']) }} were
                                                                            captured.
                                                                        </p>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </details>
                                        @else
                                            <div class="ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:p-4 ndb:dark:border-zinc-800">
                                                <h3 class="ndb:text-xs ndb:font-bold">Runtime summary</h3>
                                                <dl class="ndb:mt-4 ndb:grid ndb:grid-cols-2 ndb:gap-x-5 ndb:gap-y-3 ndb:sm:grid-cols-4">
                                                    @foreach ([
                                                        ['Type', str($profile['profile_type'] ?? 'runtime')->replace('_', ' ')->title()],
                                                        ['Name', ($requestPayload['name'] ?? null) ?: $requestPath],
                                                        ['Status', $requestPayload['exit_code'] ?? $requestPayload['status'] ?? '—'],
                                                        ['Duration', $requestDuration.' ms'],
                                                    ] as [$label, $value])
                                                        <div class="ndb:min-w-0">
                                                            <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                                {{ $label }}
                                                            </dt>
                                                            <dd class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-semibold">
                                                                {{ $value }}
                                                            </dd>
                                                        </div>
                                                    @endforeach
                                                </dl>
                                            </div>
                                        @endif
                                    @elseif ($sectionKey === 'queries')
                                        <x-newdebugbar::query-section
                                            :section="$section"
                                            :query-explains="$queryExplains"
                                            :query-explain-errors="$queryExplainErrors"
                                        />
                                    @elseif ($sectionKey === 'http_client')
                                        <dl class="ndb:grid ndb:grid-cols-3 ndb:divide-x ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                                            @foreach ([['Requests', $section['summary']['count']], ['Total time', $section['summary']['duration_ms'].' ms'], ['Failures', $section['summary']['failed_count']]] as [$label, $value])
                                                <div class="ndb:px-3.5 ndb:py-3">
                                                    <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                        {{ $label }}
                                                    </dt>
                                                    <dd class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">
                                                        {{ $value }}
                                                    </dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                        <div class="ndb:space-y-2">
                                            @forelse ($section['payload']['items'] as $index => $item)
                                                <article
                                                    wire:key="http-client-{{ $index }}"
                                                    class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:px-3.5 ndb:py-3 {{ ($item['failed'] ?? false) ? 'ndb:border-red-200 ndb:bg-red-50/35 ndb:dark:border-red-950 ndb:dark:bg-red-950/15' : 'ndb:border-zinc-200 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30' }}"
                                                >
                                                    <span class="ndb:rounded-md ndb:bg-indigo-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:font-bold ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300">{{ $item['method'] }}</span>
                                                    <div class="ndb:min-w-0 ndb:flex-1">
                                                        <p class="ndb:truncate ndb:text-xs ndb:font-semibold">
                                                            {{ $item['url'] }}
                                                        </p>
                                                        <p class="ndb:mt-1 ndb:text-[10px] ndb:font-semibold {{ ($item['failed'] ?? false) ? 'ndb:text-red-700 ndb:dark:text-red-300' : 'ndb:text-zinc-400' }}">
                                                            {{ ($item['failed'] ?? false) ? ($item['exception_message'] ?? $item['exception_class'] ?? 'Request failed') : 'HTTP '.$item['status'] }}
                                                        </p>
                                                        @if (is_array($item['callsite'] ?? null))
                                                            <p class="ndb:mt-1 ndb:min-w-0 ndb:truncate ndb:text-[10px] ndb:text-zinc-400">
                                                                <span class="ndb:min-w-0 ndb:truncate">{{ $item['callsite']['copy'] ?? (($item['callsite']['file'] ?? 'Unknown source').':'.($item['callsite']['line'] ?? '?')) }}</span>
                                                            </p>
                                                        @endif
                                                    </div>
                                                    <span class="ndb:shrink-0 ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $item['duration_ms'] }} ms</span>
                                                </article>
                                            @empty
                                                <x-newdebugbar::empty-state label="No outbound HTTP requests were captured." />
                                            @endforelse
                                        </div>
                                    @elseif ($sectionKey === 'queue')
                                        <dl class="ndb:grid ndb:grid-cols-2 ndb:divide-x ndb:divide-y ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:sm:grid-cols-4 ndb:sm:divide-y-0 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                                            @foreach ([['Queued', $section['summary']['queued_count']], ['Executed', $section['summary']['executed_count']], ['Failures', $section['summary']['failed_count']], ['Run time', $section['summary']['duration_ms'].' ms']] as [$label, $value])
                                                <div class="ndb:px-3.5 ndb:py-3">
                                                    <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                        {{ $label }}
                                                    </dt>
                                                    <dd class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">
                                                        {{ $value }}
                                                    </dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                        <div class="ndb:space-y-2">
                                            @forelse ($section['payload']['items'] as $index => $item)
                                                <article
                                                    wire:key="queue-{{ $index }}"
                                                    class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:px-3.5 ndb:py-3 {{ ($item['kind'] ?? null) === 'failed' ? 'ndb:border-red-200 ndb:bg-red-50/35 ndb:dark:border-red-950 ndb:dark:bg-red-950/15' : 'ndb:border-zinc-200 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30' }}"
                                                >
                                                    <span class="ndb:w-16 ndb:shrink-0 ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:tracking-wider {{ ($item['kind'] ?? null) === 'failed' ? 'ndb:text-red-600 ndb:dark:text-red-300' : 'ndb:text-zinc-400' }}">{{ $item['kind'] }}</span>
                                                    <div class="ndb:min-w-0 ndb:flex-1">
                                                        <code
                                                            title="{{ $item['job'] }}"
                                                            class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold"
                                                        >{{ class_basename($item['job']) }}</code>
                                                        <p class="ndb:mt-1 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">
                                                            <span>{{ $item['connection'] }}</span
                                                            ><span>{{ $item['queue'] ?: 'default queue' }}</span>
                                                            @if (($item['attempt'] ?? null) !== null)
                                                                <span>Attempt {{ $item['attempt'] }}</span>
                                                            @endif
                                                        </p>
                                                    </div>
                                                    @if (($item['kind'] ?? null) !== 'queued')
                                                        <span class="ndb:shrink-0 ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $item['duration_ms'] }} ms</span>
                                                    @elseif (($item['delay_seconds'] ?? null) !== null)
                                                        <span class="ndb:shrink-0 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">{{ $item['delay_seconds'] }} s delay</span>
                                                    @endif
                                                </article>
                                            @empty
                                                <x-newdebugbar::empty-state label="No queue activity was captured." />
                                            @endforelse
                                        </div>
                                    @elseif ($sectionKey === 'mail')
                                        <dl class="ndb:grid ndb:grid-cols-3 ndb:divide-x ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                                            @foreach ([['Messages', $section['summary']['count']], ['Recipients', $section['summary']['recipient_count']], ['Attachments', $section['summary']['attachment_count']]] as [$label, $value])
                                                <div class="ndb:px-3.5 ndb:py-3">
                                                    <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                        {{ $label }}
                                                    </dt>
                                                    <dd class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">
                                                        {{ $value }}
                                                    </dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                        <div class="ndb:space-y-2">
                                            @forelse ($section['payload']['items'] as $index => $item)
                                                <article
                                                    wire:key="mail-{{ $index }}"
                                                    class="ndb:min-w-0 ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:bg-white/45 ndb:px-3.5 ndb:py-3 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30"
                                                >
                                                    <div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3">
                                                        <div class="ndb:min-w-0 ndb:flex-1">
                                                            <code class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold">{{ $item['source'] ?: 'Mail message' }}</code>
                                                            <p class="ndb:mt-1 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">
                                                                <span>{{ $item['recipient_count'] }} recipients</span
                                                                ><span>{{ $item['attachment_count'] }} attachments</span
                                                                ><span
                                                                    >{{ $item['has_html'] ? 'HTML' : 'No HTML' }}</span
                                                                ><span
                                                                    >{{ $item['has_text'] ? 'Text' : 'No text' }}</span>
                                                            </p>
                                                        </div>
                                                        <span class="ndb:shrink-0 ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $item['duration_ms'] }} ms</span>
                                                    </div>
                                                    @if (is_array($item['preview'] ?? null))
                                                        <div class="ndb:mt-3 ndb:space-y-2 ndb:border-t ndb:border-zinc-200 ndb:pt-3 ndb:dark:border-zinc-800">
                                                            <p class="ndb:text-xs ndb:font-semibold">
                                                                {{ $item['preview']['subject'] ?: '(No subject)' }}
                                                            </p>
                                                            <p class="ndb:break-all ndb:text-[10px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                                                To: {{ implode(', ', $item['preview']['to']) ?: '(none)' }}
                                                            </p>
                                                            <div class="ndb:flex ndb:flex-wrap ndb:gap-2">
                                                                @if (is_string($item['preview']['html'] ?? null))
                                                                    <a
                                                                        href="{{ route('newdebugbar.mail-preview', ['profile' => $profileId, 'index' => $index, 'format' => 'html']) }}"
                                                                        target="_blank"
                                                                        rel="noreferrer"
                                                                        class="ndb:rounded-md ndb:border ndb:border-zinc-200 ndb:px-2 ndb:py-1 ndb:text-[10px] ndb:font-semibold ndb:hover:bg-zinc-100 ndb:dark:border-zinc-700 ndb:dark:hover:bg-zinc-800"
                                                                    >Open HTML preview</a>
                                                                @endif
                                                                @if (is_string($item['preview']['text'] ?? null))
                                                                    <a
                                                                        href="{{ route('newdebugbar.mail-preview', ['profile' => $profileId, 'index' => $index, 'format' => 'text']) }}"
                                                                        target="_blank"
                                                                        rel="noreferrer"
                                                                        class="ndb:rounded-md ndb:border ndb:border-zinc-200 ndb:px-2 ndb:py-1 ndb:text-[10px] ndb:font-semibold ndb:hover:bg-zinc-100 ndb:dark:border-zinc-700 ndb:dark:hover:bg-zinc-800"
                                                                    >Open text preview</a>
                                                                @endif
                                                                <a
                                                                    href="{{ route('newdebugbar.mail-preview', ['profile' => $profileId, 'index' => $index, 'format' => 'eml']) }}"
                                                                    class="ndb:rounded-md ndb:border ndb:border-zinc-200 ndb:px-2 ndb:py-1 ndb:text-[10px] ndb:font-semibold ndb:hover:bg-zinc-100 ndb:dark:border-zinc-700 ndb:dark:hover:bg-zinc-800"
                                                                >Download .eml</a>
                                                            </div>
                                                            @if (($item['preview']['attachments_omitted'] ?? 0) > 0 || ($item['preview']['addresses_omitted'] ?? 0) > 0 || ($item['preview']['truncated'] ?? false))
                                                                <p class="ndb:text-[10px] ndb:font-semibold ndb:text-amber-700 ndb:dark:text-amber-300">
                                                                    @if (($item['preview']['attachments_omitted'] ?? 0) > 0) {{ $item['preview']['attachments_omitted'] }}attachments omitted.@endif
                                                                    @if (($item['preview']['addresses_omitted'] ?? 0) > 0) {{ $item['preview']['addresses_omitted'] }}addresses omitted.@endif
                                                                    @if ($item['preview']['truncated'] ?? false)
                                                                        Preview content was bounded.
                                                                    @endif
                                                                </p>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </article>
                                            @empty
                                                <x-newdebugbar::empty-state label="No mail was sent." />
                                            @endforelse
                                        </div>
                                    @elseif ($sectionKey === 'notifications')
                                        <dl class="ndb:grid ndb:grid-cols-2 ndb:divide-x ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                                            @foreach ([['Sent', $section['summary']['sent_count']], ['Failed', $section['summary']['failed_count']]] as [$label, $value])
                                                <div class="ndb:px-3.5 ndb:py-3">
                                                    <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                        {{ $label }}
                                                    </dt>
                                                    <dd class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">
                                                        {{ $value }}
                                                    </dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                        <div class="ndb:space-y-2">
                                            @forelse ($section['payload']['items'] as $index => $item)
                                                <article
                                                    wire:key="notification-{{ $index }}"
                                                    class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:px-3.5 ndb:py-3 {{ $item['status'] === 'failed' ? 'ndb:border-red-200 ndb:bg-red-50/35 ndb:dark:border-red-950 ndb:dark:bg-red-950/15' : 'ndb:border-zinc-200 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30' }}"
                                                >
                                                    <span class="ndb:w-12 ndb:shrink-0 ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:tracking-wider {{ $item['status'] === 'failed' ? 'ndb:text-red-600 ndb:dark:text-red-300' : 'ndb:text-emerald-600 ndb:dark:text-emerald-300' }}">{{ $item['status'] }}</span>
                                                    <div class="ndb:min-w-0 ndb:flex-1">
                                                        <code
                                                            title="{{ $item['notification'] }}"
                                                            class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold"
                                                        >{{ class_basename($item['notification']) }}</code>
                                                        <p class="ndb:mt-1 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">
                                                            <span>{{ $item['channel'] }}</span
                                                            ><span
                                                                title="{{ $item['notifiable_type'] }}"
                                                                >{{ class_basename($item['notifiable_type']) }}</span>
                                                        </p>
                                                    </div>
                                                </article>
                                            @empty
                                                <x-newdebugbar::empty-state label="No notifications were sent." />
                                            @endforelse
                                        </div>
                                    @elseif ($sectionKey === 'redis')
                                        <dl class="ndb:grid ndb:grid-cols-3 ndb:divide-x ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                                            @foreach ([['Commands', $section['summary']['count']], ['Total time', $section['summary']['duration_ms'].' ms'], ['Failures', $section['summary']['failed_count']]] as [$label, $value])
                                                <div class="ndb:px-3.5 ndb:py-3">
                                                    <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                        {{ $label }}
                                                    </dt>
                                                    <dd class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">
                                                        {{ $value }}
                                                    </dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                        <div class="ndb:space-y-2">
                                            @forelse ($section['payload']['items'] as $index => $item)
                                                <article
                                                    wire:key="redis-{{ $index }}"
                                                    class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:px-3.5 ndb:py-3 {{ ($item['failed'] ?? false) ? 'ndb:border-red-200 ndb:bg-red-50/35 ndb:dark:border-red-950 ndb:dark:bg-red-950/15' : 'ndb:border-zinc-200 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30' }}"
                                                >
                                                    <code class="ndb:w-20 ndb:shrink-0 ndb:text-xs ndb:font-bold">{{ $item['command'] }}</code>
                                                    <div class="ndb:min-w-0 ndb:flex-1">
                                                        <p class="ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:gap-y-1 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">
                                                            <span>{{ $item['connection'] }}</span
                                                            ><span>{{ $item['key_policy'] ?? 'hash' }} keys</span>
                                                        </p>
                                                        @if (($item['keys'] ?? []) !== [])
                                                            <code class="ndb:mt-1 ndb:block ndb:truncate ndb:text-[10px]">{{ implode(', ', $item['keys']) }}</code>
                                                        @elseif (($item['key_hashes'] ?? []) !== [])
                                                            <code class="ndb:mt-1 ndb:block ndb:truncate ndb:text-[10px]">{{ implode(', ', $item['key_hashes']) }}</code>
                                                        @else
                                                            <p class="ndb:mt-1 ndb:text-[10px] ndb:text-zinc-400">
                                                                No key metadata
                                                            </p>
                                                        @endif
                                                    </div>
                                                    <span class="ndb:shrink-0 ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $item['duration_ms'] }} ms</span>
                                                </article>
                                            @empty
                                                <x-newdebugbar::empty-state label="No direct Redis commands were captured." />
                                            @endforelse
                                        </div>
                                    @elseif ($sectionKey === 'authorization')
                                        @php($authorizationItems = $section['payload']['items'])
                                        @if ($authorizationItems !== [])
                                            @php($authorizationCounts = array_count_values(array_column($authorizationItems, 'result')))
                                            <div>
                                                <p class="ndb:mb-1.5 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                    Filter
                                                </p>
                                                <div
                                                    class="ndb:flex ndb:gap-1 ndb:overflow-x-auto"
                                                    role="group"
                                                    aria-label="Filter authorization decisions"
                                                >
                                                    @foreach (['all' => count($authorizationItems), 'allowed' => $authorizationCounts['allowed'] ?? 0, 'denied' => $authorizationCounts['denied'] ?? 0] as $filter => $count)
                                                        <button
                                                            type="button"
                                                            data-ndb-authorization-filter="{{ $filter }}"
                                                            @click="setAuthorizationFilter(@js($filter))"
                                                            :aria-pressed="authorizationFilter === @js($filter)"
                                                            class="ndb:rounded-lg ndb:border ndb:px-3 ndb:py-1.5 ndb:text-xs ndb:font-semibold ndb:capitalize ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
                                                            :class="authorizationFilter === @js($filter) ? 'ndb:border-indigo-200 ndb:bg-indigo-50 ndb:text-indigo-700 ndb:dark:border-indigo-900 ndb:dark:bg-indigo-950/60 ndb:dark:text-indigo-300' : 'ndb:border-transparent ndb:text-zinc-500 ndb:hover:border-zinc-200 ndb:hover:bg-white/70 ndb:hover:text-zinc-950 ndb:dark:text-zinc-400 ndb:dark:hover:border-zinc-700 ndb:dark:hover:bg-zinc-900/70 ndb:dark:hover:text-white'"
                                                        >
                                                            {{ $filter }}
                                                            <span class="ndb:tabular-nums ndb:text-zinc-400">{{ $count }}</span>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <p
                                                data-ndb-authorization-result-count
                                                class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400"
                                            >
                                                <span x-text="visibleAuthorizationCount"></span> results
                                            </p>
                                            <div x-ref="authorizationItems" class="ndb:space-y-2">
                                                @foreach ($authorizationItems as $index => $item)
                                                    <article
                                                        data-ndb-authorization-item
                                                        data-result="{{ $item['result'] }}"
                                                        wire:key="authorization-{{ $index }}"
                                                        class="ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:px-3.5 ndb:py-3 {{ $item['result'] === 'allowed' ? 'ndb:border-emerald-200 ndb:bg-emerald-50/35 ndb:dark:border-emerald-950 ndb:dark:bg-emerald-950/15' : 'ndb:border-red-200 ndb:bg-red-50/35 ndb:dark:border-red-950 ndb:dark:bg-red-950/15' }}"
                                                    >
                                                        <span class="ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:tracking-wider {{ $item['result'] === 'allowed' ? 'ndb:text-emerald-700 ndb:dark:text-emerald-300' : 'ndb:text-red-700 ndb:dark:text-red-300' }}">{{ $item['result'] }}</span>
                                                        <code class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-bold">{{ $item['ability'] }}</code>
                                                        <span
                                                            title="{{ $item['handler'] }}"
                                                            class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400"
                                                        >{{ str($item['handler'])->afterLast('\\') }}</span>
                                                        <p class="ndb:w-full ndb:text-[10px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                                            {{ implode(', ', array_filter([$item['user_type'] ?? null, ...($item['argument_types'] ?? [])])) ?: 'No typed arguments' }}
                                                        </p>
                                                        @if (is_array($item['callsite'] ?? null))
                                                            <p class="ndb:w-full ndb:min-w-0 ndb:truncate ndb:text-[10px] ndb:text-zinc-400">
                                                                <span class="ndb:min-w-0 ndb:flex-1 ndb:truncate">{{ $item['callsite']['copy'] ?? (($item['callsite']['file'] ?? 'Unknown source').':'.($item['callsite']['line'] ?? '?')) }}</span>
                                                            </p>
                                                        @endif
                                                    </article>
                                                @endforeach
                                            </div>
                                            <div x-show.important="visibleAuthorizationCount === 0">
                                                <x-newdebugbar::empty-state label="No authorization decisions match this filter." />
                                            </div>
                                        @else
                                            <x-newdebugbar::empty-state label="No authorization decisions were captured." />
                                        @endif
                                    @elseif ($sectionKey === 'validation')
                                        <div class="ndb:space-y-3">
                                            @forelse ($section['payload']['items'] as $index => $item)
                                                <article
                                                    wire:key="validation-{{ $index }}"
                                                    class="ndb:rounded-xl ndb:border ndb:border-amber-200 ndb:bg-amber-50/35 ndb:p-4 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/15"
                                                >
                                                    <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-2">
                                                        <span class="ndb:text-xs ndb:font-bold"
                                                            >{{ count($item['fields']) }} invalid fields</span
                                                        ><span
                                                            class="ndb:ml-auto ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:gap-y-1 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                                            ><span>{{ $item['error_bag'] }} bag</span
                                                            ><span>HTTP {{ $item['response_status'] }}</span></span>
                                                    </div>
                                                    <dl class="ndb:mt-3 ndb:space-y-2">
                                                        @foreach ($item['rules'] as $field => $rules)
                                                            <div class="ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:items-center ndb:gap-2">
                                                                <dt class="ndb:min-w-24 ndb:font-mono ndb:text-[10px] ndb:font-bold">
                                                                    {{ $field }}
                                                                </dt>
                                                                <dd class="ndb:flex ndb:flex-wrap ndb:gap-1">
                                                                    @foreach ($rules as $rule)
                                                                        <span class="ndb:rounded-md ndb:bg-amber-100 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:font-semibold ndb:text-amber-800 ndb:dark:bg-amber-950 ndb:dark:text-amber-300">{{ $rule }}</span>
                                                                    @endforeach
                                                                </dd>
                                                            </div>
                                                        @endforeach
                                                    </dl>
                                                    @if (($item['messages'] ?? []) !== [])
                                                        <details class="ndb:group ndb:mt-3 ndb:border-t ndb:border-amber-200 ndb:pt-3 ndb:dark:border-amber-950">
                                                            <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-2 ndb:text-[10px] ndb:font-bold ndb:text-amber-800 ndb:focus-visible:outline-2 ndb:focus-visible:outline-amber-500 ndb:dark:text-amber-300">
                                                                <span class="ndb:flex-1">Show validation messages</span
                                                                ><x-newdebugbar::icon
                                                                    name="chevron-down"
                                                                    class="ndb:size-3.5 ndb:transition ndb:group-open:rotate-180"
                                                                />
                                                            </summary>
                                                            <dl class="ndb:mt-2 ndb:space-y-2">
                                                                @foreach ($item['messages'] as $field => $messages)
                                                                    <div>
                                                                        <dt class="ndb:font-mono ndb:text-[10px] ndb:font-bold">
                                                                            {{ $field }}
                                                                        </dt>
                                                                        <dd class="ndb:mt-0.5 ndb:text-xs">
                                                                            {{ implode(' ', (array) $messages) }}
                                                                        </dd>
                                                                    </div>
                                                                @endforeach
                                                            </dl>
                                                        </details>
                                                    @endif
                                                </article>
                                            @empty
                                                <x-newdebugbar::empty-state
                                                    label="No validation failures were captured."
                                                    success
                                                />
                                            @endforelse
                                        </div>
                                    @elseif ($sectionKey === 'lifecycle')
                                        <div class="ndb:space-y-2">
                                            @if (($profile['sections']['request']['payload']['timing_scope'] ?? null) === 'global_middleware_entry')
                                                <p
                                                    data-ndb-lifecycle-scope
                                                    class="ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-zinc-50/70 ndb:px-3 ndb:py-2 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/60 ndb:dark:text-zinc-400"
                                                >
                                                    Timing starts at the debug middleware. Early Laravel bootstrap is
                                                    not measured.
                                                </p>
                                            @endif
                                            @forelse ($section['payload']['items'] as $index => $item)
                                                <article
                                                    wire:key="lifecycle-{{ $index }}"
                                                    class="ndb:flex ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:px-3.5 ndb:py-3 ndb:dark:border-zinc-800"
                                                >
                                                    <span
                                                        class="ndb:text-[10px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-400"
                                                        >#{{ $index + 1 }}</span
                                                    ><span
                                                        class="ndb:min-w-0 ndb:flex-1 ndb:text-xs ndb:font-semibold"
                                                        >{{ $item['name'] }}</span
                                                    ><span class="ndb:text-xs ndb:font-bold ndb:tabular-nums"
                                                        >{{ $item['duration_ms'] }} ms</span>
                                                </article>
                                            @empty
                                                <x-newdebugbar::empty-state label="Laravel did not expose lifecycle spans for this profile." />
                                            @endforelse
                                        </div>
                                    @elseif ($sectionKey === 'messages')
                                        <div class="ndb:space-y-3">
                                            @forelse ($section['payload']['items'] as $index => $item)
                                                <article
                                                    wire:key="message-{{ $index }}"
                                                    class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"
                                                >
                                                    <div class="ndb:flex ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3">
                                                        <span
                                                            class="ndb:min-w-0 ndb:flex-1 ndb:text-xs ndb:font-bold"
                                                            >{{ $item['label'] }}</span
                                                        ><span
                                                            class="ndb:text-[10px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
                                                            >{{ $item['at_ms'] }} ms</span>
                                                    </div>
                                                    @if (($item['context'] ?? []) !== [])
                                                        <pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="json">{{ json_encode($item['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                                    @endif
                                                </article>
                                            @empty
                                                <x-newdebugbar::empty-state label="No developer messages were recorded." />
                                            @endforelse
                                        </div>
                                    @elseif ($sectionKey === 'models')
                                        @php($modelGroups = $section['payload']['model_groups'] ?? [])
                                        @php($changedModelCount = count(array_filter($modelGroups, fn (array $group): bool => $group['change_count'] > 0)))
                                        <div
                                            data-ndb-models
                                            x-data="{ modelsAllExpanded: false }"
                                            class="ndb:space-y-5"
                                        >
                                            <p class="ndb:max-w-3xl ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                                See which Eloquent models this request loaded or changed. Find repeated
                                                record loads, unexpected writes, and when the work happened. Repeated
                                                means extra retrievals after a record’s first load.
                                            </p>

                                            @if (($section['summary']['model_change_count'] ?? 0) > 0)
                                                <div
                                                    data-ndb-model-finding="changes"
                                                    class="ndb:rounded-xl ndb:border ndb:border-amber-200 ndb:bg-amber-50/55 ndb:px-4 ndb:py-3 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/20"
                                                >
                                                    <p class="ndb:text-xs ndb:font-bold ndb:text-amber-900 ndb:dark:text-amber-200">
                                                        {{ $section['summary']['model_change_count'] }} model {{ $section['summary']['model_change_count'] === 1 ? 'change' : 'changes' }}
                                                    </p>
                                                    <p class="ndb:mt-1 ndb:text-[10px] ndb:leading-4 ndb:text-amber-800/80 ndb:dark:text-amber-300/80">
                                                        {{ $changedModelCount }} {{ $changedModelCount === 1 ? 'model class changed' : 'model classes changed' }}.
                                                        Changes appear first because they can affect application state.
                                                    </p>
                                                </div>
                                            @elseif (($section['summary']['repeated_load_count'] ?? 0) > 0)
                                                <div
                                                    data-ndb-model-finding="repeated"
                                                    class="ndb:rounded-xl ndb:border ndb:border-amber-200 ndb:bg-amber-50/55 ndb:px-4 ndb:py-3 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/20"
                                                >
                                                    <p class="ndb:text-xs ndb:font-bold ndb:text-amber-900 ndb:dark:text-amber-200">
                                                        {{ $section['summary']['repeated_load_count'] }} repeated {{ $section['summary']['repeated_load_count'] === 1 ? 'load' : 'loads' }}
                                                    </p>
                                                    <p class="ndb:mt-1 ndb:text-[10px] ndb:leading-4 ndb:text-amber-800/80 ndb:dark:text-amber-300/80">
                                                        {{ $section['summary']['retrieval_count'] }} retrievals across {{ $section['summary']['distinct_record_count'] }} distinct {{ $section['summary']['distinct_record_count'] === 1 ? 'record' : 'records' }}.
                                                        Check repeated rows for avoidable database work.
                                                    </p>
                                                </div>
                                            @elseif (($section['summary']['retrieval_count'] ?? 0) > 0)
                                                <div
                                                    data-ndb-model-finding="clear"
                                                    class="ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:bg-zinc-50/55 ndb:px-4 ndb:py-3 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/35"
                                                >
                                                    <p class="ndb:text-xs ndb:font-bold">
                                                        No repeated identified loads
                                                    </p>
                                                    <p class="ndb:mt-1 ndb:text-[10px] ndb:leading-4 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                                        {{ $section['summary']['retrieval_count'] }} retrievals across {{ $section['summary']['distinct_record_count'] }} distinct {{ $section['summary']['distinct_record_count'] === 1 ? 'record' : 'records' }}.
                                                    </p>
                                                </div>
                                            @endif

                                            @if ($modelGroups !== [])
                                                <div>
                                                    <div class="ndb:grid ndb:grid-cols-[minmax(0,1fr)_auto] ndb:items-end ndb:gap-x-3 ndb:border-b ndb:border-zinc-200/90 ndb:pb-2 ndb:sm:grid-cols-[minmax(0,1fr)_5rem_5rem_5rem_4rem] ndb:dark:border-zinc-800">
                                                        <span class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Model</span>
                                                        <span
                                                            data-ndb-model-heading="loads"
                                                            class="ndb:hidden ndb:text-right ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:sm:block"
                                                        >Loads</span>
                                                        <span
                                                            data-ndb-model-heading="records"
                                                            class="ndb:hidden ndb:text-right ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:sm:block"
                                                        >Records</span>
                                                        <span
                                                            data-ndb-model-heading="repeated"
                                                            class="ndb:hidden ndb:text-right ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:sm:block"
                                                        >Repeated</span>
                                                        <button
                                                            type="button"
                                                            data-ndb-model-expand-all
                                                            @click="
                                                                modelsAllExpanded = ! modelsAllExpanded;
                                                                $root
                                                                    .querySelectorAll('[data-ndb-model-group]')
                                                                    .forEach(
                                                                        (group) => (group.open = modelsAllExpanded),
                                                                    );
                                                            "
                                                            class="ndb:justify-self-end ndb:whitespace-nowrap ndb:text-[10px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                                                            x-text="modelsAllExpanded ? 'Collapse all' : 'Expand all'"
                                                        >
                                                            Expand all
                                                        </button>
                                                    </div>

                                                    <div class="ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800">
                                                        @foreach ($modelGroups as $index => $group)
                                                            <details
                                                                data-ndb-model-group
                                                                data-loads="{{ $group['load_count'] }}"
                                                                data-records="{{ $group['record_count'] }}"
                                                                data-repeated="{{ $group['repeated_load_count'] }}"
                                                                data-changes="{{ $group['change_count'] }}"
                                                                wire:key="model-group-{{ $index }}"
                                                                @toggle="
                                                                    modelsAllExpanded = Array.from(
                                                                        $root.querySelectorAll(
                                                                            '[data-ndb-model-group]',
                                                                        ),
                                                                    ).every((modelGroup) => modelGroup.open)
                                                                "
                                                                class="ndb:group"
                                                            >
                                                                <summary class="ndb:grid ndb:cursor-pointer ndb:list-none ndb:grid-cols-[minmax(0,1fr)_1.5rem] ndb:items-center ndb:gap-x-3 ndb:py-3 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500 ndb:sm:grid-cols-[minmax(0,1fr)_5rem_5rem_5rem_4rem]">
                                                                    <span class="ndb:min-w-0">
                                                                        <span
                                                                            data-ndb-model-name
                                                                            class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold"
                                                                        >{{ class_basename($group['model']) }}</span>
                                                                        <span
                                                                            data-ndb-model-mobile-summary
                                                                            class="ndb:mt-1 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400 ndb:sm:hidden"
                                                                        >
                                                                            <span>{{ $group['load_count'] }} loads</span>
                                                                            <span>{{ $group['record_count'] }} records</span>
                                                                            <span @class(['ndb:text-amber-600 ndb:dark:text-amber-400' => $group['repeated_load_count'] > 0])>{{ $group['repeated_load_count'] }} repeated</span>
                                                                            @if ($group['change_count'] > 0)
                                                                                <span class="ndb:text-amber-600 ndb:dark:text-amber-400">{{ $group['change_count'] }} changed</span>
                                                                            @endif
                                                                        </span>
                                                                    </span>
                                                                    <span
                                                                        data-ndb-model-load-count
                                                                        class="ndb:hidden ndb:text-right ndb:text-xs ndb:font-bold ndb:tabular-nums ndb:sm:block"
                                                                    >{{ $group['load_count'] }}</span>
                                                                    <span
                                                                        data-ndb-model-record-count
                                                                        class="ndb:hidden ndb:text-right ndb:text-xs ndb:font-bold ndb:tabular-nums ndb:sm:block"
                                                                    >{{ $group['record_count'] }}</span>
                                                                    <span
                                                                        data-ndb-model-repeat-count
                                                                        class="ndb:hidden ndb:text-right ndb:text-xs ndb:font-bold ndb:tabular-nums {{ $group['repeated_load_count'] > 0 ? 'ndb:text-amber-600 ndb:dark:text-amber-400' : 'ndb:text-zinc-400' }} ndb:sm:block"
                                                                    >{{ $group['repeated_load_count'] }}</span>
                                                                    <x-newdebugbar::icon
                                                                        name="chevron-down"
                                                                        class="ndb:size-3.5 ndb:justify-self-end ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
                                                                    />
                                                                </summary>

                                                                <div class="ndb:border-t ndb:border-zinc-200/80 ndb:pb-4 ndb:pt-3 ndb:dark:border-zinc-800">
                                                                    <code class="ndb:block ndb:break-all ndb:text-[10px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $group['model'] }}</code>
                                                                    <dl class="ndb:mt-3 ndb:flex ndb:flex-wrap ndb:gap-x-8 ndb:gap-y-2">
                                                                        <div>
                                                                            <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                                                Connection
                                                                            </dt>
                                                                            <dd class="ndb:mt-0.5 ndb:font-mono ndb:text-[10px] ndb:font-semibold">
                                                                                {{ $group['connection'] ?? 'default' }}
                                                                            </dd>
                                                                        </div>
                                                                        <div>
                                                                            <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                                                Table
                                                                            </dt>
                                                                            <dd class="ndb:mt-0.5 ndb:font-mono ndb:text-[10px] ndb:font-semibold">
                                                                                {{ $group['table'] ?? 'unknown' }}
                                                                            </dd>
                                                                        </div>
                                                                    </dl>

                                                                    @if ($group['change_events'] !== [])
                                                                        <div
                                                                            data-ndb-model-changes
                                                                            class="ndb:mt-4 ndb:rounded-lg ndb:bg-amber-50/70 ndb:px-3 ndb:py-2.5 ndb:dark:bg-amber-950/25"
                                                                        >
                                                                            <p class="ndb:text-[10px] ndb:font-bold ndb:text-amber-900 ndb:dark:text-amber-200">
                                                                                Model changes
                                                                            </p>
                                                                            <div class="ndb:mt-1.5 ndb:flex ndb:flex-wrap ndb:gap-2">
                                                                                @foreach ($group['change_events'] as $event => $count)
                                                                                    <span class="ndb:rounded-md ndb:border ndb:border-amber-200 ndb:bg-white/70 ndb:px-2 ndb:py-1 ndb:text-[9px] ndb:font-semibold ndb:text-amber-800 ndb:dark:border-amber-900 ndb:dark:bg-amber-950/30 ndb:dark:text-amber-300">{{ $count }} {{ str($event)->headline()->lower() }}</span>
                                                                                @endforeach
                                                                            </div>
                                                                        </div>
                                                                    @endif

                                                                    @if ($group['records'] !== [])
                                                                        <div class="ndb-scrollbar ndb:mt-4 ndb:overflow-x-auto">
                                                                            <table class="ndb:w-full ndb:min-w-[34rem] ndb:border-collapse ndb:text-left">
                                                                                <thead>
                                                                                    <tr class="ndb:border-b ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
                                                                                        <th
                                                                                            scope="col"
                                                                                            class="ndb:w-2/5 ndb:pb-2 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                                                        >
                                                                                            Record
                                                                                        </th>
                                                                                        <th
                                                                                            scope="col"
                                                                                            class="ndb:pb-2 ndb:text-right ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                                                        >
                                                                                            Loads
                                                                                        </th>
                                                                                        <th
                                                                                            scope="col"
                                                                                            class="ndb:pb-2 ndb:text-right ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                                                        >
                                                                                            First seen
                                                                                        </th>
                                                                                        <th
                                                                                            scope="col"
                                                                                            class="ndb:pb-2 ndb:text-right ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                                                        >
                                                                                            Last seen
                                                                                        </th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    @foreach ($group['records'] as $record)
                                                                                        <tr
                                                                                            data-ndb-model-record
                                                                                            data-loads="{{ $record['loads'] }}"
                                                                                            class="ndb:border-b ndb:border-zinc-200/70 ndb:last:border-b-0 ndb:dark:border-zinc-800/80 {{ $record['loads'] > 1 ? 'ndb:bg-amber-50/55 ndb:dark:bg-amber-950/20' : '' }}"
                                                                                        >
                                                                                            <th
                                                                                                scope="row"
                                                                                                class="ndb:py-2.5 ndb:font-mono ndb:text-[10px] ndb:font-semibold"
                                                                                            >
                                                                                                #{{ $record['key'] }}
                                                                                            </th>
                                                                                            <td class="ndb:py-2.5 ndb:text-right ndb:text-[10px] ndb:font-bold ndb:tabular-nums {{ $record['loads'] > 1 ? 'ndb:text-amber-700 ndb:dark:text-amber-300' : '' }}">
                                                                                                {{ $record['loads'] }}
                                                                                            </td>
                                                                                            <td
                                                                                                data-ndb-model-first-seen
                                                                                                class="ndb:py-2.5 ndb:text-right ndb:text-[10px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                                                                            >
                                                                                                {{ $record['first_seen_ms'] !== null ? rtrim(rtrim(number_format($record['first_seen_ms'], 1, '.', ''), '0'), '.').' ms' : '—' }}
                                                                                            </td>
                                                                                            <td
                                                                                                data-ndb-model-last-seen
                                                                                                class="ndb:py-2.5 ndb:text-right ndb:text-[10px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                                                                            >
                                                                                                {{ $record['last_seen_ms'] !== null ? rtrim(rtrim(number_format($record['last_seen_ms'], 1, '.', ''), '0'), '.').' ms' : '—' }}
                                                                                            </td>
                                                                                        </tr>
                                                                                    @endforeach
                                                                                </tbody>
                                                                            </table>
                                                                        </div>
                                                                    @elseif ($group['load_count'] > 0)
                                                                        <p class="ndb:mt-4 ndb:rounded-lg ndb:bg-zinc-50 ndb:px-3 ndb:py-2.5 ndb:text-[10px] ndb:text-zinc-500 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-400">
                                                                            Record identifiers were not available for
                                                                            these retrievals.
                                                                        </p>
                                                                    @endif

                                                                    @if ($group['unidentified_load_count'] > 0 && $group['records'] !== [])
                                                                        <p class="ndb:mt-3 ndb:text-[10px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                                                            {{ $group['unidentified_load_count'] }} additional {{ $group['unidentified_load_count'] === 1 ? 'retrieval had' : 'retrievals had' }} no
                                                                            record identifier, so {{ $group['unidentified_load_count'] === 1 ? 'it is' : 'they are' }} not
                                                                            counted as repeated.
                                                                        </p>
                                                                    @endif

                                                                    <details
                                                                        data-ndb-model-raw
                                                                        class="ndb:group/raw ndb:mt-4 ndb:overflow-hidden ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"
                                                                    >
                                                                        <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-2 ndb:px-3 ndb:py-2.5 ndb:text-[10px] ndb:font-bold ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500">
                                                                            <span class="ndb:min-w-0 ndb:flex-1">View {{ count($group['items']) }} raw {{ count($group['items']) === 1 ? 'event' : 'events' }}</span>
                                                                            <x-newdebugbar::icon
                                                                                name="chevron-down"
                                                                                class="ndb:size-3 ndb:text-zinc-400 ndb:transition ndb:group-open/raw:rotate-180"
                                                                            />
                                                                        </summary>
                                                                        <pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="json">{{ json_encode($group['items'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                                                    </details>
                                                                </div>
                                                            </details>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @else
                                                <x-newdebugbar::empty-state label="No model loads or changes were captured." />
                                            @endif

                                            @if (($section['payload']['boot_items'] ?? []) !== [])
                                                <details
                                                    data-ndb-model-boot
                                                    class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"
                                                >
                                                    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-4 ndb:py-3 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500">
                                                        <span class="ndb:min-w-0 ndb:flex-1">
                                                            <span class="ndb:block ndb:text-xs ndb:font-bold">Model boot lifecycle</span>
                                                            <span
                                                                class="ndb:mt-0.5 ndb:block ndb:text-[10px] ndb:text-zinc-400"
                                                                >{{ $section['summary']['boot_event_count'] }} events
                                                                across {{ $section['summary']['boot_model_classes'] }} {{ $section['summary']['boot_model_classes'] === 1 ? 'class' : 'classes' }}</span>
                                                        </span>
                                                        <x-newdebugbar::icon
                                                            name="chevron-down"
                                                            class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
                                                        />
                                                    </summary>
                                                    <pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="json">{{ json_encode($section['payload']['boot_items'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                                </details>
                                            @endif
                                        </div>
                                    @elseif ($sectionKey === 'cache')
                                        <dl class="ndb:grid ndb:grid-cols-2 ndb:divide-x ndb:divide-y ndb:divide-zinc-200 ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:sm:grid-cols-4 ndb:sm:divide-y-0 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                                            @foreach ([['Hit rate', $section['summary']['hit_rate'].'%'], ['Hits', $section['summary']['hits']], ['Misses', $section['summary']['misses']], ['Writes', $section['summary']['writes']]] as [$label, $value])
                                                <div class="ndb:px-3 ndb:py-2.5">
                                                    <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                        {{ $label }}
                                                    </dt>
                                                    <dd class="ndb:mt-0.5 ndb:text-sm ndb:font-bold ndb:tabular-nums">
                                                        {{ $value }}
                                                    </dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                        @if ($section['payload']['repeated_misses'] !== [])
                                            <div class="ndb:rounded-xl ndb:border ndb:border-amber-200 ndb:bg-amber-50/40 ndb:p-4 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/20">
                                                <h3 class="ndb:text-xs ndb:font-bold">Repeated misses</h3>
                                                <div class="ndb:mt-3 ndb:space-y-2">
                                                    @foreach ($section['payload']['repeated_misses'] as $miss)
                                                        <div class="ndb:flex ndb:items-center ndb:gap-3">
                                                            <span
                                                                class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-[10px]"
                                                                >Protected key #{{ substr($miss['key_hash'], 0, 8) }}</span
                                                            ><span class="ndb:text-xs ndb:font-bold ndb:tabular-nums"
                                                                >{{ $miss['count'] }} misses</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                        <div class="ndb:space-y-2">
                                            @foreach ($section['payload']['items'] as $index => $item)
                                                <details
                                                    wire:key="cache-{{ $index }}"
                                                    class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"
                                                >
                                                    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3 ndb:text-xs ndb:font-semibold">
                                                        <span
                                                            class="ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:text-zinc-400"
                                                            >{{ $item['operation'] }}</span
                                                        ><span
                                                            class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-[10px]"
                                                            >{{ ($item['key_policy'] ?? 'hash') === 'full' ? ($item['key'] ?? 'No key') : (isset($item['key_hash']) ? 'Protected key #'.substr($item['key_hash'], 0, 8) : 'No key') }}</span
                                                        ><span
                                                            class="ndb:text-[9px] ndb:font-semibold ndb:text-zinc-400"
                                                            >Details</span
                                                        ><x-newdebugbar::icon
                                                            name="chevron-down"
                                                            class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
                                                        />
                                                    </summary>
                                                    <pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="json">{{ json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                                </details>
                                            @endforeach
                                        </div>
                                    @elseif ($sectionKey === 'views')
                                        @php($viewGroups = $section['payload']['groups'] ?? [])
                                        <div data-ndb-views class="ndb:space-y-5">
                                            <p class="ndb:max-w-3xl ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                                See which Blade templates rendered and the data each received. Use this
                                                to spot missing variables, unexpected partials, and repeated renders.
                                            </p>

                                            <dl class="ndb:flex ndb:flex-wrap ndb:gap-x-10 ndb:gap-y-3 ndb:border-y ndb:border-zinc-200/90 ndb:py-3 ndb:dark:border-zinc-800">
                                                <div>
                                                    <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                        Unique views
                                                    </dt>
                                                    <dd
                                                        data-ndb-view-summary-value="unique"
                                                        class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums"
                                                    >
                                                        {{ $section['summary']['unique_views'] }}
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                        Total renders
                                                    </dt>
                                                    <dd
                                                        data-ndb-view-summary-value="renders"
                                                        class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums"
                                                    >
                                                        {{ $section['summary']['count'] }}
                                                    </dd>
                                                </div>
                                            </dl>

                                            @if ($viewGroups !== [])
                                                <div>
                                                    <div class="ndb:grid ndb:grid-cols-[minmax(0,1fr)_5rem_1.5rem] ndb:items-end ndb:gap-x-3 ndb:border-b ndb:border-zinc-200/90 ndb:pb-2 ndb:dark:border-zinc-800">
                                                        <span class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">View</span>
                                                        <span class="ndb:text-right ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Renders</span>
                                                        <span class="ndb:sr-only">Details</span>
                                                    </div>

                                                    <div class="ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800">
                                                        @foreach ($viewGroups as $index => $group)
                                                            <details
                                                                data-ndb-view-group
                                                                wire:key="view-group-{{ $index }}"
                                                                class="ndb:group"
                                                            >
                                                                <summary class="ndb:grid ndb:cursor-pointer ndb:list-none ndb:grid-cols-[minmax(0,1fr)_5rem_1.5rem] ndb:items-center ndb:gap-x-3 ndb:py-3 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500">
                                                                    <span class="ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-bold">{{ $group['name'] }}</span>
                                                                    <span
                                                                        data-ndb-view-group-count
                                                                        class="ndb:text-right ndb:text-xs ndb:font-bold ndb:tabular-nums"
                                                                    >{{ $group['count'] }}</span>
                                                                    <x-newdebugbar::icon
                                                                        name="chevron-down"
                                                                        class="ndb:size-3.5 ndb:justify-self-end ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
                                                                    />
                                                                </summary>

                                                                <div class="ndb:divide-y ndb:divide-zinc-200/80 ndb:border-t ndb:border-zinc-200/80 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                                                                    @foreach ($group['items'] as $view)
                                                                        @php($viewData = is_array($view['data'] ?? null) ? $view['data'] : [])
                                                                        <article data-ndb-view-render class="ndb:py-4">
                                                                            <div
                                                                                x-data="{ viewDataOpen: false }"
                                                                                x-id="[
                                                                                    'view-data-trigger',
                                                                                    'view-data-popover',
                                                                                ]"
                                                                                @keydown.escape.stop="
                                                                                    if (viewDataOpen) {
                                                                                        viewDataOpen = false;
                                                                                        $nextTick(() =>
                                                                                            $refs.viewDataButton.focus(),
                                                                                        );
                                                                                    }
                                                                                "
                                                                                class="ndb:relative"
                                                                            >
                                                                                <div
                                                                                    data-ndb-view-render-row
                                                                                    class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3"
                                                                                >
                                                                                    <div
                                                                                        data-ndb-view-render-context
                                                                                        class="ndb:flex ndb:min-w-0 ndb:flex-1 ndb:items-baseline ndb:gap-3"
                                                                                    >
                                                                                        <span
                                                                                            data-ndb-view-render-order
                                                                                            class="ndb:shrink-0 ndb:text-[9px] ndb:font-bold ndb:text-zinc-400"
                                                                                        >Render #{{ $view['render_order'] }}</span>
                                                                                        <code
                                                                                            data-ndb-view-source
                                                                                            class="ndb:min-w-0 ndb:flex-1 ndb:break-all ndb:text-[10px]"
                                                                                        >
                                                                                            {{ $view['source']['file'] ?? 'Template path unavailable' }}
                                                                                            @if (isset($view['source']['line'])) :{{ $view['source']['line'] }}@endif
                                                                                        </code>
                                                                                    </div>
                                                                                    <button
                                                                                        x-ref="viewDataButton"
                                                                                        type="button"
                                                                                        data-ndb-view-data-trigger
                                                                                        :id="$id('view-data-trigger')"
                                                                                        :aria-controls="$id(
                                                                                            'view-data-popover',
                                                                                        )"
                                                                                        :aria-expanded="viewDataOpen"
                                                                                        @click="
                                                                                            viewDataOpen = ! viewDataOpen
                                                                                        "
                                                                                        class="ndb:flex ndb:shrink-0 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:px-2 ndb:py-1.5 ndb:text-[10px] ndb:font-bold ndb:text-indigo-600 ndb:transition ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/60"
                                                                                    >
                                                                                        <span class="ndb:flex ndb:flex-col ndb:items-start ndb:gap-0.5 ndb:leading-none">
                                                                                            <span>View data</span>
                                                                                            <span
                                                                                                data-ndb-view-data-count
                                                                                                class="ndb:text-[9px] ndb:font-semibold ndb:text-zinc-400"
                                                                                            >{{ count($viewData) }} {{ count($viewData) === 1 ? 'variable' : 'variables' }}</span>
                                                                                        </span>
                                                                                        <span
                                                                                            :class="{
                                                                                                'ndb:rotate-180':
                                                                                                    viewDataOpen,
                                                                                            }"
                                                                                            class="ndb:transition-transform ndb:duration-150 ndb:motion-reduce:transition-none"
                                                                                        >
                                                                                            <x-newdebugbar::icon
                                                                                                name="chevron-down"
                                                                                                class="ndb:size-3.5 ndb:text-zinc-400"
                                                                                            />
                                                                                        </span>
                                                                                    </button>
                                                                                </div>

                                                                                <div
                                                                                    x-cloak
                                                                                    x-show.important="viewDataOpen"
                                                                                    x-transition:enter="ndb:transition ndb:duration-150 ndb:ease-out ndb:motion-reduce:transition-none"
                                                                                    x-transition:enter-start="ndb:translate-y-1 ndb:scale-95 ndb:opacity-0"
                                                                                    x-transition:enter-end="ndb:translate-y-0 ndb:scale-100 ndb:opacity-100"
                                                                                    x-transition:leave="ndb:transition ndb:duration-100 ndb:ease-in ndb:motion-reduce:transition-none"
                                                                                    x-transition:leave-start="ndb:translate-y-0 ndb:scale-100 ndb:opacity-100"
                                                                                    x-transition:leave-end="ndb:translate-y-1 ndb:scale-95 ndb:opacity-0"
                                                                                    @click.outside="
                                                                                        viewDataOpen = false
                                                                                    "
                                                                                    data-ndb-view-data-popover
                                                                                    :id="$id('view-data-popover')"
                                                                                    :aria-labelledby="$id(
                                                                                        'view-data-trigger',
                                                                                    )"
                                                                                    role="region"
                                                                                    class="ndb:absolute ndb:top-full ndb:right-0 ndb:z-30 ndb:mt-2 ndb:w-[min(36rem,calc(100vw-3rem))] ndb:origin-top-right ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white ndb:shadow-[0_18px_50px_-16px_rgba(24,24,27,0.45)] ndb:dark:border-zinc-700 ndb:dark:bg-zinc-950 ndb:dark:shadow-[0_18px_50px_-16px_rgba(0,0,0,0.9)]"
                                                                                >
                                                                                    @if ($viewData !== [])
                                                                                        <pre
                                                                                            tabindex="0"
                                                                                            data-ndb-view-data
                                                                                            class="ndb-code ndb-scrollbar ndb:max-h-80 ndb:overflow-auto ndb:rounded-none ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500"
                                                                                        ><code data-ndb-language="json">{{ json_encode($viewData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                                                                    @else
                                                                                        <p class="ndb:px-4 ndb:py-3 ndb:text-[10px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                                                                            No data was passed directly
                                                                                            to this view.
                                                                                        </p>
                                                                                    @endif
                                                                                </div>
                                                                            </div>

                                                                            @if (($view['composers'] ?? []) !== [])
                                                                                <div class="ndb:mt-4">
                                                                                    <h3 class="ndb:text-[10px] ndb:font-bold">
                                                                                        View composers
                                                                                    </h3>
                                                                                    <ul class="ndb:mt-2 ndb:list-none ndb:space-y-2">
                                                                                        @foreach ($view['composers'] as $composer)
                                                                                            <li class="ndb:min-w-0">
                                                                                                <code class="ndb:block ndb:break-all ndb:text-[10px] ndb:font-semibold">{{ $composer['name'] }}</code>
                                                                                                @if (is_string($composer['source']['file'] ?? null))
                                                                                                    <code class="ndb:mt-0.5 ndb:block ndb:break-all ndb:text-[9px] ndb:text-zinc-400">{{ $composer['source']['file'] }}:{{ $composer['source']['line'] ?? 1 }}</code>
                                                                                                @endif
                                                                                            </li>
                                                                                        @endforeach
                                                                                    </ul>
                                                                                </div>
                                                                            @endif
                                                                        </article>
                                                                    @endforeach
                                                                </div>
                                                            </details>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @else
                                                <x-newdebugbar::empty-state label="No views were captured." />
                                            @endif
                                        </div>
                                    @elseif ($sectionKey === 'events')
                                        @php($eventItems = $section['payload']['items'] ?? [])
                                        @php($eventSourceCounts = array_replace(['application' => 0, 'framework' => 0], array_count_values(array_column($eventItems, 'source'))))
                                        @php($eventSourceCounts['all'] = count($eventItems))
                                        <div class="ndb:flex ndb:flex-col ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:pb-3 ndb:sm:flex-row ndb:sm:items-end ndb:dark:border-zinc-800">
                                            <div class="ndb:flex-1">
                                                <p class="ndb:mb-1.5 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                    Source
                                                </p>
                                                <div
                                                    class="ndb:flex ndb:gap-1 ndb:overflow-x-auto"
                                                    role="group"
                                                    aria-label="Filter events by source"
                                                >
                                                    @foreach (['application' => 'Application', 'all' => 'All', 'framework' => 'Framework'] as $source => $label)
                                                        <x-newdebugbar::filter-tab
                                                            data-ndb-event-source="{{ $source }}"
                                                            @click="setEventSource({{ \Illuminate\Support\Js::from($source) }})"
                                                            ::aria-pressed="eventSource === {{ \Illuminate\Support\Js::from($source) }}"
                                                        >
                                                            <span>{{ $label }}</span>
                                                            <span
                                                                data-ndb-event-source-count="{{ $source }}"
                                                                class="ndb:text-[10px] ndb:font-bold ndb:tabular-nums ndb:opacity-65"
                                                            >{{ $eventSourceCounts[$source] ?? 0 }}</span>
                                                        </x-newdebugbar::filter-tab>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <label class="ndb:sm:w-72"
                                                ><span
                                                    class="ndb:mb-1.5 ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                    >Search</span
                                                ><input
                                                    data-ndb-event-search
                                                    x-model="eventSearch"
                                                    @input.debounce.100ms="applyEventFilters()"
                                                    type="search"
                                                    placeholder="Event name"
                                                    class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:outline-none ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                                            /></label>
                                        </div>
                                        <p class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">
                                            <span data-ndb-event-visible-count x-text="visibleEventCount"></span> events
                                            <span x-show.important="eventSource === 'application'">from application code</span>
                                        </p>
                                        <div
                                            x-ref="eventList"
                                            x-init="$nextTick(() => applyEventFilters())"
                                            class="ndb:space-y-2"
                                        >
                                            @foreach ($section['payload']['items'] as $index => $item)
                                                <details
                                                    data-ndb-event-item
                                                    data-source="{{ $item['source'] }}"
                                                    data-search="{{ mb_strtolower($item['name']) }}"
                                                    wire:key="event-{{ $index }}"
                                                    class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"
                                                >
                                                    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3">
                                                        <span class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-semibold">{{ $item['name'] }}</span>
                                                        @if ($item['broadcast'] ?? false)
                                                            <span class="ndb:text-[9px] ndb:font-bold ndb:text-indigo-600 ndb:dark:text-indigo-300">Broadcast</span>
                                                        @endif
                                                        <span
                                                            class="ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:text-zinc-400"
                                                            >{{ $item['source'] }}</span
                                                        ><x-newdebugbar::icon
                                                            name="chevron-down"
                                                            class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
                                                        />
                                                    </summary>
                                                    <div class="ndb:space-y-2 ndb:border-t ndb:border-zinc-200 ndb:p-3 ndb:dark:border-zinc-800">
                                                        @forelse ($item['listeners'] ?? [] as $listener)
                                                            <div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3">
                                                                <code class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-[10px]">{{ $listener['name'] }}</code>
                                                            </div>
                                                        @empty
                                                            <p class="ndb:text-[10px] ndb:text-zinc-400">
                                                                No application listener source was exposed.
                                                            </p>
                                                        @endforelse
                                                    </div>
                                                </details>
                                            @endforeach
                                        </div>
                                        <div x-show.important="visibleEventCount === 0">
                                            <x-newdebugbar::empty-state label="No events match these filters." />
                                        </div>
                                    @elseif ($sectionKey === 'logs')
                                        @php($logLevels = array_values(array_unique(array_column($section['payload']['items'], 'level'))))
                                        <div class="ndb:flex ndb:flex-col ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:pb-3 ndb:sm:flex-row ndb:sm:items-end ndb:dark:border-zinc-800">
                                            <div class="ndb:min-w-0 ndb:flex-1">
                                                <p class="ndb:mb-1.5 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                    Level
                                                </p>
                                                <div
                                                    class="ndb:flex ndb:gap-1 ndb:overflow-x-auto"
                                                    role="group"
                                                    aria-label="Filter logs by level"
                                                >
                                                    <x-newdebugbar::filter-tab
                                                        data-ndb-log-level="all"
                                                        @click="setLogLevel('all')"
                                                        ::aria-pressed="logLevel === 'all'"
                                                    >
                                                        All
                                                    </x-newdebugbar::filter-tab>
                                                    @foreach ($logLevels as $level)
                                                        <x-newdebugbar::filter-tab
                                                            data-ndb-log-level="{{ $level }}"
                                                            @click="setLogLevel({{ \Illuminate\Support\Js::from($level) }})"
                                                            ::aria-pressed="logLevel === {{ \Illuminate\Support\Js::from($level) }}"
                                                        >
                                                            {{ strtoupper($level) }}
                                                        </x-newdebugbar::filter-tab>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <label class="ndb:sm:w-72"
                                                ><span
                                                    class="ndb:mb-1.5 ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                    >Search</span
                                                ><input
                                                    data-ndb-log-search
                                                    x-model="logSearch"
                                                    @input.debounce.100ms="applyLogFilters()"
                                                    type="search"
                                                    placeholder="Log message"
                                                    class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:outline-none ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                                            /></label>
                                        </div>
                                        <p class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">
                                            <span x-text="visibleLogCount"></span> logs
                                        </p>
                                        <div x-ref="logList" class="ndb:space-y-2">
                                            @foreach ($section['payload']['items'] as $index => $item)
                                                @php($logCallsite = $item['callsite'] ?? null)
                                                <details
                                                    data-ndb-log-item
                                                    data-level="{{ $item['level'] }}"
                                                    data-search="{{ mb_strtolower($item['message']) }}"
                                                    wire:key="log-{{ $index }}"
                                                    class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"
                                                >
                                                    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3">
                                                        <span
                                                            class="ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:text-zinc-400"
                                                            >{{ $item['level'] }}</span
                                                        ><span class="ndb:min-w-0 ndb:flex-1"
                                                            ><span
                                                                class="ndb:block ndb:truncate ndb:text-xs ndb:font-semibold"
                                                                >{{ $item['message'] }}</span>
                                                            @if ($logCallsite)
                                                                <span class="ndb:mt-0.5 ndb:block ndb:truncate ndb:text-[10px] ndb:text-zinc-400">{{ $logCallsite['file'] }}:{{ $logCallsite['line'] }}</span>
                                                            @endif</span
                                                        ><span
                                                            class="ndb:text-[10px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-400"
                                                            >{{ $item['at_ms'] }} ms</span
                                                        ><x-newdebugbar::icon
                                                            name="chevron-down"
                                                            class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
                                                        />
                                                    </summary>
                                                    @if ($logCallsite)
                                                        <div class="ndb:flex ndb:justify-end ndb:gap-3 ndb:border-t ndb:border-zinc-200 ndb:px-3 ndb:py-2 ndb:dark:border-zinc-800">
                                                            <button
                                                                type="button"
                                                                data-ndb-copy-log-callsite="{{ $index }}"
                                                                @click="copyText(@js($logCallsite['file'].':'.$logCallsite['line']))"
                                                                class="ndb:text-[10px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                                                            >
                                                                Copy file and line
                                                            </button>
                                                        </div>
                                                    @endif
                                                    <pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="json">{{ json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                                </details>
                                            @endforeach
                                        </div>
                                        <div x-show.important="visibleLogCount === 0">
                                            <x-newdebugbar::empty-state label="No logs match these filters." />
                                        </div>
                                    @elseif ($sectionKey === 'exceptions')
                                        @forelse ($section['payload']['items'] as $index => $exception)
                                            <article
                                                wire:key="exception-{{ $index }}"
                                                class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-red-200 ndb:dark:border-red-950"
                                            >
                                                <div class="ndb:flex ndb:items-start ndb:gap-3 ndb:bg-red-50 ndb:p-4 ndb:dark:bg-red-950/50">
                                                    <span
                                                        class="ndb:grid ndb:size-8 ndb:shrink-0 ndb:place-items-center ndb:rounded-lg ndb:bg-red-100 ndb:text-red-600 ndb:dark:bg-red-950 ndb:dark:text-red-300"
                                                        ><x-newdebugbar::icon name="warning" class="ndb:size-4"
                                                    /></span>
                                                    <div class="ndb:min-w-0 ndb:flex-1">
                                                        <p class="ndb:truncate ndb:text-xs ndb:font-bold ndb:text-red-700 ndb:dark:text-red-300">
                                                            {{ $exception['class'] }}
                                                        </p>
                                                        <p class="ndb:mt-1 ndb:text-sm ndb:font-semibold">
                                                            {{ $exception['message'] ?: 'No message' }}
                                                        </p>
                                                        <div class="ndb:mt-1 ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3">
                                                            <button
                                                                type="button"
                                                                data-ndb-copy-exception-callsite="{{ $index }}"
                                                                @click="copyText(@js($exception['file'].':'.$exception['line']))"
                                                                class="ndb:min-w-0 ndb:truncate ndb:text-left ndb:text-[10px] ndb:text-zinc-500 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
                                                            >
                                                                {{ $exception['file'] }}:{{ $exception['line'] }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                @if ($exception['source'] ?? null)
                                                    @php($sourceText = implode("\n", array_map(fn (array $line): string => sprintf('%4d%s %s', $line['number'], $line['focus'] ? '>' : ' ', $line['code']), $exception['source']['lines'])))
                                                    <pre class="ndb-code ndb-scrollbar ndb:max-h-72 ndb:rounded-none ndb:border-b ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="php">{{ $sourceText }}</code></pre>
                                                @endif
                                                <div class="ndb:p-4">
                                                    <h3 class="ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                        Application frames
                                                    </h3>
                                                    <ol class="ndb:mt-3 ndb:list-none ndb:space-y-2">
                                                        @forelse ($exception['frames']['application'] ?? [] as $frame)
                                                            <li class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:text-xs">
                                                                <code class="ndb:min-w-0 ndb:flex-1 ndb:truncate"
                                                                    >{{ $frame['file'] }}:{{ $frame['line'] }}</code
                                                                ><span
                                                                    class="ndb:max-w-[35%] ndb:truncate ndb:text-zinc-400"
                                                                    >{{ $frame['function'] }}</span>
                                                            </li>
                                                        @empty
                                                            <li class="ndb:text-xs ndb:text-zinc-400">
                                                                No application frames were captured.
                                                            </li>
                                                        @endforelse
                                                    </ol>
                                                    <details class="ndb:group ndb:mt-4 ndb:border-t ndb:border-zinc-200 ndb:pt-3 ndb:dark:border-zinc-800">
                                                        <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-2 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                            <span class="ndb:flex-1"
                                                                >Vendor frames ({{ count($exception['frames']['vendor'] ?? []) }})</span
                                                            ><x-newdebugbar::icon
                                                                name="chevron-down"
                                                                class="ndb:size-3.5 ndb:transition ndb:group-open:rotate-180"
                                                            />
                                                        </summary>
                                                        <ol class="ndb:mt-3 ndb:list-none ndb:space-y-2">
                                                            @foreach ($exception['frames']['vendor'] ?? [] as $frame)
                                                                <li class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:text-xs">
                                                                    <code class="ndb:min-w-0 ndb:flex-1 ndb:truncate"
                                                                        >{{ $frame['file'] }}:{{ $frame['line'] }}</code
                                                                    ><span
                                                                        class="ndb:max-w-[35%] ndb:truncate ndb:text-zinc-400"
                                                                        >{{ $frame['function'] }}</span>
                                                                </li>
                                                            @endforeach
                                                        </ol>
                                                    </details>
                                                </div>
                                            </article>
                                        @empty
                                            <x-newdebugbar::empty-state label="No exceptions were reported." success />
                                        @endforelse
                                    @elseif ($sectionKey !== 'overview')
                                        @forelse ($section['payload']['items'] as $index => $item)
                                            <details
                                                wire:key="{{ $sectionKey }}-{{ $index }}"
                                                class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"
                                            >
                                                <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3 ndb:text-xs ndb:font-semibold ndb:transition ndb:hover:bg-zinc-50 ndb:dark:hover:bg-zinc-900">
                                                    <span
                                                        class="ndb:text-[10px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-400"
                                                        >#{{ $index + 1 }}</span
                                                    ><span
                                                        class="ndb:min-w-0 ndb:flex-1 ndb:truncate"
                                                        >{{ $item['model'] ?? $item['name'] ?? $item['event'] ?? $item['level'] ?? $item['operation'] ?? $section['label'] }}</span
                                                    ><x-newdebugbar::icon
                                                        name="chevron-down"
                                                        class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
                                                    />
                                                </summary>
                                                <pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="json">{{ json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                            </details>
                                        @empty
                                            <x-newdebugbar::empty-state :label="'No '.strtolower($section['label']).' were captured.'" />
                                        @endforelse
                                    @endif
                                </section>
                            @endforeach

                            <section
                                data-ndb-section-panel="history"
                                hidden
                                wire:key="section-history"
                                class="ndb:space-y-4"
                            >
                                <div>
                                    <p class="ndb:text-sm ndb:font-semibold">
                                        History keeps recent requests so you can inspect background work and earlier
                                        pages.
                                    </p>
                                    <p class="ndb:mt-1 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                        Compare appears when two requests use the same path.
                                    </p>
                                    @if (count($history) === 1)
                                        <p class="ndb:mt-1 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                            New requests will appear here as you use the app.
                                        </p>
                                    @endif
                                </div>
                                @if ($discoveredProfileId !== null)
                                    <div class="ndb:rounded-lg ndb:border ndb:border-indigo-200 ndb:bg-indigo-50/60 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-semibold ndb:text-indigo-800 ndb:dark:border-indigo-900 ndb:dark:bg-indigo-950/30 ndb:dark:text-indigo-200">
                                        A background request was added to History.
                                    </div>
                                @endif
                                @if (! ($summary['is_current_profile'] ?? true))
                                    <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:border-indigo-200 ndb:bg-indigo-50/50 ndb:px-3.5 ndb:py-3 ndb:dark:border-indigo-950 ndb:dark:bg-indigo-950/20">
                                        <div class="ndb:min-w-0 ndb:flex-1">
                                            <p class="ndb:text-xs ndb:font-bold">Inspecting an earlier request</p>
                                            <p class="ndb:mt-0.5 ndb:text-[10px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                                The compact bar and sections show this selected request.
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            data-ndb-return-current
                                            wire:click="returnToCurrent"
                                            wire:loading.attr="disabled"
                                            wire:loading.attr="aria-busy"
                                            class="ndb:rounded-lg ndb:bg-indigo-600 ndb:px-3 ndb:py-2 ndb:text-[10px] ndb:font-bold ndb:text-white ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:opacity-50"
                                        >
                                            <span wire:loading.remove wire:target="returnToCurrent"
                                                >Back to current request</span
                                            ><span wire:loading wire:target="returnToCurrent">Returning…</span>
                                        </button>
                                    </div>
                                @endif
                                <details
                                    data-ndb-history-filters
                                    class="ndb:group ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/40 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/25"
                                >
                                    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-2 ndb:px-3.5 ndb:py-3 ndb:text-xs ndb:font-bold ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500">
                                        <span class="ndb:flex-1">Filters</span
                                        ><span
                                            x-show.important="
                                                historyPath !== '' || historyMethod !== '' || historyStatus !== ''
                                            "
                                            class="ndb:text-[10px] ndb:text-indigo-600 ndb:dark:text-indigo-300"
                                            >Active</span
                                        ><x-newdebugbar::icon
                                            name="chevron-down"
                                            class="ndb-details-chevron ndb:size-3.5 ndb:text-zinc-400 ndb:transition"
                                        />
                                    </summary>
                                    <div class="ndb:grid ndb:gap-3 ndb:border-t ndb:border-zinc-200/80 ndb:p-3.5 ndb:md:grid-cols-3 ndb:dark:border-zinc-800">
                                        <label
                                            ><span
                                                class="ndb:mb-1.5 ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                >Path</span
                                            ><input
                                                data-ndb-history-path
                                                x-model="historyPath"
                                                @input.debounce.100ms="applyHistoryFilters()"
                                                type="search"
                                                placeholder="Filter by path"
                                                class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:outline-none ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                                        /></label>
                                        <label
                                            ><span
                                                class="ndb:mb-1.5 ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                >Method</span
                                            ><input
                                                data-ndb-history-method
                                                x-model="historyMethod"
                                                @input.debounce.100ms="applyHistoryFilters()"
                                                type="search"
                                                placeholder="GET"
                                                class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:uppercase ndb:outline-none ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                                        /></label>
                                        <label
                                            ><span
                                                class="ndb:mb-1.5 ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                >Status</span
                                            ><input
                                                data-ndb-history-status
                                                x-model="historyStatus"
                                                @input.debounce.100ms="applyHistoryFilters()"
                                                inputmode="numeric"
                                                placeholder="200"
                                                class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:outline-none ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                                        /></label>
                                    </div>
                                </details>
                                <div class="ndb:flex ndb:items-center ndb:gap-1 ndb:overflow-x-auto">
                                    @foreach (['all' => 'All', 'warning' => 'Warnings', 'clean' => 'Clean'] as $filter => $label)
                                        <x-newdebugbar::filter-tab
                                            data-ndb-history-warning="{{ $filter }}"
                                            @click="setHistoryWarning({{ \Illuminate\Support\Js::from($filter) }})"
                                            ::aria-pressed="historyWarning === {{ \Illuminate\Support\Js::from($filter) }}"
                                        >
                                            {{ $label }}
                                        </x-newdebugbar::filter-tab>
                                    @endforeach
                                    <button
                                        type="button"
                                        data-ndb-history-runtime
                                        @click="toggleHistoryRuntime()"
                                        :aria-pressed="historyShowRuntime"
                                        class="ndb:ml-1 ndb:rounded-md ndb:px-2 ndb:py-1.5 ndb:text-[10px] ndb:font-bold ndb:text-zinc-500 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-zinc-400"
                                        x-text="historyShowRuntime ? 'Hide CLI' : 'Show CLI'"
                                    ></button>
                                    <span class="ndb:ml-auto ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400"><span x-text="visibleHistoryCount"></span>
                                        <span x-text="visibleHistoryCount === 1 ? 'request' : 'requests'"></span
                                    ></span>
                                </div>

                                @if ($comparison !== [])
                                    <div
                                        data-ndb-comparison
                                        class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-indigo-200/90 ndb:bg-indigo-50/30 ndb:dark:border-indigo-950 ndb:dark:bg-indigo-950/20"
                                    >
                                        <div class="ndb:flex ndb:items-center ndb:gap-3 ndb:border-b ndb:border-indigo-200/70 ndb:px-4 ndb:py-3 ndb:dark:border-indigo-950">
                                            <div class="ndb:min-w-0 ndb:flex-1">
                                                <h3 class="ndb:text-xs ndb:font-bold">Compare requests</h3>
                                                <p class="ndb:mt-0.5 ndb:truncate ndb:text-[10px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                                    {{ $comparison['path'] }}
                                                </p>
                                                <p class="ndb:mt-1 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:gap-y-1 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">
                                                    <span
                                                        >Earlier request: {{ $comparison['baseline']['recorded_time'] ?? 'Unknown time' }}</span
                                                    ><span
                                                        >{{ ($summary['is_current_profile'] ?? true) ? 'Current request' : 'Selected request' }}: {{ $comparison['current']['recorded_time'] ?? 'Unknown time' }}</span>
                                                </p>
                                            </div>
                                            <button
                                                type="button"
                                                wire:click="clearComparison"
                                                class="ndb:text-[10px] ndb:font-bold ndb:text-indigo-700 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                                            >
                                                Clear
                                            </button>
                                        </div>
                                        <div class="ndb:grid ndb:grid-cols-2 ndb:divide-x ndb:divide-y ndb:divide-indigo-200/70 ndb:lg:grid-cols-4 ndb:dark:divide-indigo-950">
                                            @foreach ($comparison['metrics'] as $metric)
                                                <div
                                                    data-ndb-comparison-metric="{{ $metric['key'] }}"
                                                    class="ndb:px-3 ndb:py-2.5"
                                                >
                                                    <p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                                        {{ $metric['label'] }}
                                                    </p>
                                                    <p class="ndb:mt-1 ndb:flex ndb:items-baseline ndb:gap-1.5 ndb:text-xs ndb:tabular-nums">
                                                        <span
                                                            data-ndb-comparison-baseline
                                                            class="ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                                            >{{ $metric['baseline'] }}{{ $metric['unit'] !== '' ? ' '.$metric['unit'] : '' }}</span
                                                        ><span aria-hidden="true" class="ndb:text-zinc-400">→</span
                                                        ><strong data-ndb-comparison-current
                                                            >{{ $metric['current'] }}{{ $metric['unit'] !== '' ? ' '.$metric['unit'] : '' }}</strong>
                                                    </p>
                                                    <p
                                                        data-ndb-comparison-change="{{ $metric['tone'] }}"
                                                        class="ndb:mt-0.5 ndb:text-[10px] ndb:font-semibold ndb:tabular-nums {{ $metric['tone'] === 'regressed' ? 'ndb:text-amber-700 ndb:dark:text-amber-300' : ($metric['tone'] === 'improved' ? 'ndb:text-emerald-700 ndb:dark:text-emerald-300' : 'ndb:text-zinc-400') }}"
                                                    >
                                                        {{ $metric['delta'] === 0.0 ? 'No change' : (($metric['delta'] > 0 ? '+' : '').$metric['delta'].($metric['unit'] !== '' ? ' '.$metric['unit'] : '')) }}
                                                    </p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div
                                    x-ref="historyList"
                                    x-init="$nextTick(() => applyHistoryFilters())"
                                    class="ndb:space-y-2"
                                >
                                    @foreach ($history as $entry)
                                        <article
                                            data-ndb-history-profile="{{ $entry['id'] }}"
                                            data-path="{{ mb_strtolower($entry['path']) }}"
                                            data-method="{{ $entry['method'] }}"
                                            data-status="{{ $entry['status'] }}"
                                            data-warning="{{ $entry['warning'] ? 'true' : 'false' }}"
                                            data-runtime="{{ ($entry['profile_type'] ?? 'http') === 'http' ? 'false' : 'true' }}"
                                            @if ($entry['is_selected']) aria-current="true" @endif
                                            class="ndb:flex ndb:flex-col ndb:gap-3 ndb:rounded-xl ndb:border ndb:px-3.5 ndb:py-3 ndb:sm:flex-row ndb:sm:items-center {{ $entry['is_selected'] ? 'ndb:border-indigo-300 ndb:bg-indigo-50/55 ndb:ring-1 ndb:ring-indigo-200 ndb:dark:border-indigo-900 ndb:dark:bg-indigo-950/25 ndb:dark:ring-indigo-950' : ($entry['is_current'] ? 'ndb:border-sky-200 ndb:bg-sky-50/35 ndb:dark:border-sky-950 ndb:dark:bg-sky-950/15' : 'ndb:border-zinc-200/90 ndb:bg-white/50 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30') }}"
                                        >
                                            <div class="ndb:min-w-0 ndb:flex-1">
                                                <div class="ndb:flex ndb:items-center ndb:gap-2">
                                                    <span class="ndb:rounded-md ndb:bg-indigo-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:font-bold ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300">{{ $entry['method'] }}</span>
                                                    <p class="ndb:truncate ndb:text-xs ndb:font-bold">
                                                        {{ $entry['path'] }}
                                                    </p>
                                                    @if ($entry['is_current'])
                                                        <span class="ndb:text-[9px] ndb:font-bold ndb:text-sky-700 ndb:dark:text-sky-300">Current</span>
                                                    @elseif ($entry['is_selected'])
                                                        <span class="ndb:text-[9px] ndb:font-bold ndb:text-indigo-700 ndb:dark:text-indigo-300">Selected</span>
                                                    @endif
                                                </div>
                                                @if (is_string($entry['activity'] ?? null))
                                                    <p class="ndb:mt-1 ndb:truncate ndb:text-[11px] ndb:font-medium ndb:text-zinc-600 ndb:dark:text-zinc-300">
                                                        {{ $entry['activity'] }}
                                                    </p>
                                                @endif
                                                <div class="ndb:mt-1.5 ndb:flex ndb:flex-wrap ndb:gap-x-4 ndb:gap-y-1 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">
                                                    <time
                                                        datetime="{{ $entry['recorded_at'] ?? '' }}"
                                                        >{{ $entry['recorded_time'] ?? 'Unknown time' }}</time
                                                    ><span>{{ $entry['status'] }}</span
                                                    ><span
                                                        >{{ str($entry['request_type'])->replace('_', ' ')->title() }}</span
                                                    ><span>{{ $entry['duration_ms'] }} ms</span
                                                    ><span
                                                        >{{ $entry['query_count'] }} {{ str('query')->plural($entry['query_count']) }}</span
                                                    ><span
                                                        >{{ $entry['finding_count'] }} {{ str('finding')->plural($entry['finding_count']) }}</span>
                                                </div>
                                            </div>
                                            @if (! $entry['is_selected'])
                                                <button
                                                    type="button"
                                                    data-ndb-open-profile="{{ $entry['id'] }}"
                                                    wire:click="selectProfile('{{ $entry['id'] }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.attr="aria-busy"
                                                    aria-label="Open {{ $entry['method'] }} {{ $entry['path'] }} request from {{ $entry['recorded_time'] ?? 'unknown time' }}"
                                                    class="ndb:self-start ndb:rounded-lg ndb:bg-zinc-900 ndb:px-3 ndb:py-2 ndb:text-[10px] ndb:font-bold ndb:text-white ndb:transition ndb:hover:bg-indigo-700 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:opacity-50 ndb:sm:self-center ndb:dark:bg-zinc-100 ndb:dark:text-zinc-900 ndb:dark:hover:bg-indigo-300"
                                                >
                                                    <span wire:loading.remove wire:target="selectProfile">Open</span
                                                    ><span wire:loading wire:target="selectProfile">Opening…</span>
                                                </button>
                                            @endif
                                            @if ($entry['comparable'])
                                                <button
                                                    type="button"
                                                    data-ndb-compare-profile="{{ $entry['id'] }}"
                                                    wire:click="compareWith('{{ $entry['id'] }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.attr="aria-busy"
                                                    class="ndb:self-start ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:px-3 ndb:py-2 ndb:text-[10px] ndb:font-bold ndb:text-zinc-600 ndb:transition ndb:hover:border-indigo-300 ndb:hover:text-indigo-700 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:opacity-50 ndb:sm:self-center ndb:dark:border-zinc-700 ndb:dark:text-zinc-300 ndb:dark:hover:border-indigo-800 ndb:dark:hover:text-indigo-300"
                                                >
                                                    <span wire:loading.remove wire:target="compareWith">Compare</span
                                                    ><span wire:loading wire:target="compareWith">Comparing…</span>
                                                </button>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                                <div x-show.important="visibleHistoryCount === 0">
                                    <x-newdebugbar::empty-state label="No requests match these filters." />
                                </div>
                            </section>
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

    <div
        x-cloak
        x-show.important="paletteOpen"
        class="ndb:pointer-events-auto ndb:fixed ndb:inset-0 ndb:z-50 ndb:grid ndb:justify-items-center ndb:bg-zinc-950/45 ndb:px-3 ndb:pt-[12vh] ndb:backdrop-blur-sm"
        @click.self="closePalette()"
    >
        <div
            x-show.important="paletteOpen"
            x-transition
            @keydown="keepFocusWithin($event, $el)"
            class="ndb:w-full ndb:max-w-xl ndb:self-start ndb:overflow-hidden ndb:rounded-2xl ndb:border ndb:border-white/70 ndb:bg-white/90 ndb:shadow-2xl ndb:backdrop-blur-2xl ndb:dark:border-zinc-700/80 ndb:dark:bg-zinc-900/90"
            role="dialog"
            aria-modal="true"
            aria-label="Command palette"
        >
            <div class="ndb:flex ndb:items-center ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:px-4 ndb:dark:border-zinc-800">
                <x-newdebugbar::icon name="search" class="ndb:size-5 ndb:text-zinc-400" /><input
                    data-ndb-palette-search
                    x-ref="paletteSearch"
                    x-model="paletteSearch"
                    @input="paletteIndex = 0"
                    @keydown.down.prevent="movePalette(1)"
                    @keydown.up.prevent="movePalette(-1)"
                    @keydown.enter.prevent="runActiveCommand()"
                    type="search"
                    placeholder="Jump to a section or change a setting…"
                    class="ndb:h-14 ndb:min-w-0 ndb:flex-1 ndb:border-0 ndb:bg-transparent ndb:text-sm ndb:font-medium ndb:outline-none ndb:placeholder:text-zinc-400"
                /><kbd
                    class="ndb:rounded-md ndb:border ndb:border-zinc-200 ndb:bg-zinc-50 ndb:px-1.5 ndb:py-1 ndb:text-[9px] ndb:font-bold ndb:text-zinc-400 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-800"
                    >ESC</kbd>
            </div>
            <div class="ndb-scrollbar ndb:max-h-[min(420px,60vh)] ndb:overflow-y-auto ndb:p-2">
                <template x-for="command in allCommands" :key="command.id">
                    <button
                        x-show.important="commandIndex(command.id) !== -1"
                        type="button"
                        :data-ndb-command="command.id"
                        @mouseenter="paletteIndex = commandIndex(command.id)"
                        @click="runCommand(command.id)"
                        class="ndb:flex ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:px-3 ndb:py-2.5 ndb:text-left ndb:transition"
                        :class="filteredCommands[paletteIndex]?.id === command.id
                            ? 'ndb:bg-blue-100/60 ndb:text-blue-700 ndb:dark:bg-indigo-950/60 ndb:dark:text-indigo-200'
                            : 'ndb:text-zinc-700 ndb:dark:text-zinc-300'"
                    >
                        <span class="ndb:flex-1 ndb:text-sm ndb:font-semibold" x-text="command.label"></span
                        ><span
                            class="ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                            x-text="command.hint"
                        ></span>
                    </button>
                </template>
                <button
                    x-show.important="commandIndex('collectors:show') !== -1"
                    type="button"
                    data-ndb-command="collectors:show"
                    @mouseenter="paletteIndex = commandIndex('collectors:show')"
                    @click="runCommand('collectors:show')"
                    class="ndb:flex ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:px-3 ndb:py-2.5 ndb:text-left ndb:transition"
                    :class="filteredCommands[paletteIndex]?.id === 'collectors:show'
                        ? 'ndb:bg-blue-100/60 ndb:text-blue-700 ndb:dark:bg-indigo-950/60 ndb:dark:text-indigo-200'
                        : 'ndb:text-zinc-700 ndb:dark:text-zinc-300'"
                >
                    <span class="ndb:flex-1 ndb:text-sm ndb:font-semibold">Show other collectors</span
                    ><span
                        class="ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                        x-text="`${hiddenCommandCount} hidden`"
                    ></span>
                </button>
                <p
                    x-show.important="filteredCommands.length === 0"
                    class="ndb:px-3 ndb:py-8 ndb:text-center ndb:text-sm ndb:text-zinc-500"
                >
                    No matching commands.
                </p>
            </div>
            <div class="ndb:flex ndb:items-center ndb:gap-3 ndb:border-t ndb:border-zinc-200 ndb:bg-zinc-50 ndb:px-4 ndb:py-2 ndb:text-[10px] ndb:font-medium ndb:text-zinc-400 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950">
                <span>↑↓ Navigate</span><span>↵ Select</span><span class="ndb:ml-auto">⌘/Ctrl ⇧ P</span>
            </div>
        </div>
    </div>
</div>
