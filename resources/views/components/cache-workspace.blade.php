@props([
    'items',
    'summary',
])

<x-newdebugbar::inspector-workspace frame="top" data-ndb-cache-workspace>
    <x-newdebugbar::inspector-list-panel detail-open="cacheDetailOpen" list-ref="cacheList">
        <x-slot:controls>
            <x-newdebugbar::cache-controls :summary="$summary" :item-count="count($items)" />
        </x-slot:controls>

        <x-slot:list data-ndb-cache-list>
            @foreach ($items as $item)
                <x-newdebugbar::cache-list-item :item="$item" />
            @endforeach
        </x-slot:list>

        <x-slot:empty x-show.important="visibleCacheCount === 0">
            <x-newdebugbar::empty-state label="No cache operations match these controls." />
        </x-slot:empty>
    </x-newdebugbar::inspector-list-panel>

    <x-newdebugbar::cache-detail />
</x-newdebugbar::inspector-workspace>
