{{-- Renders the expanded evidence for one model context. --}}
@props(['group'])

@php
    $retrievalCount = (int) ($group['load_count'] ?? 0);
    $changeCount = (int) ($group['change_count'] ?? 0);
    $repeatCount = (int) ($group['repeated_load_count'] ?? 0);
    $recordCount = (int) ($group['record_count'] ?? 0);
    $unidentifiedCount = (int) ($group['unidentified_load_count'] ?? 0);
    $sourceCount = (int) ($group['source_count'] ?? 0);
    $relatedQueryCount = (int) ($group['related_query_count'] ?? 0);
    $connection = is_string($group['connection'] ?? null) && $group['connection'] !== '' ? $group['connection'] : '—';
    $table = is_string($group['table'] ?? null) && $group['table'] !== '' ? $group['table'] : '—';
    $formatTime = static fn (mixed $value): string => is_numeric($value)
        ? rtrim(rtrim(number_format((float) $value, 1, '.', ''), '0'), '.').' ms'
        : '—';
    $plural = static fn (string $word, int $count): string => \Illuminate\Support\Str::plural($word, $count);
    $headline = static fn (string $value): string => \Illuminate\Support\Str::headline($value);
    $formatEvent = static fn (string $event): string => match ($event) {
        'forceDeleted' => 'Force deleted',
        default => $headline($event),
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

        return $parts === [] ? '—' : implode(', ', $parts);
    };
    $lifecycleFacts = [];

    foreach ($group['lifecycle_events'] ?? [] as $event => $count) {
        $lifecycleFacts[] = $formatEvent((string) $event).' '.number_format((int) $count);
    }

    $hasChangedAttributes = collect($group['change_operations'] ?? [])
        ->contains(fn (array $operation): bool => (int) ($operation['change_attribute_count'] ?? 0) > 0);
    $writeMethodology = 'Completed lifecycle callbacks from the same operation are shown once.';

    if ($hasChangedAttributes) {
        $writeMethodology .= ' Changed values use capture-time redaction.';
    }
@endphp

<div
    data-ndb-model-detail
    class="ndb:border-t ndb:border-zinc-200/90 ndb:bg-zinc-50/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/25"
>
    <section class="ndb:px-3 ndb:py-4 ndb:sm:px-4">
        <p class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
            Model context
        </p>
        <code
            data-ndb-model-class
            class="ndb:mt-1 ndb:block ndb:break-all ndb:text-sm ndb:font-bold ndb:leading-5 ndb:text-zinc-950 ndb:dark:text-white"
        >{{ $group['model'] }}</code>

        <dl data-ndb-model-facts class="ndb:mt-4 ndb:grid ndb:grid-cols-2 ndb:gap-x-5 ndb:gap-y-3 ndb:sm:grid-cols-4">
            @foreach ([
                ['Connection', $connection],
                ['Table', $table],
                ['Identified records', number_format($recordCount)],
                ['Lifecycle callbacks', number_format((int) ($group['total_count'] ?? 0))],
                ['First observed', $formatTime($group['first_seen_ms'] ?? null)],
                ['Last observed', $formatTime($group['last_seen_ms'] ?? null)],
            ] as [$label, $value])
                <div class="ndb:min-w-0">
                    <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                        {{ $label }}
                    </dt>
                    <dd
                        class="ndb:mt-0.5 ndb:truncate ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-300"
                        title="{{ $value }}"
                    >
                        {{ $value }}
                    </dd>
                </div>
            @endforeach
            <div class="ndb:col-span-2 ndb:min-w-0 ndb:sm:col-span-2">
                <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                    Lifecycle events
                </dt>
                <dd class="ndb:mt-0.5 ndb:text-[11px] ndb:leading-5 ndb:text-zinc-700 ndb:dark:text-zinc-300">
                    {{ $lifecycleFacts === [] ? '—' : implode(', ', $lifecycleFacts) }}
                </dd>
            </div>
        </dl>
    </section>

    @if (($group['change_operations'] ?? []) !== [])
        <section
            data-ndb-model-operations
            class="ndb:border-t ndb:border-zinc-200/90 ndb:px-3 ndb:py-4 ndb:dark:border-zinc-800 ndb:sm:px-4"
        >
            <div class="ndb:flex ndb:items-baseline ndb:justify-between ndb:gap-3">
                <div>
                    <h4 class="ndb:text-xs ndb:font-bold">Write evidence</h4>
                    <p class="ndb:mt-0.5 ndb:text-[11px] ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        {{ $writeMethodology }}
                    </p>
                </div>
                <span class="ndb:shrink-0 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400">
                    {{ number_format($changeCount) }} {{ $plural('operation', $changeCount) }}
                </span>
            </div>

            <div class="ndb:mt-3 ndb:divide-y ndb:divide-zinc-200/90 ndb:border-y ndb:border-zinc-200/90 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                @foreach ($group['change_operations'] as $operation)
                    @php
                        $operationCallsite = $operation['callsite'] ?? null;
                        $operationSource = is_array($operationCallsite)
                            ? $operationCallsite['file'].':'.$operationCallsite['line']
                            : '—';
                        $operationRecord = ($operation['key'] ?? null) === null
                            ? '—'
                            : ($operation['key_name'] ?? 'id').' '.(string) $operation['key'];
                        $callbackCount = array_sum($operation['lifecycle_events'] ?? []);
                        $callbackLabel = number_format($callbackCount).' '.$plural('callback', $callbackCount);

                        if ($callbackCount > 1) {
                            $callbackLabel .= ', folded';
                        }
                    @endphp
                    <article
                        data-ndb-model-operation
                        data-ndb-model-operation-event="{{ $operation['event'] }}"
                        class="ndb:border-l-0 ndb:bg-transparent ndb:py-3"
                    >
                        <div class="ndb:grid ndb:min-w-0 ndb:gap-2 ndb:sm:grid-cols-[minmax(0,1fr)_7rem] ndb:sm:items-start">
                            <div class="ndb:min-w-0">
                                <p class="ndb:text-xs ndb:font-bold">
                                    {{ $formatEvent((string) $operation['event']) }}
                                </p>
                                <p class="ndb:mt-0.5 ndb:font-mono ndb:text-[11px] ndb:text-zinc-600 ndb:dark:text-zinc-300">
                                    {{ $operationRecord }}
                                </p>
                            </div>
                            <span class="ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400 ndb:sm:text-right">
                                {{ $formatTime($operation['at_ms'] ?? null) }}
                            </span>
                        </div>

                        <dl class="ndb:mt-2 ndb:grid ndb:gap-x-5 ndb:gap-y-2 ndb:sm:grid-cols-[minmax(0,1fr)_10rem]">
                            <div class="ndb:min-w-0">
                                <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    Source
                                </dt>
                                <dd>
                                    <code
                                        class="ndb:mt-0.5 ndb:block ndb:break-all ndb:text-[11px] ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                        title="{{ $sourceTitle($operationCallsite) }}"
                                    >{{ $operationSource }}</code>
                                </dd>
                            </div>
                            <div>
                                <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    Lifecycle
                                </dt>
                                <dd class="ndb:mt-0.5 ndb:text-[11px] ndb:text-zinc-600 ndb:dark:text-zinc-300">
                                    {{ $callbackLabel }}
                                </dd>
                            </div>
                        </dl>

                        @if ((int) ($operation['change_attribute_count'] ?? 0) > 0)
                            <details data-ndb-model-operation-changes class="ndb:mt-3">
                                <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:justify-between ndb:gap-3 ndb:py-1 ndb:text-[11px] ndb:font-bold ndb:text-zinc-700 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-zinc-300">
                                    <span
                                        >Changed attributes
                                        <span
                                            class="ndb:ml-1 ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
                                            >{{ number_format((int) $operation['change_attribute_count']) }}</span
                                        ></span>
                                    <x-newdebugbar::icon name="chevron-down" class="ndb:size-3 ndb:text-zinc-400" />
                                </summary>
                                <dl
                                    data-ndb-model-changes
                                    class="ndb:mt-2 ndb:divide-y ndb:divide-zinc-200/90 ndb:border-y ndb:border-zinc-200/90 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800"
                                >
                                    @foreach ($operation['changes'] ?? [] as $attribute => $value)
                                        <div class="ndb:grid ndb:gap-1 ndb:py-2 ndb:sm:grid-cols-[9rem_minmax(0,1fr)] ndb:sm:gap-4">
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
                                    <p class="ndb:mt-2 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                        Additional changed attributes were omitted.
                                    </p>
                                @endif
                            </details>
                        @endif
                    </article>
                @endforeach
            </div>

            @if ((int) ($group['hidden_change_operation_count'] ?? 0) > 0)
                <p class="ndb:mt-2 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                    Showing {{ number_format(count($group['change_operations'])) }} of {{ number_format($changeCount) }} write
                    operations.
                </p>
            @endif
        </section>
    @endif

    @if ($retrievalCount > 0)
        <section
            data-ndb-model-records
            class="ndb:border-t ndb:border-zinc-200/90 ndb:px-3 ndb:py-4 ndb:dark:border-zinc-800 ndb:sm:px-4"
        >
            <div class="ndb:flex ndb:items-baseline ndb:justify-between ndb:gap-3">
                <div>
                    <h4 class="ndb:text-xs ndb:font-bold">Retrieved records</h4>
                    <p class="ndb:mt-0.5 ndb:text-[11px] ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        Identifiers group repeated retrieved events for the same model context.
                    </p>
                </div>
                <span class="ndb:shrink-0 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400">
                    {{ number_format($recordCount) }} identified
                </span>
            </div>

            @if ($repeatCount > 0)
                <p
                    data-ndb-model-extra-guidance
                    class="ndb:mt-2 ndb:text-[11px] ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                >
                    <strong class="ndb:font-bold ndb:text-amber-700 ndb:dark:text-amber-300">{{ number_format($repeatCount) }} extra {{ $plural('retrieval', $repeatCount) }}</strong>
                    {{ $repeatCount === 1 ? 'was' : 'were' }} observed for already identified records. Check repeated
                    relationship access, loops, and missing eager loading.
                </p>
            @endif

            <div class="ndb:mt-3 ndb:border-y ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
                <div class="ndb:hidden ndb:grid-cols-[minmax(8rem,1fr)_5.5rem_6rem_6rem_minmax(8rem,1fr)] ndb:gap-3 ndb:border-b ndb:border-zinc-200/90 ndb:py-2 ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:dark:border-zinc-800 ndb:sm:grid">
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
                        @endphp
                        <article
                            data-ndb-model-record
                            data-ndb-model-record-retrievals="{{ $recordLoads }}"
                            class="ndb:grid ndb:min-w-0 ndb:gap-2 ndb:border-l-0 ndb:bg-transparent ndb:py-2.5 ndb:sm:grid-cols-[minmax(8rem,1fr)_5.5rem_6rem_6rem_minmax(8rem,1fr)] ndb:sm:items-center ndb:sm:gap-3"
                        >
                            <p class="ndb:min-w-0 ndb:break-all ndb:font-mono ndb:text-[11px] ndb:font-semibold">
                                {{ $record['key_name'] ?? 'id' }} {{ (string) $record['key'] }}
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
                            <code
                                class="ndb:min-w-0 ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                title="{{ $sourceTitle($recordSource) }}"
                            >
                                <span class="ndb:sm:hidden">Source </span>{{ $sourceShortLabel($recordSource) }}
                            </code>
                        </article>
                    @endforeach

                    @if ($unidentifiedCount > 0)
                        <article
                            data-ndb-model-missing-identifiers
                            class="ndb:grid ndb:min-w-0 ndb:gap-2 ndb:border-l-0 ndb:bg-transparent ndb:py-2.5 ndb:sm:grid-cols-[minmax(8rem,1fr)_5.5rem_6rem_6rem_minmax(8rem,1fr)] ndb:sm:items-center ndb:sm:gap-3"
                        >
                            <p class="ndb:font-mono ndb:text-[11px] ndb:font-semibold">—</p>
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
    @endif

    <section
        data-ndb-model-sources
        class="ndb:border-t ndb:border-zinc-200/90 ndb:px-3 ndb:py-4 ndb:dark:border-zinc-800 ndb:sm:px-4"
    >
        <div class="ndb:flex ndb:items-baseline ndb:justify-between ndb:gap-3">
            <div>
                <h4 class="ndb:text-xs ndb:font-bold">Application sources</h4>
                <p class="ndb:mt-0.5 ndb:text-[11px] ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                    The first retained application location for each activity group.
                </p>
            </div>
            <span class="ndb:shrink-0 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400">
                {{ number_format($sourceCount) }} {{ $plural('source', $sourceCount) }}
            </span>
        </div>

        @if ($relatedQueryCount > 0)
            <p
                data-ndb-model-query-guidance
                class="ndb:mt-2 ndb:max-w-4xl ndb:text-[11px] ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400"
            >
                Related queries share the exact file and line. This is useful correlation evidence, but it does not
                prove that a query hydrated or changed the model.
            </p>
        @endif

        @if (($group['sources'] ?? []) !== [])
            <div class="ndb:mt-3 ndb:divide-y ndb:divide-zinc-200/90 ndb:border-y ndb:border-zinc-200/90 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                @foreach ($group['sources'] as $source)
                    @php
                        $callsite = $source['callsite'];
                        $sourcePath = $callsite['file'].':'.$callsite['line'];
                        $queryCount = (int) ($source['query_count'] ?? 0);
                        $queryReadCount = (int) ($source['query_read_count'] ?? 0);
                        $queryWriteCount = (int) ($source['query_write_count'] ?? 0);
                        $isCompiledView = ($callsite['kind'] ?? null) === 'compiled_view';
                        $templateFile = is_string($callsite['template_file'] ?? null) ? $callsite['template_file'] : null;
                    @endphp
                    <article data-ndb-model-source class="ndb:border-l-0 ndb:bg-transparent ndb:py-3">
                        <div class="ndb:grid ndb:min-w-0 ndb:gap-2 ndb:sm:grid-cols-[minmax(0,1fr)_10rem] ndb:sm:items-start">
                            <div class="ndb:min-w-0">
                                @if ($isCompiledView && $templateFile !== null)
                                    <p
                                        data-ndb-model-compiled-source
                                        class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
                                    >
                                        Blade template
                                    </p>
                                    <code class="ndb:mt-0.5 ndb:block ndb:break-all ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-300">{{ $templateFile }}</code>
                                    <p class="ndb:mt-1 ndb:text-[11px] ndb:text-zinc-400">Compiled location</p>
                                    <code class="ndb:mt-0.5 ndb:block ndb:break-all ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $sourcePath }}</code>
                                @else
                                    <code class="ndb:block ndb:break-all ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-300">{{ $sourcePath }}</code>
                                @endif
                            </div>
                            <div class="ndb:sm:text-right">
                                <p class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    Activity
                                </p>
                                <p class="ndb:mt-0.5 ndb:text-[11px] ndb:text-zinc-600 ndb:dark:text-zinc-300">
                                    {{ $formatActivity((int) ($source['retrieval_count'] ?? 0), (int) ($source['change_count'] ?? 0)) }}
                                </p>
                            </div>
                        </div>

                        @if ($queryCount > 0)
                            <div
                                data-ndb-model-query-evidence
                                class="ndb:mt-2 ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-x-4 ndb:gap-y-1"
                            >
                                <button
                                    type="button"
                                    data-ndb-model-view-queries
                                    @click="navigateToQueriesAtSource(@js($sourcePath))"
                                    class="ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:underline-offset-2 ndb:hover:underline ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                                >
                                    Inspect {{ number_format($queryCount) }} related {{ $plural('query', $queryCount) }}
                                </button>
                                <span class="ndb:text-[11px] ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                    {{ number_format($queryReadCount) }} {{ $plural('read', $queryReadCount) }}, {{ number_format($queryWriteCount) }} {{ $plural('write', $queryWriteCount) }}, {{ number_format((float) ($source['query_duration_ms'] ?? 0), 2) }} ms
                                </span>
                            </div>
                        @endif
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
                class="ndb:mt-3 ndb:grid ndb:grid-cols-[6rem_minmax(0,1fr)] ndb:gap-3 ndb:border-y ndb:border-zinc-200/90 ndb:py-2.5 ndb:dark:border-zinc-800"
            >
                <span class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Source</span>
                <span class="ndb:text-[11px] ndb:text-zinc-600 ndb:dark:text-zinc-300">—</span>
            </div>
            <p class="ndb:mt-2 ndb:text-[11px] ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                No application call site was retained for this activity. Use the model identity and timing to narrow the
                source.
            </p>
        @endif
    </section>
</div>
