@php
    $button = 'ndb:inline-flex ndb:items-center ndb:justify-center ndb:rounded-xl ndb:text-zinc-500 ndb:transition ndb:hover:bg-zinc-100 ndb:hover:text-zinc-900 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-zinc-400 ndb:dark:hover:bg-zinc-800 ndb:dark:hover:text-white';
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
        x-show="mode === 'bar' && ! inspectorOpen"
        x-transition.opacity.duration.150ms
        role="toolbar"
        aria-label="New Debug Bar"
        class="ndb:pointer-events-auto ndb:fixed ndb:bottom-4 ndb:left-1/2 ndb:flex ndb:max-w-[calc(100vw-24px)] ndb:-translate-x-1/2 ndb:items-center ndb:gap-1 ndb:rounded-2xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/95 ndb:p-1.5 ndb:shadow-[0_18px_60px_-18px_rgba(24,24,27,0.4)] ndb:backdrop-blur-xl ndb:dark:border-zinc-700/80 ndb:dark:bg-zinc-900/95"
    >
        <button type="button" @click="openInspector('overview')" class="ndb:flex ndb:items-center ndb:gap-2 ndb:rounded-xl ndb:px-2.5 ndb:py-2 ndb:transition ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-zinc-800" aria-label="Open New Debug Bar overview">
            <span class="ndb:grid ndb:size-8 ndb:place-items-center ndb:rounded-lg ndb:bg-indigo-600 ndb:text-white ndb:shadow-sm ndb:shadow-indigo-600/30 ndb:dark:bg-indigo-500">
                <x-new-debug-bar::icon name="sparkles" class="ndb:size-4" />
            </span>
            <span class="ndb:hidden ndb:text-left ndb:sm:block">
                <span class="ndb:block ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-[0.12em] ndb:text-zinc-400">New Debug Bar</span>
                <span class="ndb:block ndb:text-xs ndb:font-semibold" x-text="summary.method + ' ' + summary.path"></span>
            </span>
        </button>

        <span class="ndb:mx-0.5 ndb:h-8 ndb:w-px ndb:bg-zinc-200 ndb:dark:bg-zinc-700"></span>

        <button type="button" @click="openInspector('overview')" class="ndb:flex ndb:items-center ndb:gap-2 ndb:rounded-xl ndb:px-2.5 ndb:py-2 ndb:text-left ndb:transition ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-zinc-800">
            <span class="ndb:size-2 ndb:rounded-full" :class="summary.warning ? 'ndb:bg-amber-500' : 'ndb:bg-emerald-500'"></span>
            <span>
                <span class="ndb:block ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Environment</span>
                <span class="ndb:block ndb:text-xs ndb:font-bold" x-text="summary.environment"></span>
            </span>
        </button>

        <button type="button" @click="openInspector('request')" class="ndb:hidden ndb:items-center ndb:gap-2 ndb:rounded-xl ndb:px-2.5 ndb:py-2 ndb:text-left ndb:transition ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:md:flex ndb:dark:hover:bg-zinc-800">
            <x-new-debug-bar::icon name="clock" class="ndb:size-4 ndb:text-indigo-500" />
            <span><span class="ndb:block ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Duration</span><span class="ndb:block ndb:text-xs ndb:font-bold" x-text="summary.duration_ms + ' ms'"></span></span>
        </button>

        <button type="button" @click="openInspector('overview')" class="ndb:hidden ndb:items-center ndb:gap-2 ndb:rounded-xl ndb:px-2.5 ndb:py-2 ndb:text-left ndb:transition ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:lg:flex ndb:dark:hover:bg-zinc-800">
            <x-new-debug-bar::icon name="memory" class="ndb:size-4 ndb:text-violet-500" />
            <span><span class="ndb:block ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Peak</span><span class="ndb:block ndb:text-xs ndb:font-bold" x-text="summary.memory_mb + ' MB'"></span></span>
        </button>

        <button type="button" @click="openInspector('queries')" class="ndb:hidden ndb:items-center ndb:gap-2 ndb:rounded-xl ndb:px-2.5 ndb:py-2 ndb:text-left ndb:transition ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:sm:flex ndb:dark:hover:bg-zinc-800">
            <x-new-debug-bar::icon name="database" class="ndb:size-4 ndb:text-cyan-500" />
            <span><span class="ndb:block ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Queries</span><span class="ndb:block ndb:text-xs ndb:font-bold"><span x-text="summary.query_count"></span> <span class="ndb:font-medium ndb:text-zinc-400" x-text="summary.query_duration_ms + ' ms'"></span></span></span>
        </button>

        <span class="ndb:mx-0.5 ndb:h-8 ndb:w-px ndb:bg-zinc-200 ndb:dark:bg-zinc-700"></span>

        <button type="button" @click="openPalette()" class="{{ $button }} ndb:size-9" aria-label="Open command palette" title="Command palette (Command or Control + Shift + P)">
            <x-new-debug-bar::icon name="search" class="ndb:size-4" />
        </button>
        <button type="button" @click="openInspector()" class="{{ $button }} ndb:size-9" aria-label="Expand New Debug Bar">
            <x-new-debug-bar::icon name="expand" class="ndb:size-4" />
        </button>
        <button type="button" @click="useMode('floating')" class="{{ $button }} ndb:size-9" aria-label="Minimize to floating bubble">
            <x-new-debug-bar::icon name="minimize" class="ndb:size-4" />
        </button>
    </div>

    <div
        x-cloak
        x-show="mode === 'floating' && ! inspectorOpen"
        :style="bubbleStyle"
        @pointerdown="startDrag($event)"
        @pointermove.window="drag($event)"
        @pointerup.window="finishDrag()"
        @pointercancel.window="finishDrag()"
        class="ndb:pointer-events-auto ndb:fixed ndb:left-0 ndb:top-0 ndb:flex ndb:h-16 ndb:w-[min(236px,calc(100vw-24px))] ndb:touch-none ndb:select-none ndb:items-center ndb:gap-3 ndb:rounded-2xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/95 ndb:px-3 ndb:shadow-[0_18px_55px_-16px_rgba(24,24,27,0.48)] ndb:backdrop-blur-xl ndb:transition-shadow ndb:hover:shadow-[0_20px_60px_-14px_rgba(79,70,229,0.35)] ndb:dark:border-zinc-700/80 ndb:dark:bg-zinc-900/95"
        role="button"
        tabindex="0"
        aria-label="Open New Debug Bar. Drag to move."
        @keydown.enter.prevent="openInspector()"
        @keydown.space.prevent="openInspector()"
    >
        <span class="ndb:grid ndb:size-10 ndb:shrink-0 ndb:place-items-center ndb:rounded-xl ndb:bg-indigo-600 ndb:text-white ndb:shadow-md ndb:shadow-indigo-600/30 ndb:dark:bg-indigo-500">
            <x-new-debug-bar::icon name="sparkles" class="ndb:size-5" />
        </span>
        <span class="ndb:min-w-0 ndb:flex-1">
            <span class="ndb:flex ndb:items-center ndb:gap-1.5">
                <span class="ndb:size-1.5 ndb:rounded-full" :class="summary.warning ? 'ndb:bg-amber-500' : 'ndb:bg-emerald-500'"></span>
                <span class="ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-[0.12em]" x-text="summary.environment"></span>
            </span>
            <span class="ndb:mt-0.5 ndb:flex ndb:items-baseline ndb:gap-2 ndb:whitespace-nowrap">
                <span class="ndb:text-sm ndb:font-bold" x-text="summary.duration_ms + ' ms'"></span>
                <span class="ndb:text-xs ndb:font-medium ndb:text-zinc-400" x-text="summary.memory_mb + ' MB'"></span>
            </span>
        </span>
        <x-new-debug-bar::icon name="grip" class="ndb:size-5 ndb:text-zinc-300 ndb:dark:text-zinc-600" />
        <button type="button" @pointerdown.stop @click.stop="useMode('bar')" class="{{ $button }} ndb:absolute ndb:-right-2 ndb:-top-2 ndb:size-7 ndb:border ndb:border-zinc-200 ndb:bg-white ndb:shadow-sm ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900" aria-label="Return to bottom bar">
            <x-new-debug-bar::icon name="arrow-left" class="ndb:size-3.5" />
        </button>
    </div>

    <div x-cloak x-show="inspectorOpen" class="ndb:pointer-events-auto ndb:fixed ndb:inset-0" role="presentation">
        <div x-show="inspectorOpen" x-transition.opacity.duration.150ms @click="closeInspector()" class="ndb:absolute ndb:inset-0 ndb:bg-zinc-950/35 ndb:backdrop-blur-[2px] ndb:dark:bg-black/60"></div>

        <aside
            x-show="inspectorOpen"
            x-transition:enter="ndb:transition ndb:duration-200 ndb:ease-out"
            x-transition:enter-start="ndb:translate-x-full"
            x-transition:enter-end="ndb:translate-x-0"
            x-transition:leave="ndb:transition ndb:duration-150 ndb:ease-in"
            x-transition:leave-start="ndb:translate-x-0"
            x-transition:leave-end="ndb:translate-x-full"
            role="dialog"
            aria-modal="true"
            aria-label="New Debug Bar inspector"
            class="ndb:absolute ndb:inset-y-0 ndb:right-0 ndb:flex ndb:w-full ndb:max-w-[780px] ndb:flex-col ndb:border-l ndb:border-zinc-200 ndb:bg-white ndb:shadow-2xl ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"
        >
            <header class="ndb:flex ndb:h-[76px] ndb:shrink-0 ndb:items-center ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:px-4 ndb:sm:px-5 ndb:dark:border-zinc-800">
                <span class="ndb:grid ndb:size-10 ndb:shrink-0 ndb:place-items-center ndb:rounded-xl ndb:bg-indigo-600 ndb:text-white ndb:shadow-md ndb:shadow-indigo-600/25 ndb:dark:bg-indigo-500">
                    <x-new-debug-bar::icon name="sparkles" class="ndb:size-5" />
                </span>
                <div class="ndb:min-w-0 ndb:flex-1">
                    <div class="ndb:flex ndb:items-center ndb:gap-2">
                        <h1 class="ndb:truncate ndb:text-base ndb:font-bold ndb:tracking-tight">New Debug Bar</h1>
                        <span class="ndb:rounded-md ndb:bg-emerald-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-emerald-700 ndb:dark:bg-emerald-950 ndb:dark:text-emerald-300" x-text="summary.environment"></span>
                    </div>
                    <p class="ndb:truncate ndb:text-xs ndb:font-medium ndb:text-zinc-500 ndb:dark:text-zinc-400"><span x-text="summary.method"></span> <span x-text="summary.path"></span> <span class="ndb:px-1 ndb:text-zinc-300 ndb:dark:text-zinc-700">·</span> <span x-text="summary.duration_ms + ' ms'"></span></p>
                </div>
                <button type="button" @click="openPalette()" class="{{ $button }} ndb:size-9" aria-label="Open command palette"><x-new-debug-bar::icon name="search" class="ndb:size-4" /></button>
                <button type="button" @click="cycleTheme()" class="{{ $button }} ndb:size-9" :aria-label="'Theme: ' + theme"><x-new-debug-bar::icon name="theme" class="ndb:size-4" /></button>
                <button type="button" @click="useMode('bar')" class="{{ $button }} ndb:hidden ndb:size-9 ndb:sm:inline-flex" aria-label="Return to bottom bar"><x-new-debug-bar::icon name="minimize" class="ndb:size-4" /></button>
                <button type="button" @click="closeInspector()" class="{{ $button }} ndb:size-9" aria-label="Close inspector"><x-new-debug-bar::icon name="close" class="ndb:size-4" /></button>
            </header>

            <div class="ndb:flex ndb:min-h-0 ndb:flex-1 ndb:flex-col ndb:sm:flex-row">
                <nav aria-label="Debug sections" class="ndb-scrollbar ndb:flex ndb:max-h-36 ndb:shrink-0 ndb:gap-1 ndb:overflow-x-auto ndb:border-b ndb:border-zinc-200 ndb:bg-zinc-50 ndb:p-2 ndb:sm:max-h-none ndb:sm:w-[205px] ndb:sm:flex-col ndb:sm:overflow-x-visible ndb:sm:overflow-y-auto ndb:sm:border-b-0 ndb:sm:border-r ndb:sm:p-3 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/60">
                    <template x-if="orderedSections.length">
                        <div class="ndb:contents ndb:sm:block">
                            <p class="ndb:hidden ndb:px-2 ndb:pb-1.5 ndb:pt-1 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-[0.14em] ndb:text-zinc-400 ndb:sm:block">Favorites</p>
                            <template x-for="section in orderedSections" :key="'favorite-' + section.key">
                                <div draggable="true" @dragstart="startFavoriteDrag(section.key)" @dragover.prevent @drop="dropFavorite(section.key)" class="ndb:group ndb:relative ndb:flex ndb:shrink-0 ndb:items-center">
                                    <button type="button" @click="openInspector(section.key)" class="ndb:flex ndb:h-10 ndb:w-full ndb:items-center ndb:gap-2 ndb:rounded-xl ndb:px-2.5 ndb:text-left ndb:text-xs ndb:font-semibold ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500" :class="selected === section.key ? 'ndb:bg-white ndb:text-indigo-700 ndb:shadow-sm ndb:ring-1 ndb:ring-zinc-200 ndb:dark:bg-zinc-800 ndb:dark:text-indigo-300 ndb:dark:ring-zinc-700' : 'ndb:text-zinc-600 ndb:hover:bg-white/70 ndb:hover:text-zinc-950 ndb:dark:text-zinc-400 ndb:dark:hover:bg-zinc-800/70 ndb:dark:hover:text-white'">
                                        <span class="ndb:grid ndb:size-6 ndb:place-items-center ndb:rounded-lg ndb:bg-indigo-50 ndb:text-[10px] ndb:font-bold ndb:text-indigo-600 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300" x-text="section.label.slice(0, 1)"></span>
                                        <span class="ndb:truncate" x-text="section.label"></span>
                                        <span x-show="section.count !== null" class="ndb:ml-auto ndb:text-[10px] ndb:tabular-nums ndb:text-zinc-400" x-text="section.count"></span>
                                    </button>
                                    <span class="ndb:absolute ndb:right-1 ndb:hidden ndb:items-center ndb:rounded-md ndb:bg-white ndb:shadow-sm ndb:group-hover:flex ndb:group-focus-within:flex ndb:dark:bg-zinc-800">
                                        <button type="button" @click.stop="moveFavorite(section.key, -1)" class="{{ $button }} ndb:size-6" :aria-label="'Move ' + section.label + ' up'"><x-new-debug-bar::icon name="chevron-up" class="ndb:size-3" /></button>
                                        <button type="button" @click.stop="moveFavorite(section.key, 1)" class="{{ $button }} ndb:size-6" :aria-label="'Move ' + section.label + ' down'"><x-new-debug-bar::icon name="chevron-down" class="ndb:size-3" /></button>
                                    </span>
                                </div>
                            </template>
                            <div class="ndb:hidden ndb:h-px ndb:bg-zinc-200 ndb:sm:my-2 ndb:sm:block ndb:dark:bg-zinc-800"></div>
                        </div>
                    </template>

                    <p class="ndb:hidden ndb:px-2 ndb:pb-1.5 ndb:pt-1 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-[0.14em] ndb:text-zinc-400 ndb:sm:block">All sections</p>
                    <template x-for="section in remainingSections" :key="section.key">
                        <button type="button" @click="openInspector(section.key)" class="ndb:flex ndb:h-10 ndb:w-auto ndb:shrink-0 ndb:items-center ndb:gap-2 ndb:rounded-xl ndb:px-2.5 ndb:text-left ndb:text-xs ndb:font-semibold ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:sm:w-full" :class="selected === section.key ? 'ndb:bg-white ndb:text-indigo-700 ndb:shadow-sm ndb:ring-1 ndb:ring-zinc-200 ndb:dark:bg-zinc-800 ndb:dark:text-indigo-300 ndb:dark:ring-zinc-700' : 'ndb:text-zinc-600 ndb:hover:bg-white/70 ndb:hover:text-zinc-950 ndb:dark:text-zinc-400 ndb:dark:hover:bg-zinc-800/70 ndb:dark:hover:text-white'">
                            <span class="ndb:grid ndb:size-6 ndb:place-items-center ndb:rounded-lg ndb:bg-zinc-100 ndb:text-[10px] ndb:font-bold ndb:text-zinc-500 ndb:dark:bg-zinc-800 ndb:dark:text-zinc-400" x-text="section.label.slice(0, 1)"></span>
                            <span class="ndb:truncate" x-text="section.label"></span>
                            <span x-show="section.count !== null" class="ndb:ml-auto ndb:text-[10px] ndb:tabular-nums ndb:text-zinc-400" x-text="section.count"></span>
                        </button>
                    </template>
                </nav>

                <main class="ndb-scrollbar ndb:min-w-0 ndb:flex-1 ndb:overflow-y-auto ndb:bg-white ndb:dark:bg-zinc-950">
                    <div class="ndb:sticky ndb:top-0 ndb:z-10 ndb:flex ndb:h-14 ndb:items-center ndb:border-b ndb:border-zinc-100 ndb:bg-white/90 ndb:px-4 ndb:backdrop-blur-lg ndb:sm:px-6 ndb:dark:border-zinc-900 ndb:dark:bg-zinc-950/90">
                        <div class="ndb:min-w-0 ndb:flex-1">
                            <h2 class="ndb:truncate ndb:text-sm ndb:font-bold" x-text="selectedSection.label"></h2>
                            <p class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Current request</p>
                        </div>
                        <button type="button" @click="toggleFavorite(selected)" class="{{ $button }} ndb:h-8 ndb:gap-1.5 ndb:px-2.5 ndb:text-xs ndb:font-semibold" :aria-pressed="favorites.includes(selected)" :aria-label="favorites.includes(selected) ? 'Remove from favorites' : 'Add to favorites'">
                            <x-new-debug-bar::icon name="pin" class="ndb:size-3.5" />
                            <span x-text="favorites.includes(selected) ? 'Pinned' : 'Pin'"></span>
                        </button>
                    </div>

                    <div wire:loading.flex wire:target="loadDetails" class="ndb:min-h-[360px] ndb:items-center ndb:justify-center ndb:p-8">
                        <div class="ndb:text-center">
                            <span class="ndb:mx-auto ndb:grid ndb:size-12 ndb:animate-pulse ndb:place-items-center ndb:rounded-2xl ndb:bg-indigo-50 ndb:text-indigo-600 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300"><x-new-debug-bar::icon name="sparkles" /></span>
                            <p class="ndb:mt-3 ndb:text-sm ndb:font-semibold">Loading request details…</p>
                        </div>
                    </div>

                    @if ($detailsLoaded && $profile !== [])
                        <div wire:loading.remove wire:target="loadDetails" class="ndb:p-4 ndb:sm:p-6">
                            @foreach ($profile['sections'] as $sectionKey => $section)
                                <section x-show="selected === @js($sectionKey)" wire:key="section-{{ $sectionKey }}" class="ndb:space-y-4">
                                    @if ($sectionKey === 'overview')
                                        <div class="ndb:grid ndb:grid-cols-2 ndb:gap-3 ndb:lg:grid-cols-4">
                                            @foreach ([['Duration', $profile['metrics']['duration_ms'].' ms', 'clock'], ['Peak memory', $profile['metrics']['peak_memory_mb'].' MB', 'memory'], ['Queries', $profile['sections']['queries']['summary']['count'], 'database'], ['Status', $profile['sections']['request']['summary']['status'], 'check']] as [$label, $value, $icon])
                                                <div class="ndb:rounded-2xl ndb:border ndb:border-zinc-200 ndb:bg-zinc-50 ndb:p-3.5 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900">
                                                    <x-new-debug-bar::icon :name="$icon" class="ndb:size-4 ndb:text-indigo-500" />
                                                    <p class="ndb:mt-2 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $value }}</p>
                                                    <p class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="ndb:rounded-2xl ndb:border ndb:border-zinc-200 ndb:p-4 ndb:dark:border-zinc-800">
                                            <h3 class="ndb:text-xs ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Runtime</h3>
                                            <dl class="ndb:mt-3 ndb:grid ndb:grid-cols-2 ndb:gap-x-5 ndb:gap-y-3">
                                                @foreach ($section['payload'] as $label => $value)
                                                    <div><dt class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ str($label)->replace('_', ' ')->title() }}</dt><dd class="ndb:mt-0.5 ndb:truncate ndb:text-sm ndb:font-semibold">{{ $value }}</dd></div>
                                                @endforeach
                                            </dl>
                                        </div>
                                        <div class="ndb:grid ndb:grid-cols-2 ndb:gap-2 ndb:sm:grid-cols-4">
                                            @foreach ($summary['section_counts'] as $key => $count)
                                                @if ($count !== null)
                                                    <button type="button" @click="selected = @js($key)" class="ndb:flex ndb:items-center ndb:justify-between ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:px-3 ndb:py-2.5 ndb:text-left ndb:transition ndb:hover:border-indigo-300 ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:border-zinc-800 ndb:dark:hover:border-indigo-800 ndb:dark:hover:bg-indigo-950/50"><span class="ndb:text-xs ndb:font-semibold">{{ $profile['sections'][$key]['label'] }}</span><span class="ndb:text-xs ndb:font-bold ndb:tabular-nums ndb:text-zinc-400">{{ $count }}</span></button>
                                                @endif
                                            @endforeach
                                        </div>
                                    @elseif ($sectionKey === 'request')
                                        <div class="ndb:grid ndb:grid-cols-2 ndb:gap-3">
                                            @foreach (['method' => 'Method', 'status' => 'Status', 'route' => 'Route', 'action' => 'Controller'] as $key => $label)
                                                <div class="ndb:min-w-0 ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:p-3 ndb:dark:border-zinc-800"><p class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $label }}</p><p class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:font-semibold">{{ $section['payload'][$key] ?: '—' }}</p></div>
                                            @endforeach
                                        </div>
                                        <div class="ndb:rounded-2xl ndb:border ndb:border-zinc-200 ndb:bg-zinc-950 ndb:p-4 ndb:text-zinc-100 ndb:dark:border-zinc-800"><pre class="ndb-scrollbar ndb:overflow-x-auto ndb:whitespace-pre-wrap ndb:break-words ndb:font-mono ndb:text-[11px] ndb:leading-5">{{ json_encode($section['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
                                    @elseif ($sectionKey === 'queries')
                                        @forelse ($section['payload']['items'] as $index => $query)
                                            <article wire:key="query-{{ $index }}" class="ndb:overflow-hidden ndb:rounded-2xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800">
                                                <div class="ndb:flex ndb:items-center ndb:gap-2 ndb:border-b ndb:border-zinc-200 ndb:bg-zinc-50 ndb:px-3.5 ndb:py-2.5 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900">
                                                    <span class="ndb:grid ndb:size-6 ndb:place-items-center ndb:rounded-lg ndb:bg-cyan-100 ndb:text-[10px] ndb:font-bold ndb:text-cyan-700 ndb:dark:bg-cyan-950 ndb:dark:text-cyan-300">{{ $index + 1 }}</span>
                                                    <span class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">{{ $query['connection'] }}</span>
                                                    @if ($query['duration_ms'] >= config('new-debug-bar.slow_query_ms', 100))<span class="ndb:rounded-md ndb:bg-amber-100 ndb:px-1.5 ndb:py-0.5 ndb:text-[9px] ndb:font-bold ndb:uppercase ndb:text-amber-700 ndb:dark:bg-amber-950 ndb:dark:text-amber-300">Slow</span>@endif
                                                    <span class="ndb:ml-auto ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $query['duration_ms'] }} ms</span>
                                                    <button type="button" @click="navigator.clipboard?.writeText(@js($query['sql']))" class="{{ $button }} ndb:h-7 ndb:px-2 ndb:text-[10px] ndb:font-bold">Copy</button>
                                                </div>
                                                <pre class="ndb-scrollbar ndb:overflow-x-auto ndb:bg-zinc-950 ndb:p-4 ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-cyan-100">{{ $query['sql'] }}</pre>
                                                @if ($query['bindings'] !== [])<pre class="ndb-scrollbar ndb:overflow-x-auto ndb:border-t ndb:border-zinc-800 ndb:bg-zinc-900 ndb:px-4 ndb:py-2.5 ndb:font-mono ndb:text-[10px] ndb:text-zinc-400">{{ json_encode($query['bindings'], JSON_UNESCAPED_SLASHES) }}</pre>@endif
                                            </article>
                                        @empty
                                            <x-new-debug-bar::empty-state label="No queries ran during this request." />
                                        @endforelse
                                    @elseif ($sectionKey === 'exceptions')
                                        @forelse ($section['payload']['items'] as $index => $exception)
                                            <article wire:key="exception-{{ $index }}" class="ndb:overflow-hidden ndb:rounded-2xl ndb:border ndb:border-red-200 ndb:dark:border-red-950">
                                                <div class="ndb:flex ndb:items-start ndb:gap-3 ndb:bg-red-50 ndb:p-4 ndb:dark:bg-red-950/50"><span class="ndb:grid ndb:size-8 ndb:shrink-0 ndb:place-items-center ndb:rounded-xl ndb:bg-red-100 ndb:text-red-600 ndb:dark:bg-red-950 ndb:dark:text-red-300"><x-new-debug-bar::icon name="warning" class="ndb:size-4" /></span><div class="ndb:min-w-0"><p class="ndb:truncate ndb:text-xs ndb:font-bold ndb:text-red-700 ndb:dark:text-red-300">{{ $exception['class'] }}</p><p class="ndb:mt-1 ndb:text-sm ndb:font-semibold">{{ $exception['message'] ?: 'No message' }}</p><p class="ndb:mt-1 ndb:truncate ndb:text-[10px] ndb:text-zinc-500">{{ $exception['file'] }}:{{ $exception['line'] }}</p></div></div>
                                                <pre class="ndb-scrollbar ndb:max-h-72 ndb:overflow-auto ndb:bg-zinc-950 ndb:p-4 ndb:font-mono ndb:text-[10px] ndb:leading-5 ndb:text-zinc-400">{{ $exception['trace'] }}</pre>
                                            </article>
                                        @empty
                                            <x-new-debug-bar::empty-state label="No exceptions were reported." success />
                                        @endforelse
                                    @else
                                        @forelse ($section['payload']['items'] as $index => $item)
                                            <article wire:key="{{ $sectionKey }}-{{ $index }}" class="ndb:rounded-2xl ndb:border ndb:border-zinc-200 ndb:p-4 ndb:dark:border-zinc-800">
                                                <div class="ndb:mb-2 ndb:flex ndb:items-center ndb:gap-2"><span class="ndb:grid ndb:size-6 ndb:place-items-center ndb:rounded-lg ndb:bg-zinc-100 ndb:text-[10px] ndb:font-bold ndb:text-zinc-500 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-400">{{ $index + 1 }}</span><span class="ndb:text-xs ndb:font-bold">{{ $item['model'] ?? $item['name'] ?? $item['event'] ?? $item['level'] ?? $item['operation'] ?? $section['label'] }}</span></div>
                                                <pre class="ndb-scrollbar ndb:overflow-x-auto ndb:whitespace-pre-wrap ndb:break-words ndb:rounded-xl ndb:bg-zinc-50 ndb:p-3 ndb:font-mono ndb:text-[10px] ndb:leading-5 ndb:text-zinc-600 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300">{{ json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </article>
                                        @empty
                                            <x-new-debug-bar::empty-state :label="'No '.strtolower($section['label']).' were captured.'" />
                                        @endforelse
                                    @endif
                                </section>
                            @endforeach
                        </div>
                    @elseif ($detailsLoaded)
                        <div class="ndb:p-8 ndb:text-center"><p class="ndb:text-sm ndb:font-semibold">This profile is no longer available.</p></div>
                    @endif
                </main>
            </div>
        </aside>
    </div>

    <div x-cloak x-show="paletteOpen" class="ndb:pointer-events-auto ndb:fixed ndb:inset-0 ndb:z-50 ndb:grid ndb:place-items-start ndb:bg-zinc-950/45 ndb:px-3 ndb:pt-[12vh] ndb:backdrop-blur-sm" @click.self="closePalette()">
        <div x-show="paletteOpen" x-transition class="ndb:w-full ndb:max-w-xl ndb:overflow-hidden ndb:rounded-2xl ndb:border ndb:border-zinc-200 ndb:bg-white ndb:shadow-2xl ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900" role="dialog" aria-modal="true" aria-label="New Debug Bar command palette">
            <div class="ndb:flex ndb:items-center ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:px-4 ndb:dark:border-zinc-800">
                <x-new-debug-bar::icon name="search" class="ndb:size-5 ndb:text-zinc-400" />
                <input x-ref="paletteSearch" x-model="paletteSearch" @input="paletteIndex = 0" @keydown.down.prevent="movePalette(1)" @keydown.up.prevent="movePalette(-1)" @keydown.enter.prevent="runActiveCommand()" type="search" placeholder="Jump to a section or change a setting…" class="ndb:h-14 ndb:min-w-0 ndb:flex-1 ndb:border-0 ndb:bg-transparent ndb:text-sm ndb:font-medium ndb:outline-none ndb:placeholder:text-zinc-400" />
                <kbd class="ndb:rounded-md ndb:border ndb:border-zinc-200 ndb:bg-zinc-50 ndb:px-1.5 ndb:py-1 ndb:text-[9px] ndb:font-bold ndb:text-zinc-400 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-800">ESC</kbd>
            </div>
            <div class="ndb-scrollbar ndb:max-h-[min(420px,60vh)] ndb:overflow-y-auto ndb:p-2">
                <template x-for="(command, index) in filteredCommands" :key="command.id">
                    <button type="button" @mouseenter="paletteIndex = index" @click="runCommand(command.id)" class="ndb:flex ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:px-3 ndb:py-2.5 ndb:text-left ndb:transition" :class="paletteIndex === index ? 'ndb:bg-indigo-50 ndb:text-indigo-800 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-200' : 'ndb:text-zinc-700 ndb:dark:text-zinc-300'">
                        <span class="ndb:grid ndb:size-8 ndb:place-items-center ndb:rounded-lg ndb:bg-white ndb:text-zinc-400 ndb:shadow-sm ndb:ring-1 ndb:ring-zinc-200 ndb:dark:bg-zinc-800 ndb:dark:ring-zinc-700"><x-new-debug-bar::icon name="code" class="ndb:size-4" /></span>
                        <span class="ndb:flex-1 ndb:text-sm ndb:font-semibold" x-text="command.label"></span>
                        <span class="ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400" x-text="command.hint"></span>
                    </button>
                </template>
                <p x-show="filteredCommands.length === 0" class="ndb:px-3 ndb:py-8 ndb:text-center ndb:text-sm ndb:text-zinc-500">No matching commands.</p>
            </div>
            <div class="ndb:flex ndb:items-center ndb:gap-3 ndb:border-t ndb:border-zinc-200 ndb:bg-zinc-50 ndb:px-4 ndb:py-2 ndb:text-[10px] ndb:font-medium ndb:text-zinc-400 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950"><span>↑↓ Navigate</span><span>↵ Select</span><span class="ndb:ml-auto">⌘/Ctrl ⇧ P</span></div>
        </div>
    </div>
</div>
