@props(['groups'])

<details data-ndb-overview-runtime class="ndb:group ndb:border-t ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-4 ndb:py-3 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500">
        <span class="ndb:min-w-0 ndb:flex-1">
            <span class="ndb:block ndb:text-xs ndb:font-bold">Inspect the application runtime</span>
            <span class="ndb:mt-0.5 ndb:block ndb:text-[11px] ndb:text-zinc-400">
                Check versions, drivers, framework caches, and installed Laravel tools.
            </span>
        </span>
        <span class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:group-open:hidden ndb:dark:text-zinc-400">Show</span>
        <span class="ndb:hidden ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:group-open:inline ndb:dark:text-zinc-400">Hide</span>
    </summary>

    <div x-data="{ runtimeDetail: 'runtime' }" class="ndb:border-t ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
        <div data-ndb-runtime-detail-select-wrapper class="ndb:p-3 ndb:sm:hidden">
            <x-newdebugbar::select-field
                label="Runtime detail category"
                x-model="runtimeDetail"
                data-ndb-runtime-detail-select
            >
                @foreach ($groups as $key => $group)
                    <option value="{{ $key }}">{{ $group['label'] }}</option>
                @endforeach
            </x-newdebugbar::select-field>
        </div>

        <div
            data-ndb-runtime-detail-navigation
            class="ndb:hidden ndb:justify-center ndb:border-b ndb:border-zinc-200/90 ndb:p-3 ndb:sm:flex ndb:dark:border-zinc-800"
        >
            <x-newdebugbar::filter-tabs label="Runtime detail category" variant="segmented" class="ndb:w-fit">
                @foreach ($groups as $key => $group)
                    <x-newdebugbar::filter-tab
                        variant="segmented"
                        data-ndb-runtime-detail="{{ $key }}"
                        @click="runtimeDetail = {{ \Illuminate\Support\Js::from($key) }}"
                        ::aria-pressed="runtimeDetail === {{ \Illuminate\Support\Js::from($key) }}"
                    >
                        {{ $group['label'] }}
                    </x-newdebugbar::filter-tab>
                @endforeach
            </x-newdebugbar::filter-tabs>
        </div>

        <div class="ndb:min-w-0 ndb:p-4">
            @foreach ($groups as $key => $group)
                <template x-if="runtimeDetail === @js($key)">
                    <section data-ndb-runtime-detail-panel="{{ $key }}">
                        <div class="ndb:flex ndb:items-center ndb:justify-between ndb:gap-3">
                            <h3 class="ndb:text-xs ndb:font-bold">{{ $group['label'] }}</h3>
                            <x-newdebugbar::inspector-action
                                icon="copy"
                                @click="copyText({{ \Illuminate\Support\Js::from(json_encode($group['copy'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) }})"
                            >
                                Copy all
                            </x-newdebugbar::inspector-action>
                        </div>

                        @if ($group['items'] !== [])
                            <div class="ndb:mt-3 ndb:overflow-x-auto">
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
                                        @foreach ($group['items'] as $item)
                                            @php
                                                $value = is_scalar($item['value']) || $item['value'] === null
                                                    ? ($item['value'] ?? '—')
                                                    : json_encode($item['value'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                                                $numeric = is_int($item['value']) || is_float($item['value']);
                                            @endphp
                                            <tr class="ndb:border-b ndb:border-zinc-200/70 ndb:last:border-b-0 ndb:dark:border-zinc-800/80">
                                                <th
                                                    scope="row"
                                                    class="ndb:py-2 ndb:pr-4 ndb:align-top ndb:text-[11px] ndb:font-medium ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                                >
                                                    {{ $item['name'] }}
                                                </th>
                                                <td @class([
                                                    'ndb:break-words ndb:py-2 ndb:align-top ndb:text-[11px] ndb:text-zinc-800 ndb:dark:text-zinc-200',
                                                    'ndb:font-mono ndb:tabular-nums' => $numeric,
                                                ])>
                                                    {{ $value }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="ndb:mt-3 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                No {{ strtolower($group['label']) }} details were detected.
                            </p>
                        @endif
                    </section>
                </template>
            @endforeach
        </div>
    </div>
</details>
