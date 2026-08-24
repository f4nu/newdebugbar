@props(['label'])

<div {{ $attributes->class('ndb:grid ndb:min-h-[32rem] ndb:place-items-center ndb:p-6 ndb:lg:min-h-0') }}>
    <p class="ndb:text-xs ndb:font-semibold ndb:text-zinc-400">{{ $label }}</p>
</div>
