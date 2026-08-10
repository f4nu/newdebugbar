@php($livewire = $livewireSection['payload']['presentation'] ?? [])
@php($componentIds = array_values(array_filter(array_column($livewire['components'] ?? [], 'id'), 'is_string')))
@php($eventIds = array_values(array_filter(array_column($livewire['events'] ?? [], 'id'), 'is_string')))

<div
    data-ndb-livewire
    x-data="newDebugBarLivewireSection({ componentIds: @js($componentIds), eventIds: @js($eventIds) })"
    class="ndb:space-y-5"
>
    <div class="ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:items-center ndb:justify-between ndb:gap-2">
        <div
            role="tablist"
            aria-label="Livewire diagnostics"
            data-ndb-livewire-tabs
            class="ndb-scrollbar ndb:flex ndb:min-w-0 ndb:gap-1 ndb:overflow-x-auto"
        >
            @foreach ($livewire['tabs'] ?? [] as $tab)
                <x-newdebugbar::filter-tab
                    id="newdebugbar-livewire-tab-{{ $tab['key'] }}"
                    role="tab"
                    data-ndb-livewire-tab="{{ $tab['key'] }}"
                    @click="selectLivewireTab({{ \Illuminate\Support\Js::from($tab['key']) }})"
                    @keydown="handleLivewireTabKey($event)"
                    ::aria-selected="livewireTab === {{ \Illuminate\Support\Js::from($tab['key']) }}"
                    ::tabindex="livewireTab === {{ \Illuminate\Support\Js::from($tab['key']) }} ? 0 : -1"
                    aria-controls="newdebugbar-livewire-panel-{{ $tab['key'] }}"
                >
                    {{ $tab['label'] }}
                </x-newdebugbar::filter-tab>
            @endforeach
        </div>

        <div class="ndb:flex ndb:shrink-0 ndb:items-center">
            @foreach ($livewire['components'] ?? [] as $component)
                <button
                    type="button"
                    data-ndb-copy-livewire-component="{{ $component['id'] }}"
                    x-cloak
                    x-show.important="livewireTab === 'components' && selectedComponentId === @js($component['id'])"
                    @click="copyText(@js($component['copy_details']))"
                    class="ndb:rounded-lg ndb:px-2.5 ndb:py-2 ndb:text-xs ndb:font-semibold ndb:text-indigo-600 ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/50"
                >
                    Copy details
                </button>
            @endforeach
            @foreach ($livewire['events'] ?? [] as $event)
                <button
                    type="button"
                    data-ndb-copy-livewire-event="{{ $event['id'] }}"
                    x-cloak
                    x-show.important="livewireTab === 'events' && selectedEventId === @js($event['id'])"
                    @click="copyText(@js($event['copy_details']))"
                    class="ndb:rounded-lg ndb:px-2.5 ndb:py-2 ndb:text-xs ndb:font-semibold ndb:text-indigo-600 ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/50"
                >
                    Copy event
                </button>
            @endforeach
        </div>
    </div>

    @foreach (['overview', 'components', 'events'] as $livewireTab)
        <div
            id="newdebugbar-livewire-panel-{{ $livewireTab }}"
            role="tabpanel"
            data-ndb-livewire-panel="{{ $livewireTab }}"
            aria-labelledby="newdebugbar-livewire-tab-{{ $livewireTab }}"
            x-cloak
            x-show.important="livewireTab === @js($livewireTab)"
            class="ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
        >
            @include('newdebugbar::livewire.sections.livewire-'.$livewireTab, ['livewire' => $livewire])
        </div>
    @endforeach
</div>
