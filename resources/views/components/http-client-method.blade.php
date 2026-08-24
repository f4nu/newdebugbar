@props(['outlined' => false])

<span
    {{
        $attributes->class([
            'ndb:flex ndb:w-12 ndb:shrink-0 ndb:items-center ndb:justify-center ndb:rounded-md ndb:bg-zinc-100/70 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-600 ndb:dark:bg-white/10 ndb:dark:text-zinc-200',
            'ndb:ring-1 ndb:ring-inset ndb:ring-zinc-200/70 ndb:dark:ring-zinc-700' => $outlined,
        ])
    }}
>{{ $slot }}</span>
