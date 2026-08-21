<button
    type="button"
    data-ndb-livewire-activity-item
    @click="selectLivewireActivity(item.id)"
    :aria-current="livewireSelectedActivityId === item.id ? 'true' : null"
    class="ndb:w-full ndb:min-w-0 ndb:rounded-lg ndb:border ndb:border-transparent ndb:px-3 ndb:py-2.5 ndb:text-left ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-1 ndb:focus-visible:outline-indigo-500"
    :class="livewireSelectedActivityId === item.id
        ? 'ndb:border-indigo-200 ndb:bg-linear-to-r ndb:from-transparent ndb:to-indigo-50/80 ndb:dark:border-indigo-900 ndb:dark:to-indigo-950/45'
        : 'ndb:hover:bg-zinc-50 ndb:dark:hover:bg-zinc-900/65'"
>
    <span class="ndb:flex ndb:min-w-0 ndb:items-start ndb:gap-3">
        <span class="ndb:min-w-0 ndb:flex-1">
            <span class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-2">
                <span
                    data-ndb-livewire-activity-title
                    class="ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-bold"
                    x-text="item.title"
                ></span>
                <span
                    x-show.important="item.status === 'failed' || item.status === 'failed_validation'"
                    class="ndb:shrink-0 ndb:rounded-md ndb:bg-red-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-red-700 ndb:dark:bg-red-950/60 ndb:dark:text-red-300"
                    x-text="livewireActivityStatusLabel(item)"
                ></span>
            </span>
            <span
                x-show.important="livewireActivityShowsComponent(item)"
                data-ndb-livewire-activity-component
                class="ndb:mt-0.5 ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-1.5"
            >
                <span
                    class="ndb:min-w-0 ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400"
                    x-text="livewireActivityComponentTitle(item)"
                ></span>
            </span>
        </span>
        <span
            class="ndb:flex ndb:shrink-0 ndb:flex-col ndb:items-end ndb:gap-0.5 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
        >
            <span
                data-ndb-livewire-activity-age
                class="ndb:whitespace-nowrap"
                x-text="livewireActivityAge(item)"
            ></span>
            <span
                data-ndb-livewire-activity-duration
                class="ndb:whitespace-nowrap ndb:text-zinc-500 ndb:dark:text-zinc-300"
                x-text="livewireDuration(item)"
            ></span>
        </span>
    </span>
</button>
