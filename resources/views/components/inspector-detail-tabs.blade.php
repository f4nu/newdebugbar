@props([
    'align' => 'center',
    'label',
])

@php
    [$containerClasses, $tabsClasses, $asideClasses] = match ($align) {
        'center' => [
            'ndb:grid ndb:grid-cols-1 ndb:justify-items-center ndb:gap-2 ndb:sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]',
            'ndb:sm:col-start-2',
            'ndb:sm:col-start-3 ndb:sm:row-start-1 ndb:sm:justify-self-end',
        ],
        'left' => [
            'ndb:flex ndb:flex-wrap ndb:items-center ndb:justify-between ndb:gap-2',
            '',
            'ndb:ml-auto',
        ],
        default => throw new \InvalidArgumentException("Unknown inspector detail tab alignment [{$align}]."),
    };
@endphp

<div {{ $attributes->class("ndb:border-b ndb:border-zinc-200/90 ndb:px-3 ndb:py-2.5 ndb:sm:px-4 ndb:dark:border-zinc-800 {$containerClasses}") }}>
    <x-newdebugbar::filter-tabs :label="$label" variant="segmented" class="ndb:min-w-0 {{ $tabsClasses }}">
        {{ $slot }}
    </x-newdebugbar::filter-tabs>

    @isset($aside)
        <div {{ $aside->attributes->class($asideClasses) }}>{{ $aside }}</div>
    @endisset
</div>
