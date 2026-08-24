<section
    x-ref="eventDetail"
    data-ndb-event-detail
    aria-live="polite"
    aria-label="Selected Laravel event details"
    tabindex="0"
    :class="eventDetailOpen ? 'ndb:flex' : 'ndb:hidden ndb:lg:flex'"
    class="ndb-scrollbar ndb:min-h-[32rem] ndb:min-w-0 ndb:flex-col ndb:scroll-mt-20 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:lg:min-h-0 ndb:lg:overflow-y-auto"
>
    <x-newdebugbar::inspector-detail-back data-ndb-event-detail-back @click="closeEventDetail()" label="Events" />
    <template x-if="! selectedEvent">
        <div class="ndb:p-4">
            <x-newdebugbar::empty-state label="No event is selected. Adjust the source filter or search." />
        </div>
    </template>
    <template x-if="selectedEvent">
        <div class="ndb:flex ndb:flex-col">
            <x-newdebugbar::inspector-detail-header data-ndb-event-header>
                <x-slot:title>
                    <h3
                        data-ndb-event-detail-title
                        class="ndb:break-words ndb:text-base ndb:font-bold ndb:leading-6"
                        x-text="selectedEvent.display_name"
                    ></h3>
                </x-slot:title>
                <x-slot:aside>
                    <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:justify-end ndb:gap-1.5">
                        <span
                            data-ndb-event-outcome
                            :class="{
                                'ndb:bg-emerald-100 ndb:text-emerald-700 ndb:dark:bg-emerald-950 ndb:dark:text-emerald-300':
                                    selectedEvent.listener_outcome === 'completed',
                                'ndb:bg-sky-100 ndb:text-sky-700 ndb:dark:bg-sky-950 ndb:dark:text-sky-300':
                                    selectedEvent.listener_outcome === 'queued',
                                'ndb:bg-indigo-100 ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300':
                                    selectedEvent.listener_outcome === 'mixed',
                                'ndb:bg-zinc-100 ndb:text-zinc-600 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300':
                                    selectedEvent.listener_outcome === 'observed',
                            }"
                            class="ndb:inline-flex ndb:rounded-md ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-bold"
                            x-text="selectedEvent.listener_outcome_label"
                        ></span>
                        <button
                            type="button"
                            data-ndb-event-copy-name
                            aria-label="Copy event name"
                            @click="copyText(selectedEvent.name)"
                            class="ndb:inline-flex ndb:min-h-8 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:px-2 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:transition ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/50"
                        >
                            <x-newdebugbar::icon name="copy" size="3" />
                            <span class="ndb:hidden ndb:sm:inline">Copy name</span>
                        </button>
                    </div>
                </x-slot:aside>
                <x-slot:identity data-ndb-event-identity>
                    <dl class="ndb:space-y-2">
                        <div class="ndb:grid ndb:grid-cols-[4.75rem_minmax(0,1fr)] ndb:items-baseline ndb:gap-2">
                            <dt class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">Event</dt>
                            <dd class="ndb:min-w-0">
                                <code
                                    data-ndb-event-qualified-name
                                    :title="selectedEvent.name"
                                    class="ndb:block ndb:break-all ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                    x-text="selectedEvent.name"
                                ></code>
                            </dd>
                        </div>
                        <div class="ndb:grid ndb:grid-cols-[4.75rem_minmax(0,1fr)] ndb:items-baseline ndb:gap-2">
                            <dt class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">Origin</dt>
                            <dd class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-1.5">
                                <span
                                    :class="selectedEvent.source === 'application'
                                        ? 'ndb:bg-indigo-50 ndb:text-indigo-700 ndb:ring-indigo-200/70 ndb:dark:bg-indigo-950/60 ndb:dark:text-indigo-300 ndb:dark:ring-indigo-900'
                                        : 'ndb:bg-white/90 ndb:text-zinc-600 ndb:ring-zinc-200/80 ndb:dark:bg-zinc-950/60 ndb:dark:text-zinc-300 ndb:dark:ring-zinc-700'"
                                    class="ndb:inline-flex ndb:rounded-md ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-bold ndb:ring-1 ndb:ring-inset"
                                    x-text="selectedEvent.source === 'application' ? 'Application' : 'Framework'"
                                ></span>
                                <span
                                    x-show.important="selectedEvent.broadcast"
                                    class="ndb:inline-flex ndb:rounded-md ndb:bg-indigo-50 ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-bold ndb:text-indigo-700 ndb:ring-1 ndb:ring-inset ndb:ring-indigo-200/70 ndb:dark:bg-indigo-950/60 ndb:dark:text-indigo-300 ndb:dark:ring-indigo-900"
                                >Broadcast event</span>
                            </dd>
                        </div>
                    </dl>
                </x-slot:identity>
                <x-slot:metadata data-ndb-event-metadata>
                    <div>
                        <dt class="ndb:sr-only">Sequence</dt>
                        <dd
                            class="ndb:font-semibold ndb:tabular-nums"
                            x-text="
                                selectedEvent.first_sequence === selectedEvent.last_sequence
                                    ? 'Event #' + selectedEvent.first_sequence
                                    : 'Events #' + selectedEvent.first_sequence + '–' + selectedEvent.last_sequence
                            "
                        ></dd>
                    </div>
                    <div>
                        <dt class="ndb:sr-only">Occurrences</dt>
                        <dd
                            class="ndb:font-semibold ndb:tabular-nums"
                            x-text="
                                selectedEvent.occurrence_count +
                                (selectedEvent.occurrence_count === 1 ? ' dispatch' : ' dispatches')
                            "
                        ></dd>
                    </div>
                    <div>
                        <dt class="ndb:sr-only">Request timing</dt>
                        <dd
                            class="ndb:font-semibold ndb:tabular-nums"
                            x-text="
                                selectedEvent.first_at_ms === null
                                    ? 'Timing unavailable'
                                    : selectedEvent.first_at_ms === selectedEvent.last_at_ms
                                      ? formatEventTime(selectedEvent.first_at_ms) + ' into request'
                                      : formatEventTime(selectedEvent.span_ms) + ' dispatch span'
                            "
                        ></dd>
                    </div>
                </x-slot:metadata>
            </x-newdebugbar::inspector-detail-header>

            <div class="ndb:space-y-5 ndb:p-4">
                <section data-ndb-event-summary class="ndb:space-y-2">
                    <div class="ndb:flex ndb:items-center ndb:justify-between ndb:gap-3">
                        <h4 class="ndb:text-xs ndb:font-bold">What happened</h4>
                        <span
                            x-show.important="selectedEvent.duplicate_registration_count > 0"
                            class="ndb:inline-flex ndb:rounded-md ndb:bg-amber-50 ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-bold ndb:text-amber-700 ndb:dark:bg-amber-950/30 ndb:dark:text-amber-300"
                            x-text="
                                selectedEvent.duplicate_registration_count +
                                (selectedEvent.duplicate_registration_count === 1
                                    ? ' extra registration'
                                    : ' extra registrations')
                            "
                        ></span>
                    </div>
                    <dl class="ndb:divide-y ndb:divide-zinc-200/90 ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:px-3 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                        <div class="ndb:grid ndb:gap-1 ndb:py-3 ndb:sm:grid-cols-[8rem_minmax(0,1fr)] ndb:sm:gap-4">
                            <dt class="ndb:text-[11px] ndb:font-bold ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                Listener outcome
                            </dt>
                            <dd
                                class="ndb:text-xs ndb:leading-5 ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                x-text="selectedEvent.listener_summary"
                            ></dd>
                        </div>
                        <div class="ndb:grid ndb:gap-1 ndb:py-3 ndb:sm:grid-cols-[8rem_minmax(0,1fr)] ndb:sm:gap-4">
                            <dt class="ndb:text-[11px] ndb:font-bold ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                Request timing
                            </dt>
                            <dd
                                class="ndb:text-xs ndb:leading-5 ndb:tabular-nums ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                x-text="
                                    selectedEvent.first_at_ms === null
                                        ? 'Laravel did not expose an event timestamp.'
                                        : selectedEvent.occurrence_count === 1
                                          ? 'Observed ' +
                                            formatEventTime(selectedEvent.first_at_ms) +
                                            ' after the request began.'
                                          : selectedEvent.occurrence_count +
                                            ' dispatches from ' +
                                            formatEventTime(selectedEvent.first_at_ms) +
                                            ' to ' +
                                            formatEventTime(selectedEvent.last_at_ms) +
                                            '.'
                                "
                            ></dd>
                        </div>
                        <div class="ndb:grid ndb:gap-1 ndb:py-3 ndb:sm:grid-cols-[8rem_minmax(0,1fr)] ndb:sm:gap-4">
                            <dt class="ndb:text-[11px] ndb:font-bold ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                Payload shape
                            </dt>
                            <dd
                                class="ndb:text-xs ndb:leading-5 ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                x-text="
                                    selectedEvent.payload_shape.length === 0
                                        ? 'No payload shape was exposed.'
                                        : selectedEvent.payload_shape.length +
                                          (selectedEvent.payload_shape.length === 1 ? ' argument' : ' arguments') +
                                          ', ' +
                                          selectedEvent.payload_field_count +
                                          (selectedEvent.payload_field_count === 1
                                              ? ' public field.'
                                              : ' public fields.')
                                "
                            ></dd>
                        </div>
                    </dl>
                </section>

                <section data-ndb-event-dispatch-sources class="ndb:space-y-2">
                    <div class="ndb:flex ndb:items-baseline ndb:justify-between ndb:gap-3">
                        <h4 class="ndb:text-xs ndb:font-bold">Dispatch source</h4>
                        <span
                            class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400"
                            x-text="
                                selectedEvent.dispatch_source_count === 1
                                    ? '1 application location'
                                    : selectedEvent.dispatch_source_count + ' application locations'
                            "
                        ></span>
                    </div>
                    <template x-if="selectedEvent.dispatch_sources.length === 0">
                        <p class="ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:px-3 ndb:py-3 ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:border-zinc-800 ndb:dark:text-zinc-400">
                            No application dispatch source was available. Framework-only stacks are left out.
                        </p>
                    </template>
                    <div
                        x-show.important="selectedEvent.dispatch_sources.length > 0"
                        class="ndb:divide-y ndb:divide-zinc-200/90 ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800"
                    >
                        <template
                            x-for="source in selectedEvent.dispatch_sources"
                            :key="source.file + ':' + source.line"
                        >
                            <button
                                type="button"
                                data-ndb-event-copy-dispatch-source
                                @click="copyText(source.file + ':' + source.line)"
                                class="ndb:flex ndb:w-full ndb:min-w-0 ndb:items-center ndb:justify-between ndb:gap-3 ndb:px-3 ndb:py-2.5 ndb:text-left ndb:hover:bg-zinc-50/80 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-zinc-900/60"
                            >
                                <code
                                    class="ndb:min-w-0 ndb:truncate ndb:font-mono ndb:text-[11px] ndb:font-semibold"
                                    x-text="source.file + ':' + source.line"
                                ></code>
                                <span
                                    class="ndb:shrink-0 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
                                    x-text="source.count + (source.count === 1 ? ' dispatch' : ' dispatches')"
                                ></span>
                            </button>
                        </template>
                    </div>
                    <p
                        data-ndb-event-dispatch-sources-omitted
                        x-show.important="selectedEvent.dispatch_source_omitted_count > 0"
                        class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400"
                        x-text="
                            selectedEvent.dispatch_source_omitted_count +
                            (selectedEvent.dispatch_source_omitted_count === 1
                                ? ' lower-frequency location is not shown.'
                                : ' lower-frequency locations are not shown.')
                        "
                    ></p>
                </section>

                <section data-ndb-event-listeners class="ndb:space-y-2">
                    <div class="ndb:flex ndb:items-baseline ndb:justify-between ndb:gap-3">
                        <h4 class="ndb:text-xs ndb:font-bold">Listeners</h4>
                        <span
                            class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400"
                            x-text="
                                selectedEvent.listener_count +
                                (selectedEvent.listener_count === 1 ? ' registration' : ' registrations')
                            "
                        ></span>
                    </div>
                    <template x-if="selectedEvent.listeners.length === 0">
                        <p class="ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:px-3 ndb:py-3 ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:border-zinc-800 ndb:dark:text-zinc-400">
                            No application listener was registered. Framework internals are intentionally left out.
                        </p>
                    </template>
                    <div
                        x-show.important="selectedEvent.listeners.length > 0"
                        class="ndb:divide-y ndb:divide-zinc-200/90 ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800"
                    >
                        <template
                            x-for="listener in selectedEvent.listeners"
                            :key="listener.name +
                            ':' +
                            (listener.source?.file ?? '') +
                            ':' +
                            (listener.source?.line ?? '')"
                        >
                            <div class="ndb:grid ndb:grid-cols-[minmax(0,1fr)_auto] ndb:gap-x-3 ndb:gap-y-1 ndb:px-3 ndb:py-3">
                                <code
                                    class="ndb:min-w-0 ndb:break-all ndb:font-mono ndb:text-[11px] ndb:font-semibold"
                                    x-text="listener.name"
                                ></code>
                                <span
                                    :class="listener.queued
                                        ? 'ndb:bg-sky-50 ndb:text-sky-700 ndb:dark:bg-sky-950/40 ndb:dark:text-sky-300'
                                        : 'ndb:bg-emerald-50 ndb:text-emerald-700 ndb:dark:bg-emerald-950/40 ndb:dark:text-emerald-300'"
                                    class="ndb:rounded-md ndb:px-2 ndb:py-0.5 ndb:text-[11px] ndb:font-bold"
                                    x-text="listener.queued ? 'Queued' : 'Completed'"
                                ></span>
                                <button
                                    type="button"
                                    data-ndb-event-copy-listener-source
                                    x-show.important="listener.source"
                                    @click="copyText(listener.source.file + ':' + listener.source.line)"
                                    class="ndb:min-w-0 ndb:truncate ndb:text-left ndb:font-mono ndb:text-[11px] ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                                    x-text="listener.source.file + ':' + listener.source.line"
                                ></button>
                                <span
                                    :class="listener.registrations > 1
                                        ? 'ndb:text-amber-600 ndb:dark:text-amber-300'
                                        : 'ndb:text-zinc-400'"
                                    class="ndb:justify-self-end ndb:text-[11px] ndb:font-bold ndb:tabular-nums"
                                    x-text="
                                        listener.registrations +
                                        (listener.registrations === 1 ? ' registration' : ' registrations')
                                    "
                                ></span>
                            </div>
                        </template>
                    </div>
                    <p
                        x-show.important="selectedEvent.listeners.length > 0"
                        class="ndb:text-[11px] ndb:leading-5 ndb:text-zinc-400"
                    >
                        Completed means Laravel reached this observer after synchronous listener dispatch. Queued means
                        Laravel handed the listener to the queue.
                    </p>
                </section>

                <section data-ndb-event-payload-shape class="ndb:space-y-2">
                    <div class="ndb:flex ndb:items-center ndb:justify-between ndb:gap-3">
                        <h4 class="ndb:text-xs ndb:font-bold">Payload shape</h4>
                        <button
                            type="button"
                            data-ndb-event-copy-payload-shape
                            x-show.important="selectedEvent.payload_shape.length > 0"
                            @click="copyText(JSON.stringify(selectedEvent.payload_shape, null, 2))"
                            class="ndb:inline-flex ndb:min-h-8 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:px-2 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:transition ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/50"
                        >
                            <x-newdebugbar::icon name="copy" size="3" />
                            Copy shape
                        </button>
                    </div>
                    <template x-if="selectedEvent.payload_shape.length === 0">
                        <p class="ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:px-3 ndb:py-3 ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:border-zinc-800 ndb:dark:text-zinc-400">
                            No payload arguments were exposed.
                        </p>
                    </template>
                    <div
                        x-show.important="selectedEvent.payload_shape.length > 0"
                        class="ndb:divide-y ndb:divide-zinc-200/90 ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800"
                    >
                        <template x-for="entry in selectedEvent.payload_shape" :key="entry.position">
                            <div class="ndb:space-y-2 ndb:px-3 ndb:py-3">
                                <div class="ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-2">
                                    <span
                                        class="ndb:shrink-0 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400"
                                        x-text="'Argument ' + entry.position"
                                    ></span>
                                    <code
                                        class="ndb:min-w-0 ndb:break-all ndb:font-mono ndb:text-[11px] ndb:font-semibold"
                                        x-text="entry.type"
                                    ></code>
                                </div>
                                <div class="ndb:flex ndb:flex-wrap ndb:gap-1.5">
                                    <template x-for="field in entry.fields" :key="field">
                                        <code
                                            class="ndb:rounded-md ndb:bg-zinc-100 ndb:px-2 ndb:py-1 ndb:font-mono ndb:text-[11px] ndb:text-zinc-600 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"
                                            x-text="field"
                                        ></code>
                                    </template>
                                    <span
                                        x-show.important="entry.fields.length === 0"
                                        class="ndb:text-[11px] ndb:text-zinc-400"
                                    >No public fields</span>
                                    <span
                                        x-show.important="entry.field_count > entry.fields.length"
                                        class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400"
                                        x-text="entry.field_count - entry.fields.length + ' more fields not shown'"
                                    ></span>
                                </div>
                            </div>
                        </template>
                    </div>
                    <p class="ndb:text-[11px] ndb:leading-5 ndb:text-zinc-400">
                        Field names and types are shown. Payload values are not copied into this view.
                    </p>
                </section>

                <section
                    data-ndb-event-occurrences
                    x-show.important="selectedEvent.occurrence_count > 1"
                    class="ndb:space-y-2"
                >
                    <div class="ndb:flex ndb:items-baseline ndb:justify-between ndb:gap-3">
                        <h4 class="ndb:text-xs ndb:font-bold">Dispatch timeline</h4>
                        <span class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">Request-relative time</span>
                    </div>
                    <div class="ndb:divide-y ndb:divide-zinc-200/90 ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                        <template x-for="occurrence in selectedEvent.occurrences" :key="occurrence.sequence">
                            <div class="ndb:grid ndb:grid-cols-[auto_minmax(0,1fr)_auto] ndb:items-center ndb:gap-3 ndb:px-3 ndb:py-2.5">
                                <span
                                    class="ndb:text-[11px] ndb:font-bold ndb:tabular-nums"
                                    x-text="'#' + occurrence.sequence"
                                ></span>
                                <code
                                    x-show.important="occurrence.callsite"
                                    class="ndb:min-w-0 ndb:truncate ndb:font-mono ndb:text-[11px] ndb:text-zinc-400"
                                    x-text="
                                        occurrence.callsite
                                            ? occurrence.callsite.file + ':' + occurrence.callsite.line
                                            : ''
                                    "
                                ></code>
                                <span
                                    x-show.important="! occurrence.callsite"
                                    class="ndb:min-w-0 ndb:truncate ndb:text-[11px] ndb:text-zinc-400"
                                >Source unavailable</span>
                                <span class="ndb:flex ndb:flex-wrap ndb:items-center ndb:justify-end ndb:gap-1.5">
                                    <span
                                        x-show.important="occurrence.lifecycle === 'after_response'"
                                        class="ndb:rounded-md ndb:bg-indigo-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:text-indigo-700 ndb:dark:bg-indigo-950/50 ndb:dark:text-indigo-300"
                                    >After response</span>
                                    <span
                                        class="ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                        x-text="formatEventTime(occurrence.at_ms)"
                                    ></span>
                                </span>
                            </div>
                        </template>
                    </div>
                    <p
                        data-ndb-event-occurrences-omitted
                        x-show.important="selectedEvent.occurrence_omitted_count > 0"
                        class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400"
                        x-text="
                            selectedEvent.occurrence_omitted_count +
                            (selectedEvent.occurrence_omitted_count === 1
                                ? ' middle dispatch is not shown.'
                                : ' middle dispatches are not shown.')
                        "
                    ></p>
                </section>

                <section
                    data-ndb-event-next-step
                    :class="selectedEvent.duplicate_registration_count > 0
                        ? 'ndb:border-amber-200 ndb:bg-amber-50/60 ndb:dark:border-amber-900 ndb:dark:bg-amber-950/20'
                        : 'ndb:border-indigo-200 ndb:bg-indigo-50/55 ndb:dark:border-indigo-900 ndb:dark:bg-indigo-950/20'"
                    class="ndb:rounded-xl ndb:border ndb:p-3"
                >
                    <p class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-[0.14em] ndb:text-zinc-400">
                        What to check next
                    </p>
                    <p
                        class="ndb:mt-1 ndb:text-xs ndb:font-semibold ndb:leading-5"
                        x-text="selectedEvent.next_step"
                    ></p>
                    <button
                        type="button"
                        data-ndb-event-related-section
                        x-show.important="selectedEvent.related_section"
                        @click="navigateToSection(selectedEvent.related_section.key)"
                        class="ndb:mt-2 ndb:inline-flex ndb:min-h-8 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:px-2 ndb:text-[11px] ndb:font-bold ndb:text-indigo-700 ndb:transition ndb:hover:bg-indigo-100/70 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/60"
                    >
                        <span x-text="selectedEvent.related_section ? 'Open ' + selectedEvent.related_section.label : ''"></span>
                        <x-newdebugbar::icon name="external-link" size="3" />
                    </button>
                </section>
            </div>
        </div>
    </template>
</section>
