{{-- Renders application and framework events. --}}
@php($eventItems = $section['payload']['items'] ?? [])
@php($eventSourceCounts = array_replace(['application' => 0, 'framework' => 0], array_count_values(array_column($eventItems, 'source'))))
@php($eventSourceCounts['all'] = count($eventItems))
<div class="ndb:flex ndb:flex-col ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:pb-3 ndb:sm:flex-row ndb:sm:items-end ndb:dark:border-zinc-800">
    <div class="ndb:flex-1">
        <p class="ndb:mb-1.5 ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
            Source
        </p>
        <x-newdebugbar::filter-tabs label="Filter events by source">
            @foreach (['all' => 'All', 'application' => 'Application', 'framework' => 'Framework'] as $source => $label)
                <x-newdebugbar::filter-tab
                    data-ndb-event-source="{{ $source }}"
                    @click="setEventSource({{ \Illuminate\Support\Js::from($source) }})"
                    ::aria-pressed="eventSource === {{ \Illuminate\Support\Js::from($source) }}"
                >
                    <span>{{ $label }}</span>
                    <span
                        data-ndb-event-source-count="{{ $source }}"
                        class="ndb:text-[11px] ndb:font-bold ndb:tabular-nums ndb:opacity-65"
                    >{{ $eventSourceCounts[$source] ?? 0 }}</span>
                </x-newdebugbar::filter-tab>
            @endforeach
        </x-newdebugbar::filter-tabs>
    </div>
    <label class="ndb:sm:w-72"
        ><span
            class="ndb:mb-1.5 ndb:block ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
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
<p class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
    <span data-ndb-event-visible-count x-text="visibleEventCount"></span> events
    <span x-show.important="eventSource === 'application'">from application code</span>
</p>
<div x-ref="eventList" x-init="$nextTick(() => applyEventFilters())" class="ndb:space-y-2">
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
                    <span class="ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:dark:text-indigo-300">Broadcast</span>
                @endif
                <span class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:text-zinc-400">{{ $item['source'] }}</span
                ><x-newdebugbar::icon
                    name="chevron-down"
                    class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
                />
            </summary>
            <div class="ndb:space-y-2 ndb:border-t ndb:border-zinc-200 ndb:p-3 ndb:dark:border-zinc-800">
                @forelse ($item['listeners'] ?? [] as $listener)
                    <div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3">
                        <code class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-[11px]">{{ $listener['name'] }}</code>
                    </div>
                @empty
                    <p class="ndb:text-[11px] ndb:text-zinc-400">No application listener source was exposed.</p>
                @endforelse
            </div>
        </details>
    @endforeach
</div>
<div x-show.important="visibleEventCount === 0">
    <x-newdebugbar::empty-state label="No events match these filters." />
</div>
