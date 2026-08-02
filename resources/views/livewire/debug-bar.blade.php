@php
    $profile = $detailsLoaded ? $this->profile : [];
@endphp

<div
    id="new-debug-bar"
    wire:key="new-debug-bar-{{ $profileId }}"
    x-data="newDebugBar(@js($summary))"
    :data-theme="resolvedTheme"
    @keydown.window="handleShortcut($event)"
    @new-debug-bar-content-updated.window="$nextTick(() => { syncSectionPanels(); applyHistoryFilters(); applyTimelineFilters(); applyEventFilters(); applyLogFilters(); window.newDebugBarHighlight?.($root) })"
    class="ndb:pointer-events-none ndb:fixed ndb:inset-0 ndb:z-[2147483000] ndb:text-zinc-900 ndb:dark:text-zinc-100"
>
    <div
        x-cloak
        x-show.important="! inspectorOpen"
        x-transition.opacity.duration.150ms
        role="toolbar"
        aria-label="Debug toolbar"
        class="ndb:pointer-events-auto ndb:fixed ndb:bottom-3 ndb:left-1/2 ndb:flex ndb:max-w-[calc(100vw-24px)] ndb:-translate-x-1/2 ndb:items-stretch ndb:gap-1 ndb:rounded-[18px] ndb:border ndb:border-white/70 ndb:bg-white/70 ndb:p-1.5 ndb:shadow-[0_18px_60px_-18px_rgba(24,24,27,0.4)] ndb:backdrop-blur-2xl ndb:backdrop-brightness-150 ndb:backdrop-saturate-150 ndb:dark:border-zinc-700/70 ndb:dark:bg-zinc-900/70"
    >
        <x-new-debug-bar::toolbar-button section="request" data-ndb-toolbar="request" class="ndb:flex ndb:min-w-0 ndb:max-w-52" aria-label="Open request details">
            <span class="ndb:rounded-md ndb:bg-indigo-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300" x-text="summary.method"></span>
            <span class="ndb:min-w-0">
                <span class="ndb:block ndb:truncate ndb:text-xs ndb:font-semibold" x-text="summary.path"></span>
                <span class="ndb:block ndb:text-[10px] ndb:font-medium ndb:text-zinc-400" x-text="summary.status"></span>
            </span>
        </x-new-debug-bar::toolbar-button>

        <span class="ndb:my-1 ndb:w-px ndb:bg-zinc-200 ndb:dark:bg-zinc-700"></span>

        <x-new-debug-bar::toolbar-button section="overview" data-ndb-toolbar="environment" class="ndb:flex">
            <span class="ndb:size-2 ndb:rounded-full" :class="summary.warning ? 'ndb:bg-amber-500' : 'ndb:bg-emerald-500'"></span>
            <span><span class="ndb:hidden ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:sm:block">Environment</span><span class="ndb:block ndb:max-w-24 ndb:truncate ndb:text-[10px] ndb:font-bold ndb:sm:text-xs" x-text="summary.environment"></span></span>
        </x-new-debug-bar::toolbar-button>

        <x-new-debug-bar::toolbar-button section="request" data-ndb-toolbar="duration" class="ndb:hidden ndb:md:flex">
            <x-new-debug-bar::icon name="clock" class="ndb:size-3.5 ndb:text-indigo-500" />
            <span><span class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Duration</span><span class="ndb:block ndb:whitespace-nowrap ndb:text-xs ndb:font-bold ndb:tabular-nums" x-text="summary.duration_ms + ' ms'"></span></span>
        </x-new-debug-bar::toolbar-button>

        <x-new-debug-bar::toolbar-button section="overview" data-ndb-toolbar="memory" class="ndb:hidden ndb:lg:flex">
            <x-new-debug-bar::icon name="memory" class="ndb:size-3.5 ndb:text-indigo-500" />
            <span><span class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Peak</span><span class="ndb:block ndb:whitespace-nowrap ndb:text-xs ndb:font-bold ndb:tabular-nums" x-text="summary.memory_mb + ' MB'"></span></span>
        </x-new-debug-bar::toolbar-button>

        <x-new-debug-bar::toolbar-button section="queries" data-ndb-toolbar="queries" class="ndb:hidden ndb:sm:flex">
            <x-new-debug-bar::icon name="database" class="ndb:size-3.5 ndb:text-indigo-500" />
            <span><span class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Queries</span><span class="ndb:flex ndb:items-center ndb:gap-1.5 ndb:whitespace-nowrap ndb:text-xs ndb:font-bold ndb:tabular-nums"><span x-text="summary.query_count"></span><span class="ndb:font-medium ndb:text-zinc-400" x-text="summary.query_duration_ms + ' ms'"></span></span></span>
        </x-new-debug-bar::toolbar-button>

        <span class="ndb:my-1 ndb:w-px ndb:bg-zinc-200 ndb:dark:bg-zinc-700"></span>

        <div class="ndb:flex ndb:items-center ndb:gap-0.5">
            <x-new-debug-bar::icon-button name="search" data-ndb-toolbar="palette" @click="openPalette()" class="ndb:size-9 ndb:rounded-xl" aria-label="Open command palette" title="Command palette (Command or Control + Shift + P)" />
            <x-new-debug-bar::icon-button name="expand" data-ndb-toolbar="expand" @click="openInspector()" class="ndb:size-9 ndb:rounded-xl" aria-label="Expand inspector" title="Expand inspector" />
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
            @keydown="keepFocusWithin($event, $el)"
            class="ndb:absolute ndb:inset-x-0 ndb:bottom-0 ndb:flex ndb:h-[min(82vh,780px)] ndb:max-h-[calc(100vh-12px)] ndb:flex-col ndb:overflow-hidden ndb:rounded-t-2xl ndb:border-x ndb:border-t ndb:border-white/70 ndb:bg-white/90 ndb:shadow-[0_-24px_80px_-28px_rgba(24,24,27,0.5)] ndb:backdrop-blur-2xl ndb:dark:border-zinc-800/80 ndb:dark:bg-zinc-950/90"
        >
            <header class="ndb:flex ndb:h-13 ndb:shrink-0 ndb:items-center ndb:gap-3 ndb:border-b ndb:border-zinc-200/80 ndb:bg-white/55 ndb:pl-3 ndb:pr-2 ndb:backdrop-blur-xl ndb:sm:pl-4 ndb:dark:border-zinc-800/80 ndb:dark:bg-zinc-950/55">
                <span class="ndb:rounded-md ndb:bg-indigo-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300" x-text="summary.method"></span>
                <div class="ndb:min-w-0 ndb:flex-1">
                    <p class="ndb:truncate ndb:text-sm ndb:font-semibold" x-text="summary.path"></p>
                    <p class="ndb:flex ndb:items-center ndb:gap-2.5 ndb:text-[9px] ndb:text-zinc-500 ndb:dark:text-zinc-400"><span class="ndb:font-bold" x-text="summary.status"></span><span class="ndb:font-semibold ndb:uppercase ndb:tracking-wider" x-text="summary.environment"></span><span class="ndb:font-semibold ndb:tabular-nums" x-text="summary.duration_ms + ' ms'"></span></p>
                </div>
                <div class="ndb:flex ndb:items-center ndb:gap-0.5">
                    <x-new-debug-bar::icon-button name="search" data-ndb-inspector-action="palette" @click="openPalette()" class="ndb:size-9 ndb:rounded-lg" aria-label="Open command palette" />
                    <x-new-debug-bar::icon-button data-ndb-inspector-action="theme" @click="toggleTheme()" class="ndb:size-9 ndb:rounded-lg" ::aria-label="resolvedTheme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'" ::title="resolvedTheme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'"><span x-show.important="resolvedTheme !== 'dark'"><x-new-debug-bar::icon name="moon" class="ndb:size-4" /></span><span x-show.important="resolvedTheme === 'dark'"><x-new-debug-bar::icon name="sun" class="ndb:size-4" /></span></x-new-debug-bar::icon-button>
                    <x-new-debug-bar::icon-button name="close" data-ndb-inspector-action="close" x-ref="inspectorClose" @click="closeInspector()" class="ndb:size-9 ndb:rounded-lg" aria-label="Close inspector" />
                </div>
            </header>

            <div class="ndb:flex ndb:min-h-0 ndb:flex-1 ndb:flex-col ndb:sm:flex-row">
                <nav aria-label="Debug sections" class="ndb-scrollbar ndb:flex ndb:max-h-36 ndb:shrink-0 ndb:gap-0.5 ndb:overflow-x-auto ndb:border-b ndb:border-zinc-200/80 ndb:bg-zinc-50/70 ndb:p-2 ndb:backdrop-blur-xl ndb:sm:max-h-none ndb:sm:w-[210px] ndb:sm:flex-col ndb:sm:overflow-x-visible ndb:sm:overflow-y-auto ndb:sm:border-b-0 ndb:sm:border-r ndb:sm:p-3 ndb:dark:border-zinc-800/80 ndb:dark:bg-zinc-900/60">
                    <template x-for="(section, sectionIndex) in sidebarSections" :key="'section-' + section.key">
                        <div class="ndb:contents">
                            <p x-show.important="favorites.length > 0 && sectionIndex === 0" class="ndb:hidden ndb:px-2 ndb:pb-1.5 ndb:pt-1 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-[0.14em] ndb:text-zinc-400 ndb:sm:block">Favorites</p>
                            <div x-show.important="favorites.length > 0 && sectionIndex === favorites.length" class="ndb:hidden ndb:h-px ndb:bg-zinc-200 ndb:sm:my-2 ndb:sm:block ndb:dark:bg-zinc-800"></div>
                            <p x-show.important="sectionIndex === favorites.length" class="ndb:hidden ndb:px-2 ndb:pb-1.5 ndb:pt-1 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-[0.14em] ndb:text-zinc-400 ndb:sm:block">Sections</p>
                            <div
                                :draggable="isFavorite(section.key)"
                                :data-ndb-section="section.key"
                                :data-ndb-favorite="isFavorite(section.key) ? 'true' : 'false'"
                                @dragstart="startFavoriteDrag(section.key, $event)"
                                @dragover.prevent="hoverFavorite(section.key, $event.clientY > $event.currentTarget.getBoundingClientRect().top + ($event.currentTarget.offsetHeight / 2))"
                                @dragleave="leaveFavorite(section.key)"
                                @drop.prevent="dropFavorite(section.key, favoriteDropAfter)"
                                @dragend="endFavoriteDrag()"
                                class="ndb:relative ndb:flex ndb:w-auto ndb:shrink-0 ndb:items-center ndb:rounded-lg ndb:pr-1 ndb:transition ndb:hover:bg-zinc-200/60 ndb:sm:w-full ndb:dark:hover:bg-zinc-800/60"
                                :class="(selected === section.key ? 'ndb-section-active' : '') + (favoriteDrag === section.key ? ' ndb:opacity-40' : '')"
                            >
                                <span x-show.important="favoriteDrop === section.key && ! favoriteDropAfter" class="ndb:absolute ndb:inset-x-1 ndb:-top-0.5 ndb:z-10 ndb:h-0.5 ndb:rounded-full ndb:bg-indigo-500"></span>
                                <span x-show.important="favoriteDrop === section.key && favoriteDropAfter" class="ndb:absolute ndb:inset-x-1 ndb:-bottom-0.5 ndb:z-10 ndb:h-0.5 ndb:rounded-full ndb:bg-indigo-500"></span>
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
                                    <span x-show.important="section.count !== null" class="ndb-section-count ndb:ml-auto ndb:text-[10px] ndb:tabular-nums" :class="selected === section.key ? '' : 'ndb:text-zinc-400'" x-text="section.count"></span>
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
                                    class="ndb-star-button ndb:inline-flex ndb:size-7 ndb:items-center ndb:justify-center ndb:rounded-lg ndb:text-zinc-400 ndb:transition ndb:hover:scale-105 ndb:hover:text-blue-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-1 ndb:focus-visible:outline-blue-500 ndb:dark:text-zinc-500 ndb:dark:hover:text-blue-300"
                                >
                                    <span x-show.important="! isFavorite(section.key)" class="ndb-section-star-outline"><x-new-debug-bar::icon name="star" class="ndb:size-3.5" /></span>
                                    <span x-show.important="isFavorite(section.key)"><x-new-debug-bar::icon name="star-filled" class="ndb-favorite-star ndb:size-3.5" /></span>
                                </button>
                            </div>
                        </div>
                    </template>
                </nav>

                <main x-ref="content" class="ndb-scrollbar ndb:min-w-0 ndb:flex-1 ndb:overflow-y-auto ndb:bg-white/70 ndb:dark:bg-zinc-950/70">
                    <div class="ndb:sticky ndb:top-0 ndb:z-10 ndb:flex ndb:h-12 ndb:items-center ndb:border-b ndb:border-zinc-100/80 ndb:bg-white/65 ndb:px-4 ndb:backdrop-blur-xl ndb:sm:px-6 ndb:dark:border-zinc-900/80 ndb:dark:bg-zinc-950/65">
                        <h2 data-ndb-section-heading class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-sm ndb:font-bold" x-text="selectedSection.label"></h2>
                    </div>

                    <div wire:loading.flex wire:target="loadDetails" class="ndb:min-h-64 ndb:items-center ndb:justify-center ndb:p-8">
                        <div class="ndb:text-center"><span class="ndb:mx-auto ndb:grid ndb:size-10 ndb:animate-pulse ndb:place-items-center ndb:rounded-xl ndb:bg-indigo-50 ndb:text-indigo-600 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300"><x-new-debug-bar::icon name="clock" class="ndb:size-4" /></span><p class="ndb:mt-3 ndb:text-sm ndb:font-semibold">Loading request details…</p></div>
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
                                    @php($sectionFindings = array_values(array_filter($profile['findings'] ?? [], fn (array $finding): bool => $sectionKey === 'overview' || $finding['section'] === $sectionKey)))
                                    @if ($sectionKey === 'overview')
                                        <div class="ndb:grid ndb:grid-cols-2 ndb:gap-3 ndb:lg:grid-cols-4">
                                            @foreach ([['Duration', $profile['metrics']['duration_ms'].' ms', 'clock'], ['Peak memory', $profile['metrics']['peak_memory_mb'].' MB', 'memory'], ['Queries', $profile['sections']['queries']['summary']['count'], 'database'], ['Status', $profile['sections']['request']['summary']['status'], 'check']] as [$label, $value, $icon])
                                                <div class="ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:bg-zinc-50 ndb:p-3.5 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900"><x-new-debug-bar::icon :name="$icon" class="ndb:size-4 ndb:text-indigo-500" /><p class="ndb:mt-2 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $value }}</p><p class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</p></div>
                                            @endforeach
                                        </div>
                                        <x-new-debug-bar::finding-list :findings="$sectionFindings" />
                                        <div class="ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:p-4 ndb:dark:border-zinc-800">
                                            <h3 class="ndb:text-xs ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Runtime</h3>
                                            <dl class="ndb:mt-3 ndb:grid ndb:grid-cols-2 ndb:gap-x-5 ndb:gap-y-3 ndb:lg:grid-cols-5">
                                                @foreach ($section['payload'] as $label => $value)
                                                    <div><dt class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ str($label)->replace('_', ' ')->title() }}</dt><dd class="ndb:mt-0.5 ndb:truncate ndb:text-sm ndb:font-semibold">{{ $value }}</dd></div>
                                                @endforeach
                                            </dl>
                                        </div>
                                        <div class="ndb:grid ndb:grid-cols-2 ndb:gap-2 ndb:sm:grid-cols-4">
                                            @foreach ($summary['section_counts'] as $key => $count)
                                                @if ($count !== null)
                                                    <button type="button" @click="selectSection(@js($key))" class="ndb:flex ndb:items-center ndb:justify-between ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:px-3 ndb:py-2.5 ndb:text-left ndb:transition ndb:hover:border-indigo-300 ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:border-zinc-800 ndb:dark:hover:border-indigo-800 ndb:dark:hover:bg-indigo-950/50"><span class="ndb:text-xs ndb:font-semibold">{{ $profile['sections'][$key]['label'] }}</span><span class="ndb:text-xs ndb:font-bold ndb:tabular-nums ndb:text-zinc-400">{{ $count }}</span></button>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <x-new-debug-bar::finding-list :findings="$sectionFindings" title="Related findings" />
                                    @endif

                                    @if ($sectionKey === 'timeline')
                                        @php($timelineSections = array_values(array_unique(array_column($section['payload']['items'], 'section'))))
                                        <div class="ndb:flex ndb:flex-col ndb:gap-3 ndb:border-b ndb:border-zinc-200/80 ndb:pb-3 ndb:lg:flex-row ndb:lg:items-end ndb:dark:border-zinc-800">
                                            <div class="ndb:min-w-0 ndb:flex-1"><p class="ndb:mb-1.5 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Source</p><div class="ndb:flex ndb:overflow-x-auto" role="group" aria-label="Filter timeline"><button type="button" data-ndb-timeline-filter="all" @click="setTimelineFilter('all')" :aria-pressed="timelineFilter === 'all'" class="ndb:border-b-2 ndb:px-3 ndb:py-1.5 ndb:text-xs ndb:font-semibold" :class="timelineFilter === 'all' ? 'ndb:border-indigo-500 ndb:text-indigo-700 ndb:dark:text-indigo-300' : 'ndb:border-transparent ndb:text-zinc-500 ndb:dark:text-zinc-400'">All</button>@foreach ($timelineSections as $timelineSection)<button type="button" data-ndb-timeline-filter="{{ $timelineSection }}" @click="setTimelineFilter(@js($timelineSection))" :aria-pressed="timelineFilter === @js($timelineSection)" class="ndb:border-b-2 ndb:px-3 ndb:py-1.5 ndb:text-xs ndb:font-semibold" :class="timelineFilter === @js($timelineSection) ? 'ndb:border-indigo-500 ndb:text-indigo-700 ndb:dark:text-indigo-300' : 'ndb:border-transparent ndb:text-zinc-500 ndb:dark:text-zinc-400'">{{ str($timelineSection)->title() }}</button>@endforeach</div></div>
                                            <label class="ndb:min-w-0 ndb:lg:w-72"><span class="ndb:mb-1.5 ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Search</span><input data-ndb-timeline-search x-model="timelineSearch" @input.debounce.100ms="applyTimelineFilters()" type="search" placeholder="Event or section" class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:outline-none ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70" /></label>
                                        </div>
                                        <p class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400"><span x-text="visibleTimelineCount"></span> events</p>
                                        <ol x-ref="timelineList" class="ndb:ml-2 ndb:list-none ndb:border-l ndb:border-zinc-200 ndb:dark:border-zinc-800">
                                            @foreach ($section['payload']['items'] as $item)
                                                <li data-ndb-timeline-item="{{ $item['id'] }}" data-section="{{ $item['section'] }}" data-search="{{ mb_strtolower($item['label'].' '.$item['section']) }}" class="ndb:relative ndb:pb-4 ndb:pl-5 ndb:last:pb-0"><span class="ndb:absolute ndb:-left-[5px] ndb:top-1.5 ndb:size-2.5 ndb:rounded-full ndb:border-2 ndb:border-white {{ $item['kind'] === 'span' ? 'ndb:bg-indigo-500' : ($item['kind'] === 'milestone' ? 'ndb:bg-zinc-700 ndb:dark:bg-zinc-200' : 'ndb:bg-zinc-400') }} ndb:dark:border-zinc-950"></span><div class="ndb:flex ndb:flex-wrap ndb:items-start ndb:gap-x-3 ndb:gap-y-1"><p class="ndb:min-w-0 ndb:flex-1 ndb:text-xs ndb:font-semibold">{{ $item['label'] }}</p><span class="ndb:text-[10px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-400">{{ $item['kind'] === 'span' ? $item['start_ms'].' → '.$item['at_ms'].' ms' : $item['at_ms'].' ms' }}</span></div><div class="ndb:mt-0.5 ndb:flex ndb:gap-3 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"><span>{{ $item['section'] }}</span><span>{{ $item['kind'] }}</span>@if ($item['duration_ms'] !== null)<span>{{ $item['duration_ms'] }} ms</span>@endif</div></li>
                                            @endforeach
                                        </ol>
                                        <div x-show.important="visibleTimelineCount === 0"><x-new-debug-bar::empty-state label="No timeline events match these filters." /></div>
                                    @elseif ($sectionKey === 'request')
                                        <div class="ndb:grid ndb:grid-cols-2 ndb:gap-3 ndb:lg:grid-cols-4">
                                            @foreach (['method' => 'Method', 'status' => 'Status', 'route' => 'Route', 'action' => 'Controller'] as $key => $label)
                                                <div class="ndb:min-w-0 ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:p-3 ndb:dark:border-zinc-800"><p class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</p><p class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-semibold">{{ $section['payload'][$key] ?: '—' }}</p></div>
                                            @endforeach
                                        </div>
                                        <pre class="ndb-code ndb-scrollbar"><code data-ndb-language="json">{{ json_encode($section['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                    @elseif ($sectionKey === 'queries')
                                        @php($querySummary = $section['summary'])
                                        <div class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/55 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/35">
                                            <dl class="ndb:grid ndb:grid-cols-2 ndb:divide-x ndb:divide-y ndb:divide-zinc-200/80 ndb:sm:grid-cols-5 ndb:sm:divide-y-0 ndb:dark:divide-zinc-800">
                                                @foreach ([
                                                    ['Queries', $querySummary['total_count']],
                                                    ['Query time', $querySummary['total_time_ms'].' ms'],
                                                    ['Request share', $querySummary['request_time_percent'].'%'],
                                                    ['Repeated', $querySummary['repeated_pattern_count']],
                                                    ['Extra runs', $querySummary['extra_execution_count']],
                                                ] as [$label, $value])
                                                    <div class="ndb:px-3 ndb:py-2.5"><dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</dt><dd class="ndb:mt-0.5 ndb:text-sm ndb:font-bold ndb:tabular-nums">{{ $value }}</dd></div>
                                                @endforeach
                                            </dl>
                                        </div>

                                        <div class="ndb:flex ndb:flex-col ndb:gap-3 ndb:border-b ndb:border-zinc-200/80 ndb:pb-3 ndb:lg:flex-row ndb:lg:items-end ndb:dark:border-zinc-800">
                                            <div class="ndb:min-w-0 ndb:flex-1">
                                                <p class="ndb:mb-1.5 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Filter</p>
                                                <div class="ndb:flex ndb:overflow-x-auto" role="group" aria-label="Filter queries">
                                                    @foreach (['all' => 'All', 'repeated' => 'Repeated', 'slow' => 'Slow', 'read' => 'Read', 'write' => 'Write'] as $filter => $label)
                                                        <button type="button" data-ndb-query-filter="{{ $filter }}" @click="setQueryFilter(@js($filter))" :aria-pressed="queryFilter === @js($filter)" class="ndb:border-b-2 ndb:px-3 ndb:py-1.5 ndb:text-xs ndb:font-semibold ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500" :class="queryFilter === @js($filter) ? 'ndb:border-indigo-500 ndb:text-indigo-700 ndb:dark:text-indigo-300' : 'ndb:border-transparent ndb:text-zinc-500 ndb:hover:text-zinc-950 ndb:dark:text-zinc-400 ndb:dark:hover:text-white'">{{ $label }}</button>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <label class="ndb:min-w-0 ndb:lg:w-64"><span class="ndb:mb-1.5 ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Search</span><input data-ndb-query-search x-model="querySearch" @input.debounce.100ms="applyQueryView()" type="search" placeholder="SQL or redacted binding" class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:outline-none ndb:transition ndb:placeholder:text-zinc-400 ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70" /></label>
                                            <div>
                                                <p class="ndb:mb-1.5 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Sort</p>
                                                <div class="ndb:flex ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:p-0.5 ndb:dark:border-zinc-700" role="group" aria-label="Sort queries">
                                                    @foreach (['execution' => 'Execution', 'duration' => 'Slowest'] as $sort => $label)
                                                        <button type="button" data-ndb-query-sort="{{ $sort }}" @click="setQuerySort(@js($sort))" :aria-pressed="querySort === @js($sort)" class="ndb:rounded-md ndb:px-2.5 ndb:py-1.5 ndb:text-[10px] ndb:font-semibold ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500" :class="querySort === @js($sort) ? 'ndb:bg-zinc-100 ndb:text-zinc-950 ndb:dark:bg-zinc-800 ndb:dark:text-white' : 'ndb:text-zinc-500 ndb:dark:text-zinc-400'">{{ $label }}</button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        <p class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400"><span x-text="visibleQueryCount"></span> results</p>

                                        <div x-ref="queryItems" class="ndb:space-y-3">
                                            @foreach ($section['payload']['items'] as $query)
                                                <x-new-debug-bar::query-execution :query="$query" :identity="'item-'.$query['execution']" filterable />
                                            @endforeach
                                        </div>

                                        <div x-ref="queryGroups" class="ndb:space-y-3">
                                            @foreach ($section['payload']['repeated_groups'] as $group)
                                                @php($groupSearch = mb_strtolower($group['sql'].' '.json_encode(array_column($group['executions'], 'bindings'), JSON_UNESCAPED_SLASHES)))
                                                <details
                                                    data-ndb-query-group="{{ $group['fingerprint'] }}"
                                                    data-execution="{{ $group['executions'][0]['execution'] }}"
                                                    data-duration="{{ $group['duration_ms'] }}"
                                                    data-search="{{ $groupSearch }}"
                                                    hidden
                                                    class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-indigo-200/90 ndb:bg-indigo-50/30 ndb:dark:border-indigo-950 ndb:dark:bg-indigo-950/20"
                                                >
                                                    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:flex-wrap ndb:items-center ndb:gap-x-3 ndb:gap-y-1 ndb:px-4 ndb:py-3 ndb:text-xs">
                                                        <span class="ndb:font-bold ndb:text-indigo-700 ndb:dark:text-indigo-300">Repeated {{ $group['count'] }}×</span>
                                                        @if ($group['likely_n_plus_one'])<span class="ndb:font-bold ndb:text-amber-700 ndb:dark:text-amber-300">Likely N+1</span>@endif
                                                        <span class="ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $group['extra_executions'] }} extra executions</span>
                                                        <span class="ndb:ml-auto ndb:font-bold ndb:tabular-nums">{{ $group['duration_ms'] }} ms</span>
                                                        <x-new-debug-bar::icon name="chevron-down" class="ndb-details-chevron ndb:size-3.5 ndb:text-zinc-400 ndb:transition" />
                                                    </summary>
                                                    <div class="ndb:space-y-3 ndb:border-t ndb:border-indigo-200/70 ndb:p-3 ndb:dark:border-indigo-950">
                                                        @foreach ($group['executions'] as $execution)
                                                            <x-new-debug-bar::query-execution :query="$execution" :identity="'group-'.$group['fingerprint'].'-'.$execution['execution']" />
                                                        @endforeach
                                                    </div>
                                                </details>
                                            @endforeach
                                        </div>

                                        <div x-show.important="visibleQueryCount === 0">
                                            <x-new-debug-bar::empty-state label="No queries match these filters." />
                                        </div>
                                    @elseif ($sectionKey === 'models')
                                        <div class="ndb:grid ndb:grid-cols-2 ndb:gap-3"><div class="ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:p-3 ndb:dark:border-zinc-800"><p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Model classes</p><p class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $section['summary']['model_classes'] }}</p></div><div class="ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:p-3 ndb:dark:border-zinc-800"><p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Lifecycle events</p><p class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ count($section['summary']['lifecycle_events']) }}</p></div></div>
                                        @forelse ($section['payload']['groups'] as $index => $group)
                                            <details wire:key="model-group-{{ $index }}" class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"><summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3"><span class="ndb:min-w-0 ndb:flex-1"><span class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold">{{ $group['model'] }}</span><span class="ndb:mt-0.5 ndb:block ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">{{ str($group['event'])->title() }}</span></span><span class="ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $group['count'] }}</span><x-new-debug-bar::icon name="chevron-down" class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180" /></summary><pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-800"><code data-ndb-language="json">{{ json_encode($group['items'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre></details>
                                        @empty
                                            <x-new-debug-bar::empty-state label="No model activity was captured." />
                                        @endforelse
                                    @elseif ($sectionKey === 'cache')
                                        <dl class="ndb:grid ndb:grid-cols-2 ndb:divide-x ndb:divide-y ndb:divide-zinc-200 ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:sm:grid-cols-4 ndb:sm:divide-y-0 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">@foreach ([['Hit rate', $section['summary']['hit_rate'].'%'], ['Hits', $section['summary']['hits']], ['Misses', $section['summary']['misses']], ['Writes', $section['summary']['writes']]] as [$label, $value])<div class="ndb:px-3 ndb:py-2.5"><dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</dt><dd class="ndb:mt-0.5 ndb:text-sm ndb:font-bold ndb:tabular-nums">{{ $value }}</dd></div>@endforeach</dl>
                                        @if ($section['payload']['repeated_misses'] !== [])<div class="ndb:rounded-xl ndb:border ndb:border-amber-200 ndb:bg-amber-50/40 ndb:p-4 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/20"><h3 class="ndb:text-xs ndb:font-bold">Repeated misses</h3><div class="ndb:mt-3 ndb:space-y-2">@foreach ($section['payload']['repeated_misses'] as $miss)<div class="ndb:flex ndb:items-center ndb:gap-3"><code class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-[10px]">{{ $miss['key_hash'] }}</code><span class="ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $miss['count'] }} misses</span></div>@endforeach</div></div>@endif
                                        <div class="ndb:space-y-2">@foreach ($section['payload']['items'] as $index => $item)<details wire:key="cache-{{ $index }}" class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"><summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3 ndb:text-xs ndb:font-semibold"><span class="ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:text-zinc-400">{{ $item['operation'] }}</span><code class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-[10px]">{{ $item['key_hash'] }}</code><x-new-debug-bar::icon name="chevron-down" class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180" /></summary><pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-800"><code data-ndb-language="json">{{ json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre></details>@endforeach</div>
                                    @elseif ($sectionKey === 'views')
                                        <div class="ndb:flex ndb:items-end ndb:justify-between ndb:border-b ndb:border-zinc-200 ndb:pb-3 ndb:dark:border-zinc-800"><div><p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Unique views</p><p class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $section['summary']['unique_views'] }}</p></div><p class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">Timing appears when Laravel exposes reliable render spans.</p></div>
                                        @forelse ($section['payload']['groups'] as $index => $group)<details wire:key="view-group-{{ $index }}" class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"><summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3"><span class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-bold">{{ $group['name'] }}</span><span class="ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $group['count'] }}</span><x-new-debug-bar::icon name="chevron-down" class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180" /></summary><pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-800"><code data-ndb-language="json">{{ json_encode($group['items'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre></details>@empty<x-new-debug-bar::empty-state label="No views were captured." />@endforelse
                                    @elseif ($sectionKey === 'events')
                                        <div class="ndb:flex ndb:flex-col ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:pb-3 ndb:sm:flex-row ndb:sm:items-end ndb:dark:border-zinc-800"><div class="ndb:flex-1"><p class="ndb:mb-1.5 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Source</p><div class="ndb:flex">@foreach (['all' => 'All', 'application' => 'Application', 'framework' => 'Framework'] as $source => $label)<button type="button" data-ndb-event-source="{{ $source }}" @click="setEventSource(@js($source))" class="ndb:border-b-2 ndb:px-3 ndb:py-1.5 ndb:text-xs ndb:font-semibold" :class="eventSource === @js($source) ? 'ndb:border-indigo-500 ndb:text-indigo-700 ndb:dark:text-indigo-300' : 'ndb:border-transparent ndb:text-zinc-500 ndb:dark:text-zinc-400'">{{ $label }}</button>@endforeach</div></div><label class="ndb:sm:w-72"><span class="ndb:mb-1.5 ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Search</span><input data-ndb-event-search x-model="eventSearch" @input.debounce.100ms="applyEventFilters()" type="search" placeholder="Event name" class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:outline-none ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70" /></label></div><p class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400"><span x-text="visibleEventCount"></span> events</p>
                                        <div x-ref="eventList" class="ndb:space-y-2">@foreach ($section['payload']['items'] as $index => $item)<details data-ndb-event-item data-source="{{ $item['source'] }}" data-search="{{ mb_strtolower($item['name']) }}" wire:key="event-{{ $index }}" class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"><summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3"><span class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-semibold">{{ $item['name'] }}</span><span class="ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:text-zinc-400">{{ $item['source'] }}</span><x-new-debug-bar::icon name="chevron-down" class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180" /></summary><pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-800"><code data-ndb-language="json">{{ json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre></details>@endforeach</div><div x-show.important="visibleEventCount === 0"><x-new-debug-bar::empty-state label="No events match these filters." /></div>
                                    @elseif ($sectionKey === 'logs')
                                        @php($logLevels = array_values(array_unique(array_column($section['payload']['items'], 'level'))))
                                        <div class="ndb:flex ndb:flex-col ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:pb-3 ndb:sm:flex-row ndb:sm:items-end ndb:dark:border-zinc-800"><div class="ndb:min-w-0 ndb:flex-1"><p class="ndb:mb-1.5 ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Level</p><div class="ndb:flex ndb:overflow-x-auto"><button type="button" data-ndb-log-level="all" @click="setLogLevel('all')" class="ndb:border-b-2 ndb:px-3 ndb:py-1.5 ndb:text-xs ndb:font-semibold" :class="logLevel === 'all' ? 'ndb:border-indigo-500 ndb:text-indigo-700 ndb:dark:text-indigo-300' : 'ndb:border-transparent ndb:text-zinc-500 ndb:dark:text-zinc-400'">All</button>@foreach ($logLevels as $level)<button type="button" data-ndb-log-level="{{ $level }}" @click="setLogLevel(@js($level))" class="ndb:border-b-2 ndb:px-3 ndb:py-1.5 ndb:text-xs ndb:font-semibold" :class="logLevel === @js($level) ? 'ndb:border-indigo-500 ndb:text-indigo-700 ndb:dark:text-indigo-300' : 'ndb:border-transparent ndb:text-zinc-500 ndb:dark:text-zinc-400'">{{ strtoupper($level) }}</button>@endforeach</div></div><label class="ndb:sm:w-72"><span class="ndb:mb-1.5 ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Search</span><input data-ndb-log-search x-model="logSearch" @input.debounce.100ms="applyLogFilters()" type="search" placeholder="Log message" class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:outline-none ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70" /></label></div><p class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400"><span x-text="visibleLogCount"></span> logs</p>
                                        <div x-ref="logList" class="ndb:space-y-2">@foreach ($section['payload']['items'] as $index => $item)<details data-ndb-log-item data-level="{{ $item['level'] }}" data-search="{{ mb_strtolower($item['message']) }}" wire:key="log-{{ $index }}" class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"><summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3"><span class="ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:text-zinc-400">{{ $item['level'] }}</span><span class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-semibold">{{ $item['message'] }}</span><x-new-debug-bar::icon name="chevron-down" class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180" /></summary><pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-800"><code data-ndb-language="json">{{ json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre></details>@endforeach</div><div x-show.important="visibleLogCount === 0"><x-new-debug-bar::empty-state label="No logs match these filters." /></div>
                                    @elseif ($sectionKey === 'exceptions')
                                        @forelse ($section['payload']['items'] as $index => $exception)
                                            <article wire:key="exception-{{ $index }}" class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-red-200 ndb:dark:border-red-950">
                                                <div class="ndb:flex ndb:items-start ndb:gap-3 ndb:bg-red-50 ndb:p-4 ndb:dark:bg-red-950/50"><span class="ndb:grid ndb:size-8 ndb:shrink-0 ndb:place-items-center ndb:rounded-lg ndb:bg-red-100 ndb:text-red-600 ndb:dark:bg-red-950 ndb:dark:text-red-300"><x-new-debug-bar::icon name="warning" class="ndb:size-4" /></span><div class="ndb:min-w-0"><p class="ndb:truncate ndb:text-xs ndb:font-bold ndb:text-red-700 ndb:dark:text-red-300">{{ $exception['class'] }}</p><p class="ndb:mt-1 ndb:text-sm ndb:font-semibold">{{ $exception['message'] ?: 'No message' }}</p><p class="ndb:mt-1 ndb:truncate ndb:text-[10px] ndb:text-zinc-500">{{ $exception['file'] }}:{{ $exception['line'] }}</p></div></div>
                                                <pre class="ndb-code ndb-scrollbar ndb:max-h-72 ndb:rounded-none ndb:text-zinc-400"><code>{{ $exception['trace'] }}</code></pre>
                                            </article>
                                        @empty
                                            <x-new-debug-bar::empty-state label="No exceptions were reported." success />
                                        @endforelse
                                    @elseif ($sectionKey !== 'overview')
                                        @forelse ($section['payload']['items'] as $index => $item)
                                            <details wire:key="{{ $sectionKey }}-{{ $index }}" class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800">
                                                <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3 ndb:text-xs ndb:font-semibold ndb:transition ndb:hover:bg-zinc-50 ndb:dark:hover:bg-zinc-900"><span class="ndb:text-[10px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-400">#{{ $index + 1 }}</span><span class="ndb:min-w-0 ndb:flex-1 ndb:truncate">{{ $item['model'] ?? $item['name'] ?? $item['event'] ?? $item['level'] ?? $item['operation'] ?? $section['label'] }}</span><x-new-debug-bar::icon name="chevron-down" class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180" /></summary>
                                                <pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-800"><code data-ndb-language="json">{{ json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                            </details>
                                        @empty
                                            <x-new-debug-bar::empty-state :label="'No '.strtolower($section['label']).' were captured.'" />
                                        @endforelse
                                    @endif
                                </section>
                            @endforeach

                            <section data-ndb-section-panel="history" hidden wire:key="section-history" class="ndb:space-y-4">
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
                    <button type="button" :data-ndb-command="command.id" @mouseenter="paletteIndex = index" @click="runCommand(command.id)" class="ndb:flex ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:px-3 ndb:py-2.5 ndb:text-left ndb:transition" :class="paletteIndex === index ? 'ndb:bg-indigo-50 ndb:text-indigo-800 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-200' : 'ndb:text-zinc-700 ndb:dark:text-zinc-300'"><span class="ndb:flex-1 ndb:text-sm ndb:font-semibold" x-text="command.label"></span><span class="ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400" x-text="command.hint"></span></button>
                </template>
                <p x-show.important="filteredCommands.length === 0" class="ndb:px-3 ndb:py-8 ndb:text-center ndb:text-sm ndb:text-zinc-500">No matching commands.</p>
            </div>
            <div class="ndb:flex ndb:items-center ndb:gap-3 ndb:border-t ndb:border-zinc-200 ndb:bg-zinc-50 ndb:px-4 ndb:py-2 ndb:text-[10px] ndb:font-medium ndb:text-zinc-400 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"><span>↑↓ Navigate</span><span>↵ Select</span><span class="ndb:ml-auto">⌘/Ctrl ⇧ P</span></div>
        </div>
    </div>
</div>
