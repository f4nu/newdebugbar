{{-- Groups Laravel dispatches into a compact event list with a focused evidence pane. --}}
@php
    $eventGroups = array_values($section['payload']['groups'] ?? []);
    $eventSummary = $section['summary'];
    $eventSourceCounts = [
        'all' => (int) ($eventSummary['retained_count'] ?? count($section['payload']['items'] ?? [])),
        'application' => (int) ($eventSummary['application_count'] ?? 0),
        'framework' => (int) ($eventSummary['framework_count'] ?? 0),
    ];
@endphp

<div
    data-ndb-events
    x-init="initializeEvents(JSON.parse(atob($el.querySelector('[data-ndb-event-payload]').textContent.trim())))"
    class="ndb:space-y-4 ndb:lg:flex ndb:lg:min-h-0 ndb:lg:flex-1 ndb:lg:flex-col ndb:lg:space-y-0"
>
    <script type="application/json" data-ndb-event-payload>
        {{ base64_encode(\Illuminate\Support\Js::encode($eventGroups)) }}
    </script>

    @if ($eventGroups !== [])
        <x-newdebugbar::inspector-workspace data-ndb-event-workspace>
            <div
                :class="eventDetailOpen ? 'ndb:hidden ndb:lg:flex' : 'ndb:flex'"
                class="ndb:min-h-0 ndb:flex-col ndb:border-b ndb:border-zinc-200/90 ndb:lg:border-r ndb:lg:border-b-0 ndb:dark:border-zinc-800"
            >
                <div class="ndb:space-y-3 ndb:border-b ndb:border-zinc-200/90 ndb:p-3 ndb:dark:border-zinc-800">
                    <div class="ndb:flex ndb:items-start ndb:justify-between ndb:gap-3">
                        <p
                            data-ndb-event-visible-summary
                            aria-live="polite"
                            class="ndb:min-w-0 ndb:text-xs ndb:font-semibold ndb:text-zinc-600 ndb:dark:text-zinc-300"
                            x-text="visibleEventSummary"
                        ></p>
                        <label class="ndb:relative ndb:shrink-0">
                            <span class="ndb:sr-only">Sort events</span>
                            <select
                                data-ndb-event-sort
                                x-model="eventSort"
                                @change="setEventSort($event.target.value)"
                                class="ndb:h-8 ndb:appearance-none ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/75 ndb:pr-8 ndb:pl-2.5 ndb:text-[11px] ndb:font-semibold ndb:outline-none ndb:transition ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900"
                            >
                                <option value="sequence">First fired</option>
                                <option value="frequency">Most fired</option>
                                <option value="latest">Latest fired</option>
                            </select>
                            <x-newdebugbar::icon
                                name="chevron-down"
                                class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-2.5 ndb:size-3 ndb:-translate-y-1/2 ndb:text-zinc-400"
                            />
                        </label>
                    </div>

                    <x-newdebugbar::filter-tabs label="Filter events by source" class="ndb:w-full">
                        @foreach (['all' => 'All', 'application' => 'Application', 'framework' => 'Framework'] as $source => $label)
                            <x-newdebugbar::filter-tab
                                data-ndb-event-source="{{ $source }}"
                                @click="setEventSource({{ \Illuminate\Support\Js::from($source) }})"
                                ::aria-pressed="eventSource === {{ \Illuminate\Support\Js::from($source) }}"
                                class="ndb:flex-1 ndb:justify-center ndb:px-2 ndb:py-1.5"
                            >
                                <span>{{ $label }}</span>
                                <span
                                    data-ndb-event-source-count="{{ $source }}"
                                    class="ndb:text-[11px] ndb:font-bold ndb:tabular-nums ndb:opacity-65"
                                >{{ $eventSourceCounts[$source] }}</span>
                            </x-newdebugbar::filter-tab>
                        @endforeach
                    </x-newdebugbar::filter-tabs>

                    <label class="ndb:relative ndb:block ndb:min-w-0">
                        <span class="ndb:sr-only">Search events</span>
                        <input
                            data-ndb-event-search
                            x-model="eventSearch"
                            @input.debounce.100ms="applyEventFilters()"
                            type="search"
                            placeholder="Search events, listeners, or payloads"
                            class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-9 ndb:pl-3 ndb:text-xs ndb:outline-none ndb:transition ndb:placeholder:text-zinc-400 ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                        />
                        <x-newdebugbar::icon
                            name="search"
                            class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-3 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
                        />
                    </label>
                </div>

                <div
                    x-ref="eventList"
                    data-ndb-event-list
                    aria-label="Laravel events"
                    class="ndb-scrollbar ndb:min-h-0 ndb:flex-1 ndb:divide-y ndb:divide-zinc-200/80 ndb:overflow-y-auto ndb:dark:divide-zinc-800"
                >
                    @foreach ($eventGroups as $event)
                        <button
                            type="button"
                            data-ndb-event-item="{{ $event['id'] }}"
                            data-ndb-event-id="{{ $event['id'] }}"
                            data-ndb-event-source-value="{{ $event['source'] }}"
                            data-ndb-event-search-value="{{ $event['search'] }}"
                            data-ndb-event-occurrence-count="{{ $event['occurrence_count'] }}"
                            data-ndb-event-first-sequence="{{ $event['first_sequence'] }}"
                            data-ndb-event-last-sequence="{{ $event['last_sequence'] }}"
                            @click="selectEvent({{ $event['id'] }}, $el)"
                            :aria-pressed="eventSelected === {{ $event['id'] }}"
                            :class="eventSelected === {{ $event['id'] }}
                                ? 'ndb:bg-indigo-50/65 ndb:dark:bg-indigo-950/20'
                                : 'ndb:hover:bg-zinc-50/80 ndb:dark:hover:bg-zinc-900/60'"
                            class="ndb:grid ndb:h-auto ndb:w-full ndb:grid-cols-[minmax(0,1fr)_7rem] ndb:items-baseline ndb:gap-x-3 ndb:gap-y-0.5 ndb:px-3 ndb:py-1 ndb:text-left ndb:transition-colors ndb:focus-visible:relative ndb:focus-visible:z-10 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
                        >
                            <span
                                data-ndb-event-list-name
                                class="ndb:col-start-1 ndb:row-start-1 ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-bold"
                            >{{ $event['display_name'] }}</span>
                            <span class="ndb:col-start-2 ndb:row-start-1 ndb:justify-self-end ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                @if ($event['first_sequence'] === $event['last_sequence'])
                                    #{{ $event['first_sequence'] }}
                                @else
                                    #{{ $event['first_sequence'] }}–{{ $event['last_sequence'] }}
                                @endif
                            </span>
                            <span class="ndb:col-start-1 ndb:row-start-2 ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-2 ndb:overflow-hidden ndb:text-[11px] ndb:text-zinc-400">
                                <span
                                    x-show.important="eventSource === 'all'"
                                    class="ndb:shrink-0 ndb:font-semibold"
                                >{{ $event['source'] === 'application' ? 'Application' : 'Framework' }}</span>
                                @if ($event['namespace'] !== null)
                                    <code
                                        data-ndb-event-list-namespace
                                        class="ndb:min-w-0 ndb:truncate ndb:font-mono ndb:text-[11px]"
                                    >{{ $event['namespace'] }}</code>
                                @endif
                            </span>
                            <span class="ndb:col-start-2 ndb:row-start-2 ndb:w-full ndb:truncate ndb:text-right ndb:text-[11px] ndb:font-medium ndb:tabular-nums ndb:text-zinc-400">
                                @if ($event['first_at_ms'] !== null)
                                    @if ($event['first_at_ms'] === $event['last_at_ms'])
                                        {{ number_format($event['first_at_ms'], 2) }} ms
                                    @else
                                        {{ number_format($event['first_at_ms'], 2) }}–{{ number_format($event['last_at_ms'], 2) }} ms
                                    @endif
                                @else
                                    —
                                @endif
                            </span>
                            <span
                                class="ndb:col-start-1 ndb:row-start-3 ndb:min-w-0 ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                title="{{ $event['listener_summary'] }}"
                            >
                                {{ $event['listener_summary'] }}
                            </span>
                            <span class="ndb:col-start-2 ndb:row-start-3 ndb:w-full ndb:text-right ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400">
                                {{ number_format($event['occurrence_count']) }} {{ \Illuminate\Support\Str::plural('dispatch', $event['occurrence_count']) }}
                            </span>
                            @if ($event['duplicate_registration_count'] > 0)
                                <span class="ndb:col-start-1 ndb:row-start-4 ndb:min-w-0 ndb:truncate ndb:text-[11px] ndb:font-bold ndb:text-amber-600 ndb:dark:text-amber-300">
                                    Duplicate listener registration
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>

                <div data-ndb-event-empty x-show.important="visibleEventGroupCount === 0" class="ndb:p-3">
                    <x-newdebugbar::empty-state label="No events match this source and search." />
                </div>
            </div>

            <x-newdebugbar::event-detail />
        </x-newdebugbar::inspector-workspace>
    @else
        <x-newdebugbar::empty-state label="No Laravel events were captured." />
    @endif
</div>
