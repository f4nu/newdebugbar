@props([
    'name' => null,
    'iconClass' => 'ndb:size-4',
])

<button
    type="button"
    {{ $attributes->class('ndb:inline-flex ndb:items-center ndb:justify-center ndb:text-zinc-500 ndb:transition ndb:hover:bg-zinc-100 ndb:hover:text-zinc-950 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:pointer-events-none ndb:disabled:opacity-25 ndb:dark:text-zinc-400 ndb:dark:hover:bg-zinc-800 ndb:dark:hover:text-white') }}
>
    @if ($name)
        <x-new-debug-bar::icon :name="$name" :class="$iconClass" />
    @else
        {{ $slot }}
    @endif
</button>
