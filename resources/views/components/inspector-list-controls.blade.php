@props(['showSearch'])

@php
    $hasLeading = isset($leading);
@endphp

<div
    data-ndb-inspector-list-controls
    {{ $attributes->class('ndb:grid ndb:grid-cols-[minmax(0,1fr)_8.75rem] ndb:items-start ndb:gap-x-2 ndb:gap-y-3') }}
>
    @isset($leading)
        <div @class(['ndb:min-w-0', 'ndb:col-span-2' => $showSearch])>{{ $leading }}</div>
    @endisset

    @if ($showSearch)
        <div class="ndb:min-w-0">{{ $search }}</div>
    @endif

    <div @class(['ndb:min-w-0', 'ndb:col-span-2' => ! $showSearch && ! $hasLeading])>{{ $filter }}</div>
</div>
