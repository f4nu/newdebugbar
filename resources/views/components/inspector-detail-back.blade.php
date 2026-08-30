@props(['label', 'persistent' => false])

<button
    type="button"
    {{
        $attributes->class([
            'ndb:m-0 ndb:inline-flex ndb:min-h-11 ndb:w-fit ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300',
            'ndb:lg:hidden' => ! $persistent,
        ])
    }}
>
    <x-newdebugbar::icon name="chevron-down" size="3.5" class="ndb:rotate-90" />
    {{ $label }}
</button>
