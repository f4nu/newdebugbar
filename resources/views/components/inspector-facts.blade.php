@props([
    'columns' => 4,
    'bordered' => true,
])

@php
    $columnClasses = match ((int) $columns) {
        1 => 'ndb:grid-cols-1',
        2 => 'ndb:grid-cols-2',
        4 => 'ndb:grid-cols-2 ndb:sm:grid-cols-4',
        default => throw new \InvalidArgumentException("Unsupported inspector fact column count [{$columns}]."),
    };
    $borderClasses = $bordered
        ? 'ndb:border-b ndb:border-zinc-200/90 ndb:pb-3 ndb:sm:pb-4 ndb:dark:border-zinc-800'
        : '';
@endphp

<dl {{ $attributes->class("ndb:grid ndb:border-t-0 ndb:bg-transparent ndb:pt-0 ndb:text-zinc-700 ndb:gap-x-3 ndb:gap-y-2 ndb:sm:gap-x-5 ndb:sm:gap-y-3 ndb:dark:text-zinc-200 {$columnClasses} {$borderClasses}") }}>
    {{ $slot }}
</dl>
