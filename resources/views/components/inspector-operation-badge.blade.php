@props([
    'outlined' => false,
    'wide' => false,
])

<span
    {{
        $attributes->class([
            'ndb:box-border ndb:flex ndb:h-auto ndb:shrink-0 ndb:items-center ndb:justify-center ndb:rounded-md ndb:bg-zinc-100/70 ndb:px-2 ndb:py-0.5 ndb:[font-family:inherit] ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-600 ndb:dark:bg-white/10 ndb:dark:text-zinc-200',
            'ndb:w-16' => $wide,
            'ndb:w-12' => ! $wide,
            'ndb:ring-1 ndb:ring-inset ndb:ring-zinc-200/70 ndb:dark:ring-zinc-700' => $outlined,
        ])
    }}
>{{ $slot }}</span>
