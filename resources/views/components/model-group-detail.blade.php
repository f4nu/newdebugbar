{{-- Organizes one model context into overview, record, and source views. --}}
@props(['group'])

@php
    $retrievalCount = (int) ($group['load_count'] ?? 0);
    $changeCount = (int) ($group['change_count'] ?? 0);
    $repeatCount = (int) ($group['repeated_load_count'] ?? 0);
    $recordCount = (int) ($group['record_count'] ?? 0);
    $unidentifiedCount = (int) ($group['unidentified_load_count'] ?? 0);
    $sourceCount = (int) ($group['source_count'] ?? 0);
    $connection = is_string($group['connection'] ?? null) && $group['connection'] !== '' ? $group['connection'] : '—';
    $table = is_string($group['table'] ?? null) && $group['table'] !== '' ? $group['table'] : '—';
    $formatTime = static fn (mixed $value): string => is_numeric($value)
        ? rtrim(rtrim(number_format((float) $value, 1, '.', ''), '0'), '.').' ms'
        : '—';
    $plural = static fn (string $word, int $count): string => \Illuminate\Support\Str::plural($word, $count);
    $formatEvent = static fn (string $event): string => match ($event) {
        'forceDeleted' => 'Force deleted',
        default => \Illuminate\Support\Str::headline($event),
    };
    $sourceTitle = static function (mixed $callsite): string {
        if (! is_array($callsite)) {
            return 'Source unavailable';
        }

        $exact = $callsite['file'].':'.$callsite['line'];

        if (($callsite['kind'] ?? null) === 'compiled_view' && is_string($callsite['template_file'] ?? null)) {
            return 'Blade '.$callsite['template_file'].', compiled '.$exact;
        }

        return $exact;
    };
    $sourceShortLabel = static function (mixed $callsite): string {
        if (! is_array($callsite)) {
            return '—';
        }

        if (($callsite['kind'] ?? null) === 'compiled_view' && is_string($callsite['template_file'] ?? null)) {
            return basename(str_replace('\\', '/', $callsite['template_file']));
        }

        return basename(str_replace('\\', '/', $callsite['file'])).':'.$callsite['line'];
    };
    $formatActivity = static function (int $retrievals, int $changes) use ($plural): string {
        $parts = [];

        if ($retrievals > 0) {
            $parts[] = number_format($retrievals).' '.$plural('retrieval', $retrievals);
        }

        if ($changes > 0) {
            $parts[] = number_format($changes).' '.$plural('write', $changes);
        }

        return $parts === [] ? 'No retained activity' : implode(', ', $parts);
    };
    $lifecycleFacts = [];

    foreach ($group['lifecycle_events'] ?? [] as $event => $count) {
        if ($event === 'retrieved') {
            continue;
        }

        $lifecycleFacts[] = $formatEvent((string) $event).' '.number_format((int) $count);
    }

    $hasActivityWindow = is_numeric($group['first_seen_ms'] ?? null) && is_numeric($group['last_seen_ms'] ?? null);
@endphp

<div
    data-ndb-model-detail
    class="ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
>
    <x-newdebugbar::inspector-detail-header
        data-ndb-model-header
        class="ndb:border-l-0 ndb:bg-transparent ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
    >
        <x-slot:title>
            <h3 class="ndb:min-w-0">
                <code
                    data-ndb-model-class
                    class="ndb:block ndb:min-w-0 ndb:break-all ndb:font-mono ndb:text-sm ndb:font-bold ndb:leading-5 ndb:text-zinc-950 ndb:dark:text-white"
                >{{ $group['model'] }}</code>
            </h3>
        </x-slot:title>
        <x-slot:aside></x-slot:aside>
        <x-slot:metadata>
            <div class="ndb:min-w-0">
                <dt class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">Connection</dt>
                <dd class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-300">
                    {{ $connection }}
                </dd>
            </div>
            <div class="ndb:min-w-0">
                <dt class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">Table</dt>
                <dd class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-300">{{ $table }}</dd>
            </div>
        </x-slot:metadata>
    </x-newdebugbar::inspector-detail-header>

    <x-newdebugbar::inspector-detail-tabs label="Model detail">
        @foreach (['overview' => 'Overview', 'records' => 'Records', 'source' => 'Source'] as $tab => $label)
            <x-newdebugbar::filter-tab
                variant="segmented"
                data-ndb-model-detail-tab="{{ $tab }}"
                @click="setModelDetailTab('{{ $tab }}')"
                ::aria-pressed="modelDetailTab === '{{ $tab }}'"
                class="ndb:h-auto"
            >
                {{ $label }}
            </x-newdebugbar::filter-tab>
        @endforeach
    </x-newdebugbar::inspector-detail-tabs>

    <div class="ndb:p-4">
        <div
            data-ndb-model-detail-panel="overview"
            x-show.important="modelDetailTab === 'overview'"
            class="ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
        >
            <x-newdebugbar::inspector-facts
                data-ndb-model-facts
                class="ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:pb-4 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
            >
                @foreach ([
                    ['Retrieved', $retrievalCount],
                    ['Logical writes', $changeCount],
                    ['Extra retrievals', $repeatCount],
                    ['Identified records', $recordCount],
                ] as [$label, $value])
                    <x-newdebugbar::inspector-fact :label="$label">
                        <x-slot:value @class([
                            'ndb:text-[11px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-700 ndb:dark:text-zinc-200',
                            'ndb:text-amber-700 ndb:dark:text-amber-300' => $label === 'Extra retrievals' && $value > 0,
                        ])>
                            {{ number_format($value) }}
                        </x-slot:value>
                    </x-newdebugbar::inspector-fact>
                @endforeach
            </x-newdebugbar::inspector-facts>

            <p class="ndb:mt-3 ndb:text-[11px] ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                These are Eloquent events, not database row or query counts. Completed lifecycle callbacks are counted
                once as a logical write.
            </p>

            <x-newdebugbar::inspector-definition-list class="ndb:mt-4">
                <x-newdebugbar::inspector-definition-row label="Activity window">
                    {{ $hasActivityWindow ? $formatTime($group['first_seen_ms']).' to '.$formatTime($group['last_seen_ms']).' after request start' : 'Timing unavailable' }}
                </x-newdebugbar::inspector-definition-row>
                @if ($lifecycleFacts !== [])
                    <x-newdebugbar::inspector-definition-row label="Write events">
                        {{ implode(', ', $lifecycleFacts) }}
                    </x-newdebugbar::inspector-definition-row>
                @endif
            </x-newdebugbar::inspector-definition-list>
        </div>

        <div
            data-ndb-model-detail-panel="records"
            x-show.important="modelDetailTab === 'records'"
            class="ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
        >
            @if ($retrievalCount > 0)
                <section
                    data-ndb-model-records
                    class="ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
                >
                    <div class="ndb:border-y ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
                        <div class="ndb:hidden ndb:grid-cols-[minmax(8rem,1fr)_5.5rem_6rem_6rem_minmax(8rem,1fr)] ndb:gap-3 ndb:border-b ndb:border-zinc-200/90 ndb:py-2 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400 ndb:dark:border-zinc-800 ndb:sm:grid">
                            <span>Identifier</span>
                            <span class="ndb:text-right">Retrieved</span>
                            <span class="ndb:text-right">First</span>
                            <span class="ndb:text-right">Last</span>
                            <span>Source</span>
                        </div>

                        <div class="ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800">
                            @foreach ($group['records'] ?? [] as $record)
                                @php
                                    $recordSource = $record['sources'][0]['callsite'] ?? null;
                                    $recordLoads = (int) ($record['loads'] ?? 0);
                                    $recordKey = $record['key'] ?? null;
                                @endphp
                                <article
                                    data-ndb-model-record
                                    data-ndb-model-record-retrievals="{{ $recordLoads }}"
                                    class="ndb:grid ndb:min-w-0 ndb:gap-2 ndb:border-l-0 ndb:bg-transparent ndb:px-0 ndb:py-2.5 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white ndb:sm:grid-cols-[minmax(8rem,1fr)_5.5rem_6rem_6rem_minmax(8rem,1fr)] ndb:sm:items-center ndb:sm:gap-3"
                                >
                                    <p class="ndb:min-w-0 ndb:break-all ndb:text-[11px] ndb:font-semibold">
                                        {{ $record['key_name'] ?? 'id' }}
                                        <span @class(['ndb:font-mono ndb:tabular-nums' => is_numeric($recordKey)])>{{ (string) $recordKey }}</span>
                                    </p>
                                    <span @class([
                                        'ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:sm:text-right',
                                        'ndb:text-amber-700 ndb:dark:text-amber-300' => $recordLoads > 1,
                                        'ndb:text-zinc-600 ndb:dark:text-zinc-300' => $recordLoads === 1,
                                    ])>
                                        <span class="ndb:text-zinc-400 ndb:sm:hidden">Retrieved </span
                                        >{{ number_format($recordLoads) }}
                                    </span>
                                    <span class="ndb:text-[11px] ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400 ndb:sm:text-right">
                                        <span class="ndb:sm:hidden">First </span
                                        >{{ $formatTime($record['first_seen_ms'] ?? null) }}
                                    </span>
                                    <span class="ndb:text-[11px] ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400 ndb:sm:text-right">
                                        <span class="ndb:sm:hidden">Last </span
                                        >{{ $formatTime($record['last_seen_ms'] ?? null) }}
                                    </span>
                                    <span
                                        class="ndb:min-w-0 ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                        title="{{ $sourceTitle($recordSource) }}"
                                    >
                                        <span class="ndb:sm:hidden">Source </span>{{ $sourceShortLabel($recordSource) }}
                                    </span>
                                </article>
                            @endforeach

                            @if ($unidentifiedCount > 0)
                                <article
                                    data-ndb-model-missing-identifiers
                                    class="ndb:grid ndb:min-w-0 ndb:gap-2 ndb:border-l-0 ndb:bg-transparent ndb:px-0 ndb:py-2.5 ndb:sm:grid-cols-[minmax(8rem,1fr)_5.5rem_6rem_6rem_minmax(8rem,1fr)] ndb:sm:items-center ndb:sm:gap-3"
                                >
                                    <p class="ndb:text-[11px] ndb:font-semibold">—</p>
                                    <span class="ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-600 ndb:dark:text-zinc-300 ndb:sm:text-right">
                                        <span class="ndb:text-zinc-400 ndb:sm:hidden">Retrieved </span
                                        >{{ number_format($unidentifiedCount) }}
                                    </span>
                                    <span class="ndb:text-[11px] ndb:text-zinc-400 ndb:sm:text-right">—</span>
                                    <span class="ndb:text-[11px] ndb:text-zinc-400 ndb:sm:text-right">—</span>
                                    <span class="ndb:text-[11px] ndb:text-zinc-400">—</span>
                                </article>
                            @endif
                        </div>
                    </div>

                    @if ((int) ($group['hidden_record_count'] ?? 0) > 0)
                        <p
                            data-ndb-model-record-limit
                            class="ndb:mt-2 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                        >
                            Showing {{ number_format(count($group['records'])) }} of {{ number_format($recordCount) }} identified
                            records.
                        </p>
                    @endif

                    @if ($unidentifiedCount > 0)
                        <p
                            data-ndb-model-unidentified
                            class="ndb:mt-2 ndb:text-[11px] ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400"
                        >
                            A dash means the model identifier was unavailable. These retrievals are excluded from the
                            extra-retrieval count.
                        </p>
                    @endif
                </section>
            @else
                <x-newdebugbar::empty-state label="No model retrievals were captured for this context." />
            @endif
        </div>

        <div
            data-ndb-model-detail-panel="source"
            x-show.important="modelDetailTab === 'source'"
            class="ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
        >
            <section
                data-ndb-model-sources
                class="ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
            >
                <div class="ndb:flex ndb:items-baseline ndb:justify-between ndb:gap-3">
                    <div>
                        <h4 class="ndb:text-xs ndb:font-bold">Application sources</h4>
                        <p class="ndb:mt-0.5 ndb:text-[11px] ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                            Start with locations responsible for the most model activity.
                        </p>
                    </div>
                    <span class="ndb:shrink-0 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400">
                        {{ number_format($sourceCount) }} {{ $plural('source', $sourceCount) }}
                    </span>
                </div>

                @if (($group['sources'] ?? []) !== [])
                    <div class="ndb:mt-3 ndb:divide-y ndb:divide-zinc-200/90 ndb:border-y ndb:border-zinc-200/90 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                        @foreach ($group['sources'] as $source)
                            @php
                                $callsite = $source['callsite'];
                                $sourcePath = $callsite['file'].':'.$callsite['line'];
                                $isCompiledView = ($callsite['kind'] ?? null) === 'compiled_view';
                                $templateFile = is_string($callsite['template_file'] ?? null) ? $callsite['template_file'] : null;
                            @endphp
                            <article
                                data-ndb-model-source
                                class="ndb:grid ndb:min-w-0 ndb:gap-2 ndb:border-l-0 ndb:bg-transparent ndb:px-0 ndb:py-3 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white ndb:sm:grid-cols-[minmax(0,1fr)_10rem] ndb:sm:items-start"
                            >
                                <div class="ndb:min-w-0">
                                    @if ($isCompiledView && $templateFile !== null)
                                        <p
                                            data-ndb-model-compiled-source
                                            class="ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400"
                                        >
                                            Blade template
                                        </p>
                                        <p
                                            data-ndb-model-source-path="template"
                                            class="ndb:mt-0.5 ndb:break-all ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-300"
                                        >
                                            {{ $templateFile }}
                                        </p>
                                        <p class="ndb:mt-2 ndb:text-[11px] ndb:text-zinc-400">Compiled location</p>
                                        <p
                                            data-ndb-model-source-path="compiled"
                                            class="ndb:mt-0.5 ndb:break-all ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                        >
                                            {{ $sourcePath }}
                                        </p>
                                    @else
                                        <p
                                            data-ndb-model-source-path="application"
                                            class="ndb:break-all ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-300"
                                        >
                                            {{ $sourcePath }}
                                        </p>
                                    @endif
                                </div>
                                <div class="ndb:sm:text-right">
                                    <p class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">Activity</p>
                                    <p class="ndb:mt-0.5 ndb:text-[11px] ndb:text-zinc-600 ndb:dark:text-zinc-300">
                                        {{ $formatActivity((int) ($source['retrieval_count'] ?? 0), (int) ($source['change_count'] ?? 0)) }}
                                    </p>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if ((int) ($group['hidden_source_count'] ?? 0) > 0)
                        <p class="ndb:mt-2 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                            Showing {{ number_format(count($group['sources'])) }} of {{ number_format($sourceCount) }} application
                            sources.
                        </p>
                    @endif
                @else
                    <div
                        data-ndb-model-source-gap
                        class="ndb:mt-3 ndb:border-l-0 ndb:border-y ndb:border-zinc-200/90 ndb:bg-transparent ndb:px-0 ndb:py-3 ndb:text-xs ndb:text-zinc-950 ndb:dark:border-zinc-800 ndb:dark:text-white"
                    >
                        <p class="ndb:text-xs ndb:font-semibold">Source unavailable</p>
                        <p class="ndb:mt-1 ndb:text-[11px] ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                            Use the model identity and timing in Overview to narrow the application location.
                        </p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>
