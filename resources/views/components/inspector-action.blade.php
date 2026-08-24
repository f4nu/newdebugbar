@props(['icon'])

<button
    type="button"
    {{ $attributes->class('ndb:inline-flex ndb:h-auto ndb:min-h-9 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:px-2 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:transition ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/50') }}
>
    <x-newdebugbar::icon :name="$icon" size="3.5" />
    {{ $slot }}
</button>
