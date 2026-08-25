@component('newdebugbar::studio.component', ['component' => 'icon', 'components' => $components])
    <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-5 ndb:text-zinc-600 ndb:dark:text-zinc-300">
        @foreach (['search', 'mail', 'activity', 'database', 'code', 'copy', 'external-link'] as $icon)
            <span class="ndb:inline-flex ndb:items-center ndb:gap-2 ndb:text-xs ndb:font-semibold">
                <x-newdebugbar::icon :name="$icon" size="4" />
                {{ Illuminate\Support\Str::headline($icon) }}
            </span>
        @endforeach
    </div>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'icon-button', 'components' => $components])
    <div class="ndb:flex ndb:items-center ndb:gap-2">
        <x-newdebugbar::icon-button
            name="copy"
            aria-label="Copy value"
            title="Copy value"
            class="ndb:size-9 ndb:rounded-lg"
        />
        <x-newdebugbar::icon-button
            name="external-link"
            color-only
            aria-label="Open source"
            title="Open source"
            class="ndb:size-9 ndb:rounded-lg"
        />
        <x-newdebugbar::icon-button
            name="close"
            disabled
            aria-label="Close"
            title="Close"
            class="ndb:size-9 ndb:rounded-lg"
        />
    </div>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'inspector-action', 'components' => $components])
    <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-2">
        <x-newdebugbar::inspector-action icon="copy">Copy URL</x-newdebugbar::inspector-action>
        <x-newdebugbar::inspector-action icon="external-link">Open source</x-newdebugbar::inspector-action>
    </div>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'inspector-operation-badge', 'components' => $components])
    <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-3">
        <x-newdebugbar::inspector-operation-badge>GET</x-newdebugbar::inspector-operation-badge>
        <x-newdebugbar::inspector-operation-badge outlined>POST</x-newdebugbar::inspector-operation-badge>
        <x-newdebugbar::inspector-operation-badge wide>FOREVER</x-newdebugbar::inspector-operation-badge>
        <x-newdebugbar::inspector-operation-badge wide outlined>FORGET</x-newdebugbar::inspector-operation-badge>
    </div>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'search-field', 'components' => $components])
    <div x-data="{ newdebugbarStudioSearch: '' }" class="ndb:max-w-sm">
        <x-newdebugbar::search-field
            label="Search requests"
            placeholder="Search requests"
            x-model="newdebugbarStudioSearch"
            data-ndb-studio-search
        />
    </div>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'select-field', 'components' => $components])
    <div class="ndb:max-w-52">
        <x-newdebugbar::select-field label="Filter operation" data-ndb-studio-select>
            <option>All operations</option>
            <option>Reads</option>
            <option>Writes</option>
            <option>Failures</option>
        </x-newdebugbar::select-field>
    </div>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'filter-tab', 'components' => $components])
    <div x-data="{ newdebugbarStudioFilter: 'all' }" class="ndb:max-w-sm">
        <x-newdebugbar::filter-tabs label="Request result" variant="segmented">
            @foreach (['all' => 6, 'failed' => 2, 'slow' => 1] as $filter => $count)
                <x-newdebugbar::filter-tab
                    variant="segmented"
                    @click="newdebugbarStudioFilter = '{{ $filter }}'"
                    ::aria-pressed="newdebugbarStudioFilter === '{{ $filter }}'"
                >
                    {{ ucfirst($filter) }}
                    <span class="ndb:tabular-nums ndb:text-[11px]">{{ $count }}</span>
                </x-newdebugbar::filter-tab>
            @endforeach
        </x-newdebugbar::filter-tabs>
    </div>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'filter-tabs', 'components' => $components])
    <div x-data="{ newdebugbarStudioView: 'response' }" class="ndb:max-w-md">
        <x-newdebugbar::filter-tabs label="Request detail" variant="segmented">
            @foreach (['response', 'request', 'source'] as $view)
                <x-newdebugbar::filter-tab
                    variant="segmented"
                    @click="newdebugbarStudioView = '{{ $view }}'"
                    ::aria-pressed="newdebugbarStudioView === '{{ $view }}'"
                >{{ ucfirst($view) }}</x-newdebugbar::filter-tab>
            @endforeach
        </x-newdebugbar::filter-tabs>
    </div>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'empty-state', 'components' => $components])
    <div class="ndb:grid ndb:gap-4 ndb:sm:grid-cols-2">
        <x-newdebugbar::empty-state label="No requests match these filters." />
        <x-newdebugbar::empty-state success label="No failed requests were captured." />
    </div>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'popover-surface', 'components' => $components])
    <div class="ndb:max-w-sm">
        <x-newdebugbar::popover-surface
            :anchored="true"
            width-class="ndb:w-full"
            arrow-class="ndb:hidden"
            surface-class="ndb:p-2"
        >
            <button
                type="button"
                class="ndb:flex ndb:w-full ndb:items-center ndb:justify-between ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-left ndb:text-xs ndb:font-semibold ndb:hover:bg-zinc-100 ndb:dark:hover:bg-white/10"
            >
                Copy query
                <kbd class="ndb:text-[11px] ndb:font-medium ndb:text-zinc-400">C</kbd>
            </button>
            <button
                type="button"
                class="ndb:flex ndb:w-full ndb:items-center ndb:justify-between ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-left ndb:text-xs ndb:font-semibold ndb:hover:bg-zinc-100 ndb:dark:hover:bg-white/10"
            >
                Open source
                <x-newdebugbar::icon name="external-link" size="3.5" class="ndb:text-zinc-400" />
            </button>
        </x-newdebugbar::popover-surface>
    </div>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'theme-menu-item', 'components' => $components])
    <div
        role="menu"
        class="ndb:max-w-64 ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white ndb:p-1.5 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900"
    >
        <x-newdebugbar::theme-menu-item data-ndb-studio-theme-menu-item />
    </div>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'theme-toggle', 'components' => $components])
    <x-newdebugbar::theme-toggle data-ndb-studio-theme-toggle />
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'section-heading', 'components' => $components])
    <div class="ndb:-mx-4 ndb:-my-4">
        <x-newdebugbar::section-heading>
            <x-slot:heading>HTTP Client</x-slot:heading>
            <x-slot:description>
                Review outbound requests, responses, timing, and their application source.
            </x-slot:description>
        </x-newdebugbar::section-heading>
    </div>
@endcomponent

@component('newdebugbar::studio.component', ['component' => 'code-block', 'components' => $components])
    <x-newdebugbar::code-block language="php" class="ndb:max-w-full"
        >return Http::timeout(5)->get('/weather');</x-newdebugbar::code-block>
@endcomponent
