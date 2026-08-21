<div
    data-ndb-livewire-activity
    class="ndb:min-h-[31rem] ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/45 ndb:sm:grid ndb:sm:grid-cols-[minmax(17rem,0.85fr)_minmax(0,1.35fr)] ndb:sm:items-start ndb:sm:overflow-visible ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/30"
>
    <div
        :class="livewireDetailOpen ? 'ndb:hidden ndb:sm:block' : 'ndb:block'"
        class="ndb:min-w-0 ndb:border-zinc-200/90 ndb:sm:border-r ndb:dark:border-zinc-800"
    >
        <div class="ndb:flex ndb:items-center ndb:justify-between ndb:gap-3 ndb:border-b ndb:border-zinc-200/90 ndb:bg-zinc-50/65 ndb:px-4 ndb:py-3 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/45">
            <div>
                <h3 class="ndb:text-xs ndb:font-bold">Activity</h3>
                <p class="ndb:mt-0.5 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
                    <span x-text="filteredLivewireActivity.length"></span>
                    <span x-text="filteredLivewireActivity.length === 1 ? 'interaction' : 'interactions'"></span>
                </p>
            </div>
            <div class="ndb:flex ndb:items-center ndb:gap-2">
                <span
                    x-show.important="livewireActivity.some((item) => item.status === 'updating')"
                    class="ndb:rounded-md ndb:bg-indigo-50 ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-bold ndb:text-indigo-700 ndb:dark:bg-indigo-950/60 ndb:dark:text-indigo-300"
                >Live</span>
                <label class="ndb:relative">
                    <span class="ndb:sr-only">Order Livewire activity</span>
                    <select
                        data-ndb-livewire-order
                        x-model="livewireActivityOrder"
                        @change="setLivewireActivityOrder($event.target.value)"
                        class="ndb:h-8 ndb:appearance-none ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/80 ndb:pr-7 ndb:pl-2.5 ndb:text-[11px] ndb:font-bold ndb:outline-none ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/80"
                    >
                        <option value="newest">Newest first</option>
                        <option value="oldest">Oldest first</option>
                    </select>
                    <x-newdebugbar::icon
                        name="chevron-down"
                        class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-2 ndb:size-3 ndb:-translate-y-1/2 ndb:text-zinc-400"
                    />
                </label>
            </div>
        </div>

        <ol data-ndb-livewire-activity-list class="ndb:m-0 ndb:list-none ndb:p-2">
            <template x-for="(group, index) in livewireActivityGroups" :key="group.id">
                <li class="ndb:relative ndb:grid ndb:grid-cols-[18px_minmax(0,1fr)] ndb:gap-x-3">
                    <div aria-hidden="true" class="ndb:relative ndb:translate-x-2">
                        <span
                            x-show.important="index < livewireActivityGroups.length - 1"
                            class="ndb:absolute ndb:top-[28px] ndb:-bottom-[18px] ndb:left-1/2 ndb:w-px ndb:-translate-x-1/2 ndb:bg-zinc-200 ndb:dark:bg-zinc-800"
                        ></span>
                        <span
                            data-ndb-livewire-activity-dot
                            class="ndb:absolute ndb:top-[19px] ndb:left-1/2 ndb:z-[1] ndb:size-2.5 ndb:-translate-x-1/2 ndb:-translate-y-1/2 ndb:rounded-full ndb:ring-4 ndb:ring-white ndb:dark:ring-zinc-950"
                            :class="group.items.some(
                                (item) => item.status === 'failed' || item.status === 'failed_validation',
                            )
                                ? 'ndb:bg-red-500'
                                : group.items.some((item) => item.status === 'updating')
                                  ? 'ndb:bg-indigo-500 ndb:animate-pulse'
                                  : 'ndb:bg-emerald-500'"
                        ></span>
                    </div>

                    <div class="ndb:min-w-0">
                        <template x-if="! group.grouped">
                            <div
                                x-data="{
                                    get item() {
                                        return group.first;
                                    },
                                }"
                            >
                                @include('newdebugbar::livewire.livewire.activity-item')
                            </div>
                        </template>

                        <template x-if="group.grouped">
                            <div class="ndb:min-w-0">
                                <div
                                    class="ndb:flex ndb:min-w-0 ndb:items-start ndb:rounded-lg ndb:border ndb:border-transparent ndb:transition"
                                    :class="livewireActivityGroupSelected(group)
                                        ? 'ndb:border-indigo-200 ndb:bg-linear-to-r ndb:from-transparent ndb:to-indigo-50/80 ndb:dark:border-indigo-900 ndb:dark:to-indigo-950/45'
                                        : 'ndb:hover:bg-zinc-50 ndb:dark:hover:bg-zinc-900/65'"
                                >
                                    <button
                                        type="button"
                                        @click="selectLivewireActivity(group.first.id)"
                                        class="ndb:min-w-0 ndb:flex-1 ndb:px-3 ndb:py-2.5 ndb:text-left ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
                                    >
                                        <span class="ndb:flex ndb:min-w-0 ndb:items-start ndb:gap-3">
                                            <span class="ndb:min-w-0 ndb:flex-1">
                                                <span
                                                    data-ndb-livewire-activity-title
                                                    class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold"
                                                    x-text="group.title"
                                                ></span>
                                                <span class="ndb:mt-0.5 ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-1.5">
                                                    <span
                                                        class="ndb:min-w-0 ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                                        x-text="group.subtitle"
                                                    ></span>
                                                    <span
                                                        x-show.important="group.showIdentity"
                                                        class="ndb:shrink-0 ndb:rounded ndb:bg-zinc-100 ndb:px-1 ndb:py-0.5 ndb:font-mono ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400 ndb:dark:bg-zinc-800"
                                                        x-text="livewireShortInstance(group.first.componentId)"
                                                    ></span>
                                                    <span
                                                        x-show.important="group.countLabel"
                                                        class="ndb:shrink-0 ndb:rounded ndb:bg-zinc-100 ndb:px-1 ndb:py-0.5 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:bg-zinc-800 ndb:dark:text-zinc-400"
                                                        x-text="group.countLabel"
                                                    ></span>
                                                </span>
                                            </span>
                                            <span class="ndb:flex ndb:shrink-0 ndb:flex-col ndb:items-end ndb:gap-0.5 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400">
                                                <span
                                                    class="ndb:whitespace-nowrap"
                                                    x-text="livewireActivityAge(group.first)"
                                                ></span>
                                                <span
                                                    class="ndb:whitespace-nowrap ndb:text-zinc-500 ndb:dark:text-zinc-300"
                                                    x-text="livewireDuration(group.first)"
                                                ></span>
                                            </span>
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        data-ndb-livewire-activity-group-toggle
                                        @click.stop="toggleLivewireActivityGroup(group)"
                                        :aria-expanded="livewireActivityGroupExpanded(group)"
                                        :aria-label="`${livewireActivityGroupExpanded(group) ? 'Collapse' : 'Expand'} ${group.title}`"
                                        class="ndb:mt-2.5 ndb:mr-2.5 ndb:grid ndb:size-6 ndb:shrink-0 ndb:place-items-center ndb:rounded ndb:border ndb:border-zinc-200 ndb:text-zinc-500 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:border-zinc-700 ndb:dark:text-zinc-400"
                                    >
                                        <span
                                            aria-hidden="true"
                                            x-text="livewireActivityGroupExpanded(group) ? '−' : '+'"
                                        ></span>
                                    </button>
                                </div>

                                <ol
                                    x-show.important="livewireActivityGroupExpanded(group)"
                                    class="ndb:mt-1 ndb:ml-3 ndb:list-none ndb:border-l ndb:border-zinc-200 ndb:pl-2 ndb:dark:border-zinc-800"
                                >
                                    <template x-for="item in group.items" :key="item.id">
                                        <li>
                                            @include('newdebugbar::livewire.livewire.activity-item')
                                        </li>
                                    </template>
                                </ol>
                            </div>
                        </template>
                    </div>
                </li>
            </template>
        </ol>

        <div x-show.important="filteredLivewireActivity.length === 0" class="ndb:p-4">
            <x-newdebugbar::empty-state label="No Livewire activity matches this view." />
        </div>
    </div>

    @include('newdebugbar::livewire.livewire.activity-detail')
</div>
