@props([
    'frames',
    'columns' => 2,
    'emptyLabel' => 'No application stack was captured.',
    'title' => null,
])

@php
    $columnClasses = match ((int) $columns) {
        1 => 'ndb:grid-cols-1',
        2 => 'ndb:grid-cols-1 ndb:sm:grid-cols-2',
        default => throw new \InvalidArgumentException("Unsupported inspector source panel column count [{$columns}]."),
    };
@endphp

<section data-ndb-inspector-source-panel {{ $attributes->class('ndb:p-4') }}>
    @if ($title !== null || isset($actions))
        <div @class([
            'ndb:mb-3 ndb:flex ndb:items-center ndb:gap-3',
            'ndb:justify-between' => $title !== null && isset($actions),
            'ndb:justify-end' => $title === null && isset($actions),
        ])>
            @if ($title !== null)
                <h4 class="ndb:text-xs ndb:font-bold ndb:text-zinc-800 ndb:dark:text-zinc-100">{{ $title }}</h4>
            @endif
            @isset($actions)
                <div {{ $actions->attributes->class('ndb:shrink-0') }}>{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <dl class="ndb:grid ndb:gap-2 {{ $columnClasses }}">{{ $slot }}</dl>

    <x-newdebugbar::inspector-stack :frames="$frames" :empty-label="$emptyLabel" />
</section>
