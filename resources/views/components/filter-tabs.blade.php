@props([
    'label',
    'variant' => 'tabs',
])

@php
    $classes = match ($variant) {
        'tabs' => 'ndb:flex ndb:gap-1 ndb:overflow-x-auto',
        'segmented' => 'ndb:grid ndb:auto-cols-fr ndb:grid-flow-col ndb:gap-0.5 ndb:overflow-x-auto ndb:rounded-lg ndb:border ndb:border-zinc-200/90 ndb:bg-zinc-100/80 ndb:p-0.5 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/80',
        default => throw new \InvalidArgumentException("Unknown filter tabs variant [{$variant}]."),
    };
@endphp

<div
    role="group"
    aria-label="{{ $label }}"
    data-ndb-filter-tabs
    data-ndb-filter-tabs-variant="{{ $variant }}"
    {{ $attributes->class($classes) }}
>
    {{ $slot }}
</div>
