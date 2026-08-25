<button
    type="button"
    data-ndb-inspector-source-link
    {{
        $attributes->class(
            'ndb:group ndb:inline-flex ndb:h-auto ndb:min-h-0 ndb:max-w-full ndb:items-center ndb:gap-1.5 ndb:rounded-md ndb:bg-transparent ndb:px-1 ndb:py-0.5 ndb:text-left ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:transition-colors ndb:hover:bg-zinc-100 ndb:hover:text-zinc-950 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-zinc-200 ndb:dark:hover:bg-zinc-800 ndb:dark:hover:text-white',
        )
    }}
>
    <x-newdebugbar::icon
        name="code"
        size="3"
        class="ndb:text-zinc-400 ndb:transition-colors ndb:group-hover:text-zinc-600 ndb:dark:text-zinc-500 ndb:dark:group-hover:text-zinc-300"
    />
    @isset($value)
        <span {{ $value->attributes->class('ndb:min-w-0 ndb:truncate') }}>{{ $value }}</span>
    @else
        <span class="ndb:min-w-0 ndb:truncate">{{ $slot }}</span>
    @endisset
</button>
