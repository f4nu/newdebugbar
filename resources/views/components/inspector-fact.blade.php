@props(['label'])

<div data-ndb-inspector-fact {{ $attributes->class('ndb:min-w-0') }}>
    <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">{{ $label }}</dt>
    @isset($value)
        <dd {{ $value->attributes->class('ndb:mt-0.5 ndb:min-w-0') }}>{{ $value }}</dd>
    @else
        <dd class="ndb:mt-0.5 ndb:min-w-0">{{ $slot }}</dd>
    @endisset
</div>
