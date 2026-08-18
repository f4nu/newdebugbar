{{-- Renders the request overview and runtime details. --}}
@php($overview = app(\NewDebugBar\Presentation\OverviewPresenter::class)->present($profile, $summary['sections'] ?? []))
@php($activitySections = $overview['activity'])
@php($runtimeDetailGroups = $overview['runtime'])
@if ($activitySections !== [])
    <div data-ndb-overview-activity>
        <div class="ndb:mb-3">
            <h3 class="ndb:text-xs ndb:font-bold">Relevant activity</h3>
            <p class="ndb:mt-0.5 ndb:text-[11px] ndb:text-zinc-400">Sorted by what may need attention</p>
        </div>
        <div class="ndb:border-t ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
            @foreach ($activitySections as $link)
                <button
                    type="button"
                    data-ndb-overview-activity-section="{{ $link['key'] }}"
                    @click="navigateToSection(@js($link['key']))"
                    class="ndb:grid ndb:w-full ndb:grid-cols-[minmax(0,1fr)_auto] ndb:items-center ndb:gap-x-3 ndb:border-b ndb:border-zinc-200/90 ndb:py-3 ndb:text-left ndb:transition ndb:hover:bg-indigo-50/60 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500 ndb:sm:grid-cols-[9rem_minmax(0,1fr)_auto] ndb:dark:border-zinc-800 ndb:dark:hover:bg-indigo-950/30"
                >
                    <span class="ndb:col-start-1 ndb:row-start-1 ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-bold">
                        {{ $link['label'] }}
                    </span>
                    <span class="ndb:col-start-1 ndb:row-start-2 ndb:min-w-0 ndb:text-[11px] ndb:leading-4 ndb:text-zinc-500 ndb:sm:col-start-2 ndb:sm:row-start-1 ndb:dark:text-zinc-400">
                        {{ $link['description'] }}
                    </span>
                    @if ($link['attention'] ?? false)
                        <span
                            data-ndb-overview-activity-review
                            class="ndb:col-start-2 ndb:row-span-2 ndb:row-start-1 ndb:self-center ndb:text-[11px] ndb:font-bold ndb:text-amber-600 ndb:sm:col-start-3 ndb:sm:row-span-1 ndb:dark:text-amber-400"
                        >Review</span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
@endif
<details
    data-ndb-overview-runtime
    class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/25"
>
    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-4 ndb:py-3 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500">
        <span class="ndb:min-w-0 ndb:flex-1">
            <span class="ndb:block ndb:text-xs ndb:font-bold">Runtime details</span>
            <span class="ndb:mt-0.5 ndb:block ndb:text-[11px] ndb:text-zinc-400">
                Runtime, drivers, framework cache, and ecosystem
            </span>
        </span>
        <x-newdebugbar::icon
            name="chevron-down"
            class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
        />
    </summary>
    <div
        x-data="{ runtimeDetail: 'runtime' }"
        class="ndb:border-t ndb:border-zinc-200/90 ndb:sm:grid ndb:sm:grid-cols-[11rem_minmax(0,1fr)] ndb:dark:border-zinc-800"
    >
        <div
            data-ndb-runtime-detail-select-wrapper
            class="ndb:border-b ndb:border-zinc-200/90 ndb:bg-zinc-50/70 ndb:p-3 ndb:sm:hidden ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/50"
        >
            <label
                for="ndb-runtime-detail-select"
                class="ndb:mb-1.5 ndb:block ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400"
            >
                Show details for
            </label>
            <div class="ndb:relative">
                <select
                    id="ndb-runtime-detail-select"
                    data-ndb-runtime-detail-select
                    x-model="runtimeDetail"
                    class="ndb:h-10 ndb:w-full ndb:appearance-none ndb:rounded-lg ndb:border ndb:border-zinc-300 ndb:bg-white ndb:px-3 ndb:pr-9 ndb:text-xs ndb:font-bold ndb:text-zinc-950 ndb:shadow-xs ndb:outline-none ndb:transition ndb:focus:border-indigo-500 ndb:focus:ring-2 ndb:focus:ring-indigo-500/20 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-950 ndb:dark:text-white ndb:dark:focus:border-indigo-400 ndb:dark:focus:ring-indigo-400/20"
                >
                    @foreach ($runtimeDetailGroups as $runtimeDetailKey => $runtimeDetailGroup)
                        <option value="{{ $runtimeDetailKey }}">{{ $runtimeDetailGroup['label'] }}</option>
                    @endforeach
                </select>
                <x-newdebugbar::icon
                    name="chevron-down"
                    class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-3 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
                />
            </div>
        </div>

        <nav
            data-ndb-runtime-detail-navigation
            aria-label="Runtime detail category"
            class="ndb:hidden ndb:border-r ndb:border-zinc-200/90 ndb:bg-zinc-50/70 ndb:p-2 ndb:sm:block ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/50"
        >
            @foreach ($runtimeDetailGroups as $runtimeDetailKey => $runtimeDetailGroup)
                <button
                    type="button"
                    data-ndb-runtime-detail="{{ $runtimeDetailKey }}"
                    @click="runtimeDetail = @js($runtimeDetailKey)"
                    :aria-pressed="runtimeDetail === @js($runtimeDetailKey)"
                    :class="runtimeDetail === @js($runtimeDetailKey) ? 'ndb:bg-indigo-50 ndb:text-indigo-700 ndb:dark:bg-indigo-950/70 ndb:dark:text-indigo-300' : 'ndb:text-zinc-600 ndb:hover:bg-white ndb:hover:text-zinc-950 ndb:dark:text-zinc-400 ndb:dark:hover:bg-zinc-800 ndb:dark:hover:text-white'"
                    class="ndb:flex ndb:w-full ndb:min-w-0 ndb:items-center ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-left ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-1 ndb:focus-visible:outline-indigo-500"
                >
                    <span class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-bold">
                        {{ $runtimeDetailGroup['label'] }}
                    </span>
                </button>
            @endforeach
        </nav>

        <div class="ndb:min-w-0 ndb:p-4">
            @foreach ($runtimeDetailGroups as $runtimeDetailKey => $runtimeDetailGroup)
                <div
                    data-ndb-runtime-detail-panel="{{ $runtimeDetailKey }}"
                    x-show.important="runtimeDetail === @js($runtimeDetailKey)"
                >
                    <div class="ndb:flex ndb:items-center ndb:justify-between ndb:gap-3">
                        <h3 class="ndb:text-xs ndb:font-bold">{{ $runtimeDetailGroup['label'] }}</h3>
                        <button
                            type="button"
                            @click="copyText(@js(json_encode($runtimeDetailGroup['copy'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)))"
                            class="ndb:shrink-0 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                        >
                            Copy all
                        </button>
                    </div>

                    <div class="ndb:mt-3 ndb:overflow-x-auto">
                        @if ($runtimeDetailGroup['items'] !== [])
                            <table class="ndb:w-full ndb:table-fixed ndb:border-collapse ndb:text-left">
                                <thead>
                                    <tr class="ndb:border-b ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
                                        <th
                                            scope="col"
                                            class="ndb:w-2/5 ndb:pb-2 ndb:pr-4 ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                        >
                                            Name
                                        </th>
                                        <th
                                            scope="col"
                                            class="ndb:pb-2 ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                        >
                                            Value
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($runtimeDetailGroup['items'] as $runtimeDetailItem)
                                        <tr class="ndb:border-b ndb:border-zinc-200/70 ndb:last:border-b-0 ndb:dark:border-zinc-800/80">
                                            <th
                                                scope="row"
                                                class="ndb:py-2 ndb:pr-4 ndb:align-top ndb:font-mono ndb:text-[11px] ndb:font-medium ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                            >
                                                {{ $runtimeDetailItem['name'] }}
                                            </th>
                                            <td class="ndb:break-words ndb:py-2 ndb:align-top ndb:font-mono ndb:text-[11px] ndb:text-zinc-800 ndb:dark:text-zinc-200">
                                                {{ is_scalar($runtimeDetailItem['value']) || $runtimeDetailItem['value'] === null ? ($runtimeDetailItem['value'] ?? '—') : json_encode($runtimeDetailItem['value'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="ndb:rounded-lg ndb:bg-zinc-50 ndb:px-3 ndb:py-4 ndb:text-xs ndb:text-zinc-500 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-400">
                                No {{ strtolower($runtimeDetailGroup['label']) }} details were detected.
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</details>
