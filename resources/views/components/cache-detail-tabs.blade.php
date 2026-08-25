<x-newdebugbar::inspector-detail-tabs label="Cache operation detail">
    @foreach (['overview' => ['Overview', 'eye'], 'raw' => ['Raw', 'code'], 'source' => ['Source', 'activity']] as $tab => [$label, $icon])
        <x-newdebugbar::filter-tab
            variant="segmented"
            data-ndb-cache-detail-tab="{{ $tab }}"
            @click="setCacheDetailTab({{ \Illuminate\Support\Js::from($tab) }})"
            ::aria-pressed="cacheDetailTab === {{ \Illuminate\Support\Js::from($tab) }}"
            aria-label="{{ $label }}"
            class="ndb:h-auto"
        >
            <x-newdebugbar::icon :name="$icon" size="3.5" class="ndb:sm:hidden" />
            <span class="ndb:hidden ndb:sm:inline">{{ $label }}</span>
        </x-newdebugbar::filter-tab>
    @endforeach
</x-newdebugbar::inspector-detail-tabs>
