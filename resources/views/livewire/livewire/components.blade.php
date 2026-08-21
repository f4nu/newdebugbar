<div
    data-ndb-livewire-components
    class="ndb:min-h-[31rem] ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/45 ndb:sm:grid ndb:sm:grid-cols-[minmax(15rem,0.7fr)_minmax(0,1.5fr)] ndb:sm:items-start ndb:sm:overflow-visible ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/30"
>
    <div
        :class="livewireDetailOpen ? 'ndb:hidden ndb:sm:block' : 'ndb:block'"
        class="ndb:min-w-0 ndb:border-zinc-200/90 ndb:sm:border-r ndb:dark:border-zinc-800"
    >
        <div class="ndb:border-b ndb:border-zinc-200/90 ndb:bg-zinc-50/65 ndb:px-4 ndb:py-3 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/45">
            <h3 class="ndb:text-xs ndb:font-bold">Mounted components</h3>
            <p class="ndb:mt-0.5 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
                <span x-text="matchingLivewireComponents.length"></span>
                <span x-text="matchingLivewireComponents.length === 1 ? 'instance' : 'instances'"></span>
            </p>
        </div>

        <div data-ndb-livewire-component-list class="ndb:p-2">
            <template x-for="component in filteredLivewireComponents" :key="component.id">
                <div
                    data-ndb-livewire-component-row
                    :data-ndb-livewire-component-id="component.id"
                    :data-ndb-livewire-component-depth="component.depth"
                    :style="`padding-left: ${12 + component.depth * 18}px`"
                    class="ndb:flex ndb:w-full ndb:min-w-0 ndb:items-start ndb:gap-2 ndb:rounded-lg ndb:border ndb:border-transparent ndb:pr-3 ndb:transition"
                    :class="livewireSelectedComponentId === component.id
                        ? 'ndb:border-indigo-200 ndb:bg-indigo-50/80 ndb:dark:border-indigo-900 ndb:dark:bg-indigo-950/45'
                        : livewireComponentIsSearchContext(component)
                          ? 'ndb:opacity-55'
                          : 'ndb:hover:bg-zinc-50 ndb:dark:hover:bg-zinc-900/65'"
                >
                    <button
                        x-show.important="component.hasChildren"
                        data-ndb-livewire-component-toggle
                        type="button"
                        @click.stop="toggleLivewireComponent(component)"
                        :aria-expanded="! livewireComponentCollapsed(component)"
                        :aria-label="`${livewireComponentCollapsed(component) ? 'Expand' : 'Collapse'} ${component.title}`"
                        class="ndb:mt-2.5 ndb:grid ndb:size-4 ndb:shrink-0 ndb:place-items-center ndb:rounded-[2px] ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:text-zinc-500 ndb:transition ndb:hover:border-zinc-300 ndb:hover:text-zinc-700 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70 ndb:dark:text-zinc-400 ndb:dark:hover:border-zinc-600 ndb:dark:hover:text-zinc-200"
                    >
                        <span aria-hidden="true" class="ndb:relative ndb:size-2">
                            <span class="ndb:absolute ndb:top-1/2 ndb:left-0 ndb:h-px ndb:w-full ndb:-translate-y-1/2 ndb:bg-current"></span>
                            <span
                                x-show.important="livewireComponentCollapsed(component)"
                                data-ndb-livewire-component-toggle-vertical
                                class="ndb:absolute ndb:top-0 ndb:left-1/2 ndb:h-full ndb:w-px ndb:-translate-x-1/2 ndb:bg-current"
                            ></span>
                        </span>
                    </button>
                    <span
                        x-show.important="! component.hasChildren"
                        aria-hidden="true"
                        class="ndb:mt-2.5 ndb:size-4 ndb:shrink-0"
                    ></span>
                    <button
                        data-ndb-livewire-component-select
                        type="button"
                        @click="selectLivewireComponent(component.id)"
                        :aria-current="livewireSelectedComponentId === component.id ? 'true' : null"
                        class="ndb:flex ndb:min-w-0 ndb:flex-1 ndb:items-start ndb:gap-2 ndb:py-2.5 ndb:text-left ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-1 ndb:focus-visible:outline-indigo-500"
                    >
                        <span
                            data-ndb-livewire-component-dot
                            class="ndb:mt-1 ndb:size-2 ndb:shrink-0 ndb:rounded-full"
                            :class="component.status === 'failed'
                                ? 'ndb:bg-red-500'
                                : component.status === 'updating'
                                  ? 'ndb:bg-indigo-500 ndb:animate-pulse'
                                  : 'ndb:bg-emerald-500'"
                        ></span>
                        <span class="ndb:min-w-0 ndb:flex-1">
                            <span
                                data-ndb-livewire-component-title
                                class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold"
                                x-text="component.title"
                            ></span>
                            <span class="ndb:mt-0.5 ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-1.5">
                                <span
                                    class="ndb:min-w-0 ndb:truncate ndb:font-mono ndb:text-[11px] ndb:text-zinc-400"
                                    x-text="component.name"
                                ></span>
                                <span
                                    x-show.important="livewireComponentIsSearchContext(component)"
                                    class="ndb:shrink-0 ndb:rounded ndb:bg-zinc-100 ndb:px-1 ndb:py-0.5 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-500 ndb:dark:bg-zinc-800 ndb:dark:text-zinc-400"
                                >Context</span>
                                <span
                                    x-show.important="livewireComponentNeedsIdentity(component)"
                                    class="ndb:shrink-0 ndb:rounded ndb:bg-zinc-100 ndb:px-1 ndb:py-0.5 ndb:font-mono ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400 ndb:dark:bg-zinc-800"
                                    x-text="livewireShortInstance(component.id)"
                                ></span>
                            </span>
                            <span
                                x-show.important="livewireComponentNeedsIdentity(component)"
                                class="ndb:mt-1 ndb:block ndb:truncate ndb:text-[11px] ndb:text-zinc-400"
                                x-text="livewireComponentParentLabel(component)"
                            ></span>
                            <span
                                x-show.important="
                                    livewireComponentLatestActivity(component) &&
                                    livewireComponentLatestActivity(component)?.kind !== 'mount'
                                "
                                class="ndb:mt-1.5 ndb:block ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                x-text="livewireComponentLatestActivity(component)?.title"
                            ></span>
                        </span>
                    </button>
                </div>
            </template>
        </div>

        <div x-show.important="filteredLivewireComponents.length === 0" class="ndb:p-4">
            <x-newdebugbar::empty-state label="No mounted components match this search." />
        </div>
    </div>

    <div
        :class="livewireDetailOpen ? 'ndb:block' : 'ndb:hidden ndb:sm:block'"
        class="ndb:min-w-0 ndb:sm:sticky ndb:sm:top-0 ndb:sm:z-10 ndb:sm:overflow-x-clip ndb:sm:bg-white/95 ndb:sm:dark:bg-zinc-950/95"
    >
        <button
            type="button"
            data-ndb-livewire-detail-back="components"
            @click="livewireDetailOpen = false"
            class="ndb:m-2 ndb:inline-flex ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:p-2 ndb:text-xs ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:sm:hidden ndb:dark:text-indigo-300"
        >
            <x-newdebugbar::icon name="chevron-down" class="ndb:size-3.5 ndb:rotate-90" />
            Components
        </button>

        <template x-if="selectedLivewireComponent">
            <article class="ndb:min-w-0">
                <header class="ndb:border-b ndb:border-zinc-200/90 ndb:px-4 ndb:py-4 ndb:sm:px-5 ndb:dark:border-zinc-800">
                    <div class="ndb:flex ndb:min-w-0 ndb:items-start ndb:gap-4">
                        <span class="ndb:mt-0.5 ndb:grid ndb:size-8 ndb:shrink-0 ndb:place-items-center ndb:rounded-lg ndb:bg-indigo-50 ndb:text-indigo-600 ndb:dark:bg-indigo-950/60 ndb:dark:text-indigo-300">
                            <x-newdebugbar::icon name="server" class="ndb:size-4" />
                        </span>
                        <div class="ndb:min-w-0 ndb:flex-1">
                            <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-2">
                                <h3
                                    class="ndb:min-w-0 ndb:text-sm ndb:font-bold"
                                    x-text="selectedLivewireComponent.title"
                                ></h3>
                                <span
                                    class="ndb:rounded-md ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide"
                                    :class="selectedLivewireComponent.status === 'failed'
                                        ? 'ndb:bg-red-50 ndb:text-red-700 ndb:dark:bg-red-950/60 ndb:dark:text-red-300'
                                        : selectedLivewireComponent.status === 'updating'
                                          ? 'ndb:bg-indigo-50 ndb:text-indigo-700 ndb:dark:bg-indigo-950/60 ndb:dark:text-indigo-300'
                                          : 'ndb:bg-emerald-50 ndb:text-emerald-700 ndb:dark:bg-emerald-950/60 ndb:dark:text-emerald-300'"
                                    :title="livewireComponentStatusDescription(selectedLivewireComponent)"
                                    :aria-label="`${selectedLivewireComponent.status}. ${livewireComponentStatusDescription(selectedLivewireComponent)}`"
                                    x-text="selectedLivewireComponent.status"
                                ></span>
                                <span
                                    x-show.important="
                                        selectedLivewireComponent.server?.implementation === 'single_file'
                                    "
                                    class="ndb:rounded-md ndb:bg-zinc-100 ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-500 ndb:dark:bg-zinc-800 ndb:dark:text-zinc-400"
                                >Single file</span>
                            </div>
                            <code
                                class="ndb:mt-1 ndb:block ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                x-text="selectedLivewireComponent.name"
                            ></code>
                            <p
                                class="ndb:mt-1 ndb:text-[11px] ndb:text-zinc-400"
                                x-text="livewireComponentStatusDescription(selectedLivewireComponent)"
                            ></p>
                        </div>
                        <button
                            type="button"
                            @click="inspectLivewireComponentActivity()"
                            :disabled="! selectedLivewireComponent.latestActivityId"
                            class="ndb:shrink-0 ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:px-2.5 ndb:py-1.5 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:transition ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:opacity-40 ndb:dark:border-zinc-700 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/50"
                        >
                            View activity
                        </button>
                    </div>

                    <dl class="ndb:mt-4 ndb:grid ndb:grid-cols-2 ndb:gap-x-4 ndb:gap-y-3 ndb:sm:grid-cols-4">
                        <div class="ndb:min-w-0">
                            <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                Instance
                            </dt>
                            <dd class="ndb:mt-1 ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-1.5">
                                <code
                                    class="ndb:min-w-0 ndb:truncate ndb:text-[11px]"
                                    x-text="selectedLivewireComponent.id"
                                ></code>
                            </dd>
                        </div>
                        <div class="ndb:min-w-0">
                            <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                Parent
                            </dt>
                            <dd
                                class="ndb:mt-1 ndb:truncate ndb:text-[11px] ndb:font-semibold"
                                x-text="
                                    selectedLivewireComponent.parentId
                                        ? livewireComponentTitle(selectedLivewireComponent.parentId)
                                        : 'Top level'
                                "
                            ></dd>
                        </div>
                        <div class="ndb:min-w-0">
                            <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                Source
                            </dt>
                            <dd
                                class="ndb:mt-1 ndb:truncate ndb:text-[11px] ndb:font-semibold"
                                :title="selectedLivewireComponent.server?.source?.file"
                                x-text="selectedLivewireComponent.server?.source?.file ?? 'Browser only'"
                            ></dd>
                        </div>
                        <div class="ndb:min-w-0">
                            <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                View
                            </dt>
                            <dd
                                class="ndb:mt-1 ndb:truncate ndb:text-[11px] ndb:font-semibold"
                                :title="selectedLivewireComponent.server?.view?.name"
                                x-text="selectedLivewireComponent.server?.view?.name ?? 'Not exposed'"
                            ></dd>
                        </div>
                    </dl>
                </header>

                <div class="ndb:space-y-6 ndb:p-4 ndb:sm:p-5">
                    <section>
                        <div class="ndb:flex ndb:items-center ndb:justify-between ndb:gap-3">
                            <div>
                                <h4 class="ndb:text-xs ndb:font-bold">Properties</h4>
                                <p class="ndb:mt-0.5 ndb:text-[11px] ndb:text-zinc-400">
                                    Browser is the value on this page. Server is the latest confirmed value. Differences
                                    are marked Dirty.
                                </p>
                            </div>
                            <span
                                class="ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
                                x-text="
                                    `${selectedLivewireComponent.properties.length} ${selectedLivewireComponent.properties.length === 1 ? 'property' : 'properties'}`
                                "
                            ></span>
                        </div>

                        <div
                            x-show.important="livewirePropertyRows.length > 0"
                            data-ndb-livewire-property-table
                            class="ndb-scrollbar ndb:mt-3 ndb:overflow-x-auto ndb:rounded-lg ndb:border ndb:border-zinc-200/90 ndb:dark:border-zinc-800"
                        >
                            <div class="ndb:hidden ndb:grid-cols-[minmax(10rem,1fr)_minmax(7rem,0.8fr)_minmax(7rem,0.8fr)_5rem_3rem] ndb:gap-3 ndb:border-b ndb:border-zinc-200/90 ndb:bg-zinc-50/75 ndb:px-3 ndb:py-2 ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:sm:grid ndb:sm:min-w-[36rem] ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/55">
                                <span>Property</span><span>Browser</span><span>Server</span><span>State</span
                                ><span></span>
                            </div>

                            <template x-for="row in livewirePropertyRows" :key="`${row.componentId}:${row.path}`">
                                <div
                                    :data-ndb-livewire-property-path="row.path"
                                    class="ndb:border-b ndb:border-zinc-200/80 ndb:last:border-b-0 ndb:sm:min-w-[36rem] ndb:dark:border-zinc-800"
                                >
                                    <div class="ndb:grid ndb:min-w-0 ndb:gap-2 ndb:px-3 ndb:py-2.5 ndb:sm:grid-cols-[minmax(10rem,1fr)_minmax(7rem,0.8fr)_minmax(7rem,0.8fr)_5rem_3rem] ndb:sm:items-center ndb:sm:gap-3">
                                        <div
                                            class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-1.5"
                                            :style="`padding-left: ${row.depth * 16}px`"
                                        >
                                            <button
                                                type="button"
                                                @click="toggleLivewireProperty(row)"
                                                :disabled="! row.hasChildren"
                                                :aria-label="`${row.expanded ? 'Collapse' : 'Expand'} ${row.path}`"
                                                class="ndb:grid ndb:size-5 ndb:shrink-0 ndb:place-items-center ndb:rounded ndb:text-zinc-400 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:opacity-0"
                                            >
                                                <x-newdebugbar::icon
                                                    name="chevron-down"
                                                    class="ndb:size-3 ndb:transition"
                                                    ::class="row.expanded ? '' : 'ndb:-rotate-90'"
                                                />
                                            </button>
                                            <code
                                                class="ndb:min-w-0 ndb:truncate ndb:text-[11px] ndb:font-bold"
                                                :title="row.path"
                                                x-text="row.label"
                                            ></code>
                                            <span
                                                class="ndb:shrink-0 ndb:rounded-md ndb:bg-zinc-100 ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:text-zinc-500 ndb:dark:bg-zinc-800 ndb:dark:text-zinc-400"
                                                x-text="row.phpType ?? row.type"
                                            ></span>
                                        </div>
                                        <div class="ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-2 ndb:sm:block">
                                            <span class="ndb:w-16 ndb:shrink-0 ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:sm:hidden">Browser</span>
                                            <code
                                                class="ndb:block ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-[11px] ndb:sm:w-full"
                                                :title="row.valueSummary"
                                                x-text="row.valueSummary"
                                            ></code>
                                        </div>
                                        <div class="ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-2 ndb:sm:block">
                                            <span class="ndb:w-16 ndb:shrink-0 ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:sm:hidden">Server</span>
                                            <code
                                                class="ndb:block ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:sm:w-full ndb:dark:text-zinc-400"
                                                :title="row.serverSummary"
                                                x-text="row.serverSummary"
                                            ></code>
                                        </div>
                                        <div>
                                            <span
                                                class="ndb:rounded-md ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide"
                                                :class="row.state === 'Dirty'
                                                    ? 'ndb:bg-amber-50 ndb:text-amber-700 ndb:dark:bg-amber-950/60 ndb:dark:text-amber-300'
                                                    : row.state === 'Updating'
                                                      ? 'ndb:bg-indigo-50 ndb:text-indigo-700 ndb:dark:bg-indigo-950/60 ndb:dark:text-indigo-300'
                                                      : ['Locked', 'Unknown'].includes(row.state)
                                                        ? 'ndb:bg-zinc-100 ndb:text-zinc-500 ndb:dark:bg-zinc-800 ndb:dark:text-zinc-400'
                                                        : 'ndb:bg-emerald-50 ndb:text-emerald-700 ndb:dark:bg-emerald-950/60 ndb:dark:text-emerald-300'"
                                                :title="livewirePropertyStateDescription(row)"
                                                x-text="livewirePropertyStateLabel(row)"
                                            ></span>
                                        </div>
                                        <x-newdebugbar::livewire-property-editor />
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div x-show.important="livewirePropertyRows.length === 0" class="ndb:mt-3">
                            <x-newdebugbar::empty-state label="This component has no serialized public properties." />
                        </div>
                    </section>

                    <section>
                        <h4 class="ndb:text-xs ndb:font-bold">Recent activity</h4>
                        <div class="ndb:mt-3 ndb:space-y-2">
                            <template
                                x-for="item in livewireComponentActivity(selectedLivewireComponent.id)"
                                :key="item.id"
                            >
                                <button
                                    type="button"
                                    @click="
                                        selectLivewireActivity(item.id);
                                        livewireTab = 'activity';
                                    "
                                    class="ndb:flex ndb:w-full ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:border ndb:border-zinc-200/90 ndb:px-3 ndb:py-2.5 ndb:text-left ndb:transition ndb:hover:bg-zinc-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:border-zinc-800 ndb:dark:hover:bg-zinc-900/65"
                                >
                                    <span
                                        class="ndb:size-2 ndb:shrink-0 ndb:rounded-full"
                                        :class="item.status === 'failed' || item.status === 'failed_validation'
                                            ? 'ndb:bg-red-500'
                                            : 'ndb:bg-emerald-500'"
                                    ></span>
                                    <span
                                        class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-semibold"
                                        x-text="livewireComponentActivityTitle(item)"
                                    ></span>
                                    <span
                                        class="ndb:shrink-0 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
                                        x-text="livewireDuration(item)"
                                    ></span>
                                </button>
                            </template>
                            <p
                                x-show.important="livewireComponentActivity(selectedLivewireComponent.id).length === 0"
                                class="ndb:text-xs ndb:text-zinc-400"
                            >
                                No activity has been observed for this instance.
                            </p>
                        </div>
                    </section>
                </div>
            </article>
        </template>

        <div x-show.important="! selectedLivewireComponent" data-ndb-livewire-component-detail-empty class="ndb:p-5">
            <div x-show.important="matchingLivewireComponents.length === 0">
                <x-newdebugbar::empty-state label="No matching component to inspect." />
            </div>
            <div x-show.important="matchingLivewireComponents.length > 0">
                <x-newdebugbar::empty-state label="Select a mounted component to inspect it." />
            </div>
        </div>
    </div>
</div>
