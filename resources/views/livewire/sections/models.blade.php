{{-- Renders model load and write activity. --}}
@php($modelGroups = $section['payload']['model_groups'] ?? [])
<div data-ndb-models x-data="{ modelsAllExpanded: false }" class="ndb:space-y-5">
    @if ($modelGroups !== [])
        <div>
            <div class="ndb:grid ndb:grid-cols-[minmax(0,1fr)_auto] ndb:items-end ndb:gap-x-3 ndb:border-b ndb:border-zinc-200/90 ndb:pb-2 ndb:sm:grid-cols-[minmax(0,1fr)_5rem_5rem_5rem_4rem] ndb:dark:border-zinc-800">
                <span class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Model</span>
                <span
                    data-ndb-model-heading="loads"
                    class="ndb:hidden ndb:text-right ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:sm:block"
                >Loads</span>
                <span
                    data-ndb-model-heading="records"
                    class="ndb:hidden ndb:text-right ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:sm:block"
                >Records</span>
                <span
                    data-ndb-model-heading="repeated"
                    class="ndb:hidden ndb:text-right ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:sm:block"
                >Repeated</span>
                <button
                    type="button"
                    data-ndb-model-expand-all
                    @click="
                        modelsAllExpanded = ! modelsAllExpanded;
                        $root
                            .querySelectorAll('[data-ndb-model-group]')
                            .forEach((group) => (group.open = modelsAllExpanded));
                    "
                    class="ndb:justify-self-end ndb:whitespace-nowrap ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                    x-text="modelsAllExpanded ? 'Collapse all' : 'Expand all'"
                >
                    Expand all
                </button>
            </div>

            <div class="ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800">
                @foreach ($modelGroups as $index => $group)
                    <details
                        data-ndb-model-group
                        data-loads="{{ $group['load_count'] }}"
                        data-records="{{ $group['record_count'] }}"
                        data-repeated="{{ $group['repeated_load_count'] }}"
                        data-changes="{{ $group['change_count'] }}"
                        wire:key="model-group-{{ $index }}"
                        @toggle="
                            modelsAllExpanded = Array.from($root.querySelectorAll('[data-ndb-model-group]')).every(
                                (modelGroup) => modelGroup.open,
                            )
                        "
                        class="ndb:group"
                    >
                        <summary class="ndb:grid ndb:cursor-pointer ndb:list-none ndb:grid-cols-[minmax(0,1fr)_1.5rem] ndb:items-center ndb:gap-x-3 ndb:py-3 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500 ndb:sm:grid-cols-[minmax(0,1fr)_5rem_5rem_5rem_4rem]">
                            <span class="ndb:min-w-0">
                                <span
                                    data-ndb-model-name
                                    class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold"
                                >{{ class_basename($group['model']) }}</span>
                                <span
                                    data-ndb-model-mobile-summary
                                    class="ndb:mt-1 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400 ndb:sm:hidden"
                                >
                                    <span>{{ $group['load_count'] }} loads</span>
                                    <span>{{ $group['record_count'] }} records</span>
                                    <span @class(['ndb:text-amber-600 ndb:dark:text-amber-400' => $group['repeated_load_count'] > 0])>{{ $group['repeated_load_count'] }} repeated</span>
                                    @if ($group['change_count'] > 0)
                                        <span class="ndb:text-amber-600 ndb:dark:text-amber-400">{{ $group['change_count'] }} changed</span>
                                    @endif
                                </span>
                            </span>
                            <span
                                data-ndb-model-load-count
                                class="ndb:hidden ndb:text-right ndb:text-xs ndb:font-bold ndb:tabular-nums ndb:sm:block"
                            >{{ $group['load_count'] }}</span>
                            <span
                                data-ndb-model-record-count
                                class="ndb:hidden ndb:text-right ndb:text-xs ndb:font-bold ndb:tabular-nums ndb:sm:block"
                            >{{ $group['record_count'] }}</span>
                            <span
                                data-ndb-model-repeat-count
                                class="ndb:hidden ndb:text-right ndb:text-xs ndb:font-bold ndb:tabular-nums {{ $group['repeated_load_count'] > 0 ? 'ndb:text-amber-600 ndb:dark:text-amber-400' : 'ndb:text-zinc-400' }} ndb:sm:block"
                            >{{ $group['repeated_load_count'] }}</span>
                            <x-newdebugbar::icon
                                name="chevron-down"
                                class="ndb:size-3.5 ndb:justify-self-end ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
                            />
                        </summary>

                        <div class="ndb:border-t ndb:border-zinc-200/80 ndb:pb-4 ndb:pt-3 ndb:dark:border-zinc-800">
                            <code class="ndb:block ndb:break-all ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $group['model'] }}</code>
                            <dl class="ndb:mt-3 ndb:flex ndb:flex-wrap ndb:gap-x-8 ndb:gap-y-2">
                                <div>
                                    <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                        Connection
                                    </dt>
                                    <dd class="ndb:mt-0.5 ndb:font-mono ndb:text-[11px] ndb:font-semibold">
                                        {{ $group['connection'] ?? 'default' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                        Table
                                    </dt>
                                    <dd class="ndb:mt-0.5 ndb:font-mono ndb:text-[11px] ndb:font-semibold">
                                        {{ $group['table'] ?? 'unknown' }}
                                    </dd>
                                </div>
                            </dl>

                            @if ($group['change_events'] !== [])
                                <div
                                    data-ndb-model-changes
                                    class="ndb:mt-4 ndb:rounded-lg ndb:bg-amber-50/70 ndb:px-3 ndb:py-2.5 ndb:dark:bg-amber-950/25"
                                >
                                    <p class="ndb:text-[11px] ndb:font-bold ndb:text-amber-900 ndb:dark:text-amber-200">
                                        Model changes
                                    </p>
                                    <div class="ndb:mt-1.5 ndb:flex ndb:flex-wrap ndb:gap-2">
                                        @foreach ($group['change_events'] as $event => $count)
                                            <span class="ndb:rounded-md ndb:border ndb:border-amber-200 ndb:bg-white/70 ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-semibold ndb:text-amber-800 ndb:dark:border-amber-900 ndb:dark:bg-amber-950/30 ndb:dark:text-amber-300">{{ $count }} {{ str($event)->headline()->lower() }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($group['records'] !== [])
                                <div class="ndb-scrollbar ndb:mt-4 ndb:overflow-x-auto">
                                    <table class="ndb:w-full ndb:min-w-[34rem] ndb:border-collapse ndb:text-left">
                                        <thead>
                                            <tr class="ndb:border-b ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
                                                <th
                                                    scope="col"
                                                    class="ndb:w-2/5 ndb:pb-2 ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                >
                                                    Record
                                                </th>
                                                <th
                                                    scope="col"
                                                    class="ndb:pb-2 ndb:text-right ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                >
                                                    Loads
                                                </th>
                                                <th
                                                    scope="col"
                                                    class="ndb:pb-2 ndb:text-right ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                >
                                                    First seen
                                                </th>
                                                <th
                                                    scope="col"
                                                    class="ndb:pb-2 ndb:text-right ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                                >
                                                    Last seen
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($group['records'] as $record)
                                                <tr
                                                    data-ndb-model-record
                                                    data-loads="{{ $record['loads'] }}"
                                                    class="ndb:border-b ndb:border-zinc-200/70 ndb:last:border-b-0 ndb:dark:border-zinc-800/80 {{ $record['loads'] > 1 ? 'ndb:bg-amber-50/55 ndb:dark:bg-amber-950/20' : '' }}"
                                                >
                                                    <th
                                                        scope="row"
                                                        class="ndb:py-2.5 ndb:font-mono ndb:text-[11px] ndb:font-semibold"
                                                    >
                                                        #{{ $record['key'] }}
                                                    </th>
                                                    <td class="ndb:py-2.5 ndb:text-right ndb:text-[11px] ndb:font-bold ndb:tabular-nums {{ $record['loads'] > 1 ? 'ndb:text-amber-700 ndb:dark:text-amber-300' : '' }}">
                                                        {{ $record['loads'] }}
                                                    </td>
                                                    <td
                                                        data-ndb-model-first-seen
                                                        class="ndb:py-2.5 ndb:text-right ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                                    >
                                                        {{ $record['first_seen_ms'] !== null ? rtrim(rtrim(number_format($record['first_seen_ms'], 1, '.', ''), '0'), '.').' ms' : '—' }}
                                                    </td>
                                                    <td
                                                        data-ndb-model-last-seen
                                                        class="ndb:py-2.5 ndb:text-right ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                                    >
                                                        {{ $record['last_seen_ms'] !== null ? rtrim(rtrim(number_format($record['last_seen_ms'], 1, '.', ''), '0'), '.').' ms' : '—' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @elseif ($group['load_count'] > 0)
                                <p class="ndb:mt-4 ndb:rounded-lg ndb:bg-zinc-50 ndb:px-3 ndb:py-2.5 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-400">
                                    Record identifiers were not available for these retrievals.
                                </p>
                            @endif

                            @if ($group['unidentified_load_count'] > 0 && $group['records'] !== [])
                                <p class="ndb:mt-3 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                    {{ $group['unidentified_load_count'] }} additional {{ $group['unidentified_load_count'] === 1 ? 'retrieval had' : 'retrievals had' }} no
                                    record identifier, so {{ $group['unidentified_load_count'] === 1 ? 'it is' : 'they are' }} not
                                    counted as repeated.
                                </p>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    @else
        <x-newdebugbar::empty-state label="No model loads or changes were captured." />
    @endif

    @if (($section['payload']['boot_items'] ?? []) !== [])
        <details
            data-ndb-model-boot
            class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"
        >
            <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-4 ndb:py-3 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500">
                <span class="ndb:min-w-0 ndb:flex-1">
                    <span class="ndb:block ndb:text-xs ndb:font-bold">Model boot lifecycle</span>
                    <span class="ndb:mt-0.5 ndb:block ndb:text-[11px] ndb:text-zinc-400">{{ $section['summary']['boot_event_count'] }} events across {{ $section['summary']['boot_model_classes'] }} {{ $section['summary']['boot_model_classes'] === 1 ? 'class' : 'classes' }}</span>
                </span>
                <x-newdebugbar::icon
                    name="chevron-down"
                    class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
                />
            </summary>
            <pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="json">{{ json_encode($section['payload']['boot_items'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
        </details>
    @endif
</div>
