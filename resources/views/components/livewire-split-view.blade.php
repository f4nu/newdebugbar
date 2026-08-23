<div {{
    $attributes->class(
        'ndb:min-h-[31rem] ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/45 ndb:sm:grid ndb:sm:grid-cols-[minmax(15rem,0.7fr)_minmax(0,1.5fr)] ndb:sm:items-start ndb:sm:overflow-visible ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/30',
    )
}}>
    <div
        :class="livewireDetailOpen ? 'ndb:hidden ndb:sm:block' : 'ndb:block'"
        class="ndb:min-w-0 ndb:border-zinc-200/90 ndb:sm:border-r ndb:dark:border-zinc-800"
    >
        {{ $list }}
    </div>

    {{ $slot }}
</div>
