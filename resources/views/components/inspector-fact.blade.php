@props(['label'])

<div data-ndb-inspector-fact {{ $attributes->class('ndb:min-w-0 ndb:bg-transparent') }}>
    <dt class="ndb:bg-transparent ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-400">
        {{ $label }}
    </dt>
    @isset($value)
        <dd {{ $value->attributes->class('ndb:mt-0.5 ndb:min-w-0 ndb:bg-transparent ndb:text-zinc-700 ndb:dark:text-zinc-200') }}>
            {{ $value }}
        </dd>
    @else
        <dd class="ndb:mt-0.5 ndb:min-w-0 ndb:bg-transparent ndb:text-zinc-700 ndb:dark:text-zinc-200">{{ $slot }}</dd>
    @endisset
</div>
