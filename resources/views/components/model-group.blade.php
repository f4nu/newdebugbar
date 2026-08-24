{{-- Renders one model context with write, source, record, and lifecycle evidence. --}}
@props(['group', 'index'])

@php
    $shortName = class_basename($group['model']);
    $retrievalCount = (int) ($group['load_count'] ?? 0);
    $changeCount = (int) ($group['change_count'] ?? 0);
    $repeatCount = (int) ($group['repeated_load_count'] ?? 0);
    $recordCount = (int) ($group['record_count'] ?? 0);
    $unidentifiedCount = (int) ($group['unidentified_load_count'] ?? 0);
    $sourceCount = (int) ($group['source_count'] ?? 0);
    $primarySource = $group['sources'][0]['callsite'] ?? null;
    $shortSource = is_array($primarySource)
        ? basename(str_replace('\\', '/', $primarySource['file'])).':'.$primarySource['line']
        : 'Source unavailable';
    $formatTime = static fn (mixed $value): string => is_numeric($value)
        ? rtrim(rtrim(number_format((float) $value, 1, '.', ''), '0'), '.').' ms'
        : 'Unavailable';
    $formatEvent = static fn (string $event): string => match ($event) {
        'forceDeleted' => 'Force deleted',
        default => \Illuminate\Support\Str::headline($event),
    };
    $formatActivity = static function (int $retrievals, int $changes): string {
        $parts = [];

        if ($retrievals > 0) {
            $parts[] = number_format($retrievals).' '.\Illuminate\Support\Str::plural('retrieval', $retrievals);
        }

        if ($changes > 0) {
            $parts[] = number_format($changes).' '.\Illuminate\Support\Str::plural('write', $changes);
        }

        return $parts === [] ? 'Activity captured' : implode(', ', $parts);
    };
@endphp

<details
    data-ndb-model-group
    data-ndb-model-short-name="{{ $shortName }}"
    data-ndb-model-retrievals="{{ $retrievalCount }}"
    data-ndb-model-records="{{ $recordCount }}"
    data-ndb-model-repeats="{{ $repeatCount }}"
    data-ndb-model-writes="{{ $changeCount }}"
    wire:key="model-group-{{ $index }}"
    @class([
        'ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:bg-white/55 ndb:text-xs ndb:text-zinc-950 ndb:dark:bg-zinc-950/25 ndb:dark:text-white',
        'ndb:border-amber-200/90 ndb:dark:border-amber-950' => $changeCount > 0,
        'ndb:border-zinc-200/90 ndb:dark:border-zinc-800' => $changeCount === 0,
    ])
>
    <summary class="ndb:grid ndb:cursor-pointer ndb:list-none ndb:grid-cols-[minmax(0,1fr)_auto] ndb:items-center ndb:gap-3 ndb:px-3 ndb:py-3 ndb:text-zinc-950 ndb:transition-colors ndb:hover:bg-zinc-50/70 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500 ndb:dark:text-white ndb:dark:hover:bg-zinc-900/55 ndb:sm:px-4">
        <span class="ndb:min-w-0">
            <span class="ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:items-center ndb:gap-1.5">
                <span
                    data-ndb-model-name
                    class="ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-bold"
                >{{ $shortName }}</span>
                @if ($changeCount > 0)
                    <span class="ndb:rounded-md ndb:bg-amber-100 ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:text-amber-800 ndb:dark:bg-amber-950/70 ndb:dark:text-amber-300">
                        {{ number_format($changeCount) }} {{ \Illuminate\Support\Str::plural('write', $changeCount) }}
                    </span>
                @endif
                @if ($retrievalCount > 0)
                    <span class="ndb:rounded-md ndb:bg-zinc-100 ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:text-zinc-600 ndb:dark:bg-zinc-800 ndb:dark:text-zinc-300">
                        {{ number_format($retrievalCount) }} retrieved
                    </span>
                @endif
                @if ($repeatCount > 0)
                    <span class="ndb:rounded-md ndb:bg-amber-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:text-amber-700 ndb:dark:bg-amber-950/40 ndb:dark:text-amber-300">
                        {{ number_format($repeatCount) }} extra
                    </span>
                @endif
            </span>
            <span
                class="ndb:mt-1 ndb:block ndb:truncate ndb:font-mono ndb:text-[11px] ndb:text-zinc-400"
                title="{{ is_array($primarySource) ? $primarySource['file'].':'.$primarySource['line'] : $shortSource }}"
            >
                {{ $shortSource }}
            </span>
        </span>
        <x-newdebugbar::icon
            name="chevron-down"
            class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
        />
    </summary>

    <div class="ndb:border-t ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
        <div class="ndb:flex ndb:min-w-0 ndb:items-start ndb:justify-between ndb:gap-3 ndb:px-3 ndb:py-3 ndb:sm:px-4">
            <div class="ndb:min-w-0">
                <p class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                    Full class
                </p>
                <code
                    data-ndb-model-class
                    class="ndb:mt-1 ndb:block ndb:break-all ndb:text-[11px] ndb:font-semibold ndb:leading-5 ndb:text-zinc-700 ndb:dark:text-zinc-300"
                >{{ $group['model'] }}</code>
            </div>
            <button
                type="button"
                data-ndb-model-copy-class
                @click="copyText(@js($group['model']))"
                aria-label="Copy full model class"
                title="Copy full model class"
                class="ndb:inline-flex ndb:size-8 ndb:shrink-0 ndb:items-center ndb:justify-center ndb:rounded-lg ndb:text-zinc-500 ndb:transition ndb:hover:bg-zinc-100 ndb:hover:text-zinc-950 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-zinc-400 ndb:dark:hover:bg-zinc-800 ndb:dark:hover:text-white"
            >
                <x-newdebugbar::icon name="copy" class="ndb:size-4" />
            </button>
        </div>

        <dl class="ndb:grid ndb:grid-cols-2 ndb:gap-x-4 ndb:gap-y-3 ndb:border-t ndb:border-zinc-200/80 ndb:bg-zinc-50/45 ndb:px-3 ndb:py-3 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/35 ndb:sm:grid-cols-4 ndb:sm:px-4">
            @foreach ([
                ['Connection', $group['connection'] ?? 'default'],
                ['Table', $group['table'] ?? 'Unknown'],
                ['Identified records', number_format($recordCount)],
                ['Lifecycle callbacks', number_format((int) ($group['total_count'] ?? 0))],
            ] as [$label, $value])
                <div class="ndb:min-w-0">
                    <dt class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                        {{ $label }}
                    </dt>
                    <dd
                        class="ndb:mt-1 ndb:truncate ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:text-zinc-950 ndb:dark:text-white"
                        title="{{ $value }}"
                    >
                        {{ $value }}
                    </dd>
                </div>
            @endforeach
        </dl>

        @if (($group['change_operations'] ?? []) !== [])
            <section
                data-ndb-model-operations
                class="ndb:border-t ndb:border-zinc-200/90 ndb:px-3 ndb:py-4 ndb:dark:border-zinc-800 ndb:sm:px-4"
            >
                <div class="ndb:flex ndb:items-baseline ndb:justify-between ndb:gap-3">
                    <div>
                        <h4 class="ndb:text-xs ndb:font-bold">Write operations</h4>
                        <p class="ndb:mt-0.5 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                            Lifecycle callbacks from the same Eloquent operation are shown once.
                        </p>
                    </div>
                    <span class="ndb:shrink-0 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-amber-700 ndb:dark:text-amber-300">{{ number_format($changeCount) }}</span>
                </div>

                <div class="ndb:mt-3 ndb:space-y-2">
                    @foreach ($group['change_operations'] as $operationIndex => $operation)
                        @php
                            $operationCallsite = $operation['callsite'] ?? null;
                            $operationSource = is_array($operationCallsite)
                                ? $operationCallsite['file'].':'.$operationCallsite['line']
                                : null;
                            $operationRecord = ($operation['key'] ?? null) === null
                                ? 'Identifier unavailable'
                                : ($operation['key_name'] ?? 'id').' '.(string) $operation['key'];
                            $callbackCount = array_sum($operation['lifecycle_events'] ?? []);
                        @endphp
                        <article
                            data-ndb-model-operation
                            data-ndb-model-operation-event="{{ $operation['event'] }}"
                            class="ndb:rounded-lg ndb:border ndb:border-amber-200/90 ndb:bg-amber-50/55 ndb:px-3 ndb:py-3 ndb:text-xs ndb:text-zinc-950 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/20 ndb:dark:text-white"
                        >
                            <div class="ndb:flex ndb:min-w-0 ndb:items-start ndb:justify-between ndb:gap-3">
                                <div class="ndb:min-w-0">
                                    <p class="ndb:text-xs ndb:font-bold ndb:text-amber-900 ndb:dark:text-amber-200">
                                        {{ $formatEvent((string) $operation['event']) }}
                                    </p>
                                    <p class="ndb:mt-0.5 ndb:font-mono ndb:text-[11px] ndb:text-zinc-600 ndb:dark:text-zinc-300">
                                        {{ $operationRecord }}
                                    </p>
                                </div>
                                <span class="ndb:shrink-0 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $formatTime($operation['at_ms'] ?? null) }}</span>
                            </div>

                            <div class="ndb:mt-2 ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-2">
                                <code
                                    class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                    title="{{ $operationSource ?? 'Source unavailable' }}"
                                >{{ $operationSource ?? 'Source unavailable' }}</code>
                                @if ($operationSource !== null)
                                    <button
                                        type="button"
                                        data-ndb-model-copy-operation-source="{{ $operationIndex }}"
                                        @click="copyText(@js($operationSource))"
                                        aria-label="Copy write source"
                                        title="Copy write source"
                                        class="ndb:inline-flex ndb:size-7 ndb:shrink-0 ndb:items-center ndb:justify-center ndb:rounded-md ndb:text-zinc-500 ndb:transition ndb:hover:bg-amber-100 ndb:hover:text-zinc-950 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-zinc-400 ndb:dark:hover:bg-amber-900/50 ndb:dark:hover:text-white"
                                    >
                                        <x-newdebugbar::icon name="copy" class="ndb:size-3.5" />
                                    </button>
                                @endif
                            </div>

                            @if ((int) ($operation['change_attribute_count'] ?? 0) > 0)
                                <details
                                    data-ndb-model-operation-changes
                                    class="ndb:mt-2 ndb:rounded-md ndb:border ndb:border-amber-200/80 ndb:bg-white/60 ndb:dark:border-amber-900/70 ndb:dark:bg-zinc-950/30"
                                >
                                    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:justify-between ndb:gap-2 ndb:px-2.5 ndb:py-2 ndb:text-[11px] ndb:font-bold ndb:text-zinc-700 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500 ndb:dark:text-zinc-300">
                                        <span>{{ number_format((int) $operation['change_attribute_count']) }} changed {{ \Illuminate\Support\Str::plural('attribute', (int) $operation['change_attribute_count']) }}</span>
                                        <x-newdebugbar::icon name="chevron-down" class="ndb:size-3 ndb:text-zinc-400" />
                                    </summary>
                                    <dl class="ndb:divide-y ndb:divide-zinc-200/80 ndb:border-t ndb:border-amber-200/80 ndb:dark:divide-zinc-800 ndb:dark:border-amber-900/70">
                                        @foreach ($operation['changes'] ?? [] as $attribute => $value)
                                            <div class="ndb:grid ndb:gap-1 ndb:px-2.5 ndb:py-2 ndb:sm:grid-cols-[8rem_minmax(0,1fr)] ndb:sm:gap-3">
                                                <dt class="ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                                    {{ $attribute }}
                                                </dt>
                                                <dd class="ndb:min-w-0 ndb:break-all ndb:font-mono ndb:text-[11px] ndb:text-zinc-700 ndb:dark:text-zinc-300">
                                                    {{ is_scalar($value) || $value === null ? var_export($value, true) : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                    @if ($operation['changes_truncated'] ?? false)
                                        <p class="ndb:border-t ndb:border-amber-200/80 ndb:px-2.5 ndb:py-2 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:border-amber-900/70 ndb:dark:text-zinc-400">
                                            Additional changed attributes were omitted.
                                        </p>
                                    @endif
                                </details>
                            @endif

                            @if ($callbackCount > 1)
                                <p class="ndb:mt-2 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                    Built from {{ number_format($callbackCount) }} lifecycle callbacks.
                                </p>
                            @endif
                        </article>
                    @endforeach
                </div>

                @if ((int) ($group['hidden_change_operation_count'] ?? 0) > 0)
                    <p class="ndb:mt-2 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        {{ number_format((int) $group['hidden_change_operation_count']) }} additional write {{ \Illuminate\Support\Str::plural('operation', (int) $group['hidden_change_operation_count']) }} were
                        captured but not rendered.
                    </p>
                @endif
            </section>
        @endif

        <section class="ndb:border-t ndb:border-zinc-200/90 ndb:px-3 ndb:py-4 ndb:dark:border-zinc-800 ndb:sm:px-4">
            <div class="ndb:flex ndb:items-baseline ndb:justify-between ndb:gap-3">
                <div>
                    <h4 class="ndb:text-xs ndb:font-bold">Application sources</h4>
                    <p class="ndb:mt-0.5 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        Where New Debug Bar first found application code for this activity.
                    </p>
                </div>
                <span class="ndb:shrink-0 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400">{{ number_format($sourceCount) }}</span>
            </div>

            @if (($group['sources'] ?? []) !== [])
                <div class="ndb:mt-3 ndb:divide-y ndb:divide-zinc-200/80 ndb:rounded-lg ndb:border ndb:border-zinc-200/90 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                    @foreach ($group['sources'] as $sourceIndex => $source)
                        @php
                            $sourcePath = $source['callsite']['file'].':'.$source['callsite']['line'];
                            $queryCount = (int) ($source['query_count'] ?? 0);
                        @endphp
                        <article
                            data-ndb-model-source
                            class="ndb:border-l-0 ndb:bg-transparent ndb:px-3 ndb:py-3 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
                        >
                            <div class="ndb:flex ndb:min-w-0 ndb:items-start ndb:justify-between ndb:gap-3">
                                <div class="ndb:min-w-0">
                                    <code class="ndb:block ndb:break-all ndb:text-[11px] ndb:font-semibold ndb:leading-5 ndb:text-zinc-700 ndb:dark:text-zinc-300">{{ $sourcePath }}</code>
                                    <p class="ndb:mt-0.5 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                        {{ $formatActivity((int) ($source['retrieval_count'] ?? 0), (int) ($source['change_count'] ?? 0)) }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    data-ndb-model-copy-source="{{ $sourceIndex }}"
                                    @click="copyText(@js($sourcePath))"
                                    aria-label="Copy model activity source"
                                    title="Copy model activity source"
                                    class="ndb:inline-flex ndb:size-7 ndb:shrink-0 ndb:items-center ndb:justify-center ndb:rounded-md ndb:text-zinc-500 ndb:transition ndb:hover:bg-zinc-100 ndb:hover:text-zinc-950 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-zinc-400 ndb:dark:hover:bg-zinc-800 ndb:dark:hover:text-white"
                                >
                                    <x-newdebugbar::icon name="copy" class="ndb:size-3.5" />
                                </button>
                            </div>

                            @if ($queryCount > 0)
                                <div
                                    data-ndb-model-query-evidence
                                    class="ndb:mt-2 ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-x-3 ndb:gap-y-1"
                                >
                                    <button
                                        type="button"
                                        data-ndb-model-view-queries
                                        @click="selectSection('queries')"
                                        class="ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                                    >
                                        View {{ number_format($queryCount) }} {{ \Illuminate\Support\Str::plural('query', $queryCount) }} at
                                        the same source
                                    </button>
                                    <span class="ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400">{{ number_format((float) ($source['query_duration_ms'] ?? 0), 2) }} ms</span>
                                    <span class="ndb:text-[11px] ndb:text-zinc-400">{{ number_format((int) ($source['query_read_count'] ?? 0)) }} reads</span>
                                    <span class="ndb:text-[11px] ndb:text-zinc-400">{{ number_format((int) ($source['query_write_count'] ?? 0)) }} writes</span>
                                </div>
                                <p class="ndb:mt-1 ndb:text-[11px] ndb:leading-4 ndb:text-zinc-400">
                                    The source location matches exactly. That does not prove which query hydrated or
                                    changed this model.
                                </p>
                            @endif
                        </article>
                    @endforeach
                </div>

                @if ((int) ($group['hidden_source_count'] ?? 0) > 0)
                    <p class="ndb:mt-2 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        {{ number_format((int) $group['hidden_source_count']) }} additional application {{ \Illuminate\Support\Str::plural('source', (int) $group['hidden_source_count']) }} were
                        captured but not rendered.
                    </p>
                @endif
            @else
                <p class="ndb:mt-3 ndb:rounded-lg ndb:bg-zinc-50 ndb:px-3 ndb:py-2.5 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-400">
                    No application source was available for this model activity.
                </p>
            @endif
        </section>

        @if ($retrievalCount > 0)
            <section class="ndb:border-t ndb:border-zinc-200/90 ndb:px-3 ndb:py-4 ndb:dark:border-zinc-800 ndb:sm:px-4">
                <div class="ndb:flex ndb:items-baseline ndb:justify-between ndb:gap-3">
                    <div>
                        <h4 class="ndb:text-xs ndb:font-bold">Retrieved records</h4>
                        <p class="ndb:mt-0.5 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                            Identifiers are grouped only when the captured model key is available.
                        </p>
                    </div>
                    <span class="ndb:shrink-0 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400">{{ number_format($recordCount) }}</span>
                </div>

                @if (($group['records'] ?? []) !== [])
                    <div class="ndb:mt-3 ndb:divide-y ndb:divide-zinc-200/80 ndb:rounded-lg ndb:border ndb:border-zinc-200/90 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                        @foreach ($group['records'] as $record)
                            @php
                                $recordSource = $record['sources'][0]['callsite'] ?? null;
                                $recordSourceLabel = is_array($recordSource)
                                    ? basename(str_replace('\\', '/', $recordSource['file'])).':'.$recordSource['line']
                                    : 'Source unavailable';
                                $recordLoads = (int) ($record['loads'] ?? 0);
                            @endphp
                            <article
                                data-ndb-model-record
                                data-ndb-model-record-retrievals="{{ $recordLoads }}"
                                @class([
                                    'ndb:grid ndb:min-w-0 ndb:gap-2 ndb:border-l-0 ndb:px-3 ndb:py-2.5 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white ndb:sm:grid-cols-[minmax(0,1fr)_auto] ndb:sm:items-center',
                                    'ndb:bg-amber-50/45 ndb:dark:bg-amber-950/15' => $recordLoads > 1,
                                    'ndb:bg-transparent' => $recordLoads === 1,
                                ])
                            >
                                <div class="ndb:min-w-0">
                                    <p class="ndb:break-all ndb:font-mono ndb:text-[11px] ndb:font-semibold">
                                        {{ $record['key_name'] ?? 'id' }} {{ (string) $record['key'] }}
                                    </p>
                                    <p
                                        class="ndb:mt-0.5 ndb:truncate ndb:font-mono ndb:text-[11px] ndb:text-zinc-400"
                                        title="{{ is_array($recordSource) ? $recordSource['file'].':'.$recordSource['line'] : $recordSourceLabel }}"
                                    >
                                        {{ $recordSourceLabel }}
                                    </p>
                                </div>
                                <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-x-3 ndb:gap-y-1 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400 ndb:sm:justify-end">
                                    <span @class(['ndb:font-bold ndb:text-amber-700 ndb:dark:text-amber-300' => $recordLoads > 1, 'ndb:font-semibold' => $recordLoads === 1])>{{ number_format($recordLoads) }} {{ \Illuminate\Support\Str::plural('retrieval', $recordLoads) }}</span>
                                    <span class="ndb:tabular-nums">First {{ $formatTime($record['first_seen_ms'] ?? null) }}</span>
                                    @if (($record['last_seen_ms'] ?? null) !== ($record['first_seen_ms'] ?? null))
                                        <span class="ndb:tabular-nums">Last {{ $formatTime($record['last_seen_ms'] ?? null) }}</span>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if ((int) ($group['hidden_record_count'] ?? 0) > 0)
                        <p
                            data-ndb-model-record-limit
                            class="ndb:mt-2 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                        >
                            Showing {{ number_format(count($group['records'])) }} of {{ number_format($recordCount) }} identified
                            records. Counts above include every captured record.
                        </p>
                    @endif
                @else
                    <p
                        data-ndb-model-missing-identifiers
                        class="ndb:mt-3 ndb:rounded-lg ndb:bg-zinc-50 ndb:px-3 ndb:py-2.5 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-400"
                    >
                        Record identifiers were unavailable for these retrievals, so repeated records cannot be
                        determined.
                    </p>
                @endif

                @if ($unidentifiedCount > 0 && ($group['records'] ?? []) !== [])
                    <p
                        data-ndb-model-unidentified
                        class="ndb:mt-2 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                    >
                        {{ number_format($unidentifiedCount) }} additional {{ \Illuminate\Support\Str::plural('retrieval', $unidentifiedCount) }} had
                        no record identifier and {{ $unidentifiedCount === 1 ? 'is' : 'are' }} excluded from the
                        repeated count.
                    </p>
                @endif
            </section>
        @endif

        <details data-ndb-model-lifecycle class="ndb:border-t ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
            <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:justify-between ndb:gap-3 ndb:px-3 ndb:py-3 ndb:text-[11px] ndb:font-bold ndb:text-zinc-500 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500 ndb:dark:text-zinc-400 ndb:sm:px-4">
                <span>Raw lifecycle callbacks</span>
                <span class="ndb:flex ndb:items-center ndb:gap-2">
                    <span class="ndb:tabular-nums">{{ number_format((int) ($group['total_count'] ?? 0)) }}</span>
                    <x-newdebugbar::icon name="chevron-down" class="ndb:size-3 ndb:text-zinc-400" />
                </span>
            </summary>
            <dl class="ndb:flex ndb:flex-wrap ndb:gap-2 ndb:border-t ndb:border-zinc-200/80 ndb:bg-zinc-50/45 ndb:px-3 ndb:py-3 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/35 ndb:sm:px-4">
                @foreach ($group['lifecycle_events'] ?? [] as $event => $count)
                    <div class="ndb:inline-flex ndb:items-center ndb:gap-1.5 ndb:rounded-md ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:dark:border-zinc-700 ndb:dark:bg-zinc-950/50">
                        <dt class="ndb:font-mono ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $event }}</dt>
                        <dd class="ndb:font-bold ndb:tabular-nums">{{ number_format((int) $count) }}</dd>
                    </div>
                @endforeach
            </dl>
        </details>
    </div>
</details>
