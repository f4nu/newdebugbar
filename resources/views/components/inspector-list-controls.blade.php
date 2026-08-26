@props(['showSearch'])

@php
    $hasLeading = isset($leading);
    $hasFilter = isset($filter);
@endphp

<div
    data-ndb-inspector-list-controls
    {{
        $attributes->class([
            'ndb:grid ndb:items-start ndb:gap-x-2 ndb:gap-y-3',
            'ndb:grid-cols-[minmax(0,1fr)_8.75rem]' => $hasFilter,
            'ndb:grid-cols-1' => ! $hasFilter,
        ])
    }}
>
    @isset($leading)
        <div @class(['ndb:min-w-0', 'ndb:col-span-2' => $showSearch && $hasFilter])>{{ $leading }}</div>
    @endisset

    @if ($showSearch && isset($search))
        <div @class(['ndb:min-w-0', 'ndb:col-span-2' => ! $hasFilter])>{{ $search }}</div>
    @endif

    @isset($filter)
        <div @class(['ndb:min-w-0', 'ndb:col-span-2' => ! $showSearch && ! $hasLeading])>{{ $filter }}</div>
    @endisset
</div>
