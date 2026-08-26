<ol
    data-ndb-livewire-activity-list
    class="ndb:m-0 ndb:list-none ndb:divide-y ndb:divide-zinc-200/80 ndb:p-0 ndb:dark:divide-zinc-800"
>
    <template x-for="item in filteredLivewireActivity" :key="item.id">
        <li class="ndb:min-w-0">
            @include('newdebugbar::livewire.livewire.activity-item')
        </li>
    </template>
</ol>

<div x-show.important="filteredLivewireActivity.length === 0" class="ndb:p-3">
    <x-newdebugbar::empty-state label="No Livewire activity matches this view." />
</div>
