@php($livewire = $livewireSection['payload']['presentation'] ?? [])
@php($headline = $livewire['headline'] ?? [])
@php($outcome = $livewire['outcome'] ?? [])

<div data-ndb-livewire x-data="newDebugBarLivewireSection()" class="ndb:space-y-5">
    <div data-ndb-livewire-headline class="ndb:grid ndb:gap-5 ndb:lg:grid-cols-[minmax(0,1.45fr)_minmax(15rem,0.75fr)]">
        <div class="ndb:min-w-0">
            <p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                {{ $headline['kind_label'] ?? 'Unknown trigger' }}
            </p>
            <h3 class="ndb:mt-1 ndb:text-xl ndb:font-bold ndb:tracking-tight ndb:text-zinc-950 ndb:dark:text-white">
                {{ $headline['title'] ?? 'Livewire exchange' }}
            </h3>
            <p class="ndb:mt-2 ndb:max-w-2xl ndb:text-xs ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300">
                {{ $headline['detail'] ?? 'The trigger is unknown.' }}
            </p>
            <p class="ndb:mt-2 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">
                {{ ($headline['confidence'] ?? 'unknown') === 'unknown' ? 'Title not derived' : ucfirst($headline['confidence']).' title' }}
            </p>
        </div>
        <div class="ndb:border-t ndb:border-zinc-200 ndb:pt-4 ndb:lg:border-t-0 ndb:lg:border-l ndb:lg:pt-0 ndb:lg:pl-5 ndb:dark:border-zinc-800">
            <p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Result</p>
            <p class="ndb:mt-1 ndb:text-sm ndb:font-bold">{{ $outcome['title'] ?? 'Result unknown' }}</p>
            <p class="ndb:mt-1 ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                {{ $outcome['detail'] ?? 'No result details were observed.' }}
            </p>
        </div>
    </div>

    <dl
        data-ndb-livewire-facts
        class="ndb:grid ndb:grid-cols-2 ndb:border-y ndb:border-zinc-200 ndb:sm:grid-cols-4 ndb:dark:border-zinc-800"
    >
        @foreach ($livewire['facts'] ?? [] as $fact)
            <div class="ndb:py-3 ndb:nth-[n+3]:border-t ndb:sm:nth-[n+3]:border-t-0 ndb:not-last:sm:border-r ndb:border-zinc-200 ndb:sm:px-4 ndb:first:sm:pl-0 ndb:dark:border-zinc-800">
                <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                    {{ $fact['label'] }}
                </dt>
                <dd class="ndb:mt-1 ndb:text-base ndb:font-bold ndb:tabular-nums">{{ $fact['value'] }}</dd>
            </div>
        @endforeach
    </dl>

    @foreach ($livewire['notices'] ?? [] as $notice)
        <div
            data-ndb-livewire-notice
            role="status"
            class="ndb:rounded-lg ndb:border ndb:px-3.5 ndb:py-3 {{ ($notice['tone'] ?? null) === 'attention' ? 'ndb:border-amber-200 ndb:bg-amber-50/60 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/25' : 'ndb:border-zinc-200 ndb:bg-zinc-50/70 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/40' }}"
        >
            <p class="ndb:text-xs ndb:font-bold">{{ $notice['title'] }}</p>
            <p class="ndb:mt-1 ndb:text-[10px] ndb:leading-4 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                {{ $notice['detail'] }}
            </p>
        </div>
    @endforeach

    @if (($livewire['findings'] ?? []) !== [])
        <section data-ndb-livewire-findings class="ndb:border-y ndb:border-zinc-200 ndb:dark:border-zinc-800">
            <div class="ndb:py-3">
                <p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                    Next check
                </p>
                @foreach ($livewire['findings'] as $finding)
                    <div
                        data-ndb-livewire-finding="{{ $finding['rule_id'] }}"
                        class="ndb:mt-2 ndb:grid ndb:gap-2 ndb:sm:grid-cols-[minmax(0,1fr)_minmax(14rem,0.65fr)]"
                    >
                        <div>
                            <h4 class="ndb:text-xs ndb:font-bold">{{ $finding['summary'] }}</h4>
                            @if ($finding['why'])
                                <p class="ndb:mt-1 ndb:text-[10px] ndb:leading-4 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                    {{ $finding['why'] }}
                                </p>
                            @endif
                        </div>
                        @if ($finding['next'])
                            <p class="ndb:text-[10px] ndb:leading-4 ndb:text-zinc-500 ndb:sm:border-l ndb:sm:border-zinc-200 ndb:sm:pl-3 ndb:dark:text-zinc-400 ndb:dark:sm:border-zinc-800">
                                {{ $finding['next'] }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div
        role="tablist"
        aria-label="Livewire diagnostics"
        data-ndb-livewire-tabs
        class="ndb-scrollbar ndb:flex ndb:gap-1 ndb:overflow-x-auto"
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
                <span>{{ $tab['label'] }}</span>
                @if ($tab['count'] !== null)
                    <span class="ndb:tabular-nums ndb:text-[10px] ndb:font-bold ndb:opacity-65">{{ $tab['count'] }}</span>
                @endif
            </x-newdebugbar::filter-tab>
        @endforeach
    </div>

    @foreach (['overview', 'components', 'timeline', 'events'] as $livewireTab)
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
