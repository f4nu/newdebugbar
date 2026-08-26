@props(['label'])

<div
    data-ndb-inspector-detail-empty
    {{ $attributes->class('ndb:flex ndb:min-h-[32rem] ndb:flex-1 ndb:items-center ndb:justify-center ndb:p-6 ndb:lg:min-h-0') }}
>
    <p class="ndb:max-w-sm ndb:text-center ndb:text-xs ndb:font-semibold ndb:text-zinc-400">{{ $label }}</p>
</div>
