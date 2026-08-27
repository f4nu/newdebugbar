@props([
    'items',
    'summary',
])

<x-newdebugbar::inspector-workspace frame="top" data-ndb-http-client-workspace>
    <x-newdebugbar::inspector-list-panel detail-open="httpClientDetailOpen" list-ref="httpClientList">
        <x-slot:controls>
            <x-newdebugbar::http-client-controls :summary="$summary" :items="$items" />
        </x-slot:controls>

        <x-slot:list data-ndb-http-client-list x-show.important="visibleHttpClientCount > 0">
            @foreach ($items as $item)
                <x-newdebugbar::http-client-list-item :item="$item" />
            @endforeach
        </x-slot:list>

        <x-slot:empty
            x-show.important="visibleHttpClientCount === 0"
            class="ndb:flex ndb:min-h-0 ndb:flex-1 ndb:items-center ndb:justify-center"
        >
            <x-newdebugbar::empty-state label="No outbound HTTP requests match these controls." />
        </x-slot:empty>
    </x-newdebugbar::inspector-list-panel>

    <x-newdebugbar::http-client-detail />
</x-newdebugbar::inspector-workspace>
