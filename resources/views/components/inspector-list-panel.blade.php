@props([
    'detailOpen',
    'listRef',
])

<div
    :class="{{ $detailOpen }} ? 'ndb:hidden ndb:lg:flex' : 'ndb:flex'"
    {{ $attributes->class('ndb:min-h-0 ndb:flex-col ndb:border-b ndb:border-zinc-200/90 ndb:lg:border-r ndb:lg:border-b-0 ndb:dark:border-zinc-800') }}
>
    <div {{ $controls->attributes->class('ndb:space-y-3 ndb:border-b ndb:border-zinc-200/90 ndb:p-3 ndb:dark:border-zinc-800') }}>
        {{ $controls }}
    </div>

    <div
        x-ref="{{ $listRef }}"
        {{ $list->attributes->class('ndb-scrollbar ndb:min-h-0 ndb:flex-1 ndb:divide-y ndb:divide-zinc-200/80 ndb:overflow-y-auto ndb:dark:divide-zinc-800') }}
    >
        {{ $list }}
    </div>

    @isset($empty)
        <div {{ $empty->attributes->class('ndb:p-3') }}>{{ $empty }}</div>
    @endisset
</div>
