{{-- Renders Blade view activity and view data. --}}
@php($viewGroups = $section['payload']['groups'] ?? [])
<div data-ndb-views class="ndb:space-y-5">
    <dl class="ndb:flex ndb:flex-wrap ndb:gap-x-10 ndb:gap-y-3 ndb:border-y ndb:border-zinc-200/90 ndb:py-3 ndb:dark:border-zinc-800">
        <div>
            <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                Unique views
            </dt>
            <dd data-ndb-view-summary-value="unique" class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">
                {{ $section['summary']['unique_views'] }}
            </dd>
        </div>
        <div>
            <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                Total renders
            </dt>
            <dd data-ndb-view-summary-value="renders" class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">
                {{ $section['summary']['count'] }}
            </dd>
        </div>
    </dl>

    @if ($viewGroups !== [])
        <div class="ndb:space-y-2">
            <div class="ndb:grid ndb:grid-cols-[minmax(0,1fr)_5rem_1.5rem] ndb:items-end ndb:gap-x-3 ndb:border-b ndb:border-zinc-200/90 ndb:pb-2 ndb:dark:border-zinc-800">
                <span
                    role="columnheader"
                    :aria-sort="viewSort === 'name'
                        ? viewSortDirection === 'asc'
                            ? 'ascending'
                            : 'descending'
                        : 'none'"
                >
                    <button
                        type="button"
                        data-ndb-view-sort="name"
                        @click="toggleViewSort('name')"
                        :class="viewSort === 'name'
                            ? 'ndb:text-zinc-950 ndb:dark:text-white'
                            : 'ndb:text-zinc-400 ndb:hover:text-zinc-700 ndb:dark:hover:text-zinc-200'"
                        class="ndb:inline-flex ndb:items-center ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:transition-colors ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500"
                    >
                        View
                    </button>
                </span>
                <span
                    role="columnheader"
                    :aria-sort="viewSort === 'count'
                        ? viewSortDirection === 'asc'
                            ? 'ascending'
                            : 'descending'
                        : 'none'"
                    class="ndb:flex ndb:justify-end"
                >
                    <button
                        type="button"
                        data-ndb-view-sort="count"
                        @click="toggleViewSort('count')"
                        :class="viewSort === 'count'
                            ? 'ndb:text-zinc-950 ndb:dark:text-white'
                            : 'ndb:text-zinc-400 ndb:hover:text-zinc-700 ndb:dark:hover:text-zinc-200'"
                        class="ndb:inline-flex ndb:items-center ndb:justify-end ndb:text-right ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:transition-colors ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500"
                    >
                        Renders
                    </button>
                </span>
                <span class="ndb:sr-only">Details</span>
            </div>

            <div
                x-ref="viewGroups"
                x-init="$nextTick(() => applyViewSort())"
                class="ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800"
            >
                @foreach ($viewGroups as $index => $group)
                    <details
                        data-ndb-view-group
                        data-order="{{ $index }}"
                        data-count="{{ $group['count'] }}"
                        data-name="{{ mb_strtolower($group['name']) }}"
                        wire:key="view-group-{{ $index }}"
                        class="ndb:group"
                    >
                        <summary class="ndb:grid ndb:cursor-pointer ndb:list-none ndb:grid-cols-[minmax(0,1fr)_5rem_1.5rem] ndb:items-center ndb:gap-x-3 ndb:py-3 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500">
                            <span class="ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-bold">{{ $group['name'] }}</span>
                            <span
                                data-ndb-view-group-count
                                class="ndb:text-right ndb:text-xs ndb:font-bold ndb:tabular-nums"
                            >{{ $group['count'] }}</span>
                            <x-newdebugbar::icon
                                name="chevron-down"
                                class="ndb:size-3.5 ndb:justify-self-end ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
                            />
                        </summary>

                        <div class="ndb:divide-y ndb:divide-zinc-200/80 ndb:border-t ndb:border-zinc-200/80 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                            @foreach ($group['items'] as $view)
                                @php($viewData = is_array($view['data'] ?? null) ? $view['data'] : [])
                                <article data-ndb-view-render class="ndb:py-4">
                                    <div
                                        x-data="{ viewDataOpen: false }"
                                        x-id="['view-data-trigger', 'view-data-popover']"
                                        @keydown.escape.stop="
                                            if (viewDataOpen) {
                                                viewDataOpen = false;
                                                $nextTick(() => $refs.viewDataButton.focus());
                                            }
                                        "
                                        class="ndb:relative"
                                    >
                                        <div
                                            data-ndb-view-render-row
                                            class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3"
                                        >
                                            <div
                                                data-ndb-view-render-context
                                                class="ndb:flex ndb:min-w-0 ndb:flex-1 ndb:items-baseline ndb:gap-3"
                                            >
                                                <span
                                                    data-ndb-view-render-order
                                                    class="ndb:shrink-0 ndb:text-[11px] ndb:font-bold ndb:text-zinc-400"
                                                >Render #{{ $view['render_order'] }}</span>
                                                <code
                                                    data-ndb-view-source
                                                    class="ndb:min-w-0 ndb:flex-1 ndb:break-all ndb:text-[11px]"
                                                >
                                                    {{ $view['source']['file'] ?? 'Template path unavailable' }}@if (isset($view['source']['line'])):{{ $view['source']['line'] }}@endif
                                                </code>
                                            </div>
                                            <button
                                                x-ref="viewDataButton"
                                                type="button"
                                                data-ndb-view-data-trigger
                                                :id="$id('view-data-trigger')"
                                                :aria-controls="$id('view-data-popover')"
                                                :aria-expanded="viewDataOpen"
                                                @click="viewDataOpen = ! viewDataOpen"
                                                class="ndb:flex ndb:min-h-8 ndb:shrink-0 ndb:items-center ndb:rounded-lg ndb:px-2.5 ndb:py-1.5 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:transition ndb:hover:bg-indigo-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950/60"
                                            >
                                                <span>View data</span>
                                            </button>
                                        </div>

                                        <x-newdebugbar::popover-surface
                                            x-cloak
                                            x-show.important="viewDataOpen"
                                            x-transition:enter="ndb:transition ndb:duration-150 ndb:ease-out ndb:motion-reduce:transition-none"
                                            x-transition:enter-start="ndb:translate-y-1 ndb:scale-95 ndb:opacity-0"
                                            x-transition:enter-end="ndb:translate-y-0 ndb:scale-100 ndb:opacity-100"
                                            x-transition:leave="ndb:transition ndb:duration-100 ndb:ease-in ndb:motion-reduce:transition-none"
                                            x-transition:leave-start="ndb:translate-y-0 ndb:scale-100 ndb:opacity-100"
                                            x-transition:leave-end="ndb:translate-y-1 ndb:scale-95 ndb:opacity-0"
                                            @click.outside="viewDataOpen = false"
                                            data-ndb-view-data-popover
                                            ::id="$id('view-data-popover')"
                                            ::aria-labelledby="$id(
                                                'view-data-trigger',
                                            )"
                                            role="region"
                                            direction="below"
                                            width-class="ndb:w-[min(36rem,calc(100vw-3rem))]"
                                            arrow-class="ndb:right-[24px]"
                                        >
                                            @if ($viewData !== [])
                                                <pre
                                                    tabindex="0"
                                                    data-ndb-view-data
                                                    class="ndb-code ndb-scrollbar ndb:max-h-80 ndb:overflow-auto ndb:rounded-none ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500"
                                                ><code data-ndb-language="json">{{ json_encode($viewData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                            @else
                                                <p class="ndb:px-4 ndb:py-3 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                                    No data was passed directly to this view.
                                                </p>
                                            @endif
                                        </x-newdebugbar::popover-surface>
                                    </div>

                                    @if (($view['composers'] ?? []) !== [])
                                        <div class="ndb:mt-4">
                                            <h3 class="ndb:text-[11px] ndb:font-bold">View composers</h3>
                                            <ul class="ndb:mt-2 ndb:list-none ndb:space-y-2">
                                                @foreach ($view['composers'] as $composer)
                                                    <li class="ndb:min-w-0">
                                                        <code class="ndb:block ndb:break-all ndb:text-[11px] ndb:font-semibold">{{ $composer['name'] }}</code>
                                                        @if (is_string($composer['source']['file'] ?? null))
                                                            <code class="ndb:mt-0.5 ndb:block ndb:break-all ndb:text-[11px] ndb:text-zinc-400">{{ $composer['source']['file'] }}:{{ $composer['source']['line'] ?? 1 }}</code>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    @else
        <x-newdebugbar::empty-state label="No views were captured." />
    @endif
</div>
