<div
    :class="livewireDetailOpen ? 'ndb:block' : 'ndb:hidden ndb:sm:block'"
    class="ndb:min-w-0 ndb:sm:sticky ndb:sm:top-0 ndb:sm:z-10 ndb:sm:overflow-x-clip ndb:sm:bg-white/95 ndb:sm:dark:bg-zinc-950/95"
>
    <button
        type="button"
        data-ndb-livewire-detail-back="activity"
        @click="livewireDetailOpen = false"
        class="ndb:m-2 ndb:inline-flex ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:p-2 ndb:text-xs ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:sm:hidden ndb:dark:text-indigo-300"
    >
        <x-newdebugbar::icon name="chevron-down" class="ndb:size-3.5 ndb:rotate-90" />
        Activity
    </button>

    <template x-if="selectedLivewireActivity">
        <article class="ndb:min-w-0">
            <header class="ndb:border-b ndb:border-zinc-200/90 ndb:px-4 ndb:py-4 ndb:sm:px-5 ndb:dark:border-zinc-800">
                <div class="ndb:flex ndb:min-w-0 ndb:items-start ndb:gap-4">
                    <span
                        class="ndb:mt-0.5 ndb:grid ndb:size-8 ndb:shrink-0 ndb:place-items-center ndb:rounded-lg"
                        :class="selectedLivewireActivity.status === 'failed' ||
                        selectedLivewireActivity.status === 'failed_validation'
                            ? 'ndb:bg-red-50 ndb:text-red-600 ndb:dark:bg-red-950/60 ndb:dark:text-red-300'
                            : 'ndb:bg-indigo-50 ndb:text-indigo-600 ndb:dark:bg-indigo-950/60 ndb:dark:text-indigo-300'"
                    >
                        <x-newdebugbar::icon name="activity" class="ndb:size-4" />
                    </span>
                    <div class="ndb:min-w-0 ndb:flex-1">
                        <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-2">
                            <h3
                                class="ndb:min-w-0 ndb:text-sm ndb:font-bold"
                                x-text="selectedLivewireActivity.title"
                            ></h3>
                            <span
                                x-show.important="selectedLivewireActivity.status !== 'complete'"
                                class="ndb:rounded-md ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide"
                                :class="selectedLivewireActivity.status === 'failed' ||
                                selectedLivewireActivity.status === 'failed_validation'
                                    ? 'ndb:bg-red-50 ndb:text-red-700 ndb:dark:bg-red-950/60 ndb:dark:text-red-300'
                                    : selectedLivewireActivity.status === 'updating'
                                      ? 'ndb:bg-indigo-50 ndb:text-indigo-700 ndb:dark:bg-indigo-950/60 ndb:dark:text-indigo-300'
                                      : 'ndb:bg-emerald-50 ndb:text-emerald-700 ndb:dark:bg-emerald-950/60 ndb:dark:text-emerald-300'"
                                x-text="livewireActivityStatusLabel(selectedLivewireActivity)"
                            ></span>
                        </div>
                        <p
                            class="ndb:mt-1 ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400"
                            x-text="livewireActivitySummary(selectedLivewireActivity)"
                        ></p>
                        <button
                            type="button"
                            @click="inspectLivewireActivityComponent()"
                            class="ndb:mt-2 ndb:flex ndb:max-w-full ndb:items-center ndb:gap-1.5 ndb:text-left ndb:text-xs ndb:font-semibold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                        >
                            <span
                                class="ndb:truncate"
                                x-text="livewireActivityComponentTitle(selectedLivewireActivity)"
                            ></span>
                            <span
                                x-show.important="livewireActivityComponentContext(selectedLivewireActivity)"
                                class="ndb:min-w-0 ndb:truncate ndb:text-[11px] ndb:font-normal ndb:text-indigo-400 ndb:dark:text-indigo-500"
                                x-text="livewireActivityComponentContext(selectedLivewireActivity)"
                            ></span>
                            <x-newdebugbar::icon name="chevron-down" class="ndb:size-3 ndb:shrink-0 ndb:-rotate-90" />
                        </button>
                    </div>
                </div>

                <dl class="ndb:mt-4 ndb:flex ndb:flex-wrap ndb:gap-x-5 ndb:gap-y-2 ndb:text-[11px]">
                    <div class="ndb:flex ndb:items-baseline ndb:gap-1.5">
                        <dt class="ndb:font-semibold ndb:text-zinc-400">Happened</dt>
                        <dd
                            class="ndb:font-bold ndb:tabular-nums"
                            x-text="livewireActivityAge(selectedLivewireActivity)"
                        ></dd>
                    </div>
                    <div class="ndb:flex ndb:items-baseline ndb:gap-1.5">
                        <dt class="ndb:font-semibold ndb:text-zinc-400">Duration</dt>
                        <dd
                            class="ndb:font-bold ndb:tabular-nums"
                            x-text="livewireDuration(selectedLivewireActivity)"
                        ></dd>
                    </div>
                </dl>
            </header>

            <div class="ndb:space-y-5 ndb:p-4 ndb:sm:p-5">
                <div
                    x-show.important="selectedLivewireActivity.error"
                    role="alert"
                    class="ndb:rounded-lg ndb:border ndb:border-red-200 ndb:bg-red-50/70 ndb:px-3 ndb:py-2.5 ndb:text-xs ndb:font-semibold ndb:text-red-800 ndb:dark:border-red-950 ndb:dark:bg-red-950/30 ndb:dark:text-red-200"
                    x-text="selectedLivewireActivity.error"
                ></div>

                <section x-show.important="selectedLivewireActivity.changes.length > 0">
                    <h4 class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                        Property changes
                    </h4>
                    <div
                        class="ndb:mt-2 ndb:overflow-hidden ndb:rounded-lg ndb:border ndb:border-zinc-200/90 ndb:dark:border-zinc-800"
                    >
                        <template x-for="change in selectedLivewireActivity.changes" :key="change.path">
                            <div
                                class="ndb:grid ndb:grid-cols-[minmax(7rem,0.7fr)_minmax(0,1fr)] ndb:gap-3 ndb:border-b ndb:border-zinc-200/80 ndb:px-3 ndb:py-2.5 ndb:last:border-b-0 ndb:dark:border-zinc-800"
                            >
                                <code
                                    class="ndb:truncate ndb:text-[11px] ndb:font-semibold"
                                    x-text="change.path"
                                ></code>
                                <div class="ndb:min-w-0 ndb:text-[11px]">
                                    <p class="ndb:truncate">
                                        <span class="ndb:text-zinc-400">Before </span
                                        ><code x-text="JSON.stringify(change.before)"></code>
                                    </p>
                                    <p class="ndb:mt-1 ndb:truncate">
                                        <span class="ndb:text-zinc-400">Server </span
                                        ><code
                                            x-text="
                                                change.serverKnown ? JSON.stringify(change.server) : 'Not confirmed'
                                            "
                                        ></code>
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>

                <section x-show.important="livewireMeaningfulActions(selectedLivewireActivity).length > 0">
                    <h4 class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                        Actions
                    </h4>
                    <div class="ndb:mt-2 ndb:space-y-2">
                        <template
                            x-for="(action, index) in livewireMeaningfulActions(selectedLivewireActivity)"
                            :key="`${action.name}-${index}`"
                        >
                            <div class="ndb:rounded-lg ndb:bg-zinc-50 ndb:px-3 ndb:py-2.5 ndb:dark:bg-zinc-900/65">
                                <code class="ndb:text-[11px] ndb:font-bold" x-text="action.name"></code>
                                <pre
                                    x-show.important="action.params.length > 0"
                                    class="ndb-scrollbar ndb:mt-2 ndb:overflow-x-auto ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                ><code x-text="JSON.stringify(action.params, null, 2)"></code></pre>
                            </div>
                        </template>
                    </div>
                </section>

                <section x-show.important="livewireActivityEvents(selectedLivewireActivity).length > 0">
                    <h4 class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                        Events
                    </h4>
                    <div class="ndb:mt-2 ndb:space-y-2">
                        <template x-for="event in livewireActivityEvents(selectedLivewireActivity)" :key="event.name">
                            <div
                                class="ndb:rounded-lg ndb:border ndb:border-zinc-200/90 ndb:px-3 ndb:py-3 ndb:dark:border-zinc-800"
                            >
                                <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-2">
                                    <code class="ndb:text-[11px] ndb:font-bold" x-text="event.name"></code>
                                    <span
                                        class="ndb:rounded-md ndb:bg-zinc-100 ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:text-zinc-500 ndb:dark:bg-zinc-800"
                                        x-text="event.mode"
                                    ></span>
                                </div>
                                <p
                                    x-show.important="event.declaredTarget"
                                    class="ndb:mt-2 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                >
                                    Declared target <code x-text="event.declaredTarget"></code>
                                </p>
                                <div
                                    x-show.important="event.observedRecipientIds.length > 0"
                                    class="ndb:mt-2 ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-1.5 ndb:text-[11px]"
                                >
                                    <span class="ndb:text-zinc-400">Observed recipients</span>
                                    <template x-for="recipient in event.observedRecipientIds" :key="recipient">
                                        <button
                                            type="button"
                                            @click="inspectLivewireComponent(recipient)"
                                            class="ndb:rounded-md ndb:bg-emerald-50 ndb:px-1.5 ndb:py-0.5 ndb:font-semibold ndb:text-emerald-700 ndb:focus-visible:outline-2 ndb:focus-visible:outline-emerald-500 ndb:dark:bg-emerald-950/60 ndb:dark:text-emerald-300"
                                            x-text="livewireComponentTitle(recipient)"
                                        ></button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>

                <section>
                    <h4 class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                        Trace
                    </h4>
                    <div class="ndb:mt-3 ndb:space-y-4">
                        <template
                            x-for="group in livewireActivityPhaseGroups(selectedLivewireActivity)"
                            :key="group.name"
                        >
                            <div>
                                <h5 class="ndb:text-xs ndb:font-bold" x-text="group.name"></h5>
                                <ol class="ndb:mt-2 ndb:m-0 ndb:list-none ndb:p-0">
                                    <template x-for="(phase, index) in group.phases" :key="`${phase.name}-${index}`">
                                        <li class="ndb:grid ndb:grid-cols-[14px_minmax(0,1fr)_auto] ndb:gap-x-3">
                                            <div
                                                aria-hidden="true"
                                                class="ndb:relative ndb:flex ndb:justify-center ndb:pt-1"
                                            >
                                                <span
                                                    x-show.important="index < group.phases.length - 1"
                                                    class="ndb:absolute ndb:top-3 ndb:-bottom-1 ndb:left-1/2 ndb:w-px ndb:-translate-x-1/2 ndb:bg-zinc-200 ndb:dark:bg-zinc-800"
                                                ></span>
                                                <span
                                                    class="ndb:relative ndb:z-[1] ndb:size-2 ndb:rounded-full ndb:bg-indigo-500 ndb:ring-2 ndb:ring-white ndb:dark:bg-indigo-400 ndb:dark:ring-zinc-950"
                                                ></span>
                                            </div>
                                            <span class="ndb:pb-3">
                                                <span
                                                    class="ndb:block ndb:text-xs ndb:font-semibold"
                                                    x-text="phase.name"
                                                ></span>
                                                <span
                                                    class="ndb:mt-0.5 ndb:block ndb:text-[11px] ndb:text-zinc-400"
                                                    x-text="livewirePhaseDescription(phase.name)"
                                                ></span>
                                            </span>
                                            <span
                                                class="ndb:pb-3 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
                                                x-text="
                                                    `${Math.max(0, phase.at - selectedLivewireActivity.startedAt).toFixed(1)} ms`
                                                "
                                            ></span>
                                        </li>
                                    </template>
                                </ol>
                            </div>
                        </template>
                    </div>
                    <p
                        x-show.important="selectedLivewireActivity.phases.length === 0"
                        class="ndb:mt-2 ndb:text-xs ndb:text-zinc-400"
                    >Browser phases were not available for this stored request.</p>
                </section>
            </div>
        </article>
    </template>

    <div x-show.important="!selectedLivewireActivity" data-ndb-livewire-activity-detail-empty class="ndb:p-5">
        <div x-show.important="filteredLivewireActivity.length === 0">
            <x-newdebugbar::empty-state label="No matching activity to inspect." />
        </div>
        <div x-show.important="filteredLivewireActivity.length > 0">
            <x-newdebugbar::empty-state label="Select an interaction to inspect it." />
        </div>
    </div>
</div>
