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
            <template x-for="(item, index) in filteredLivewireActivity" :key="item.id">
                <li class="ndb:relative ndb:grid ndb:grid-cols-[18px_minmax(0,1fr)] ndb:gap-x-3">
                    <div aria-hidden="true" class="ndb:relative ndb:translate-x-2">
                        <span
                            x-show.important="index < filteredLivewireActivity.length - 1"
                            class="ndb:absolute ndb:top-[28px] ndb:-bottom-[18px] ndb:left-1/2 ndb:w-px ndb:-translate-x-1/2 ndb:bg-zinc-200 ndb:dark:bg-zinc-800"
                        ></span>
                        <span
                            data-ndb-livewire-activity-dot
                            class="ndb:absolute ndb:top-[19px] ndb:left-1/2 ndb:z-[1] ndb:size-2.5 ndb:-translate-x-1/2 ndb:-translate-y-1/2 ndb:rounded-full ndb:ring-4 ndb:ring-white ndb:dark:ring-zinc-950"
                            :class="item.status === 'failed' || item.status === 'failed_validation'
                                ? 'ndb:bg-red-500'
                                : item.status === 'updating'
                                  ? 'ndb:bg-indigo-500 ndb:animate-pulse'
                                  : 'ndb:bg-emerald-500'"
                        ></span>
                    </div>

                    <div class="ndb:min-w-0">
                        @include('newdebugbar::livewire.livewire.activity-item')
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
