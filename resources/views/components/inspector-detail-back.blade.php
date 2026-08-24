@props(['label', 'persistent' => false])

<button
    type="button"
    {{
        $attributes->class([
            'ndb:m-2 ndb:inline-flex ndb:h-auto ndb:w-fit ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:p-2 ndb:text-xs ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300',
            'ndb:lg:hidden' => ! $persistent,
        ])
    }}
>
    <x-newdebugbar::icon name="chevron-down" size="3.5" class="ndb:rotate-90" />
    {{ $label }}
</button>
