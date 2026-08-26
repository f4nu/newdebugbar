@props([
    'frames',
    'columns' => 2,
    'emptyLabel' => 'No application stack was captured.',
])

@php
    $columnClasses = match ((int) $columns) {
        1 => 'ndb:grid-cols-1',
        2 => 'ndb:grid-cols-1 ndb:sm:grid-cols-2',
        default => throw new \InvalidArgumentException("Unsupported inspector source panel column count [{$columns}]."),
    };
@endphp

<section data-ndb-inspector-source-panel {{ $attributes->class('ndb:p-4') }}>
    <dl class="ndb:grid ndb:gap-2 {{ $columnClasses }}">{{ $slot }}</dl>

    <x-newdebugbar::inspector-stack :frames="$frames" :empty-label="$emptyLabel" />
</section>
