{{-- Explains the selected Laravel event through listener, payload, and source evidence. --}}
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
                </x-slot:aside>

                <x-slot:identity data-ndb-event-identity>
                    <code
                        data-ndb-event-qualified-name
                        :title="selectedEvent.name"
                        class="ndb:block ndb:break-all ndb:bg-transparent ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                        x-text="selectedEvent.name"
                    ></code>
                </x-slot:identity>

                <x-slot:metadata data-ndb-event-metadata class="ndb:w-full">
                    <div
                        data-ndb-event-facts
                        class="ndb:grid ndb:w-full ndb:grid-cols-2 ndb:gap-x-4 ndb:gap-y-3 ndb:border-0 ndb:bg-transparent ndb:p-0 ndb:sm:grid-cols-4"
                    >
                        <div data-ndb-event-fact class="ndb:min-w-0 ndb:bg-transparent">
                            <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">
                                Origin
                            </dt>
                            <dd
                                class="ndb:mt-0.5 ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                x-text="
                                    selectedEvent.source === 'application'
                                        ? selectedEvent.broadcast
                                            ? 'Application broadcast'
                                            : 'Application'
                                        : 'Framework'
                                "
                            ></dd>
                        </div>
                        <div data-ndb-event-fact class="ndb:min-w-0 ndb:bg-transparent">
                            <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">
                                Sequence
                            </dt>
                            <dd
                                class="ndb:mt-0.5 ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                x-text="
                                    selectedEvent.first_sequence === selectedEvent.last_sequence
                                        ? '#' + selectedEvent.first_sequence
                                        : '#' + selectedEvent.first_sequence + '–' + selectedEvent.last_sequence
                                "
                            ></dd>
                        </div>
                        <div data-ndb-event-fact class="ndb:min-w-0 ndb:bg-transparent">
                            <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">
                                Dispatches
                            </dt>
                            <dd
                                class="ndb:mt-0.5 ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                x-text="selectedEvent.occurrence_count"
                            ></dd>
                        </div>
                        <div
                            data-ndb-event-fact
                            x-show.important="selectedEvent.first_at_ms !== null"
                            class="ndb:min-w-0 ndb:bg-transparent"
                        >
                            <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">
                                First seen
                            </dt>
                            <dd
                                class="ndb:mt-0.5 ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                x-text="formatEventTime(selectedEvent.first_at_ms)"
                            ></dd>
                        </div>
                    </div>
                </x-slot:metadata>
            </x-newdebugbar::inspector-detail-header>

            <div class="ndb:border-b ndb:border-zinc-200/90 ndb:px-4 ndb:py-2.5 ndb:dark:border-zinc-800">
                <x-newdebugbar::filter-tabs label="Laravel event detail" class="ndb:min-w-0">
                    @foreach (['overview' => ['Overview', 'eye'], 'payload' => ['Payload', 'database'], 'source' => ['Source', 'code']] as $tab => [$label, $icon])
                        <x-newdebugbar::filter-tab
                            data-ndb-event-detail-tab="{{ $tab }}"
                            @click="setEventDetailTab({{ \Illuminate\Support\Js::from($tab) }})"
                            ::aria-pressed="eventDetailTab === {{ \Illuminate\Support\Js::from($tab) }}"
                            aria-label="{{ $label }}"
                            class="ndb:h-auto"
                        >
                            <x-newdebugbar::icon
                                name="{{ $icon }}"
                                size="3.5"
                                data-ndb-event-detail-tab-icon="{{ $tab }}"
                                class="ndb:sm:hidden"
                            />
                            <span class="ndb:hidden ndb:sm:inline">{{ $label }}</span>
                        </x-newdebugbar::filter-tab>
                    @endforeach
                </x-newdebugbar::filter-tabs>
            </div>

            <div
                data-ndb-event-detail-panel="overview"
                x-show.important="eventDetailTab === 'overview'"
                class="ndb:p-4"
            >
                <section data-ndb-event-listeners>
                    <div class="ndb:flex ndb:items-baseline ndb:justify-between ndb:gap-3">
                        <h4 class="ndb:text-xs ndb:font-bold">Listener handling</h4>
                        <span
                            data-ndb-event-listener-outcome
                            class="ndb:bg-transparent ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400"
                            x-text="selectedEvent.listener_outcome_label"
                        ></span>
                    </div>
                    <p
                        class="ndb:mt-1 ndb:text-xs ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                        x-text="selectedEvent.listener_summary"
                    ></p>
                    <p
                        x-show.important="selectedEvent.duplicate_registration_count > 0"
                        class="ndb:mt-1 ndb:text-[11px] ndb:font-bold ndb:text-amber-600 ndb:dark:text-amber-300"
                        x-text="
                            selectedEvent.duplicate_registration_count +
                            (selectedEvent.duplicate_registration_count === 1
                                ? ' extra registration needs review.'
                                : ' extra registrations need review.')
                        "
                    ></p>

                    <div
                        x-show.important="selectedEvent.listeners.length > 0"
                        class="ndb:mt-3 ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800"
                    >
                        <template
                            x-for="listener in selectedEvent.listeners"
                            :key="listener.name +
                            ':' +
                            (listener.source?.file ?? '') +
                            ':' +
                            (listener.source?.line ?? '')"
                        >
                            <div
                                data-ndb-event-listener-row
                                class="ndb:grid ndb:grid-cols-[minmax(0,1fr)_auto] ndb:gap-x-3 ndb:gap-y-1.5 ndb:bg-transparent ndb:px-0 ndb:py-3 ndb:first:pt-0"
                            >
                                <code
                                    class="ndb:col-start-1 ndb:row-start-1 ndb:min-w-0 ndb:break-all ndb:bg-transparent ndb:font-mono ndb:text-[11px] ndb:font-semibold"
                                    x-text="listener.name"
                                ></code>
                                <span
                                    class="ndb:col-start-2 ndb:row-start-1 ndb:justify-self-end ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                    x-text="listener.queued ? 'Queued' : 'Completed'"
                                ></span>
                                <button
                                    type="button"
                                    data-ndb-event-copy-listener-source
                                    x-show.important="listener.source"
                                    @click="copyText(listener.source.file + ':' + listener.source.line)"
                                    :aria-label="listener.source
                                        ? 'Copy listener source ' + listener.source.file + ':' + listener.source.line
                                        : 'Copy listener source'"
                                    class="ndb:col-start-1 ndb:row-start-2 ndb:min-w-0 ndb:truncate ndb:bg-transparent ndb:text-left ndb:font-mono ndb:text-[11px] ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                                    x-text="listener.source ? listener.source.file + ':' + listener.source.line : ''"
                                ></button>
                                <span
                                    :class="listener.registrations > 1
                                        ? 'ndb:text-amber-600 ndb:dark:text-amber-300'
                                        : 'ndb:text-zinc-400'"
                                    class="ndb:col-start-2 ndb:row-start-2 ndb:justify-self-end ndb:text-[11px] ndb:font-semibold ndb:tabular-nums"
                                    x-text="
                                        listener.registrations +
                                        (listener.registrations === 1 ? ' registration' : ' registrations')
                                    "
                                ></span>
                            </div>
                        </template>
                    </div>
                </section>

                <section data-ndb-event-next-step class="ndb:mt-6 ndb:bg-transparent ndb:p-0 ndb:text-inherit">
                    <h4 class="ndb:text-xs ndb:font-bold">What to inspect next</h4>
                    <p
                        class="ndb:mt-1 ndb:text-xs ndb:font-medium ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                        x-text="selectedEvent.next_step"
                    ></p>
                    <button
                        type="button"
                        data-ndb-event-related-section
                        x-show.important="selectedEvent.related_section"
                        @click="navigateToSection(selectedEvent.related_section.key)"
                        class="ndb:mt-2 ndb:inline-flex ndb:min-h-8 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:bg-transparent ndb:px-2 ndb:text-[11px] ndb:font-bold ndb:text-indigo-700 ndb:transition ndb:hover:bg-indigo-100/70 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/60"
                    >
                        <span x-text="selectedEvent.related_section ? 'Open ' + selectedEvent.related_section.label : ''"></span>
                        <x-newdebugbar::icon name="external-link" size="3" />
                    </button>
                </section>

                <details
                    data-ndb-event-outcome-help
                    x-show.important="selectedEvent.listeners.length > 0"
                    class="ndb:group ndb:mt-4 ndb:border-0 ndb:bg-transparent ndb:p-0"
                >
                    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-1.5 ndb:bg-transparent ndb:p-0 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-zinc-400">
                        How listener outcomes are recorded
                        <x-newdebugbar::icon
                            name="chevron-down"
                            size="3"
                            class="ndb:transition ndb:group-open:rotate-180"
                        />
                    </summary>
                    <p class="ndb:mt-2 ndb:text-[11px] ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        Completed means Laravel reached this observer after synchronous listener dispatch. Queued means
                        Laravel handed the listener to the queue. Laravel does not expose per-listener duration to this
                        observer.
                    </p>
                </details>
            </div>

            <div data-ndb-event-detail-panel="payload" x-show.important="eventDetailTab === 'payload'" class="ndb:p-4">
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
                    <p class="ndb:mt-2 ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        No payload arguments were exposed.
                    </p>
                </template>

                <dl
                    x-show.important="selectedEvent.payload_shape.length > 0"
                    class="ndb:mt-2 ndb:divide-y ndb:divide-zinc-200/90 ndb:bg-transparent ndb:dark:divide-zinc-800"
                >
                    <template x-for="entry in selectedEvent.payload_shape" :key="entry.position">
                        <div class="ndb:grid ndb:gap-1 ndb:bg-transparent ndb:py-3 ndb:first:pt-0 ndb:sm:grid-cols-[8rem_minmax(0,1fr)] ndb:sm:gap-4">
                            <dt class="ndb:text-[11px] ndb:font-bold ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                <span x-text="'Argument ' + entry.position"></span>
                            </dt>
                            <dd class="ndb:min-w-0 ndb:bg-transparent">
                                <code
                                    class="ndb:block ndb:break-all ndb:bg-transparent ndb:font-mono ndb:text-[11px] ndb:font-semibold"
                                    x-text="entry.type"
                                ></code>
                                <div
                                    x-show.important="entry.fields.length > 0"
                                    class="ndb:mt-2 ndb:grid ndb:gap-1 ndb:sm:grid-cols-[4rem_minmax(0,1fr)]"
                                >
                                    <span class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">Fields</span>
                                    <code
                                        class="ndb:break-all ndb:bg-transparent ndb:font-mono ndb:text-[11px] ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                        x-text="entry.fields.join(', ')"
                                    ></code>
                                </div>
                                <p
                                    x-show.important="entry.field_count > entry.fields.length"
                                    class="ndb:mt-1 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400"
                                    x-text="
                                        entry.field_count -
                                        entry.fields.length +
                                        (entry.field_count - entry.fields.length === 1
                                            ? ' more field is not shown.'
                                            : ' more fields are not shown.')
                                    "
                                ></p>
                            </dd>
                        </div>
                    </template>
                </dl>

                <p class="ndb:mt-3 ndb:text-[11px] ndb:leading-5 ndb:text-zinc-400">
                    Field names and types are shown. Payload values are not copied into this view.
                </p>
            </div>

            <div data-ndb-event-detail-panel="source" x-show.important="eventDetailTab === 'source'" class="ndb:p-4">
                <section data-ndb-event-dispatch-sources>
                    <div class="ndb:flex ndb:items-baseline ndb:justify-between ndb:gap-3">
                        <h4 class="ndb:text-xs ndb:font-bold">Dispatch locations</h4>
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
                        <p class="ndb:mt-2 ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                            No application dispatch location was captured. Framework-only stack frames stay hidden.
                        </p>
                    </template>

                    <div
                        x-show.important="selectedEvent.dispatch_sources.length > 0"
                        class="ndb:mt-2 ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800"
                    >
                        <template
                            x-for="source in selectedEvent.dispatch_sources"
                            :key="source.file + ':' + source.line"
                        >
                            <button
                                type="button"
                                data-ndb-event-copy-dispatch-source
                                @click="copyText(source.file + ':' + source.line)"
                                :aria-label="'Copy dispatch source ' + source.file + ':' + source.line"
                                class="ndb:grid ndb:w-full ndb:min-w-0 ndb:grid-cols-[minmax(0,1fr)_auto] ndb:items-center ndb:gap-3 ndb:bg-transparent ndb:py-2.5 ndb:text-left ndb:hover:bg-zinc-50/80 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-zinc-900/60"
                            >
                                <code
                                    class="ndb:min-w-0 ndb:truncate ndb:bg-transparent ndb:font-mono ndb:text-[11px] ndb:font-semibold"
                                    x-text="source.file + ':' + source.line"
                                ></code>
                                <span class="ndb:flex ndb:items-center ndb:gap-1.5 ndb:text-zinc-400">
                                    <span
                                        class="ndb:text-[11px] ndb:font-semibold ndb:tabular-nums"
                                        x-text="source.count + (source.count === 1 ? ' dispatch' : ' dispatches')"
                                    ></span>
                                    <x-newdebugbar::icon name="copy" size="3" />
                                </span>
                            </button>
                        </template>
                    </div>

                    <p
                        data-ndb-event-dispatch-sources-omitted
                        x-show.important="selectedEvent.dispatch_source_omitted_count > 0"
                        class="ndb:mt-2 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400"
                        x-text="
                            selectedEvent.dispatch_source_omitted_count +
                            (selectedEvent.dispatch_source_omitted_count === 1
                                ? ' lower-frequency location is not shown.'
                                : ' lower-frequency locations are not shown.')
                        "
                    ></p>
                </section>

                <details
                    data-ndb-event-timeline
                    x-show.important="selectedEvent.occurrence_count > 1"
                    class="ndb:group ndb:mt-6 ndb:border-0 ndb:bg-transparent ndb:p-0"
                >
                    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:justify-between ndb:gap-3 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:font-bold ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500">
                        <span>Dispatch timeline</span>
                        <span class="ndb:flex ndb:items-center ndb:gap-1.5 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
                            <span
                                x-text="
                                    selectedEvent.occurrences.length +
                                    (selectedEvent.occurrences.length === 1 ? ' dispatch shown' : ' dispatches shown')
                                "
                            ></span>
                            <x-newdebugbar::icon
                                name="chevron-down"
                                size="3"
                                class="ndb:transition ndb:group-open:rotate-180"
                            />
                        </span>
                    </summary>

                    <div class="ndb:mt-3 ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800">
                        <template x-for="occurrence in selectedEvent.occurrences" :key="occurrence.sequence">
                            <div class="ndb:grid ndb:grid-cols-[auto_minmax(0,1fr)_auto] ndb:items-center ndb:gap-3 ndb:bg-transparent ndb:py-2.5">
                                <span
                                    class="ndb:text-[11px] ndb:font-bold ndb:tabular-nums"
                                    x-text="'#' + occurrence.sequence"
                                ></span>
                                <code
                                    x-show.important="occurrence.callsite"
                                    class="ndb:min-w-0 ndb:truncate ndb:bg-transparent ndb:font-mono ndb:text-[11px] ndb:text-zinc-400"
                                    x-text="
                                        occurrence.callsite
                                            ? occurrence.callsite.file + ':' + occurrence.callsite.line
                                            : ''
                                    "
                                ></code>
                                <span class="ndb:flex ndb:flex-wrap ndb:items-center ndb:justify-end ndb:gap-2">
                                    <span
                                        x-show.important="occurrence.lifecycle === 'after_response'"
                                        class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400"
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
                        class="ndb:mt-2 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400"
                        x-text="
                            selectedEvent.occurrence_omitted_count +
                            (selectedEvent.occurrence_omitted_count === 1
                                ? ' middle dispatch is not shown.'
                                : ' middle dispatches are not shown.')
                        "
                    ></p>
                </details>
            </div>
        </div>
    </template>
</section>
