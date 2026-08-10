<button
    type="button"
    {{
        $attributes->class(
            'ndb:flex ndb:shrink-0 ndb:items-baseline ndb:gap-1.5 ndb:whitespace-nowrap ndb:rounded-lg ndb:border ndb:border-transparent ndb:bg-zinc-100/70 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-semibold ndb:text-zinc-500 ndb:transition ndb:hover:bg-zinc-200/70 ndb:hover:text-zinc-950 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:aria-pressed:border-indigo-200 ndb:aria-pressed:bg-indigo-50 ndb:aria-pressed:text-indigo-700 ndb:aria-pressed:hover:bg-indigo-50 ndb:aria-pressed:hover:text-indigo-700 ndb:dark:bg-zinc-900/70 ndb:dark:text-zinc-400 ndb:dark:hover:bg-zinc-800 ndb:dark:hover:text-white ndb:dark:aria-pressed:border-indigo-900 ndb:dark:aria-pressed:bg-indigo-950/60 ndb:dark:aria-pressed:text-indigo-300 ndb:dark:aria-pressed:hover:bg-indigo-950/60 ndb:dark:aria-pressed:hover:text-indigo-300',
        )
    }}
>
    {{ $slot }}
</button>
