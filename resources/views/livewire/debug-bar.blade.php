@php
    $iconButton = 'ndb:inline-flex ndb:items-center ndb:justify-center ndb:rounded-lg ndb:text-zinc-500 ndb:transition ndb:hover:bg-zinc-100 ndb:hover:text-zinc-950 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:pointer-events-none ndb:disabled:opacity-25 ndb:dark:text-zinc-400 ndb:dark:hover:bg-zinc-800 ndb:dark:hover:text-white';
    $starButton = 'ndb-star-button ndb:inline-flex ndb:size-7 ndb:items-center ndb:justify-center ndb:rounded-lg ndb:text-zinc-400 ndb:transition ndb:hover:scale-105 ndb:hover:text-blue-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-1 ndb:focus-visible:outline-blue-500 ndb:dark:text-zinc-500 ndb:dark:hover:text-blue-300';
    $profile = $detailsLoaded ? $this->profile : [];
@endphp

<div
    id="new-debug-bar"
    wire:key="new-debug-bar-{{ $profileId }}"
    x-data="newDebugBar(@js($summary))"
    x-init="init()"
    :data-theme="resolvedTheme"
    @keydown.window="handleShortcut($event)"
    class="ndb:pointer-events-none ndb:fixed ndb:inset-0 ndb:z-[2147483000] ndb:text-zinc-900 ndb:dark:text-zinc-100"
>
    <div
        x-cloak
        x-show.important="! inspectorOpen"
        x-transition.opacity.duration.150ms
        role="toolbar"
        aria-label="Debug toolbar"
        class="ndb:pointer-events-auto ndb:fixed ndb:bottom-3 ndb:left-1/2 ndb:flex ndb:max-w-[calc(100vw-24px)] ndb:-translate-x-1/2 ndb:items-stretch ndb:gap-1 ndb:rounded-2xl ndb:border ndb:border-white/70 ndb:bg-white/80 ndb:p-1.5 ndb:shadow-[0_18px_60px_-18px_rgba(24,24,27,0.4)] ndb:backdrop-blur-2xl ndb:dark:border-zinc-700/70 ndb:dark:bg-zinc-900/80"
    >
        <button type="button" @click="openInspector('request')" class="ndb:flex ndb:min-w-0 ndb:max-w-52 ndb:self-stretch ndb:items-center ndb:gap-2 ndb:rounded-xl ndb:px-2.5 ndb:py-1.5 ndb:text-left ndb:transition ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-zinc-800" aria-label="Open request details">
            <span class="ndb:rounded-md ndb:bg-indigo-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300" x-text="summary.method"></span>
            <span class="ndb:min-w-0">
                <span class="ndb:block ndb:truncate ndb:text-xs ndb:font-semibold" x-text="summary.path"></span>
                <span class="ndb:block ndb:text-[10px] ndb:font-medium ndb:text-zinc-400" x-text="summary.status"></span>
            </span>
        </button>

        <span class="ndb:my-1 ndb:w-px ndb:bg-zinc-200 ndb:dark:bg-zinc-700"></span>

        <button type="button" @click="openInspector('overview')" class="ndb:flex ndb:self-stretch ndb:items-center ndb:gap-2 ndb:rounded-xl ndb:px-2.5 ndb:py-1.5 ndb:text-left ndb:transition ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-zinc-800">
            <span class="ndb:size-2 ndb:rounded-full" :class="summary.warning ? 'ndb:bg-amber-500' : 'ndb:bg-emerald-500'"></span>
            <span><span class="ndb:hidden ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:sm:block">Environment</span><span class="ndb:block ndb:max-w-24 ndb:truncate ndb:text-[10px] ndb:font-bold ndb:sm:text-xs" x-text="summary.environment"></span></span>
        </button>

        <button type="button" @click="openInspector('request')" class="ndb:hidden ndb:self-stretch ndb:items-center ndb:gap-2 ndb:rounded-xl ndb:px-2.5 ndb:py-1.5 ndb:text-left ndb:transition ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:md:flex ndb:dark:hover:bg-zinc-800">
            <x-new-debug-bar::icon name="clock" class="ndb:size-3.5 ndb:text-indigo-500" />
            <span><span class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Duration</span><span class="ndb:block ndb:whitespace-nowrap ndb:text-xs ndb:font-bold ndb:tabular-nums" x-text="summary.duration_ms + ' ms'"></span></span>
        </button>

        <button type="button" @click="openInspector('overview')" class="ndb:hidden ndb:self-stretch ndb:items-center ndb:gap-2 ndb:rounded-xl ndb:px-2.5 ndb:py-1.5 ndb:text-left ndb:transition ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:lg:flex ndb:dark:hover:bg-zinc-800">
            <x-new-debug-bar::icon name="memory" class="ndb:size-3.5 ndb:text-violet-500" />
            <span><span class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Peak</span><span class="ndb:block ndb:whitespace-nowrap ndb:text-xs ndb:font-bold ndb:tabular-nums" x-text="summary.memory_mb + ' MB'"></span></span>
        </button>

        <button type="button" @click="openInspector('queries')" class="ndb:hidden ndb:self-stretch ndb:items-center ndb:gap-2 ndb:rounded-xl ndb:px-2.5 ndb:py-1.5 ndb:text-left ndb:transition ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:sm:flex ndb:dark:hover:bg-zinc-800">
            <x-new-debug-bar::icon name="database" class="ndb:size-3.5 ndb:text-cyan-500" />
            <span><span class="ndb:block ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Queries</span><span class="ndb:flex ndb:items-center ndb:gap-2 ndb:whitespace-nowrap ndb:text-xs ndb:font-bold ndb:tabular-nums"><span x-text="summary.query_count"></span><span class="ndb:rounded ndb:bg-zinc-100/80 ndb:px-1 ndb:font-medium ndb:text-zinc-400 ndb:dark:bg-zinc-800/80" x-text="summary.query_duration_ms + ' ms'"></span></span></span>
        </button>

        <span class="ndb:my-1 ndb:w-px ndb:bg-zinc-200 ndb:dark:bg-zinc-700"></span>

        <div class="ndb:flex ndb:items-center ndb:gap-0.5">
            <button type="button" @click="openPalette()" class="{{ $iconButton }} ndb:size-9" aria-label="Open command palette" title="Command palette (Command or Control + Shift + P)"><x-new-debug-bar::icon name="search" class="ndb:size-4" /></button>
            <button type="button" @click="openInspector()" class="{{ $iconButton }} ndb:size-9" aria-label="Expand inspector" title="Expand inspector"><x-new-debug-bar::icon name="expand" class="ndb:size-4" /></button>
        </div>
    </div>

    <div x-cloak x-show.important="inspectorOpen" class="ndb:pointer-events-auto ndb:fixed ndb:inset-0" role="presentation">
        <div x-show.important="inspectorOpen" x-transition.opacity.duration.150ms @click="closeInspector()" class="ndb:absolute ndb:inset-0 ndb:bg-zinc-950/30 ndb:backdrop-blur-[1px] ndb:dark:bg-black/55"></div>

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
            class="ndb:absolute ndb:inset-x-0 ndb:bottom-0 ndb:flex ndb:h-[min(82vh,780px)] ndb:max-h-[calc(100vh-12px)] ndb:flex-col ndb:overflow-hidden ndb:rounded-t-2xl ndb:border-x ndb:border-t ndb:border-white/70 ndb:bg-white/90 ndb:shadow-[0_-24px_80px_-28px_rgba(24,24,27,0.5)] ndb:backdrop-blur-2xl ndb:dark:border-zinc-800/80 ndb:dark:bg-zinc-950/90"
        >
            <header class="ndb:flex ndb:h-13 ndb:shrink-0 ndb:items-center ndb:gap-3 ndb:border-b ndb:border-zinc-200/80 ndb:bg-white/55 ndb:pl-3 ndb:pr-2 ndb:backdrop-blur-xl ndb:sm:pl-4 ndb:dark:border-zinc-800/80 ndb:dark:bg-zinc-950/55">
                <span class="ndb:rounded-md ndb:bg-indigo-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300" x-text="summary.method"></span>
                <div class="ndb:min-w-0 ndb:flex-1">
                    <p class="ndb:truncate ndb:text-sm ndb:font-semibold" x-text="summary.path"></p>
                    <p class="ndb:flex ndb:items-center ndb:gap-1 ndb:text-[9px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400"><span class="ndb:rounded ndb:bg-zinc-100/75 ndb:px-1.5 ndb:py-px ndb:dark:bg-zinc-800/75" x-text="summary.status"></span><span class="ndb:rounded ndb:bg-zinc-100/75 ndb:px-1.5 ndb:py-px ndb:dark:bg-zinc-800/75" x-text="summary.environment"></span><span class="ndb:rounded ndb:bg-zinc-100/75 ndb:px-1.5 ndb:py-px ndb:tabular-nums ndb:dark:bg-zinc-800/75" x-text="summary.duration_ms + ' ms'"></span></p>
                </div>
                <div class="ndb:flex ndb:items-center ndb:gap-0.5">
                    <button type="button" @click="openPalette()" class="{{ $iconButton }} ndb:size-9" aria-label="Open command palette"><x-new-debug-bar::icon name="search" class="ndb:size-4" /></button>
                    <button type="button" @click="toggleTheme()" class="{{ $iconButton }} ndb:size-9" :aria-label="resolvedTheme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'" :title="resolvedTheme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'"><span x-show.important="resolvedTheme !== 'dark'"><x-new-debug-bar::icon name="moon" class="ndb:size-4" /></span><span x-show.important="resolvedTheme === 'dark'"><x-new-debug-bar::icon name="sun" class="ndb:size-4" /></span></button>
                    <button type="button" @click="closeInspector()" class="{{ $iconButton }} ndb:size-9" aria-label="Close inspector"><x-new-debug-bar::icon name="close" class="ndb:size-4" /></button>
                </div>
            </header>

            <div class="ndb:flex ndb:min-h-0 ndb:flex-1 ndb:flex-col ndb:sm:flex-row">
                <nav aria-label="Debug sections" class="ndb-scrollbar ndb:flex ndb:max-h-36 ndb:shrink-0 ndb:gap-0.5 ndb:overflow-x-auto ndb:border-b ndb:border-zinc-200/80 ndb:bg-zinc-50/70 ndb:p-2 ndb:backdrop-blur-xl ndb:sm:max-h-none ndb:sm:w-[210px] ndb:sm:flex-col ndb:sm:overflow-x-visible ndb:sm:overflow-y-auto ndb:sm:border-b-0 ndb:sm:border-r ndb:sm:p-3 ndb:dark:border-zinc-800/80 ndb:dark:bg-zinc-900/60">
                    <template x-if="orderedSections.length">
                        <div class="ndb:contents ndb:sm:flex ndb:sm:flex-col ndb:sm:gap-0.5">
                            <p class="ndb:hidden ndb:px-2 ndb:pb-1.5 ndb:pt-1 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-[0.14em] ndb:text-zinc-400 ndb:sm:block">Favorites</p>
                            <template x-for="section in orderedSections" :key="'favorite-' + section.key">
                                <div draggable="true" @dragstart="startFavoriteDrag(section.key, $event)" @dragover.prevent="hoverFavorite(section.key, $event.clientY > $event.currentTarget.getBoundingClientRect().top + ($event.currentTarget.offsetHeight / 2))" @dragleave="leaveFavorite(section.key)" @drop.prevent="dropFavorite(section.key, favoriteDropAfter)" @dragend="endFavoriteDrag()" class="ndb:relative ndb:flex ndb:shrink-0 ndb:items-center ndb:rounded-lg ndb:pr-1 ndb:transition ndb:hover:bg-zinc-200/60 ndb:dark:hover:bg-zinc-800/60" :class="(selected === section.key ? 'ndb-section-active' : '') + (favoriteDrag === section.key ? ' ndb:opacity-40' : '')">
                                    <span x-show.important="favoriteDrop === section.key && ! favoriteDropAfter" class="ndb:absolute ndb:inset-x-1 ndb:-top-0.5 ndb:z-10 ndb:h-0.5 ndb:rounded-full ndb:bg-indigo-500"></span>
                                    <span x-show.important="favoriteDrop === section.key && favoriteDropAfter" class="ndb:absolute ndb:inset-x-1 ndb:-bottom-0.5 ndb:z-10 ndb:h-0.5 ndb:rounded-full ndb:bg-indigo-500"></span>
                                    <button type="button" @click="selectSection(section.key)" @keydown.shift.arrow-up.prevent="moveFavorite(section.key, -1)" @keydown.shift.arrow-down.prevent="moveFavorite(section.key, 1)" class="ndb:flex ndb:h-9 ndb:min-w-0 ndb:flex-1 ndb:cursor-grab ndb:items-center ndb:gap-2 ndb:rounded-lg ndb:px-2.5 ndb:text-left ndb:text-xs ndb:font-semibold ndb:transition ndb:active:cursor-grabbing ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500" :class="selected === section.key ? '' : 'ndb:text-zinc-600 ndb:hover:text-zinc-950 ndb:dark:text-zinc-400 ndb:dark:hover:text-white'" :aria-label="section.label + '. Drag to reorder. Shift and arrow keys also reorder.'">
                                        <span class="ndb-section-label ndb:truncate" x-text="section.label"></span>
                                        <span x-show.important="section.count !== null" class="ndb-section-count ndb:ml-auto ndb:text-[10px] ndb:tabular-nums" :class="selected === section.key ? '' : 'ndb:text-zinc-400'" x-text="section.count"></span>
                                    </button>
                                    <button type="button" draggable="false" @dragstart.prevent @click.stop="toggleFavorite(section.key)" class="{{ $starButton }}" :aria-label="'Remove ' + section.label + ' from favorites'" title="Remove from favorites"><x-new-debug-bar::icon name="star-filled" class="ndb-favorite-star ndb:size-3.5" /></button>
                                </div>
                            </template>
                            <div class="ndb:hidden ndb:h-px ndb:bg-zinc-200 ndb:sm:my-2 ndb:sm:block ndb:dark:bg-zinc-800"></div>
                        </div>
                    </template>

                    <p class="ndb:hidden ndb:px-2 ndb:pb-1.5 ndb:pt-1 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-[0.14em] ndb:text-zinc-400 ndb:sm:block">Sections</p>
                    <template x-for="section in unpinnedSections" :key="section.key">
                        <div class="ndb:flex ndb:w-auto ndb:shrink-0 ndb:items-center ndb:rounded-lg ndb:pr-1 ndb:transition ndb:hover:bg-zinc-200/60 ndb:sm:w-full ndb:dark:hover:bg-zinc-800/60" :class="selected === section.key ? 'ndb-section-active' : ''">
                            <button type="button" @click="selectSection(section.key)" class="ndb:flex ndb:h-9 ndb:min-w-0 ndb:flex-1 ndb:items-center ndb:gap-2 ndb:rounded-lg ndb:px-2.5 ndb:text-left ndb:text-xs ndb:font-semibold ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500" :class="selected === section.key ? '' : 'ndb:text-zinc-600 ndb:hover:text-zinc-950 ndb:dark:text-zinc-400 ndb:dark:hover:text-white'">
                                <span class="ndb-section-label ndb:truncate" x-text="section.label"></span>
                                <span x-show.important="section.count !== null" class="ndb-section-count ndb:ml-auto ndb:text-[10px] ndb:tabular-nums" :class="selected === section.key ? '' : 'ndb:text-zinc-400'" x-text="section.count"></span>
                            </button>
                            <button type="button" @click.stop="toggleFavorite(section.key)" class="{{ $starButton }}" :aria-label="'Add ' + section.label + ' to favorites'" title="Add to favorites"><span class="ndb-section-star-outline"><x-new-debug-bar::icon name="star" class="ndb:size-3.5" /></span></button>
                        </div>
                    </template>
                </nav>

                <main x-ref="content" class="ndb-scrollbar ndb:min-w-0 ndb:flex-1 ndb:overflow-y-auto ndb:bg-white/70 ndb:dark:bg-zinc-950/70">
                    <div class="ndb:sticky ndb:top-0 ndb:z-10 ndb:flex ndb:h-12 ndb:items-center ndb:border-b ndb:border-zinc-100/80 ndb:bg-white/65 ndb:px-4 ndb:backdrop-blur-xl ndb:sm:px-6 ndb:dark:border-zinc-900/80 ndb:dark:bg-zinc-950/65">
                        <h2 class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-sm ndb:font-bold" x-text="selectedSection.label"></h2>
                    </div>

                    <div wire:loading.flex wire:target="loadDetails" class="ndb:min-h-64 ndb:items-center ndb:justify-center ndb:p-8">
                        <div class="ndb:text-center"><span class="ndb:mx-auto ndb:grid ndb:size-10 ndb:animate-pulse ndb:place-items-center ndb:rounded-xl ndb:bg-indigo-50 ndb:text-indigo-600 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300"><x-new-debug-bar::icon name="clock" class="ndb:size-4" /></span><p class="ndb:mt-3 ndb:text-sm ndb:font-semibold">Loading request details…</p></div>
                    </div>

                    @if ($detailsLoaded && $profile !== [])
                        <div wire:loading.remove wire:target="loadDetails" class="ndb:p-4 ndb:sm:p-6">
                            @foreach ($profile['sections'] as $sectionKey => $section)
                                <template x-if="selected === @js($sectionKey)">
                                <section wire:key="section-{{ $sectionKey }}" class="ndb:space-y-4">
                                    @if ($sectionKey === 'overview')
                                        <div class="ndb:grid ndb:grid-cols-2 ndb:gap-3 ndb:lg:grid-cols-4">
                                            @foreach ([['Duration', $profile['metrics']['duration_ms'].' ms', 'clock'], ['Peak memory', $profile['metrics']['peak_memory_mb'].' MB', 'memory'], ['Queries', $profile['sections']['queries']['summary']['count'], 'database'], ['Status', $profile['sections']['request']['summary']['status'], 'check']] as [$label, $value, $icon])
                                                <div class="ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:bg-zinc-50 ndb:p-3.5 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900"><x-new-debug-bar::icon :name="$icon" class="ndb:size-4 ndb:text-indigo-500" /><p class="ndb:mt-2 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $value }}</p><p class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</p></div>
                                            @endforeach
                                        </div>
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
                                    @elseif ($sectionKey === 'request')
                                        <div class="ndb:grid ndb:grid-cols-2 ndb:gap-3 ndb:lg:grid-cols-4">
                                            @foreach (['method' => 'Method', 'status' => 'Status', 'route' => 'Route', 'action' => 'Controller'] as $key => $label)
                                                <div class="ndb:min-w-0 ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:p-3 ndb:dark:border-zinc-800"><p class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</p><p class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-semibold">{{ $section['payload'][$key] ?: '—' }}</p></div>
                                            @endforeach
                                        </div>
                                        <pre class="ndb-code ndb-scrollbar"><code data-ndb-language="json">{{ json_encode($section['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                    @elseif ($sectionKey === 'queries')
                                        @forelse ($section['payload']['items'] as $index => $query)
                                            <article wire:key="query-{{ $index }}" class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800">
                                                <div class="ndb:flex ndb:items-center ndb:gap-2 ndb:border-b ndb:border-zinc-200 ndb:bg-zinc-50 ndb:px-3 ndb:py-2 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900">
                                                    <span class="ndb:text-[10px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-400">#{{ $index + 1 }}</span><span class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $query['connection'] }}</span>
                                                    @if ($query['duration_ms'] >= config('new-debug-bar.slow_query_ms', 100))<span class="ndb:rounded-md ndb:bg-amber-100 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:text-amber-700 ndb:dark:bg-amber-950 ndb:dark:text-amber-300">Slow</span>@endif
                                                    <span class="ndb:ml-auto ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $query['duration_ms'] }} ms</span>
                                                    <button type="button" @click="navigator.clipboard?.writeText(@js($query['sql']))" class="{{ $iconButton }} ndb:size-7" aria-label="Copy query {{ $index + 1 }}" title="Copy query"><x-new-debug-bar::icon name="copy" class="ndb:size-3.5" /></button>
                                                </div>
                                                <pre class="ndb-code ndb-scrollbar ndb:rounded-none"><code data-ndb-language="sql">{{ $query['sql'] }}</code></pre>
                                                @if ($query['bindings'] !== [])
                                                    <details class="ndb:group ndb:border-t ndb:border-zinc-200 ndb:bg-zinc-100 ndb:text-zinc-700 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"><summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-2 ndb:px-3 ndb:py-2 ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-500 ndb:dark:text-zinc-400"><span>Bindings</span><span class="ndb:rounded ndb:bg-zinc-200 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:tabular-nums ndb:text-zinc-600 ndb:dark:bg-zinc-800 ndb:dark:text-zinc-300">{{ count($query['bindings']) }}</span><x-new-debug-bar::icon name="chevron-down" class="ndb:ml-auto ndb:size-3.5 ndb:transition ndb:group-open:rotate-180" /></summary><pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="json">{{ json_encode($query['bindings'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre></details>
                                                @endif
                                            </article>
                                        @empty
                                            <x-new-debug-bar::empty-state label="No queries ran during this request." />
                                        @endforelse
                                    @elseif ($sectionKey === 'exceptions')
                                        @forelse ($section['payload']['items'] as $index => $exception)
                                            <article wire:key="exception-{{ $index }}" class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-red-200 ndb:dark:border-red-950">
                                                <div class="ndb:flex ndb:items-start ndb:gap-3 ndb:bg-red-50 ndb:p-4 ndb:dark:bg-red-950/50"><span class="ndb:grid ndb:size-8 ndb:shrink-0 ndb:place-items-center ndb:rounded-lg ndb:bg-red-100 ndb:text-red-600 ndb:dark:bg-red-950 ndb:dark:text-red-300"><x-new-debug-bar::icon name="warning" class="ndb:size-4" /></span><div class="ndb:min-w-0"><p class="ndb:truncate ndb:text-xs ndb:font-bold ndb:text-red-700 ndb:dark:text-red-300">{{ $exception['class'] }}</p><p class="ndb:mt-1 ndb:text-sm ndb:font-semibold">{{ $exception['message'] ?: 'No message' }}</p><p class="ndb:mt-1 ndb:truncate ndb:text-[10px] ndb:text-zinc-500">{{ $exception['file'] }}:{{ $exception['line'] }}</p></div></div>
                                                <pre class="ndb-code ndb-scrollbar ndb:max-h-72 ndb:rounded-none ndb:text-zinc-400"><code>{{ $exception['trace'] }}</code></pre>
                                            </article>
                                        @empty
                                            <x-new-debug-bar::empty-state label="No exceptions were reported." success />
                                        @endforelse
                                    @else
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
                                </template>
                            @endforeach
                        </div>
                    @elseif ($detailsLoaded)
                        <div class="ndb:p-8 ndb:text-center"><p class="ndb:text-sm ndb:font-semibold">This profile is no longer available.</p></div>
                    @endif
                </main>
            </div>
        </aside>
    </div>

    <div x-cloak x-show.important="paletteOpen" class="ndb:pointer-events-auto ndb:fixed ndb:inset-0 ndb:z-50 ndb:grid ndb:justify-items-center ndb:bg-zinc-950/45 ndb:px-3 ndb:pt-[12vh] ndb:backdrop-blur-sm" @click.self="closePalette()">
        <div x-show.important="paletteOpen" x-transition class="ndb:w-full ndb:max-w-xl ndb:self-start ndb:overflow-hidden ndb:rounded-2xl ndb:border ndb:border-white/70 ndb:bg-white/90 ndb:shadow-2xl ndb:backdrop-blur-2xl ndb:dark:border-zinc-700/80 ndb:dark:bg-zinc-900/90" role="dialog" aria-modal="true" aria-label="Command palette">
            <div class="ndb:flex ndb:items-center ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:px-4 ndb:dark:border-zinc-800"><x-new-debug-bar::icon name="search" class="ndb:size-5 ndb:text-zinc-400" /><input x-ref="paletteSearch" x-model="paletteSearch" @input="paletteIndex = 0" @keydown.down.prevent="movePalette(1)" @keydown.up.prevent="movePalette(-1)" @keydown.enter.prevent="runActiveCommand()" type="search" placeholder="Jump to a section or change a setting…" class="ndb:h-14 ndb:min-w-0 ndb:flex-1 ndb:border-0 ndb:bg-transparent ndb:text-sm ndb:font-medium ndb:outline-none ndb:placeholder:text-zinc-400" /><kbd class="ndb:rounded-md ndb:border ndb:border-zinc-200 ndb:bg-zinc-50 ndb:px-1.5 ndb:py-1 ndb:text-[9px] ndb:font-bold ndb:text-zinc-400 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-800">ESC</kbd></div>
            <div class="ndb-scrollbar ndb:max-h-[min(420px,60vh)] ndb:overflow-y-auto ndb:p-2">
                <template x-for="(command, index) in filteredCommands" :key="command.id">
                    <button type="button" @mouseenter="paletteIndex = index" @click="runCommand(command.id)" class="ndb:flex ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:px-3 ndb:py-2.5 ndb:text-left ndb:transition" :class="paletteIndex === index ? 'ndb:bg-indigo-50 ndb:text-indigo-800 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-200' : 'ndb:text-zinc-700 ndb:dark:text-zinc-300'"><span class="ndb:flex-1 ndb:text-sm ndb:font-semibold" x-text="command.label"></span><span class="ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400" x-text="command.hint"></span></button>
                </template>
                <p x-show.important="filteredCommands.length === 0" class="ndb:px-3 ndb:py-8 ndb:text-center ndb:text-sm ndb:text-zinc-500">No matching commands.</p>
            </div>
            <div class="ndb:flex ndb:items-center ndb:gap-3 ndb:border-t ndb:border-zinc-200 ndb:bg-zinc-50 ndb:px-4 ndb:py-2 ndb:text-[10px] ndb:font-medium ndb:text-zinc-400 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"><span>↑↓ Navigate</span><span>↵ Select</span><span class="ndb:ml-auto">⌘/Ctrl ⇧ P</span></div>
        </div>
    </div>
</div>
