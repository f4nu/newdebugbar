{{-- Renders the request activity timeline. --}}
@php($timelineItems = $section['payload']['items'])
@php($timelineSections = $section['payload']['available_sections'] ?? array_values(array_unique(array_column($timelineItems, 'section'))))
@php($timelineSourceSections = array_values(array_filter($timelineSections, fn ($timelineSection) => $timelineSection !== 'request')))
@php($timelineKeySections = ['request', 'queries', 'http_client', 'exceptions', 'authorization', 'validation', 'queue'])
@php($timelineDuration = (float) ($section['payload']['total_duration_ms'] ?? max(0.001, ...array_column($timelineItems, 'at_ms'))))
@php($timelineTotal = (int) ($section['payload']['total_item_count'] ?? count($timelineItems)))
@php($timelineLoaded = count($timelineItems))
@php($timelineTicks = [0, 25, 50, 75, 100])
<div data-ndb-timeline-results-header class="ndb:space-y-3">
    <div data-ndb-timeline-overview class="ndb:flex ndb:flex-wrap ndb:items-end ndb:justify-between ndb:gap-3">
        <div>
            <h3 class="ndb:text-xs ndb:font-bold">Waterfall</h3>
            <p data-ndb-timeline-summary class="ndb:mt-0.5 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
                <span x-text="visibleTimelineCount"></span> matching of {{ number_format($timelineLoaded) }} loaded
                events across {{ number_format($timelineDuration, $timelineDuration < 10 ? 1 : 0) }} ms
            </p>
        </div>
        <div
            class="ndb:flex ndb:items-center ndb:gap-4 ndb:pb-0.5 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400"
            aria-label="Timeline legend"
        >
            <span class="ndb:flex ndb:items-center ndb:gap-1.5"
                ><span class="ndb:h-1.5 ndb:w-5 ndb:rounded-sm ndb:bg-indigo-500"></span>Duration</span
            ><span class="ndb:flex ndb:items-center ndb:gap-1.5"
                ><span class="ndb:size-2 ndb:rounded-full ndb:bg-sky-500"></span>Event</span>
        </div>
    </div>
    <div
        data-ndb-timeline-toolbar
        class="ndb:grid ndb:w-full ndb:min-w-0 ndb:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)] ndb:gap-2 ndb:sm:grid-cols-[12rem_16rem] ndb:sm:justify-between"
    >
        <label class="ndb:min-w-0"
            ><span
                class="ndb:mb-1.5 ndb:block ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
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
                class="ndb:mb-1.5 ndb:block ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
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
            <div class="ndb:self-end ndb:px-3 ndb:pb-2 ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
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
                            class="ndb:absolute ndb:bottom-2 ndb:whitespace-nowrap {{ $tick === 0 ? 'ndb:translate-x-0' : ($tick === 100 ? 'ndb:-translate-x-full' : 'ndb:-translate-x-1/2') }} ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
                            style="left: {{ $tick }}%"
                        >{{ number_format($timelineTickMs, $timelineTickMs > 0 && $timelineTickMs < 10 ? 1 : 0) }} ms</span>
                    @endforeach
                </div>
            </div>
            <div class="ndb:self-end ndb:px-3 ndb:pb-2 ndb:text-right ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
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
                        <p class="ndb:truncate ndb:text-xs ndb:font-semibold" title="{{ $item['label'] }}">
                            {{ $item['label'] }}
                        </p>
                        <button
                            type="button"
                            data-ndb-timeline-activity-section
                            @click="selectSection(@js($item['section']))"
                            class="ndb:mt-0.5 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400 ndb:transition ndb:hover:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:text-indigo-300"
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
                            <p class="ndb:text-[11px] ndb:font-bold">
                                {{ number_format((float) $item['duration_ms'], $item['duration_ms'] < 10 ? 1 : 0) }} ms
                            </p>
                            <p class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
                                {{ $item['start_ms'] }}–{{ $item['at_ms'] }} ms
                            </p>
                        @else
                            <p class="ndb:text-[11px] ndb:font-bold">
                                {{ number_format((float) $item['at_ms'], $item['at_ms'] > 0 && $item['at_ms'] < 10 ? 1 : 0) }} ms
                            </p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</div>
@if ($section['payload']['has_more'] ?? false)
    <div
        data-ndb-timeline-pagination
        class="ndb:flex ndb:flex-wrap ndb:items-center ndb:justify-between ndb:gap-3 ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/55 ndb:px-4 ndb:py-3 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30"
    >
        <p class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400">
            Showing {{ number_format($timelineLoaded) }} of {{ number_format($timelineTotal) }} timeline events.
        </p>
        <button
            type="button"
            wire:click="loadMoreTimeline"
            wire:loading.attr="disabled"
            wire:target="loadMoreTimeline"
            data-ndb-timeline-load-more
            class="ndb:min-h-9 ndb:rounded-lg ndb:bg-indigo-600 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-bold ndb:text-white ndb:transition ndb:hover:bg-indigo-500 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:opacity-60 ndb:dark:bg-indigo-400 ndb:dark:text-indigo-950 ndb:dark:hover:bg-indigo-300"
        >
            <span wire:loading.remove wire:target="loadMoreTimeline"
                >Load {{ number_format(min(50, $timelineTotal - $timelineLoaded)) }} more</span>
            <span wire:loading wire:target="loadMoreTimeline">Loading more…</span>
        </button>
    </div>
@elseif ($timelineTotal > 50)
    <p data-ndb-timeline-complete class="ndb:text-center ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
        All {{ number_format($timelineTotal) }} timeline events are loaded.
    </p>
@endif
<div x-show.important="visibleTimelineCount === 0">
    <x-newdebugbar::empty-state label="No timeline events match these filters." />
</div>
