@props(['label'])

<div
    role="group"
    aria-label="{{ $label }}"
    data-ndb-filter-tabs
    {{ $attributes->class('ndb:flex ndb:gap-1 ndb:overflow-x-auto') }}
>
    {{ $slot }}
</div>
