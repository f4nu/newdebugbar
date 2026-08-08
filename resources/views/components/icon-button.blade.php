@props([
    'name' => null,
    'iconClass' => 'ndb:size-4',
    'darkSurface' => false,
])

<button
    type="button"
    {{
        $attributes->class([
            'ndb:inline-flex ndb:items-center ndb:justify-center ndb:text-zinc-500 ndb:transition ndb:hover:bg-zinc-100 ndb:hover:text-zinc-950 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:pointer-events-none ndb:disabled:opacity-25 ndb:dark:hover:text-white',
            'ndb:dark:text-zinc-300 ndb:dark:hover:bg-white/10' => $darkSurface,
            'ndb:dark:text-zinc-400 ndb:dark:hover:bg-zinc-800' => ! $darkSurface,
        ])
    }}
>
    @if ($name)
        <x-newdebugbar::icon :name="$name" :class="$iconClass" />
    @else
        {{ $slot }}
    @endif
</button>
