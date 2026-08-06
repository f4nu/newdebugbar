@php
    $profile = $detailsLoaded ? $this->profile : [];
@endphp

<div
    id="new-debug-bar"
    x-data="newDebugBar(@js($summary))"
    :data-theme="resolvedTheme"
    @keydown.window="handleShortcut($event)"
    @new-debug-bar-content-updated.window="$nextTick(() => { syncSectionPanels(); applyHistoryFilters(); applyTimelineFilters(); applyEventFilters(); applyLogFilters(); window.newDebugBarHighlight?.($root) })"
    @new-debug-bar-profile-switched.window="switchProfile($event.detail.summary)"
    @new-debug-bar-profile-discovered.window="noticeProfile($event.detail.profileId)"
    class="ndb:pointer-events-none ndb:fixed ndb:inset-0 ndb:z-[2147483000] ndb:text-zinc-900 ndb:dark:text-zinc-100"
>
    <div
        x-cloak
        x-show.important="! inspectorOpen"
        x-transition.opacity.duration.150ms
        role="toolbar"
        aria-label="Debug toolbar"
        class="ndb:pointer-events-auto ndb:fixed ndb:bottom-3 ndb:left-1/2 ndb:flex ndb:w-[calc(100vw-24px)] ndb:max-w-[calc(100vw-24px)] ndb:-translate-x-1/2 ndb:items-stretch ndb:gap-1 ndb:rounded-[18px] ndb:border ndb:border-white/70 ndb:bg-white/80 ndb:py-1.5 ndb:pl-1.5 ndb:pr-2.5 ndb:shadow-[0_18px_60px_-18px_rgba(24,24,27,0.4)] ndb:backdrop-blur-xl ndb:backdrop-brightness-110 ndb:backdrop-saturate-125 ndb:sm:w-auto ndb:dark:border-white/10 ndb:dark:bg-zinc-950/90 ndb:dark:shadow-[0_18px_60px_-18px_rgba(0,0,0,0.8)] ndb:dark:backdrop-brightness-75 ndb:dark:backdrop-saturate-100"
    >
        <x-new-debug-bar::toolbar-button section="request" data-ndb-toolbar="request" class="ndb:flex ndb:w-28 ndb:min-w-0 ndb:flex-none ndb:sm:w-auto ndb:sm:max-w-52" aria-label="Open request details">
            <span class="ndb:rounded-md ndb:bg-indigo-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300" x-text="summary.method"></span>
            <span class="ndb:min-w-0">
                <span class="ndb:block ndb:truncate ndb:text-xs ndb:font-semibold" x-text="summary.path"></span>
                <span class="ndb:block ndb:text-[10px] ndb:font-medium ndb:text-zinc-400" x-text="summary.status"></span>
            </span>
        </x-new-debug-bar::toolbar-button>

        <div data-ndb-toolbar-facts class="ndb-toolbar-facts ndb:flex ndb:min-w-0 ndb:flex-1 ndb:items-stretch ndb:gap-1 ndb:overflow-x-auto ndb:overscroll-x-contain ndb:sm:flex-none ndb:sm:overflow-visible">
            <x-new-debug-bar::toolbar-button section="overview" data-ndb-toolbar="environment" class="ndb:flex ndb:min-w-max ndb:shrink-0">
                <span class="ndb:size-2 ndb:shrink-0 ndb:rounded-full" :class="summary.warning ? 'ndb:bg-amber-500' : 'ndb:bg-emerald-500'"></span>
                <span><span class="ndb:hidden ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:sm:block">Environment</span><span class="ndb:block ndb:max-w-24 ndb:truncate ndb:text-[10px] ndb:font-bold ndb:sm:text-xs" x-text="summary.environment"></span></span>
            </x-new-debug-bar::toolbar-button>

            <x-new-debug-bar::toolbar-button section="request" data-ndb-toolbar="duration" class="ndb:flex ndb:min-w-max ndb:shrink-0">
                <x-new-debug-bar::icon name="clock" class="ndb:size-3.5 ndb:shrink-0 ndb:text-indigo-500 ndb:dark:text-indigo-400" />
                <span><span class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Duration</span><span class="ndb:block ndb:whitespace-nowrap ndb:text-xs ndb:font-bold ndb:tabular-nums" x-text="summary.duration_ms + ' ms'"></span></span>
            </x-new-debug-bar::toolbar-button>

            <x-new-debug-bar::toolbar-button section="overview" data-ndb-toolbar="memory" class="ndb:flex ndb:min-w-max ndb:shrink-0">
                <x-new-debug-bar::icon name="memory" class="ndb:size-3.5 ndb:shrink-0 ndb:text-indigo-500 ndb:dark:text-indigo-400" />
                <span><span class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Peak</span><span class="ndb:block ndb:whitespace-nowrap ndb:text-xs ndb:font-bold ndb:tabular-nums" x-text="summary.memory_mb + ' MB'"></span></span>
            </x-new-debug-bar::toolbar-button>

            <x-new-debug-bar::toolbar-button section="queries" data-ndb-toolbar="queries" class="ndb:flex ndb:min-w-max ndb:shrink-0">
                <x-new-debug-bar::icon name="database" class="ndb:size-3.5 ndb:shrink-0 ndb:text-indigo-500 ndb:dark:text-indigo-400" />
                <span><span class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Queries</span><span class="ndb:flex ndb:items-center ndb:gap-1.5 ndb:whitespace-nowrap ndb:text-xs ndb:font-bold ndb:tabular-nums"><span x-text="summary.query_count"></span><span class="ndb:font-medium ndb:text-zinc-400" x-text="summary.query_duration_ms + ' ms'"></span></span></span>
            </x-new-debug-bar::toolbar-button>
        </div>

        <div data-ndb-toolbar-actions class="ndb:ml-auto ndb:flex ndb:shrink-0 ndb:items-center ndb:gap-0.5 ndb:sm:ml-0.5">
            <x-new-debug-bar::icon-button name="search" :dark-surface="true" data-ndb-toolbar="palette" @click="openPalette()" class="ndb:size-9 ndb:rounded-xl" aria-label="Open command palette" title="Command palette (Command or Control + Shift + P)" />
            <x-new-debug-bar::icon-button name="expand" :dark-surface="true" data-ndb-toolbar="expand" @click="openInspector()" class="ndb:size-9 ndb:rounded-xl" aria-label="Expand inspector" title="Expand inspector" />
        </div>
    </div>

    <div x-cloak x-show.important="inspectorOpen" class="ndb:pointer-events-auto ndb:fixed ndb:inset-0" role="presentation">
        <div data-ndb-backdrop x-show.important="inspectorOpen" x-transition.opacity.duration.150ms @click="closeInspector()" class="ndb:absolute ndb:inset-0 ndb:bg-zinc-950/30 ndb:backdrop-blur-[1px] ndb:dark:bg-black/55"></div>

        <aside
            x-show.important="inspectorOpen"
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
            class="ndb:absolute ndb:inset-x-0 ndb:bottom-0 ndb:flex ndb:h-[min(82vh,780px)] ndb:max-h-[calc(100vh-12px)] ndb:flex-col ndb:overflow-hidden ndb:rounded-t-2xl ndb:border-x ndb:border-t ndb:border-white/70 ndb:bg-white/90 ndb:shadow-[0_-24px_80px_-28px_rgba(24,24,27,0.5)] ndb:backdrop-blur-2xl ndb:dark:border-zinc-800/80 ndb:dark:bg-zinc-950/90"
        >
            <header class="ndb:shrink-0 ndb:border-b ndb:border-zinc-200/80 ndb:bg-white ndb:p-1.5 ndb:dark:border-zinc-800/80 ndb:dark:bg-zinc-950">
                <div data-ndb-header-toolbar class="ndb:flex ndb:flex-wrap ndb:items-stretch ndb:gap-1 ndb:sm:flex-nowrap">
                    <x-new-debug-bar::toolbar-button section="request" data-ndb-header-request class="ndb:flex ndb:min-w-0 ndb:flex-1" aria-label="Open request details">
                        <span class="ndb:rounded-md ndb:bg-indigo-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300" x-text="summary.method"></span>
                        <span class="ndb:min-w-0">
                            <span class="ndb:block ndb:truncate ndb:text-xs ndb:font-semibold" x-text="summary.path"></span>
                            <span data-ndb-header-status class="ndb:block ndb:text-[10px] ndb:font-medium ndb:text-zinc-400" x-text="summary.status"></span>
                        </span>
                    </x-new-debug-bar::toolbar-button>

                    <div data-ndb-header-mobile-row class="ndb:order-3 ndb:flex ndb:w-full ndb:min-w-0 ndb:items-stretch ndb:gap-2 ndb:sm:contents">
                        <button
                            type="button"
                            data-ndb-mobile-sections-toggle
                            @click="toggleMobileSections()"
                            :aria-expanded="mobileSectionsOpen"
                            :aria-label="mobileSectionsOpen ? 'Close sections' : 'Open sections'"
                            :title="mobileSectionsOpen ? 'Close sections' : 'Open sections'"
                            aria-controls="new-debug-bar-section-navigation"
                            class="ndb:flex ndb:size-11 ndb:shrink-0 ndb:items-center ndb:justify-center ndb:rounded-xl ndb:text-zinc-500 ndb:transition-colors ndb:hover:text-zinc-950 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:sm:hidden ndb:dark:text-zinc-400 ndb:dark:hover:text-white"
                        >
                            <span x-show.important="! mobileSectionsOpen"><x-new-debug-bar::icon name="sidebar" class="ndb:size-4" /></span>
                            <span x-cloak x-show.important="mobileSectionsOpen"><x-new-debug-bar::icon name="close" class="ndb:size-4" /></span>
                        </button>

                        <div data-ndb-header-facts class="ndb-scrollbar ndb:flex ndb:min-w-0 ndb:flex-1 ndb:gap-2 ndb:overflow-x-auto ndb:overscroll-x-contain ndb:pb-0.5 ndb:sm:order-none ndb:sm:w-auto ndb:sm:flex-none ndb:sm:gap-1 ndb:sm:overflow-visible ndb:sm:pb-0">
                            <x-new-debug-bar::toolbar-button section="overview" data-ndb-header-fact="environment" class="ndb:flex ndb:min-w-max ndb:shrink-0">
                                <span class="ndb:size-2 ndb:shrink-0 ndb:rounded-full" :class="summary.warning ? 'ndb:bg-amber-500' : 'ndb:bg-emerald-500'"></span>
                                <span class="ndb:min-w-0"><span class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Environment</span><span data-ndb-header-environment class="ndb:block ndb:max-w-24 ndb:truncate ndb:text-xs ndb:font-bold" x-text="summary.environment"></span></span>
                            </x-new-debug-bar::toolbar-button>

                            <x-new-debug-bar::toolbar-button section="request" data-ndb-header-fact="duration" class="ndb:flex ndb:min-w-max ndb:shrink-0">
                                <x-new-debug-bar::icon name="clock" class="ndb:size-3.5 ndb:shrink-0 ndb:text-indigo-500 ndb:dark:text-indigo-400" />
                                <span><span class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Duration</span><span class="ndb:block ndb:whitespace-nowrap ndb:text-xs ndb:font-bold ndb:tabular-nums" x-text="summary.duration_ms + ' ms'"></span></span>
                            </x-new-debug-bar::toolbar-button>

                            <x-new-debug-bar::toolbar-button section="overview" data-ndb-header-fact="memory" class="ndb:flex ndb:min-w-max ndb:shrink-0">
                                <x-new-debug-bar::icon name="memory" class="ndb:size-3.5 ndb:shrink-0 ndb:text-indigo-500 ndb:dark:text-indigo-400" />
                                <span><span class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Peak</span><span data-ndb-header-memory class="ndb:block ndb:whitespace-nowrap ndb:text-xs ndb:font-bold ndb:tabular-nums" x-text="summary.memory_mb + ' MB'"></span></span>
                            </x-new-debug-bar::toolbar-button>

                            <x-new-debug-bar::toolbar-button section="queries" data-ndb-header-fact="queries" class="ndb:flex ndb:min-w-max ndb:shrink-0">
                                <x-new-debug-bar::icon name="database" class="ndb:size-3.5 ndb:shrink-0 ndb:text-indigo-500 ndb:dark:text-indigo-400" />
                                <span><span class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Queries</span><span class="ndb:flex ndb:items-center ndb:gap-1.5 ndb:whitespace-nowrap ndb:text-xs ndb:font-bold ndb:tabular-nums"><span data-ndb-header-query-count x-text="summary.query_count"></span><span data-ndb-header-query-duration class="ndb:font-medium ndb:text-zinc-400" x-text="summary.query_duration_ms + ' ms'"></span></span></span>
                            </x-new-debug-bar::toolbar-button>
                        </div>
                    </div>

                    <div class="ndb:ml-auto ndb:flex ndb:items-center ndb:gap-0.5">
                        <x-new-debug-bar::icon-button name="search" data-ndb-inspector-action="palette" @click="openPalette()" class="ndb:size-9 ndb:rounded-xl" aria-label="Open command palette" />
                        <x-new-debug-bar::icon-button data-ndb-inspector-action="theme" @click="toggleTheme()" class="ndb:size-9 ndb:rounded-xl" ::aria-label="resolvedTheme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'" ::title="resolvedTheme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'"><span x-show.important="resolvedTheme !== 'dark'"><x-new-debug-bar::icon name="moon" class="ndb:size-4" /></span><span x-show.important="resolvedTheme === 'dark'"><x-new-debug-bar::icon name="sun" class="ndb:size-4" /></span></x-new-debug-bar::icon-button>
                        <x-new-debug-bar::icon-button name="close" data-ndb-inspector-action="close" x-ref="inspectorClose" @click="closeInspector()" class="ndb:size-9 ndb:rounded-xl" aria-label="Close inspector" />
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
                    class="ndb:absolute ndb:inset-y-0 ndb:right-0 ndb:left-[min(82vw,280px)] ndb:z-20 ndb:bg-zinc-950/25 ndb:backdrop-blur-[1px] ndb:sm:hidden ndb:dark:bg-black/55"
                    aria-hidden="true"
                ></div>

                <nav
                    id="new-debug-bar-section-navigation"
                    x-ref="mobileSectionsNav"
                    aria-label="Debug sections"
                    :data-ndb-mobile-open="mobileSectionsOpen ? 'true' : 'false'"
                    class="ndb-mobile-section-navigation ndb:absolute ndb:inset-y-0 ndb:left-0 ndb:z-30 ndb:flex ndb:w-[82vw] ndb:max-w-[280px] ndb:flex-col ndb:border-r ndb:border-zinc-200/80 ndb:bg-zinc-50/95 ndb:p-3 ndb:shadow-2xl ndb:backdrop-blur-2xl ndb:sm:static ndb:sm:z-auto ndb:sm:w-[210px] ndb:sm:max-w-none ndb:sm:shrink-0 ndb:sm:shadow-none ndb:dark:border-zinc-800/80 ndb:dark:bg-zinc-900/95 ndb:sm:dark:bg-zinc-900/60"
                >
                    <div id="new-debug-bar-section-list" class="ndb-scrollbar ndb:flex ndb:min-h-0 ndb:flex-1 ndb:flex-col ndb:gap-0.5 ndb:overflow-y-auto">
                        <template x-for="(section, sectionIndex) in orderedSections" :key="'section-' + section.key">
                            <div x-show.important="isSectionVisible(section)" class="ndb:contents">
                                <p data-ndb-favorites-heading x-show.important="favorites.length > 0 && sectionIndex === 0" class="ndb:px-2 ndb:pb-1.5 ndb:pt-1 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-[0.14em] ndb:text-zinc-400">Favorites</p>
                                <div x-show.important="favorites.length > 0 && section.key === firstVisibleNonFavoriteKey" class="ndb:my-2 ndb:h-px ndb:bg-zinc-200 ndb:dark:bg-zinc-800"></div>
                                <p data-ndb-sections-heading x-show.important="favorites.length > 0 && section.key === firstVisibleNonFavoriteKey" class="ndb:px-2 ndb:pb-1.5 ndb:pt-1 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-[0.14em] ndb:text-zinc-400">Sections</p>
                                <div
                                    :draggable="isFavorite(section.key)"
                                    :data-ndb-section="section.key"
                                    :data-ndb-section-visible="isSectionVisible(section) ? 'true' : 'false'"
                                    :data-ndb-favorite="isFavorite(section.key) ? 'true' : 'false'"
                                    data-ndb-dragging="false"
                                    @dragstart="startFavoriteDrag(section.key, $event)"
                                    @dragover.prevent="hoverFavorite(section.key, $event.clientY > $event.currentTarget.getBoundingClientRect().top + ($event.currentTarget.offsetHeight / 2))"
                                    @dragleave="leaveFavorite(section.key)"
                                    @drop.prevent="dropFavorite(section.key, favoriteDropAfter)"
                                    @dragend="endFavoriteDrag()"
                                    class="ndb:group ndb:relative ndb:flex ndb:w-full ndb:items-center ndb:rounded-lg ndb:pr-1 ndb:transition ndb:hover:bg-zinc-200/60 ndb:dark:hover:bg-zinc-800/60"
                                    :class="selected === section.key ? 'ndb-section-active' : ''"
                                >
                                    <span :data-ndb-favorite-drop-before="section.key" hidden class="ndb:absolute ndb:inset-x-0.5 ndb:top-0 ndb:z-20 ndb:h-1 ndb:-translate-y-1/2 ndb:rounded-full ndb:bg-indigo-500 ndb:shadow-[0_0_0_2px_rgba(255,255,255,0.8)] ndb:dark:shadow-[0_0_0_2px_rgba(9,9,11,0.9)]"></span>
                                    <span :data-ndb-favorite-drop-after="section.key" hidden class="ndb:absolute ndb:inset-x-0.5 ndb:bottom-0 ndb:z-20 ndb:h-1 ndb:translate-y-1/2 ndb:rounded-full ndb:bg-indigo-500 ndb:shadow-[0_0_0_2px_rgba(255,255,255,0.8)] ndb:dark:shadow-[0_0_0_2px_rgba(9,9,11,0.9)]"></span>
                                    <button
                                        type="button"
                                        :data-ndb-select-section="section.key"
                                        :aria-current="selected === section.key ? 'page' : null"
                                        :aria-label="isFavorite(section.key) ? section.label + '. Drag to reorder. Shift and arrow keys also reorder.' : section.label"
                                        @click="selectSection(section.key)"
                                        @keydown.shift.arrow-up.prevent="moveFavorite(section.key, -1)"
                                        @keydown.shift.arrow-down.prevent="moveFavorite(section.key, 1)"
                                        class="ndb:flex ndb:h-9 ndb:min-w-0 ndb:flex-1 ndb:items-center ndb:gap-2 ndb:rounded-lg ndb:px-2.5 ndb:text-left ndb:text-xs ndb:font-semibold ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
                                        :class="(isFavorite(section.key) ? 'ndb:cursor-grab ndb:active:cursor-grabbing ' : '') + (selected === section.key ? '' : 'ndb:text-zinc-600 ndb:hover:text-zinc-950 ndb:dark:text-zinc-400 ndb:dark:hover:text-white')"
                                    >
                                        <span class="ndb-section-label ndb:truncate" x-text="section.label"></span>
                                        <span class="ndb:ml-auto ndb:flex ndb:shrink-0 ndb:items-center ndb:gap-1.5">
                                            <span x-show.important="section.count !== null" class="ndb-section-count ndb:text-[10px] ndb:tabular-nums" :class="selected === section.key ? '' : 'ndb:text-zinc-400'" x-text="section.count"></span>
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        draggable="false"
                                        :data-ndb-toggle-favorite="section.key"
                                        :aria-label="(isFavorite(section.key) ? 'Remove ' : 'Add ') + section.label + (isFavorite(section.key) ? ' from favorites' : ' to favorites')"
                                        :aria-pressed="isFavorite(section.key)"
                                        :title="isFavorite(section.key) ? 'Remove from favorites' : 'Add to favorites'"
                                        @dragstart.prevent
                                        @click.stop="toggleFavorite(section.key)"
                                        class="ndb-star-button ndb:inline-flex ndb:size-7 ndb:items-center ndb:justify-center ndb:rounded-lg ndb:text-zinc-400 ndb:transition ndb:hover:scale-105 ndb:hover:text-blue-600 ndb:focus-visible:opacity-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-1 ndb:focus-visible:outline-blue-500 ndb:sm:opacity-0 ndb:sm:group-focus-within:opacity-100 ndb:sm:group-hover:opacity-100 ndb:dark:text-zinc-500 ndb:dark:hover:text-blue-300"
                                        :class="isFavorite(section.key) || selected === section.key ? 'ndb:sm:opacity-100' : ''"
                                    >
                                        <span x-show.important="! isFavorite(section.key)" class="ndb-section-star-outline"><x-new-debug-bar::icon name="star" class="ndb:size-3.5" /></span>
                                        <span x-show.important="isFavorite(section.key)"><x-new-debug-bar::icon name="star-filled" class="ndb-favorite-star ndb:size-3.5" /></span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </nav>

                <main x-ref="content" :inert="mobileSectionsOpen" class="ndb-scrollbar ndb:min-w-0 ndb:flex-1 ndb:overflow-y-auto ndb:bg-white/70 ndb:dark:bg-zinc-950/70">
                    <div class="ndb:sticky ndb:top-0 ndb:z-10 ndb:flex ndb:h-12 ndb:items-center ndb:border-b ndb:border-zinc-100/80 ndb:bg-white/65 ndb:px-4 ndb:backdrop-blur-xl ndb:sm:px-6 ndb:dark:border-zinc-900/80 ndb:dark:bg-zinc-950/65">
                        <h2 data-ndb-section-heading x-ref="sectionHeading" tabindex="-1" class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-sm ndb:font-bold ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500" x-text="selectedSection.label"></h2>
                    </div>

                    <div wire:loading.flex wire:target="loadDetails" class="ndb:min-h-64 ndb:items-center ndb:justify-center ndb:p-8">
                        <div class="ndb:text-center"><span class="ndb-loading-pulse ndb:mx-auto ndb:grid ndb:size-10 ndb:place-items-center ndb:rounded-xl ndb:bg-indigo-50 ndb:text-indigo-600 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300"><x-new-debug-bar::icon name="clock" class="ndb:size-4" /></span><p class="ndb:mt-3 ndb:text-sm ndb:font-semibold">Loading request details…</p></div>
                    </div>

                    @if ($detailsLoaded && $profile !== [])
                        <div wire:loading.remove wire:target="loadDetails" class="ndb:p-4 ndb:sm:p-6">
                            @foreach ($profile['sections'] as $sectionKey => $section)
                                <section
                                    data-ndb-section-panel="{{ $sectionKey }}"
                                    @if ($sectionKey !== 'overview') hidden @endif
                                    wire:key="section-{{ $sectionKey }}"
                                    class="ndb:space-y-4"
                                >
                                    @php($sectionFindings = array_values(array_filter($profile['findings'] ?? [], fn (array $finding): bool => $finding['rule_id'] !== 'collector.truncated' && ($sectionKey === 'overview' || $finding['section'] === $sectionKey))))
                                    @php($collectionDropped = (int) ($section['payload']['dropped'] ?? 0))
                                    @php($collectionRetained = (int) ($section['summary']['retained_count'] ?? $section['payload']['retained'] ?? count($section['payload']['items'] ?? [])))
                                    @php($collectionTotal = (int) ($section['summary']['count'] ?? $section['payload']['total'] ?? ($collectionRetained + $collectionDropped)))
                                    @if ($sectionKey !== 'overview' && $collectionDropped > 0)
                                        <div data-ndb-collection-status="{{ $sectionKey }}" role="status" class="ndb:rounded-lg ndb:border ndb:border-amber-200 ndb:bg-amber-50/60 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-semibold ndb:text-amber-800 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/25 ndb:dark:text-amber-300">Showing {{ number_format($collectionRetained) }} of {{ number_format($collectionTotal) }} {{ strtolower($section['label']) }}.</div>
                                    @endif
                                    @if ($sectionKey === 'queries' && (int) ($section['payload']['transaction_dropped'] ?? 0) > 0)
                                        <div data-ndb-collection-status="query-transactions" role="status" class="ndb:rounded-lg ndb:border ndb:border-amber-200 ndb:bg-amber-50/60 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-semibold ndb:text-amber-800 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/25 ndb:dark:text-amber-300">Showing {{ number_format((int) ($section['payload']['transaction_retained'] ?? count($section['payload']['transactions'] ?? []))) }} of {{ number_format((int) ($section['payload']['transaction_total'] ?? 0)) }} query transaction events.</div>
                                    @endif
                                    @if ($sectionKey === 'timeline' && ($section['payload']['incomplete'] ?? false))
                                        <div data-ndb-timeline-incomplete role="status" class="ndb:rounded-lg ndb:border ndb:border-amber-200 ndb:bg-amber-50/60 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-semibold ndb:text-amber-800 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/25 ndb:dark:text-amber-300">Timeline incomplete: {{ number_format((int) ($section['payload']['omitted_count'] ?? 0)) }} source events were omitted.</div>
                                    @endif
                                    @if ($sectionKey === 'overview')
                                        @php($runtimeFacts = is_array($section['payload']['runtime'] ?? null) ? $section['payload']['runtime'] : array_filter(['environment' => $section['payload']['environment'] ?? null, 'php' => $section['payload']['php'] ?? null, 'laravel' => $section['payload']['laravel'] ?? null]))
                                        @php($runtimeDrivers = is_array($section['payload']['drivers'] ?? null) ? $section['payload']['drivers'] : [])
                                        @php($runtimeCacheState = is_array($section['payload']['cache_state'] ?? null) ? $section['payload']['cache_state'] : [])
                                        @php($runtimeEcosystem = is_array($section['payload']['ecosystem'] ?? null) ? $section['payload']['ecosystem'] : [])
                                        @php($runtimeSummary = array_values(array_filter([isset($runtimeFacts['environment']) ? (string) $runtimeFacts['environment'] : null, isset($runtimeFacts['php']) ? 'PHP '.$runtimeFacts['php'] : null, isset($runtimeFacts['laravel']) ? 'Laravel '.$runtimeFacts['laravel'] : null])))
                                        @php($activitySections = array_values(array_filter($summary['sections'] ?? [], fn (array $link): bool => ! in_array($link['key'], ['overview', 'request', 'history'], true) && $link['count'] !== null && ($link['active'] ?? true))))
                                        <x-new-debug-bar::finding-list :findings="$sectionFindings" />
                                        @if (is_string($section['payload']['action_location']['editor_url'] ?? null))
                                            <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:px-3 ndb:py-2 ndb:text-[10px] ndb:dark:border-zinc-800"><span class="ndb:font-semibold ndb:text-zinc-400">Controller source</span><code class="ndb:min-w-0 ndb:flex-1 ndb:truncate">{{ $section['payload']['action_location']['copy'] }}</code><a href="{{ $section['payload']['action_location']['editor_url'] }}" class="ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300">Open in editor</a></div>
                                        @endif
                                        @if ($activitySections !== [])
                                            <div data-ndb-overview-activity>
                                                <div class="ndb:mb-2 ndb:flex ndb:items-center ndb:justify-between ndb:gap-3"><h3 class="ndb:text-xs ndb:font-bold">Activity</h3><span class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">{{ count($activitySections) }} relevant sections</span></div>
                                                <div class="ndb:grid ndb:grid-cols-2 ndb:gap-2 ndb:sm:grid-cols-4">
                                                    @foreach ($activitySections as $link)
                                                        <button type="button" data-ndb-overview-activity-section="{{ $link['key'] }}" @click="selectSection(@js($link['key']))" class="ndb:flex ndb:items-center ndb:justify-between ndb:gap-2 ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:px-3 ndb:py-2.5 ndb:text-left ndb:transition ndb:hover:border-indigo-300 ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:border-zinc-800 ndb:dark:hover:border-indigo-800 ndb:dark:hover:bg-indigo-950/50"><span class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-semibold">{{ $link['label'] }}</span><span class="ndb:text-xs ndb:font-bold ndb:tabular-nums ndb:text-zinc-400">{{ $link['count'] }}</span></button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                        <details data-ndb-overview-environment class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800">
                                            <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-4 ndb:py-3.5 ndb:transition ndb:hover:bg-zinc-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-zinc-900">
                                                <span class="ndb:min-w-0 ndb:flex-1"><span class="ndb:block ndb:text-xs ndb:font-bold">Environment details</span>@if ($runtimeSummary !== [])<span class="ndb:mt-0.5 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:gap-y-0.5 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">@foreach ($runtimeSummary as $runtimeSummaryFact)<span>{{ $runtimeSummaryFact }}</span>@endforeach</span>@endif</span>
                                                <span class="ndb:hidden ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400 ndb:sm:inline">Runtime, drivers, ecosystem</span>
                                                <x-new-debug-bar::icon name="chevron-down" class="ndb-details-chevron ndb:size-3.5 ndb:text-zinc-400 ndb:transition" />
                                            </summary>
                                            <div data-ndb-overview-environment-content class="ndb:space-y-5 ndb:border-t ndb:border-zinc-200 ndb:p-4 ndb:dark:border-zinc-800">
                                                <div>
                                                    <h3 class="ndb:text-xs ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Runtime</h3>
                                                    <dl class="ndb:mt-3 ndb:grid ndb:grid-cols-2 ndb:gap-x-5 ndb:gap-y-3 ndb:lg:grid-cols-5">
                                                        @foreach ($runtimeFacts as $label => $value)
                                                            <div><dt class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ str($label)->replace('_', ' ')->title() }}</dt><dd class="ndb:mt-0.5 ndb:truncate ndb:text-sm ndb:font-semibold">{{ is_bool($value) ? ($value ? 'On' : 'Off') : $value }}</dd></div>
                                                        @endforeach
                                                    </dl>
                                                </div>
                                                @if ($runtimeDrivers !== [] || $runtimeCacheState !== [])
                                                    <div class="ndb:grid ndb:gap-5 ndb:border-t ndb:border-zinc-200 ndb:pt-5 ndb:lg:grid-cols-2 ndb:dark:border-zinc-800">
                                                        @if ($runtimeDrivers !== [])
                                                            <div>
                                                                <h3 class="ndb:text-xs ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Drivers</h3>
                                                                <dl class="ndb:mt-3 ndb:grid ndb:grid-cols-2 ndb:gap-x-5 ndb:gap-y-3 ndb:sm:grid-cols-3">
                                                                    @foreach ($runtimeDrivers as $label => $value)
                                                                        <div><dt class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ str($label)->title() }}</dt><dd class="ndb:mt-0.5 ndb:truncate ndb:text-sm ndb:font-semibold">{{ $value }}</dd></div>
                                                                    @endforeach
                                                                </dl>
                                                            </div>
                                                        @endif
                                                        @if ($runtimeCacheState !== [])
                                                            <div>
                                                                <h3 class="ndb:text-xs ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Framework cache</h3>
                                                                <dl class="ndb:mt-3 ndb:grid ndb:grid-cols-3 ndb:gap-3">
                                                                    @foreach ($runtimeCacheState as $label => $value)
                                                                        <div><dt class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ str($label)->title() }}</dt><dd class="ndb:mt-0.5 ndb:text-sm ndb:font-semibold">{{ $value === null ? 'Not exposed' : ($value ? 'Cached' : 'Open') }}</dd></div>
                                                                    @endforeach
                                                                </dl>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                                <div class="ndb:border-t ndb:border-zinc-200 ndb:pt-5 ndb:dark:border-zinc-800">
                                                    <div class="ndb:flex ndb:items-center ndb:justify-between ndb:gap-3"><h3 class="ndb:text-xs ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Ecosystem</h3><span class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">Observed host packages</span></div>
                                                    @if ($runtimeEcosystem === [])
                                                        <p class="ndb:mt-3 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">No supported ecosystem packages were detected for this request.</p>
                                                    @else
                                                        <ul class="ndb:mt-3 ndb:flex ndb:flex-wrap ndb:gap-2">
                                                            @foreach ($runtimeEcosystem as $package)
                                                                <li class="ndb:rounded-lg ndb:bg-zinc-100 ndb:px-2.5 ndb:py-1.5 ndb:text-xs ndb:font-semibold ndb:dark:bg-zinc-900">{{ $package['label'] }} <span class="ndb:text-zinc-400">{{ $package['version'] }}</span></li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </div>
                                            </div>
                                        </details>
                                    @elseif ($sectionKey !== 'queries')
                                        <x-new-debug-bar::finding-list :findings="$sectionFindings" title="Related findings" />
                                    @endif

                                    @if ($sectionKey === 'timeline')
                                        @php($timelineItems = $section['payload']['items'])
                                        @php($timelineSections = array_values(array_unique(array_column($timelineItems, 'section'))))
                                        @php($timelineDuration = max(0.001, ...array_column($timelineItems, 'at_ms')))
                                        @php($timelineTicks = [0, 25, 50, 75, 100])
                                        <div class="ndb:flex ndb:flex-col ndb:gap-3 ndb:border-b ndb:border-zinc-200/80 ndb:pb-3 ndb:lg:flex-row ndb:lg:items-end ndb:dark:border-zinc-800">
                                            <div class="ndb:min-w-0 ndb:flex-1"><p class="ndb:mb-1.5 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Source</p><div class="ndb:flex ndb:overflow-x-auto" role="group" aria-label="Filter timeline"><button type="button" data-ndb-timeline-filter="all" @click="setTimelineFilter('all')" :aria-pressed="timelineFilter === 'all'" class="ndb:border-b-2 ndb:px-3 ndb:py-1.5 ndb:text-xs ndb:font-semibold" :class="timelineFilter === 'all' ? 'ndb:border-indigo-500 ndb:text-indigo-700 ndb:dark:text-indigo-300' : 'ndb:border-transparent ndb:text-zinc-500 ndb:dark:text-zinc-400'">All</button>@foreach ($timelineSections as $timelineSection)<button type="button" data-ndb-timeline-filter="{{ $timelineSection }}" @click="setTimelineFilter(@js($timelineSection))" :aria-pressed="timelineFilter === @js($timelineSection)" class="ndb:border-b-2 ndb:px-3 ndb:py-1.5 ndb:text-xs ndb:font-semibold" :class="timelineFilter === @js($timelineSection) ? 'ndb:border-indigo-500 ndb:text-indigo-700 ndb:dark:text-indigo-300' : 'ndb:border-transparent ndb:text-zinc-500 ndb:dark:text-zinc-400'">{{ str($timelineSection)->title() }}</button>@endforeach</div></div>
                                            <label class="ndb:min-w-0 ndb:lg:w-72"><span class="ndb:mb-1.5 ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Search</span><input data-ndb-timeline-search x-model="timelineSearch" @input.debounce.100ms="applyTimelineFilters()" type="search" placeholder="Event or section" class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:outline-none ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70" /></label>
                                        </div>
                                        <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:justify-between ndb:gap-3">
                                            <div><h3 class="ndb:text-xs ndb:font-bold">Waterfall</h3><p data-ndb-timeline-summary class="ndb:mt-0.5 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400"><span x-text="visibleTimelineCount"></span> events across {{ number_format($timelineDuration, $timelineDuration < 10 ? 1 : 0) }} ms</p></div>
                                            <div class="ndb:flex ndb:items-center ndb:gap-4 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400" aria-label="Timeline legend"><span class="ndb:flex ndb:items-center ndb:gap-1.5"><span class="ndb:h-1.5 ndb:w-5 ndb:rounded-sm ndb:bg-indigo-500"></span>Duration</span><span class="ndb:flex ndb:items-center ndb:gap-1.5"><span class="ndb:size-2 ndb:rounded-full ndb:bg-sky-500"></span>Event</span></div>
                                        </div>
                                        <div data-ndb-timeline-waterfall class="ndb-scrollbar ndb:overflow-x-auto ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/55 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30">
                                            <div class="ndb:min-w-[760px]">
                                                <div class="ndb:grid ndb:grid-cols-[minmax(190px,0.8fr)_minmax(420px,2fr)_88px] ndb:border-b ndb:border-zinc-200/90 ndb:bg-zinc-50/80 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/70">
                                                    <div class="ndb:self-end ndb:px-3 ndb:pb-2 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Activity</div>
                                                    <div class="ndb:h-10 ndb:border-x ndb:border-zinc-200/90 ndb:dark:border-zinc-800" aria-label="Timeline from zero to {{ number_format($timelineDuration, $timelineDuration < 10 ? 1 : 0) }} milliseconds">
                                                        <div class="ndb:relative ndb:mx-2 ndb:h-full">
                                                            @foreach ($timelineTicks as $tick)
                                                                @php($timelineTickMs = $timelineDuration * $tick / 100)
                                                                <span data-ndb-timeline-tick="{{ $tick }}" class="ndb:absolute ndb:bottom-2 ndb:whitespace-nowrap {{ $tick === 0 ? 'ndb:translate-x-0' : ($tick === 100 ? 'ndb:-translate-x-full' : 'ndb:-translate-x-1/2') }} ndb:text-[9px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400" style="left: {{ $tick }}%">{{ number_format($timelineTickMs, $timelineTickMs > 0 && $timelineTickMs < 10 ? 1 : 0) }} ms</span>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                    <div class="ndb:self-end ndb:px-3 ndb:pb-2 ndb:text-right ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Timing</div>
                                                </div>
                                                <ol x-ref="timelineList" class="ndb:m-0 ndb:list-none ndb:divide-y ndb:divide-zinc-100 ndb:p-0 ndb:dark:divide-zinc-800/80">
                                                    @foreach ($timelineItems as $item)
                                                        <li
                                                            data-ndb-timeline-item="{{ $item['id'] }}"
                                                            data-section="{{ $item['section'] }}"
                                                            data-kind="{{ $item['kind'] }}"
                                                            data-position="{{ $item['at_percent'] }}"
                                                            @if ($item['start_percent'] !== null) data-start="{{ $item['start_percent'] }}" @endif
                                                            @if ($item['duration_percent'] !== null) data-duration="{{ $item['duration_percent'] }}" @endif
                                                            data-search="{{ mb_strtolower($item['label'].' '.$item['section']) }}"
                                                            class="ndb:grid ndb:min-h-14 ndb:grid-cols-[minmax(190px,0.8fr)_minmax(420px,2fr)_88px]"
                                                            style="--ndb-timeline-at: {{ $item['at_percent'] }}%; --ndb-timeline-start: {{ $item['start_percent'] ?? $item['at_percent'] }}%; --ndb-timeline-width: {{ $item['duration_percent'] ?? 0 }}%;"
                                                        >
                                                            <div class="ndb:min-w-0 ndb:px-3 ndb:py-2.5">
                                                                <p class="ndb:truncate ndb:text-xs ndb:font-semibold" title="{{ $item['label'] }}">{{ $item['label'] }}</p>
                                                                <button type="button" @click="selectSection(@js($item['section']))" class="ndb:mt-0.5 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:transition ndb:hover:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:text-indigo-300">{{ str($item['section'])->replace('_', ' ')->title() }}</button>
                                                            </div>
                                                            <div data-ndb-timeline-track class="ndb:overflow-hidden ndb:border-x ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
                                                                <div class="ndb:relative ndb:mx-2 ndb:h-full">
                                                                    @foreach ([25, 50, 75] as $tick)
                                                                        <span aria-hidden="true" class="ndb:absolute ndb:inset-y-0 ndb:border-l ndb:border-zinc-200/60 ndb:dark:border-zinc-800/80" style="left: {{ $tick }}%"></span>
                                                                    @endforeach
                                                                    <span aria-hidden="true" class="ndb:absolute ndb:inset-x-0 ndb:top-1/2 ndb:border-t ndb:border-zinc-200/80 ndb:dark:border-zinc-700/80"></span>
                                                                    @if ($item['kind'] === 'span')
                                                                        <span data-ndb-timeline-mark title="{{ $item['label'] }} — {{ number_format((float) $item['duration_ms'], $item['duration_ms'] < 10 ? 1 : 0) }} ms" class="ndb:absolute ndb:top-1/2 ndb:left-[var(--ndb-timeline-start)] ndb:h-2.5 ndb:w-[max(3px,var(--ndb-timeline-width))] ndb:-translate-y-1/2 ndb:rounded-sm ndb:bg-indigo-500 ndb:shadow-[0_0_0_1px_rgba(79,70,229,0.18)] ndb:dark:bg-indigo-400"></span>
                                                                    @elseif ($item['kind'] === 'milestone')
                                                                        <span data-ndb-timeline-mark title="{{ $item['label'] }}" class="ndb:absolute ndb:top-1/2 ndb:left-[clamp(4px,var(--ndb-timeline-at),calc(100%-4px))] ndb:h-5 ndb:w-px ndb:-translate-x-1/2 ndb:-translate-y-1/2 ndb:bg-zinc-700 ndb:dark:bg-zinc-200"></span>
                                                                    @else
                                                                        <span data-ndb-timeline-mark title="{{ $item['label'] }}" class="ndb:absolute ndb:top-1/2 ndb:left-[clamp(4px,var(--ndb-timeline-at),calc(100%-4px))] ndb:size-2.5 ndb:-translate-x-1/2 ndb:-translate-y-1/2 ndb:rounded-full ndb:border-2 ndb:border-white ndb:bg-sky-500 ndb:shadow-sm ndb:dark:border-zinc-900 ndb:dark:bg-sky-400"></span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <div class="ndb:self-center ndb:px-3 ndb:text-right ndb:tabular-nums">
                                                                @if ($item['kind'] === 'span')
                                                                    <p class="ndb:text-[10px] ndb:font-bold">{{ number_format((float) $item['duration_ms'], $item['duration_ms'] < 10 ? 1 : 0) }} ms</p>
                                                                    <p class="ndb:text-[9px] ndb:font-semibold ndb:text-zinc-400">{{ $item['start_ms'] }}–{{ $item['at_ms'] }} ms</p>
                                                                @else
                                                                    <p class="ndb:text-[10px] ndb:font-bold">{{ number_format((float) $item['at_ms'], $item['at_ms'] > 0 && $item['at_ms'] < 10 ? 1 : 0) }} ms</p>
                                                                @endif
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ol>
                                            </div>
                                        </div>
                                        <div x-show.important="visibleTimelineCount === 0"><x-new-debug-bar::empty-state label="No timeline events match these filters." /></div>
                                    @elseif ($sectionKey === 'request')
                                        <div class="ndb:grid ndb:grid-cols-2 ndb:gap-3 ndb:lg:grid-cols-4">
                                            @foreach (['method' => 'Method', 'status' => 'Status', 'route' => 'Route', 'action' => 'Controller'] as $key => $label)
                                                <div class="ndb:min-w-0 ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:p-3 ndb:dark:border-zinc-800"><p class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</p><p class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-semibold">{{ $section['payload'][$key] ?: '—' }}</p></div>
                                            @endforeach
                                        </div>
                                        <dl class="ndb:grid ndb:grid-cols-2 ndb:divide-x ndb:divide-y ndb:divide-zinc-200 ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:lg:grid-cols-5 ndb:lg:divide-y-0 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                                            @foreach ([
                                                ['Content type', ($section['payload']['content_type'] ?? null) ?: '—'],
                                                ['Request size', number_format($section['payload']['request_size_bytes'] ?? 0).' B'],
                                                ['Response size', number_format($section['payload']['response_size_bytes'] ?? 0).' B'],
                                                ['Session', ($section['payload']['session_present'] ?? false) ? 'Present' : 'None'],
                                                ['Authentication', ($section['payload']['authenticated'] ?? false) ? 'Present' : 'None'],
                                            ] as [$label, $value])
                                                <div class="ndb:min-w-0 ndb:px-3 ndb:py-2.5"><dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</dt><dd class="ndb:mt-0.5 ndb:truncate ndb:text-xs ndb:font-bold">{{ $value }}</dd></div>
                                            @endforeach
                                        </dl>
                                        <div class="ndb:grid ndb:gap-3 ndb:lg:grid-cols-2">
                                            @php($authentication = $section['payload']['authentication'] ?? [])
                                            @php($sessionShape = $section['payload']['session'] ?? [])
                                            <div class="ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:p-4 ndb:dark:border-zinc-800">
                                                <h3 class="ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Authentication</h3>
                                                <dl class="ndb:mt-3 ndb:grid ndb:grid-cols-2 ndb:gap-3"><div><dt class="ndb:text-[9px] ndb:font-semibold ndb:text-zinc-400">Guard</dt><dd class="ndb:mt-0.5 ndb:text-xs ndb:font-bold">{{ $authentication['guard'] ?? 'unknown' }}</dd></div><div><dt class="ndb:text-[9px] ndb:font-semibold ndb:text-zinc-400">Model</dt><dd class="ndb:mt-0.5 ndb:truncate ndb:text-xs ndb:font-bold">{{ $authentication['model'] ?? 'Guest' }}</dd></div>@if (is_string($authentication['identifier'] ?? null))<div class="ndb:col-span-2"><dt class="ndb:text-[9px] ndb:font-semibold ndb:text-zinc-400">Private identifier</dt><dd class="ndb:mt-0.5 ndb:truncate ndb:font-mono ndb:text-[10px]">{{ $authentication['identifier'] }}</dd></div>@endif</dl>
                                            </div>
                                            <div class="ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:p-4 ndb:dark:border-zinc-800">
                                                <h3 class="ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Session shape</h3>
                                                @if ($sessionShape['present'] ?? false)<p class="ndb:mt-2 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:gap-y-1 ndb:text-xs ndb:font-semibold"><span>{{ $sessionShape['key_count'] }} keys</span><span>{{ $sessionShape['driver'] ?? 'unknown' }} driver</span></p>@else<p class="ndb:mt-2 ndb:text-xs ndb:font-semibold">No started session</p>@endif
                                                @if (($sessionShape['keys'] ?? []) !== [])<p class="ndb:mt-2 ndb:break-words ndb:text-[10px] ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ implode(', ', $sessionShape['keys']) }}</p>@endif
                                                @if (($sessionShape['flash_keys'] ?? []) !== [] || ($sessionShape['error_bags'] ?? []) !== [])<p class="ndb:mt-2 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:gap-y-1 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400"><span>Flash: {{ implode(', ', $sessionShape['flash_keys'] ?? []) ?: 'none' }}</span><span>Error bags: {{ implode(', ', $sessionShape['error_bags'] ?? []) ?: 'none' }}</span></p>@endif
                                            </div>
                                        </div>
                                        <div class="ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:p-4 ndb:dark:border-zinc-800">
                                            <h3 class="ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Configured middleware order</h3>
                                            <ol class="ndb:mt-3 ndb:list-none ndb:space-y-2">
                                                @forelse ($section['payload']['middleware'] ?? [] as $position => $middleware)
                                                    <li class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:text-xs"><span class="ndb:w-5 ndb:shrink-0 ndb:text-right ndb:font-bold ndb:tabular-nums ndb:text-zinc-400">{{ $position + 1 }}</span><code class="ndb:truncate">{{ $middleware }}</code></li>
                                                @empty
                                                    <li class="ndb:text-xs ndb:text-zinc-400">No route middleware was resolved.</li>
                                                @endforelse
                                            </ol>
                                        </div>
                                        <pre class="ndb-code ndb-scrollbar"><code data-ndb-language="json">{{ json_encode($section['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                    @elseif ($sectionKey === 'queries')
                                        @php($querySummary = $section['summary'])
                                        @php($queryNPlusOneCount = count(array_filter($sectionFindings, fn (array $finding): bool => $finding['rule_id'] === 'query.n_plus_one')))
                                        @if ($sectionFindings !== [])
                                            <div data-ndb-query-findings class="ndb:flex ndb:flex-col ndb:gap-3 ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:bg-white/45 ndb:p-4 ndb:sm:flex-row ndb:sm:items-center ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30">
                                                <div class="ndb:min-w-0 ndb:flex-1">
                                                    <h3 class="ndb:text-sm ndb:font-bold">Query findings</h3>
                                                    <p data-ndb-query-finding-summary class="ndb:mt-0.5 ndb:text-xs ndb:text-zinc-600 ndb:dark:text-zinc-300">
                                                        @if (($querySummary['repeated_pattern_count'] ?? 0) > 0)
                                                            {{ $querySummary['repeated_pattern_count'] }} repeated {{ ($querySummary['repeated_pattern_count'] ?? 0) === 1 ? 'pattern' : 'patterns' }} found.
                                                            @if ($queryNPlusOneCount > 0)
                                                                {{ $queryNPlusOneCount }} {{ $queryNPlusOneCount === 1 ? 'has' : 'have' }} likely N+1 evidence.
                                                            @endif
                                                        @elseif (($querySummary['slow_count'] ?? 0) > 0)
                                                            {{ $querySummary['slow_count'] }} slow {{ ($querySummary['slow_count'] ?? 0) === 1 ? 'query needs' : 'queries need' }} review.
                                                        @else
                                                            Review the matching query evidence below.
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="ndb:flex ndb:flex-wrap ndb:gap-2">
                                                    @if (($querySummary['repeated_pattern_count'] ?? 0) > 0)<button type="button" data-ndb-query-review="repeated" @click="reviewQueryEvidence('repeated')" class="ndb:rounded-lg ndb:bg-indigo-600 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-bold ndb:text-white ndb:transition ndb:hover:bg-indigo-700 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:bg-indigo-500 ndb:dark:text-indigo-950 ndb:dark:hover:bg-indigo-400">Review repeated</button>@endif
                                                    @if (($querySummary['slow_count'] ?? 0) > 0)<button type="button" data-ndb-query-review="slow" @click="reviewQueryEvidence('slow')" class="ndb:rounded-lg ndb:border ndb:border-violet-200 ndb:bg-white/70 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-bold ndb:text-violet-700 ndb:transition ndb:hover:bg-white ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-violet-500 ndb:dark:border-violet-900 ndb:dark:bg-violet-950/40 ndb:dark:text-violet-300 ndb:dark:hover:bg-violet-950/70">Review slow</button>@endif
                                                </div>
                                            </div>
                                        @endif
                                        <div class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/55 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/35">
                                            <dl class="ndb:grid ndb:grid-cols-2 ndb:divide-x ndb:divide-y ndb:divide-zinc-200/80 ndb:sm:grid-cols-5 ndb:sm:divide-y-0 ndb:dark:divide-zinc-800">
                                                @foreach ([
                                                    ['Queries', $querySummary['total_count']],
                                                    ['Query time', $querySummary['total_time_ms'].' ms'],
                                                    ['Request share', $querySummary['request_time_percent'].'%'],
                                                    ['Repeated', $querySummary['repeated_pattern_count']],
                                                    ['Extra runs', $querySummary['extra_execution_count']],
                                                ] as [$label, $value])
                                                    <div class="ndb:px-3 ndb:py-2.5"><dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</dt><dd data-ndb-query-summary-value="{{ str($label)->slug() }}" class="ndb:mt-0.5 ndb:text-sm ndb:font-bold ndb:tabular-nums">{{ $value }}</dd></div>
                                                @endforeach
                                            </dl>
                                        </div>

                                        <div class="ndb:flex ndb:flex-col ndb:gap-3 ndb:border-b ndb:border-zinc-200/80 ndb:pb-3 ndb:lg:flex-row ndb:lg:items-end ndb:dark:border-zinc-800">
                                            <div class="ndb:min-w-0 ndb:flex-1">
                                                <p class="ndb:mb-1.5 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Filter</p>
                                                <div class="ndb:flex ndb:gap-1 ndb:overflow-x-auto" role="group" aria-label="Filter queries">
                                                    @foreach (['all' => 'All', 'repeated' => 'Repeated', 'slow' => 'Slow', 'read' => 'Read', 'write' => 'Write'] as $filter => $label)
                                                        <button type="button" data-ndb-query-filter="{{ $filter }}" @click="setQueryFilter(@js($filter))" :aria-pressed="queryFilter === @js($filter)" class="ndb:rounded-lg ndb:border ndb:px-3 ndb:py-1.5 ndb:text-xs ndb:font-semibold ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500" :class="queryFilter === @js($filter) ? 'ndb:border-indigo-200 ndb:bg-indigo-50 ndb:text-indigo-700 ndb:dark:border-indigo-900 ndb:dark:bg-indigo-950/60 ndb:dark:text-indigo-300' : 'ndb:border-transparent ndb:text-zinc-500 ndb:hover:border-zinc-200 ndb:hover:bg-white/70 ndb:hover:text-zinc-950 ndb:dark:text-zinc-400 ndb:dark:hover:border-zinc-700 ndb:dark:hover:bg-zinc-900/70 ndb:dark:hover:text-white'">{{ $label }}</button>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <label class="ndb:min-w-0 ndb:lg:w-64"><span class="ndb:mb-1.5 ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Search</span><input data-ndb-query-search x-model="querySearch" @input.debounce.100ms="applyQueryView()" type="search" placeholder="SQL or redacted binding" class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:outline-none ndb:transition ndb:placeholder:text-zinc-400 ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70" /></label>
                                            <div>
                                                <p class="ndb:mb-1.5 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Sort</p>
                                                <div class="ndb:flex ndb:rounded-lg ndb:bg-zinc-200/70 ndb:p-0.5 ndb:dark:bg-zinc-800/80" role="group" aria-label="Sort queries">
                                                    @foreach (['execution' => 'Execution', 'duration' => 'Slowest'] as $sort => $label)
                                                        <button type="button" data-ndb-query-sort="{{ $sort }}" @click="setQuerySort(@js($sort))" :aria-pressed="querySort === @js($sort)" class="ndb:flex ndb:h-8 ndb:min-w-20 ndb:items-center ndb:justify-center ndb:rounded-md ndb:px-3 ndb:text-xs ndb:font-bold ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500" :class="querySort === @js($sort) ? 'ndb:bg-white ndb:text-zinc-950 ndb:shadow-sm ndb:dark:bg-zinc-700 ndb:dark:text-white' : 'ndb:text-zinc-500 ndb:hover:text-zinc-950 ndb:dark:text-zinc-400 ndb:dark:hover:text-white'">{{ $label }}</button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        <p data-ndb-query-result-count class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400"><span x-text="visibleQueryCount"></span> results</p>

                                        <div x-ref="queryItems" class="ndb:space-y-3">
                                            @foreach ($section['payload']['items'] as $query)
                                                <x-new-debug-bar::query-execution :query="$query" :identity="'item-'.$query['execution']" :explain="$queryExplains[$query['execution']] ?? null" :explain-error="$queryExplainErrors[$query['execution']] ?? null" filterable />
                                            @endforeach
                                        </div>

                                        <div x-ref="queryGroups" class="ndb:space-y-3">
                                            @foreach ($section['payload']['repeated_groups'] as $group)
                                                @php($groupSearch = mb_strtolower($group['sql'].' '.json_encode(array_column($group['executions'], 'bindings'), JSON_UNESCAPED_SLASHES)))
                                                <article
                                                    data-ndb-query-group="{{ $group['fingerprint'] }}"
                                                    data-execution="{{ $group['executions'][0]['execution'] }}"
                                                    data-duration="{{ $group['duration_ms'] }}"
                                                    data-search="{{ $groupSearch }}"
                                                    hidden
                                                    class="ndb:scroll-mt-16 ndb:overflow-hidden ndb:rounded-2xl ndb:border ndb:border-violet-200/90 ndb:bg-white/70 ndb:shadow-sm ndb:dark:border-violet-950 ndb:dark:bg-zinc-950/50"
                                                >
                                                    <div class="ndb:bg-gradient-to-br ndb:from-violet-50 ndb:via-white ndb:to-rose-50/70 ndb:p-4 ndb:dark:from-violet-950/45 ndb:dark:via-zinc-950 ndb:dark:to-rose-950/25">
                                                        <div class="ndb:flex ndb:items-start ndb:gap-3">
                                                            <span class="ndb:grid ndb:size-10 ndb:shrink-0 ndb:place-items-center ndb:rounded-xl ndb:bg-violet-100 ndb:text-violet-700 ndb:dark:bg-violet-900/60 ndb:dark:text-violet-300"><x-new-debug-bar::icon name="database" class="ndb:size-5" /></span>
                                                            <div class="ndb:min-w-0 ndb:flex-1"><h3 data-ndb-query-group-count class="ndb:text-sm ndb:font-bold">{{ $group['count'] }} matching executions</h3><p data-ndb-query-group-extra class="ndb:mt-0.5 ndb:text-xs ndb:text-zinc-600 ndb:dark:text-zinc-300">{{ $group['extra_executions'] }} extra {{ $group['extra_executions'] === 1 ? 'execution' : 'executions' }} from one query pattern</p></div>
                                                            <div class="ndb:shrink-0 ndb:text-right"><p data-ndb-query-group-duration class="ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $group['duration_ms'] }} ms</p><p class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400">Total query time</p></div>
                                                        </div>
                                                        <div class="ndb:mt-3 ndb:flex ndb:flex-wrap ndb:gap-2">
                                                            @if ($group['likely_n_plus_one'])<span class="ndb:rounded-full ndb:bg-rose-100 ndb:px-2.5 ndb:py-1 ndb:text-[10px] ndb:font-bold ndb:text-rose-700 ndb:dark:bg-rose-900/50 ndb:dark:text-rose-300">Likely N+1</span>@endif
                                                            <span class="ndb:rounded-full ndb:bg-violet-100 ndb:px-2.5 ndb:py-1 ndb:text-[10px] ndb:font-bold ndb:text-violet-700 ndb:dark:bg-violet-900/50 ndb:dark:text-violet-300">{{ $group['connection'] }} connection</span>
                                                        </div>
                                                    </div>
                                                    <div data-ndb-query-group-pattern class="ndb:border-y ndb:border-zinc-200/80 ndb:bg-zinc-50/70 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/70">
                                                        <div class="ndb:flex ndb:items-center ndb:justify-between ndb:gap-3 ndb:px-4 ndb:py-2 ndb:text-zinc-600 ndb:dark:text-zinc-300"><span class="ndb:text-[10px] ndb:font-bold">Query pattern</span><button type="button" @click="copyText(@js($group['sql']))" class="ndb:inline-flex ndb:size-7 ndb:items-center ndb:justify-center ndb:rounded-lg ndb:text-zinc-500 ndb:transition ndb:hover:bg-zinc-200/70 ndb:hover:text-zinc-950 ndb:focus-visible:outline-2 ndb:focus-visible:outline-violet-400 ndb:dark:text-zinc-400 ndb:dark:hover:bg-zinc-800 ndb:dark:hover:text-white" aria-label="Copy repeated query pattern" title="Copy query pattern"><x-new-debug-bar::icon name="copy" class="ndb:size-3.5" /></button></div>
                                                        <pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200/80 ndb:dark:border-zinc-800"><code data-ndb-language="sql">{{ $group['sql'] }}</code></pre>
                                                    </div>
                                                    <div data-ndb-query-group-executions class="ndb:space-y-2 ndb:p-3">
                                                        <div class="ndb:flex ndb:items-center ndb:justify-between ndb:gap-3 ndb:px-1"><h4 class="ndb:text-xs ndb:font-bold">Executions</h4><span class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">Ordered by the selected sort</span></div>
                                                        @foreach ($group['executions'] as $execution)
                                                            <x-new-debug-bar::query-execution :query="$execution" :identity="'group-'.$group['fingerprint'].'-'.$execution['execution']" :explain="$queryExplains[$execution['execution']] ?? null" :explain-error="$queryExplainErrors[$execution['execution']] ?? null" grouped />
                                                        @endforeach
                                                    </div>
                                                </article>
                                            @endforeach
                                        </div>

                                        <div x-show.important="visibleQueryCount === 0">
                                            <x-new-debug-bar::empty-state label="No queries match these filters." />
                                        </div>
                                    @elseif ($sectionKey === 'livewire')
                                        <div class="ndb:grid ndb:grid-cols-2 ndb:divide-x ndb:divide-y ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:sm:grid-cols-4 ndb:sm:divide-y-0 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                                            @foreach ([['Initial renders', $section['summary']['initial_render_count'] ?? 0], ['Updates', $section['summary']['update_count'] ?? 0], ['Components', $section['summary']['component_count'] ?? 0], ['Livewire', $section['summary']['version'] ?? 'Detected']] as [$label, $value])
                                                <div class="ndb:min-w-0 ndb:px-3.5 ndb:py-3"><p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</p><p class="ndb:mt-1 ndb:truncate ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $value }}</p></div>
                                            @endforeach
                                        </div>
                                        <div class="ndb:space-y-3">
                                            @forelse ($section['payload']['items'] as $index => $item)
                                                <article wire:key="livewire-{{ $index }}" class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30">
                                                    <div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:px-4 ndb:py-3 ndb:dark:border-zinc-800">
                                                        <span class="ndb:rounded-md ndb:bg-zinc-100 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-500 ndb:dark:bg-zinc-800 ndb:dark:text-zinc-300">{{ $item['kind'] ?? 'update' }}</span>
                                                        <code class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-bold">{{ $item['component'] }}</code>
                                                        <span class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">{{ isset($item['duration_ms']) ? $item['duration_ms'].' ms render' : ($item['at_ms'].' ms') }}</span>
                                                    </div>
                                                    @if (is_string($item['parent_component'] ?? null))<p class="ndb:border-b ndb:border-zinc-200 ndb:px-4 ndb:py-2 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400 ndb:dark:border-zinc-800">Parent: <code>{{ $item['parent_component'] }}</code></p>@endif
                                                    <dl class="ndb:grid ndb:grid-cols-2 ndb:divide-x ndb:divide-y ndb:divide-zinc-200 ndb:sm:grid-cols-4 ndb:sm:divide-y-0 ndb:dark:divide-zinc-800">
                                                        @foreach ([
                                                            ['Actions', ($item['actions'] ?? []) === [] ? '—' : implode(', ', $item['actions'])],
                                                            ['Updated properties', ($item['updated_properties'] ?? []) === [] ? '—' : implode(', ', $item['updated_properties'])],
                                                            ['Request', number_format($item['payload_size_bytes'] ?? 0).' B'],
                                                            ['Response', number_format($item['response_size_bytes'] ?? 0).' B'],
                                                        ] as [$label, $value])
                                                            <div class="ndb:min-w-0 ndb:px-3.5 ndb:py-3"><dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</dt><dd class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-semibold">{{ $value }}</dd></div>
                                                        @endforeach
                                                    </dl>
                                                    @if (($item['validation_failure_count'] ?? 0) > 0)
                                                        <div class="ndb:border-t ndb:border-amber-200 ndb:bg-amber-50/45 ndb:px-4 ndb:py-3 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/20">
                                                            <p class="ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-amber-700 ndb:dark:text-amber-300">{{ $item['validation_failure_count'] }} validation failure{{ $item['validation_failure_count'] === 1 ? '' : 's' }}</p>
                                                            <p class="ndb:mt-1 ndb:text-xs ndb:font-semibold">{{ implode(', ', $item['validation_fields'] ?? []) }}</p>
                                                        </div>
                                                    @endif
                                                </article>
                                            @empty
                                                <x-new-debug-bar::empty-state label="No application Livewire updates were captured." />
                                            @endforelse
                                        </div>
                                    @elseif ($sectionKey === 'http_client')
                                        <dl class="ndb:grid ndb:grid-cols-3 ndb:divide-x ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                                            @foreach ([['Requests', $section['summary']['count']], ['Total time', $section['summary']['duration_ms'].' ms'], ['Failures', $section['summary']['failed_count']]] as [$label, $value])
                                                <div class="ndb:px-3.5 ndb:py-3"><dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</dt><dd class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $value }}</dd></div>
                                            @endforeach
                                        </dl>
                                        <div class="ndb:space-y-2">
                                            @forelse ($section['payload']['items'] as $index => $item)
                                                <article wire:key="http-client-{{ $index }}" class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:px-3.5 ndb:py-3 {{ ($item['failed'] ?? false) ? 'ndb:border-red-200 ndb:bg-red-50/35 ndb:dark:border-red-950 ndb:dark:bg-red-950/15' : 'ndb:border-zinc-200 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30' }}">
                                                    <span class="ndb:rounded-md ndb:bg-indigo-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:font-bold ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300">{{ $item['method'] }}</span>
                                                    <div class="ndb:min-w-0 ndb:flex-1"><p class="ndb:truncate ndb:text-xs ndb:font-semibold">{{ $item['url'] }}</p><p class="ndb:mt-1 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">{{ ($item['failed'] ?? false) ? ($item['exception_class'] ?? 'Connection failed') : 'HTTP '.$item['status'] }}</p></div>
                                                    <span class="ndb:shrink-0 ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $item['duration_ms'] }} ms</span>
                                                </article>
                                            @empty
                                                <x-new-debug-bar::empty-state label="No outbound HTTP requests were captured." />
                                            @endforelse
                                        </div>
                                    @elseif ($sectionKey === 'queue')
                                        <dl class="ndb:grid ndb:grid-cols-2 ndb:divide-x ndb:divide-y ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:sm:grid-cols-4 ndb:sm:divide-y-0 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                                            @foreach ([['Queued', $section['summary']['queued_count']], ['Executed', $section['summary']['executed_count']], ['Failures', $section['summary']['failed_count']], ['Run time', $section['summary']['duration_ms'].' ms']] as [$label, $value])
                                                <div class="ndb:px-3.5 ndb:py-3"><dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</dt><dd class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $value }}</dd></div>
                                            @endforeach
                                        </dl>
                                        <div class="ndb:space-y-2">
                                            @forelse ($section['payload']['items'] as $index => $item)
                                                <article wire:key="queue-{{ $index }}" class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:px-3.5 ndb:py-3 {{ ($item['kind'] ?? null) === 'failed' ? 'ndb:border-red-200 ndb:bg-red-50/35 ndb:dark:border-red-950 ndb:dark:bg-red-950/15' : 'ndb:border-zinc-200 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30' }}">
                                                    <span class="ndb:w-16 ndb:shrink-0 ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:tracking-wider {{ ($item['kind'] ?? null) === 'failed' ? 'ndb:text-red-600 ndb:dark:text-red-300' : 'ndb:text-zinc-400' }}">{{ $item['kind'] }}</span>
                                                    <div class="ndb:min-w-0 ndb:flex-1"><code class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold">{{ $item['job'] }}</code><p class="ndb:mt-1 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400"><span>{{ $item['connection'] }}</span><span>{{ $item['queue'] ?: 'default queue' }}</span>@if (($item['attempt'] ?? null) !== null)<span>Attempt {{ $item['attempt'] }}</span>@endif</p></div>
                                                    @if (($item['kind'] ?? null) !== 'queued')<span class="ndb:shrink-0 ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $item['duration_ms'] }} ms</span>@elseif (($item['delay_seconds'] ?? null) !== null)<span class="ndb:shrink-0 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">{{ $item['delay_seconds'] }} s delay</span>@endif
                                                </article>
                                            @empty
                                                <x-new-debug-bar::empty-state label="No queue activity was captured." />
                                            @endforelse
                                        </div>
                                    @elseif ($sectionKey === 'mail')
                                        <dl class="ndb:grid ndb:grid-cols-3 ndb:divide-x ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                                            @foreach ([['Messages', $section['summary']['count']], ['Recipients', $section['summary']['recipient_count']], ['Attachments', $section['summary']['attachment_count']]] as [$label, $value])
                                                <div class="ndb:px-3.5 ndb:py-3"><dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</dt><dd class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $value }}</dd></div>
                                            @endforeach
                                        </dl>
                                        <div class="ndb:space-y-2">
                                            @forelse ($section['payload']['items'] as $index => $item)
                                                <article wire:key="mail-{{ $index }}" class="ndb:min-w-0 ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:bg-white/45 ndb:px-3.5 ndb:py-3 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30">
                                                    <div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3">
                                                        <div class="ndb:min-w-0 ndb:flex-1"><code class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold">{{ $item['source'] ?: 'Mail message' }}</code><p class="ndb:mt-1 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400"><span>{{ $item['recipient_count'] }} recipients</span><span>{{ $item['attachment_count'] }} attachments</span><span>{{ $item['has_html'] ? 'HTML' : 'No HTML' }}</span><span>{{ $item['has_text'] ? 'Text' : 'No text' }}</span></p></div>
                                                        <span class="ndb:shrink-0 ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $item['duration_ms'] }} ms</span>
                                                    </div>
                                                    @if (is_array($item['preview'] ?? null))
                                                        <div class="ndb:mt-3 ndb:space-y-2 ndb:border-t ndb:border-zinc-200 ndb:pt-3 ndb:dark:border-zinc-800">
                                                            <p class="ndb:text-xs ndb:font-semibold">{{ $item['preview']['subject'] ?: '(No subject)' }}</p>
                                                            <p class="ndb:break-all ndb:text-[10px] ndb:text-zinc-500 ndb:dark:text-zinc-400">To: {{ implode(', ', $item['preview']['to']) ?: '(none)' }}</p>
                                                            <div class="ndb:flex ndb:flex-wrap ndb:gap-2">
                                                                @if (is_string($item['preview']['html'] ?? null))<a href="{{ route('new-debug-bar.mail-preview', ['profile' => $profileId, 'index' => $index, 'format' => 'html']) }}" target="_blank" rel="noreferrer" class="ndb:rounded-md ndb:border ndb:border-zinc-200 ndb:px-2 ndb:py-1 ndb:text-[10px] ndb:font-semibold ndb:hover:bg-zinc-100 ndb:dark:border-zinc-700 ndb:dark:hover:bg-zinc-800">Open HTML preview</a>@endif
                                                                @if (is_string($item['preview']['text'] ?? null))<a href="{{ route('new-debug-bar.mail-preview', ['profile' => $profileId, 'index' => $index, 'format' => 'text']) }}" target="_blank" rel="noreferrer" class="ndb:rounded-md ndb:border ndb:border-zinc-200 ndb:px-2 ndb:py-1 ndb:text-[10px] ndb:font-semibold ndb:hover:bg-zinc-100 ndb:dark:border-zinc-700 ndb:dark:hover:bg-zinc-800">Open text preview</a>@endif
                                                                <a href="{{ route('new-debug-bar.mail-preview', ['profile' => $profileId, 'index' => $index, 'format' => 'eml']) }}" class="ndb:rounded-md ndb:border ndb:border-zinc-200 ndb:px-2 ndb:py-1 ndb:text-[10px] ndb:font-semibold ndb:hover:bg-zinc-100 ndb:dark:border-zinc-700 ndb:dark:hover:bg-zinc-800">Download .eml</a>
                                                            </div>
                                                            @if (($item['preview']['attachments_omitted'] ?? 0) > 0 || ($item['preview']['addresses_omitted'] ?? 0) > 0 || ($item['preview']['truncated'] ?? false))
                                                                <p class="ndb:text-[10px] ndb:font-semibold ndb:text-amber-700 ndb:dark:text-amber-300">@if (($item['preview']['attachments_omitted'] ?? 0) > 0){{ $item['preview']['attachments_omitted'] }} attachments omitted.@endif @if (($item['preview']['addresses_omitted'] ?? 0) > 0){{ $item['preview']['addresses_omitted'] }} addresses omitted.@endif @if ($item['preview']['truncated'] ?? false)Preview content was bounded.@endif</p>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </article>
                                            @empty
                                                <x-new-debug-bar::empty-state label="No mail was sent." />
                                            @endforelse
                                        </div>
                                    @elseif ($sectionKey === 'notifications')
                                        <dl class="ndb:grid ndb:grid-cols-2 ndb:divide-x ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                                            @foreach ([['Sent', $section['summary']['sent_count']], ['Failed', $section['summary']['failed_count']]] as [$label, $value])
                                                <div class="ndb:px-3.5 ndb:py-3"><dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</dt><dd class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $value }}</dd></div>
                                            @endforeach
                                        </dl>
                                        <div class="ndb:space-y-2">
                                            @forelse ($section['payload']['items'] as $index => $item)
                                                <article wire:key="notification-{{ $index }}" class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:px-3.5 ndb:py-3 {{ $item['status'] === 'failed' ? 'ndb:border-red-200 ndb:bg-red-50/35 ndb:dark:border-red-950 ndb:dark:bg-red-950/15' : 'ndb:border-zinc-200 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30' }}">
                                                    <span class="ndb:w-12 ndb:shrink-0 ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:tracking-wider {{ $item['status'] === 'failed' ? 'ndb:text-red-600 ndb:dark:text-red-300' : 'ndb:text-emerald-600 ndb:dark:text-emerald-300' }}">{{ $item['status'] }}</span>
                                                    <div class="ndb:min-w-0 ndb:flex-1"><code class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold">{{ $item['notification'] }}</code><p class="ndb:mt-1 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400"><span>{{ $item['channel'] }}</span><span>{{ $item['notifiable_type'] }}</span></p></div>
                                                </article>
                                            @empty
                                                <x-new-debug-bar::empty-state label="No notifications were sent." />
                                            @endforelse
                                        </div>
                                    @elseif ($sectionKey === 'redis')
                                        <dl class="ndb:grid ndb:grid-cols-3 ndb:divide-x ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                                            @foreach ([['Commands', $section['summary']['count']], ['Total time', $section['summary']['duration_ms'].' ms'], ['Failures', $section['summary']['failed_count']]] as [$label, $value])
                                                <div class="ndb:px-3.5 ndb:py-3"><dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</dt><dd class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $value }}</dd></div>
                                            @endforeach
                                        </dl>
                                        <div class="ndb:space-y-2">
                                            @forelse ($section['payload']['items'] as $index => $item)
                                                <article wire:key="redis-{{ $index }}" class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:px-3.5 ndb:py-3 {{ ($item['failed'] ?? false) ? 'ndb:border-red-200 ndb:bg-red-50/35 ndb:dark:border-red-950 ndb:dark:bg-red-950/15' : 'ndb:border-zinc-200 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30' }}">
                                                    <code class="ndb:w-20 ndb:shrink-0 ndb:text-xs ndb:font-bold">{{ $item['command'] }}</code>
                                                    <div class="ndb:min-w-0 ndb:flex-1"><p class="ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:gap-y-1 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400"><span>{{ $item['connection'] }}</span><span>{{ $item['key_policy'] ?? 'hash' }} keys</span></p>@if (($item['keys'] ?? []) !== [])<code class="ndb:mt-1 ndb:block ndb:truncate ndb:text-[10px]">{{ implode(', ', $item['keys']) }}</code>@elseif (($item['key_hashes'] ?? []) !== [])<code class="ndb:mt-1 ndb:block ndb:truncate ndb:text-[10px]">{{ implode(', ', $item['key_hashes']) }}</code>@else<p class="ndb:mt-1 ndb:text-[10px] ndb:text-zinc-400">No key metadata</p>@endif</div>
                                                    <span class="ndb:shrink-0 ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $item['duration_ms'] }} ms</span>
                                                </article>
                                            @empty
                                                <x-new-debug-bar::empty-state label="No direct Redis commands were captured." />
                                            @endforelse
                                        </div>
                                    @elseif ($sectionKey === 'authorization')
                                        <div class="ndb:space-y-2">
                                            @forelse ($section['payload']['items'] as $index => $item)
                                                <article wire:key="authorization-{{ $index }}" class="ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:px-3.5 ndb:py-3 {{ $item['result'] === 'allowed' ? 'ndb:border-emerald-200 ndb:bg-emerald-50/35 ndb:dark:border-emerald-950 ndb:dark:bg-emerald-950/15' : 'ndb:border-red-200 ndb:bg-red-50/35 ndb:dark:border-red-950 ndb:dark:bg-red-950/15' }}">
                                                    <span class="ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:tracking-wider {{ $item['result'] === 'allowed' ? 'ndb:text-emerald-700 ndb:dark:text-emerald-300' : 'ndb:text-red-700 ndb:dark:text-red-300' }}">{{ $item['result'] }}</span>
                                                    <code class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-bold">{{ $item['ability'] }}</code>
                                                    <span class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">{{ $item['handler'] }}</span>
                                                    <p class="ndb:w-full ndb:text-[10px] ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ implode(', ', array_filter([$item['user_type'] ?? null, ...($item['argument_types'] ?? [])])) ?: 'No typed arguments' }}</p>
                                                </article>
                                            @empty
                                                <x-new-debug-bar::empty-state label="No authorization decisions were captured." />
                                            @endforelse
                                        </div>
                                    @elseif ($sectionKey === 'validation')
                                        <div class="ndb:space-y-3">
                                            @forelse ($section['payload']['items'] as $index => $item)
                                                <article wire:key="validation-{{ $index }}" class="ndb:rounded-xl ndb:border ndb:border-amber-200 ndb:bg-amber-50/35 ndb:p-4 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/15">
                                                    <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-2"><span class="ndb:text-xs ndb:font-bold">{{ count($item['fields']) }} invalid fields</span><span class="ndb:ml-auto ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:gap-y-1 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400"><span>{{ $item['error_bag'] }} bag</span><span>HTTP {{ $item['response_status'] }}</span></span></div>
                                                    <dl class="ndb:mt-3 ndb:space-y-2">@foreach ($item['rules'] as $field => $rules)<div class="ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:items-center ndb:gap-2"><dt class="ndb:min-w-24 ndb:font-mono ndb:text-[10px] ndb:font-bold">{{ $field }}</dt><dd class="ndb:flex ndb:flex-wrap ndb:gap-1">@foreach ($rules as $rule)<span class="ndb:rounded-md ndb:bg-amber-100 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:font-semibold ndb:text-amber-800 ndb:dark:bg-amber-950 ndb:dark:text-amber-300">{{ $rule }}</span>@endforeach</dd></div>@endforeach</dl>
                                                </article>
                                            @empty
                                                <x-new-debug-bar::empty-state label="No validation failures were captured." success />
                                            @endforelse
                                        </div>
                                    @elseif ($sectionKey === 'lifecycle')
                                        <div class="ndb:space-y-2">
                                            @if (($profile['sections']['request']['payload']['timing_scope'] ?? null) === 'global_middleware_entry')
                                                <p data-ndb-lifecycle-scope class="ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-zinc-50/70 ndb:px-3 ndb:py-2 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/60 ndb:dark:text-zinc-400">Timing starts at the debug middleware. Early Laravel bootstrap is not measured.</p>
                                            @endif
                                            @forelse ($section['payload']['items'] as $index => $item)
                                                <article wire:key="lifecycle-{{ $index }}" class="ndb:flex ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:px-3.5 ndb:py-3 ndb:dark:border-zinc-800"><span class="ndb:text-[10px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-400">#{{ $index + 1 }}</span><span class="ndb:min-w-0 ndb:flex-1 ndb:text-xs ndb:font-semibold">{{ $item['name'] }}</span><span class="ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $item['duration_ms'] }} ms</span></article>
                                            @empty
                                                <x-new-debug-bar::empty-state label="Laravel did not expose lifecycle spans for this profile." />
                                            @endforelse
                                        </div>
                                    @elseif ($sectionKey === 'messages')
                                        <div class="ndb:space-y-3">
                                            @forelse ($section['payload']['items'] as $index => $item)
                                                <article wire:key="message-{{ $index }}" class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"><div class="ndb:flex ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3"><span class="ndb:min-w-0 ndb:flex-1 ndb:text-xs ndb:font-bold">{{ $item['label'] }}</span><span class="ndb:text-[10px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400">{{ $item['at_ms'] }} ms</span></div>@if (($item['context'] ?? []) !== [])<pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="json">{{ json_encode($item['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>@endif</article>
                                            @empty
                                                <x-new-debug-bar::empty-state label="No developer messages were recorded." />
                                            @endforelse
                                        </div>
                                    @elseif ($sectionKey === 'models')
                                        <div class="ndb:grid ndb:grid-cols-2 ndb:gap-3"><div class="ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:p-3 ndb:dark:border-zinc-800"><p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Model classes</p><p class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $section['summary']['model_classes'] }}</p></div><div class="ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:p-3 ndb:dark:border-zinc-800"><p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Lifecycle events</p><p class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ count($section['summary']['lifecycle_events']) }}</p></div></div>
                                        @forelse ($section['payload']['groups'] as $index => $group)
                                            <details data-ndb-model-group data-count="{{ $group['count'] }}" wire:key="model-group-{{ $index }}" class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"><summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3"><span class="ndb:min-w-0 ndb:flex-1"><span class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold">{{ $group['model'] }}</span><span class="ndb:mt-0.5 ndb:block ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">{{ str($group['event'])->title() }}</span></span><span class="ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $group['count'] }}</span><x-new-debug-bar::icon name="chevron-down" class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180" /></summary><pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="json">{{ json_encode($group['items'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre></details>
                                        @empty
                                            <x-new-debug-bar::empty-state label="No model activity was captured." />
                                        @endforelse
                                    @elseif ($sectionKey === 'cache')
                                        <dl class="ndb:grid ndb:grid-cols-2 ndb:divide-x ndb:divide-y ndb:divide-zinc-200 ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:sm:grid-cols-4 ndb:sm:divide-y-0 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">@foreach ([['Hit rate', $section['summary']['hit_rate'].'%'], ['Hits', $section['summary']['hits']], ['Misses', $section['summary']['misses']], ['Writes', $section['summary']['writes']]] as [$label, $value])<div class="ndb:px-3 ndb:py-2.5"><dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</dt><dd class="ndb:mt-0.5 ndb:text-sm ndb:font-bold ndb:tabular-nums">{{ $value }}</dd></div>@endforeach</dl>
                                        @if ($section['payload']['repeated_misses'] !== [])<div class="ndb:rounded-xl ndb:border ndb:border-amber-200 ndb:bg-amber-50/40 ndb:p-4 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/20"><h3 class="ndb:text-xs ndb:font-bold">Repeated misses</h3><div class="ndb:mt-3 ndb:space-y-2">@foreach ($section['payload']['repeated_misses'] as $miss)<div class="ndb:flex ndb:items-center ndb:gap-3"><code class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-[10px]">{{ $miss['key_hash'] }}</code><span class="ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $miss['count'] }} misses</span></div>@endforeach</div></div>@endif
                                        <div class="ndb:space-y-2">@foreach ($section['payload']['items'] as $index => $item)<details wire:key="cache-{{ $index }}" class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"><summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3 ndb:text-xs ndb:font-semibold"><span class="ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:text-zinc-400">{{ $item['operation'] }}</span><code class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-[10px]">{{ ($item['key_policy'] ?? 'hash') === 'full' ? ($item['key'] ?? 'No key') : ($item['key_hash'] ?? 'No key') }}</code><span class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:text-zinc-400">{{ $item['key_policy'] ?? 'hash' }}</span><x-new-debug-bar::icon name="chevron-down" class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180" /></summary><pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="json">{{ json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre></details>@endforeach</div>
                                    @elseif ($sectionKey === 'views')
                                        <div class="ndb:flex ndb:items-end ndb:justify-between ndb:border-b ndb:border-zinc-200 ndb:pb-3 ndb:dark:border-zinc-800"><div><p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Unique views</p><p class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $section['summary']['unique_views'] }}</p></div><p class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">Timing appears when Laravel exposes reliable render spans.</p></div>
                                        @forelse ($section['payload']['groups'] as $index => $group)
                                            <details wire:key="view-group-{{ $index }}" class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800">
                                                <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3"><span class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-bold">{{ $group['name'] }}</span><span class="ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $group['count'] }}</span><x-new-debug-bar::icon name="chevron-down" class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180" /></summary>
                                                <div class="ndb:space-y-2 ndb:border-t ndb:border-zinc-200 ndb:p-3 ndb:dark:border-zinc-800">@foreach ($group['items'] as $view)<article class="ndb:rounded-lg ndb:bg-zinc-50/70 ndb:p-3 ndb:dark:bg-zinc-900/60"><div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3"><span class="ndb:text-[9px] ndb:font-bold ndb:text-zinc-400">#{{ $view['render_order'] }}</span><code class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-[10px]">{{ $view['source']['file'] ?? 'Source not exposed' }}@if (isset($view['source']['line'])):{{ $view['source']['line'] }}@endif</code>@if (is_string($view['source']['editor_url'] ?? null))<a href="{{ $view['source']['editor_url'] }}" class="ndb:text-[10px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300">Open in editor</a>@endif</div><p class="ndb:mt-2 ndb:text-[10px] ndb:text-zinc-500 ndb:dark:text-zinc-400">Data keys: {{ implode(', ', $view['data_keys'] ?? []) ?: 'none' }}</p>@if (($view['composers'] ?? []) !== [])<div class="ndb:mt-2 ndb:flex ndb:flex-wrap ndb:gap-2">@foreach ($view['composers'] as $composer)<span class="ndb:text-[10px] ndb:font-semibold">{{ $composer['name'] }} @if (is_string($composer['source']['editor_url'] ?? null))<a href="{{ $composer['source']['editor_url'] }}" class="ndb:text-indigo-600 ndb:dark:text-indigo-300">Open</a>@endif</span>@endforeach</div>@endif</article>@endforeach</div>
                                            </details>
                                        @empty
                                            <x-new-debug-bar::empty-state label="No views were captured." />
                                        @endforelse
                                    @elseif ($sectionKey === 'events')
                                        <div class="ndb:flex ndb:flex-col ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:pb-3 ndb:sm:flex-row ndb:sm:items-end ndb:dark:border-zinc-800"><div class="ndb:flex-1"><p class="ndb:mb-1.5 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Source</p><div class="ndb:flex">@foreach (['all' => 'All', 'application' => 'Application', 'framework' => 'Framework'] as $source => $label)<button type="button" data-ndb-event-source="{{ $source }}" @click="setEventSource(@js($source))" class="ndb:border-b-2 ndb:px-3 ndb:py-1.5 ndb:text-xs ndb:font-semibold" :class="eventSource === @js($source) ? 'ndb:border-indigo-500 ndb:text-indigo-700 ndb:dark:text-indigo-300' : 'ndb:border-transparent ndb:text-zinc-500 ndb:dark:text-zinc-400'">{{ $label }}</button>@endforeach</div></div><label class="ndb:sm:w-72"><span class="ndb:mb-1.5 ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Search</span><input data-ndb-event-search x-model="eventSearch" @input.debounce.100ms="applyEventFilters()" type="search" placeholder="Event name" class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:outline-none ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70" /></label></div><p class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400"><span x-text="visibleEventCount"></span> events</p>
                                        <div x-ref="eventList" class="ndb:space-y-2">@foreach ($section['payload']['items'] as $index => $item)<details data-ndb-event-item data-source="{{ $item['source'] }}" data-search="{{ mb_strtolower($item['name']) }}" wire:key="event-{{ $index }}" class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"><summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3"><span class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-semibold">{{ $item['name'] }}</span>@if ($item['broadcast'] ?? false)<span class="ndb:text-[9px] ndb:font-bold ndb:text-indigo-600 ndb:dark:text-indigo-300">Broadcast</span>@endif<span class="ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:text-zinc-400">{{ $item['source'] }}</span><x-new-debug-bar::icon name="chevron-down" class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180" /></summary><div class="ndb:space-y-2 ndb:border-t ndb:border-zinc-200 ndb:p-3 ndb:dark:border-zinc-800">@forelse ($item['listeners'] ?? [] as $listener)<div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3"><code class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-[10px]">{{ $listener['name'] }}</code>@if (is_string($listener['source']['editor_url'] ?? null))<a href="{{ $listener['source']['editor_url'] }}" class="ndb:text-[10px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300">Open in editor</a>@endif</div>@empty<p class="ndb:text-[10px] ndb:text-zinc-400">No application listener source was exposed.</p>@endforelse</div></details>@endforeach</div><div x-show.important="visibleEventCount === 0"><x-new-debug-bar::empty-state label="No events match these filters." /></div>
                                    @elseif ($sectionKey === 'logs')
                                        @php($logLevels = array_values(array_unique(array_column($section['payload']['items'], 'level'))))
                                        <div class="ndb:flex ndb:flex-col ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:pb-3 ndb:sm:flex-row ndb:sm:items-end ndb:dark:border-zinc-800"><div class="ndb:min-w-0 ndb:flex-1"><p class="ndb:mb-1.5 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Level</p><div class="ndb:flex ndb:overflow-x-auto"><button type="button" data-ndb-log-level="all" @click="setLogLevel('all')" class="ndb:border-b-2 ndb:px-3 ndb:py-1.5 ndb:text-xs ndb:font-semibold" :class="logLevel === 'all' ? 'ndb:border-indigo-500 ndb:text-indigo-700 ndb:dark:text-indigo-300' : 'ndb:border-transparent ndb:text-zinc-500 ndb:dark:text-zinc-400'">All</button>@foreach ($logLevels as $level)<button type="button" data-ndb-log-level="{{ $level }}" @click="setLogLevel(@js($level))" class="ndb:border-b-2 ndb:px-3 ndb:py-1.5 ndb:text-xs ndb:font-semibold" :class="logLevel === @js($level) ? 'ndb:border-indigo-500 ndb:text-indigo-700 ndb:dark:text-indigo-300' : 'ndb:border-transparent ndb:text-zinc-500 ndb:dark:text-zinc-400'">{{ strtoupper($level) }}</button>@endforeach</div></div><label class="ndb:sm:w-72"><span class="ndb:mb-1.5 ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Search</span><input data-ndb-log-search x-model="logSearch" @input.debounce.100ms="applyLogFilters()" type="search" placeholder="Log message" class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:outline-none ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70" /></label></div><p class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400"><span x-text="visibleLogCount"></span> logs</p>
                                        <div x-ref="logList" class="ndb:space-y-2">
                                            @foreach ($section['payload']['items'] as $index => $item)
                                                @php($logCallsite = $item['callsite'] ?? null)
                                                <details data-ndb-log-item data-level="{{ $item['level'] }}" data-search="{{ mb_strtolower($item['message']) }}" wire:key="log-{{ $index }}" class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800">
                                                    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3"><span class="ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:text-zinc-400">{{ $item['level'] }}</span><span class="ndb:min-w-0 ndb:flex-1"><span class="ndb:block ndb:truncate ndb:text-xs ndb:font-semibold">{{ $item['message'] }}</span>@if ($logCallsite)<span class="ndb:mt-0.5 ndb:block ndb:truncate ndb:text-[10px] ndb:text-zinc-400">{{ $logCallsite['file'] }}:{{ $logCallsite['line'] }}</span>@endif</span><span class="ndb:text-[10px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-400">{{ $item['at_ms'] }} ms</span><x-new-debug-bar::icon name="chevron-down" class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180" /></summary>
                                                    @if ($logCallsite)<div class="ndb:flex ndb:justify-end ndb:gap-3 ndb:border-t ndb:border-zinc-200 ndb:px-3 ndb:py-2 ndb:dark:border-zinc-800"><button type="button" data-ndb-copy-log-callsite="{{ $index }}" @click="copyText(@js($logCallsite['file'].':'.$logCallsite['line']))" class="ndb:text-[10px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300">Copy file and line</button>@if (is_string($logCallsite['editor_url'] ?? null))<a href="{{ $logCallsite['editor_url'] }}" class="ndb:text-[10px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300">Open in editor</a>@endif</div>@endif
                                                    <pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="json">{{ json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                                </details>
                                            @endforeach
                                        </div><div x-show.important="visibleLogCount === 0"><x-new-debug-bar::empty-state label="No logs match these filters." /></div>
                                    @elseif ($sectionKey === 'exceptions')
                                        @forelse ($section['payload']['items'] as $index => $exception)
                                            <article wire:key="exception-{{ $index }}" class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-red-200 ndb:dark:border-red-950">
                                                <div class="ndb:flex ndb:items-start ndb:gap-3 ndb:bg-red-50 ndb:p-4 ndb:dark:bg-red-950/50"><span class="ndb:grid ndb:size-8 ndb:shrink-0 ndb:place-items-center ndb:rounded-lg ndb:bg-red-100 ndb:text-red-600 ndb:dark:bg-red-950 ndb:dark:text-red-300"><x-new-debug-bar::icon name="warning" class="ndb:size-4" /></span><div class="ndb:min-w-0 ndb:flex-1"><p class="ndb:truncate ndb:text-xs ndb:font-bold ndb:text-red-700 ndb:dark:text-red-300">{{ $exception['class'] }}</p><p class="ndb:mt-1 ndb:text-sm ndb:font-semibold">{{ $exception['message'] ?: 'No message' }}</p><div class="ndb:mt-1 ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3"><button type="button" data-ndb-copy-exception-callsite="{{ $index }}" @click="copyText(@js($exception['file'].':'.$exception['line']))" class="ndb:min-w-0 ndb:truncate ndb:text-left ndb:text-[10px] ndb:text-zinc-500 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500">{{ $exception['file'] }}:{{ $exception['line'] }}</button>@if (is_string($exception['location']['editor_url'] ?? null))<a href="{{ $exception['location']['editor_url'] }}" class="ndb:shrink-0 ndb:text-[10px] ndb:font-bold ndb:text-red-700 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-red-300">Open in editor</a>@endif</div></div></div>
                                                @if ($exception['source'] ?? null)
                                                    @php($sourceText = implode("\n", array_map(fn (array $line): string => sprintf('%4d%s %s', $line['number'], $line['focus'] ? '>' : ' ', $line['code']), $exception['source']['lines'])))
                                                    <pre class="ndb-code ndb-scrollbar ndb:max-h-72 ndb:rounded-none ndb:border-b ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="php">{{ $sourceText }}</code></pre>
                                                @endif
                                                <div class="ndb:p-4">
                                                    <h3 class="ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Application frames</h3>
                                                    <ol class="ndb:mt-3 ndb:list-none ndb:space-y-2">@forelse ($exception['frames']['application'] ?? [] as $frame)<li class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:text-xs"><code class="ndb:min-w-0 ndb:flex-1 ndb:truncate">{{ $frame['file'] }}:{{ $frame['line'] }}</code><span class="ndb:max-w-[35%] ndb:truncate ndb:text-zinc-400">{{ $frame['function'] }}</span>@if (is_string($frame['editor_url'] ?? null))<a href="{{ $frame['editor_url'] }}" class="ndb:text-[10px] ndb:font-bold ndb:text-indigo-600 ndb:dark:text-indigo-300">Open</a>@endif</li>@empty<li class="ndb:text-xs ndb:text-zinc-400">No application frames were captured.</li>@endforelse</ol>
                                                    <details class="ndb:group ndb:mt-4 ndb:border-t ndb:border-zinc-200 ndb:pt-3 ndb:dark:border-zinc-800"><summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-2 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"><span class="ndb:flex-1">Vendor frames ({{ count($exception['frames']['vendor'] ?? []) }})</span><x-new-debug-bar::icon name="chevron-down" class="ndb:size-3.5 ndb:transition ndb:group-open:rotate-180" /></summary><ol class="ndb:mt-3 ndb:list-none ndb:space-y-2">@foreach ($exception['frames']['vendor'] ?? [] as $frame)<li class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:text-xs"><code class="ndb:min-w-0 ndb:flex-1 ndb:truncate">{{ $frame['file'] }}:{{ $frame['line'] }}</code><span class="ndb:max-w-[35%] ndb:truncate ndb:text-zinc-400">{{ $frame['function'] }}</span>@if (is_string($frame['editor_url'] ?? null))<a href="{{ $frame['editor_url'] }}" class="ndb:text-[10px] ndb:font-bold ndb:text-indigo-600 ndb:dark:text-indigo-300">Open</a>@endif</li>@endforeach</ol></details>
                                                </div>
                                            </article>
                                        @empty
                                            <x-new-debug-bar::empty-state label="No exceptions were reported." success />
                                        @endforelse
                                    @elseif ($sectionKey !== 'overview')
                                        @forelse ($section['payload']['items'] as $index => $item)
                                            <details wire:key="{{ $sectionKey }}-{{ $index }}" class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800">
                                                <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3 ndb:text-xs ndb:font-semibold ndb:transition ndb:hover:bg-zinc-50 ndb:dark:hover:bg-zinc-900"><span class="ndb:text-[10px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-400">#{{ $index + 1 }}</span><span class="ndb:min-w-0 ndb:flex-1 ndb:truncate">{{ $item['model'] ?? $item['name'] ?? $item['event'] ?? $item['level'] ?? $item['operation'] ?? $section['label'] }}</span><x-new-debug-bar::icon name="chevron-down" class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180" /></summary>
                                                <pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="json">{{ json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                            </details>
                                        @empty
                                            <x-new-debug-bar::empty-state :label="'No '.strtolower($section['label']).' were captured.'" />
                                        @endforelse
                                    @endif
                                </section>
                            @endforeach

                            <section data-ndb-section-panel="history" hidden wire:key="section-history" class="ndb:space-y-4">
                                @if ($discoveredProfileId !== null)<div class="ndb:rounded-lg ndb:border ndb:border-indigo-200 ndb:bg-indigo-50/60 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-semibold ndb:text-indigo-800 ndb:dark:border-indigo-900 ndb:dark:bg-indigo-950/30 ndb:dark:text-indigo-200">A background request was added to History.</div>@endif
                                <div class="ndb:grid ndb:gap-3 ndb:md:grid-cols-3">
                                    <label><span class="ndb:mb-1.5 ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Path</span><input data-ndb-history-path x-model="historyPath" @input.debounce.100ms="applyHistoryFilters()" type="search" placeholder="Filter by path" class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:outline-none ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70" /></label>
                                    <label><span class="ndb:mb-1.5 ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Method</span><input data-ndb-history-method x-model="historyMethod" @input.debounce.100ms="applyHistoryFilters()" type="search" placeholder="GET" class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:uppercase ndb:outline-none ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70" /></label>
                                    <label><span class="ndb:mb-1.5 ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Status</span><input data-ndb-history-status x-model="historyStatus" @input.debounce.100ms="applyHistoryFilters()" inputmode="numeric" placeholder="200" class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:outline-none ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70" /></label>
                                </div>
                                <div class="ndb:flex ndb:items-center ndb:gap-1 ndb:border-b ndb:border-zinc-200/80 ndb:pb-2 ndb:dark:border-zinc-800">
                                    @foreach (['all' => 'All', 'warning' => 'Warnings', 'clean' => 'Clean'] as $filter => $label)
                                        <button type="button" data-ndb-history-warning="{{ $filter }}" @click="setHistoryWarning(@js($filter))" :aria-pressed="historyWarning === @js($filter)" class="ndb:border-b-2 ndb:px-3 ndb:py-1.5 ndb:text-xs ndb:font-semibold ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500" :class="historyWarning === @js($filter) ? 'ndb:border-indigo-500 ndb:text-indigo-700 ndb:dark:text-indigo-300' : 'ndb:border-transparent ndb:text-zinc-500 ndb:dark:text-zinc-400'">{{ $label }}</button>
                                    @endforeach
                                    <span class="ndb:ml-auto ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400"><span x-text="visibleHistoryCount"></span> profiles</span>
                                </div>

                                @if ($comparison !== [])
                                    <div data-ndb-comparison class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-indigo-200/90 ndb:bg-indigo-50/30 ndb:dark:border-indigo-950 ndb:dark:bg-indigo-950/20">
                                        <div class="ndb:flex ndb:items-center ndb:gap-3 ndb:border-b ndb:border-indigo-200/70 ndb:px-4 ndb:py-3 ndb:dark:border-indigo-950"><div class="ndb:min-w-0 ndb:flex-1"><h3 class="ndb:text-xs ndb:font-bold">Comparison</h3><p class="ndb:mt-0.5 ndb:truncate ndb:text-[10px] ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $comparison['path'] }}</p></div><button type="button" wire:click="clearComparison" class="ndb:text-[10px] ndb:font-bold ndb:text-indigo-700 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300">Clear</button></div>
                                        <div class="ndb:grid ndb:grid-cols-2 ndb:divide-x ndb:divide-y ndb:divide-indigo-200/70 ndb:lg:grid-cols-4 ndb:dark:divide-indigo-950">
                                            @foreach ($comparison['metrics'] as $metric)
                                                <div class="ndb:px-3 ndb:py-2.5"><p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $metric['label'] }}</p><p class="ndb:mt-1 ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $metric['current'] }}{{ $metric['unit'] !== '' ? ' '.$metric['unit'] : '' }}</p><p class="ndb:mt-0.5 ndb:text-[10px] ndb:font-semibold ndb:tabular-nums {{ $metric['delta'] > 0 ? 'ndb:text-amber-700 ndb:dark:text-amber-300' : ($metric['delta'] < 0 ? 'ndb:text-emerald-700 ndb:dark:text-emerald-300' : 'ndb:text-zinc-400') }}">{{ $metric['delta'] > 0 ? '+' : '' }}{{ $metric['delta'] }}{{ $metric['unit'] !== '' ? ' '.$metric['unit'] : '' }}</p></div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div x-ref="historyList" class="ndb:space-y-2">
                                    @foreach ($history as $entry)
                                        <article
                                            data-ndb-history-profile="{{ $entry['id'] }}"
                                            data-path="{{ mb_strtolower($entry['path']) }}"
                                            data-method="{{ $entry['method'] }}"
                                            data-status="{{ $entry['status'] }}"
                                            data-warning="{{ $entry['warning'] ? 'true' : 'false' }}"
                                            class="ndb:flex ndb:flex-col ndb:gap-3 ndb:rounded-xl ndb:border ndb:px-3.5 ndb:py-3 ndb:sm:flex-row ndb:sm:items-center {{ $entry['is_current'] ? 'ndb:border-indigo-200 ndb:bg-indigo-50/40 ndb:dark:border-indigo-950 ndb:dark:bg-indigo-950/20' : 'ndb:border-zinc-200/90 ndb:bg-white/50 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30' }}"
                                        >
                                            <div class="ndb:min-w-0 ndb:flex-1"><div class="ndb:flex ndb:items-center ndb:gap-2"><span class="ndb:rounded-md ndb:bg-indigo-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:font-bold ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300">{{ $entry['method'] }}</span><p class="ndb:truncate ndb:text-xs ndb:font-bold">{{ $entry['path'] }}</p>@if ($entry['is_current'])<span class="ndb:text-[9px] ndb:font-bold ndb:text-indigo-600 ndb:dark:text-indigo-300">Current</span>@endif</div><div class="ndb:mt-1.5 ndb:flex ndb:flex-wrap ndb:gap-x-4 ndb:gap-y-1 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400"><span>{{ $entry['status'] }}</span><span>{{ str($entry['request_type'])->replace('_', ' ')->title() }}</span><span>{{ $entry['duration_ms'] }} ms</span><span>{{ $entry['peak_memory_mb'] }} MB</span><span>{{ $entry['query_count'] }} queries</span><span>{{ $entry['finding_count'] }} findings</span></div></div>
                                            @if ($entry['comparable'])
                                                <button type="button" data-ndb-compare-profile="{{ $entry['id'] }}" wire:click="compareWith('{{ $entry['id'] }}')" wire:loading.attr="disabled" class="ndb:self-start ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:px-3 ndb:py-2 ndb:text-[10px] ndb:font-bold ndb:text-zinc-600 ndb:transition ndb:hover:border-indigo-300 ndb:hover:text-indigo-700 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:opacity-50 ndb:sm:self-center ndb:dark:border-zinc-700 ndb:dark:text-zinc-300 ndb:dark:hover:border-indigo-800 ndb:dark:hover:text-indigo-300">Compare</button>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                                <div x-show.important="visibleHistoryCount === 0"><x-new-debug-bar::empty-state label="No retained profiles match these filters." /></div>
                            </section>
                        </div>
                    @elseif ($detailsLoaded)
                        <div class="ndb:p-8 ndb:text-center"><p class="ndb:text-sm ndb:font-semibold">This profile is no longer available.</p></div>
                    @endif
                </main>
            </div>
        </aside>
    </div>

    <div x-cloak x-show.important="paletteOpen" class="ndb:pointer-events-auto ndb:fixed ndb:inset-0 ndb:z-50 ndb:grid ndb:justify-items-center ndb:bg-zinc-950/45 ndb:px-3 ndb:pt-[12vh] ndb:backdrop-blur-sm" @click.self="closePalette()">
        <div x-show.important="paletteOpen" x-transition @keydown="keepFocusWithin($event, $el)" class="ndb:w-full ndb:max-w-xl ndb:self-start ndb:overflow-hidden ndb:rounded-2xl ndb:border ndb:border-white/70 ndb:bg-white/90 ndb:shadow-2xl ndb:backdrop-blur-2xl ndb:dark:border-zinc-700/80 ndb:dark:bg-zinc-900/90" role="dialog" aria-modal="true" aria-label="Command palette">
            <div class="ndb:flex ndb:items-center ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:px-4 ndb:dark:border-zinc-800"><x-new-debug-bar::icon name="search" class="ndb:size-5 ndb:text-zinc-400" /><input data-ndb-palette-search x-ref="paletteSearch" x-model="paletteSearch" @input="paletteIndex = 0" @keydown.down.prevent="movePalette(1)" @keydown.up.prevent="movePalette(-1)" @keydown.enter.prevent="runActiveCommand()" type="search" placeholder="Jump to a section or change a setting…" class="ndb:h-14 ndb:min-w-0 ndb:flex-1 ndb:border-0 ndb:bg-transparent ndb:text-sm ndb:font-medium ndb:outline-none ndb:placeholder:text-zinc-400" /><kbd class="ndb:rounded-md ndb:border ndb:border-zinc-200 ndb:bg-zinc-50 ndb:px-1.5 ndb:py-1 ndb:text-[9px] ndb:font-bold ndb:text-zinc-400 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-800">ESC</kbd></div>
            <div class="ndb-scrollbar ndb:max-h-[min(420px,60vh)] ndb:overflow-y-auto ndb:p-2">
                <template x-for="(command, index) in filteredCommands" :key="command.id">
                    <button type="button" :data-ndb-command="command.id" @mouseenter="paletteIndex = index" @click="runCommand(command.id)" class="ndb:flex ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:px-3 ndb:py-2.5 ndb:text-left ndb:transition" :class="paletteIndex === index ? 'ndb:bg-blue-100/60 ndb:text-blue-700 ndb:dark:bg-indigo-950/60 ndb:dark:text-indigo-200' : 'ndb:text-zinc-700 ndb:dark:text-zinc-300'"><span class="ndb:flex-1 ndb:text-sm ndb:font-semibold" x-text="command.label"></span><span class="ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400" x-text="command.hint"></span></button>
                </template>
                <p x-show.important="filteredCommands.length === 0" class="ndb:px-3 ndb:py-8 ndb:text-center ndb:text-sm ndb:text-zinc-500">No matching commands.</p>
            </div>
            <div class="ndb:flex ndb:items-center ndb:gap-3 ndb:border-t ndb:border-zinc-200 ndb:bg-zinc-50 ndb:px-4 ndb:py-2 ndb:text-[10px] ndb:font-medium ndb:text-zinc-400 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"><span>↑↓ Navigate</span><span>↵ Select</span><span class="ndb:ml-auto">⌘/Ctrl ⇧ P</span></div>
        </div>
    </div>
</div>
