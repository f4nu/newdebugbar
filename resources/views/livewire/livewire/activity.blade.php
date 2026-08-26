<ol data-ndb-livewire-activity-list aria-label="Livewire activity timeline" class="ndb:m-0 ndb:list-none ndb:p-2">
    <template x-for="(item, index) in filteredLivewireActivity" :key="item.id">
        <li
            data-ndb-livewire-activity-timeline-item
            class="ndb:grid ndb:min-w-0 ndb:grid-cols-[18px_minmax(0,1fr)] ndb:gap-x-2.5"
        >
            <span aria-hidden="true" class="ndb:relative ndb:flex ndb:justify-center">
                <span
                    data-ndb-livewire-activity-connector
                    x-show.important="index < filteredLivewireActivity.length - 1"
                    class="ndb:absolute ndb:top-6 ndb:-bottom-6 ndb:left-1/2 ndb:w-px ndb:-translate-x-1/2 ndb:bg-zinc-200 ndb:dark:bg-zinc-800"
                ></span>
                <span
                    data-ndb-livewire-activity-dot
                    class="ndb:absolute ndb:top-6 ndb:left-1/2 ndb:z-[1] ndb:size-2.5 ndb:-translate-x-1/2 ndb:-translate-y-1/2 ndb:rounded-full ndb:ring-4 ndb:ring-white ndb:dark:ring-zinc-950"
                    :class="item.status === 'failed' || item.status === 'failed_validation'
                        ? 'ndb:bg-red-500'
                        : item.status === 'updating'
                          ? 'ndb:animate-pulse ndb:bg-indigo-500 ndb:motion-reduce:animate-none'
                          : 'ndb:bg-emerald-500'"
                ></span>
            </span>

            <div class="ndb:min-w-0 ndb:pb-1">
                @include('newdebugbar::livewire.livewire.activity-item')
            </div>
        </li>
    </template>
</ol>

<div x-show.important="filteredLivewireActivity.length === 0" class="ndb:p-3">
    <x-newdebugbar::empty-state label="No Livewire activity matches this view." />
</div>
