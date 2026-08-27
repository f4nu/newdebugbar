{{-- Organizes one model context into record and source views. --}}
@props(['group'])

@php
    $retrievalCount = (int) ($group['load_count'] ?? 0);
    $changeCount = (int) ($group['change_count'] ?? 0);
    $recordCount = (int) ($group['record_count'] ?? 0);
    $unidentifiedCount = (int) ($group['unidentified_load_count'] ?? 0);
    $sourceCount = (int) ($group['source_count'] ?? 0);
    $writeOperations = is_array($group['change_operations'] ?? null) ? $group['change_operations'] : [];
    $hiddenWriteCount = (int) ($group['hidden_change_operation_count'] ?? 0);
    $connection = is_string($group['connection'] ?? null) && $group['connection'] !== '' ? $group['connection'] : '—';
    $table = is_string($group['table'] ?? null) && $group['table'] !== '' ? $group['table'] : '—';
    $plural = static fn (string $word, int $count): string => \Illuminate\Support\Str::plural($word, $count);
    $formatEvent = static fn (string $event): string => match ($event) {
        'forceDeleted' => 'Force deleted',
        default => \Illuminate\Support\Str::headline($event),
    };
    $sourceLocation = static function (mixed $callsite): ?string {
        if (! is_array($callsite) || ! is_string($callsite['file'] ?? null) || $callsite['file'] === '') {
            return null;
        }

        $line = is_numeric($callsite['line'] ?? null) && (int) $callsite['line'] > 0
            ? (int) $callsite['line']
            : null;

        return $callsite['file'].($line === null ? '' : ':'.$line);
    };
    $sourceTitle = static function (mixed $callsite) use ($sourceLocation): string {
        $exact = $sourceLocation($callsite);

        if ($exact === null) {
            return 'Source unavailable';
        }

        if (($callsite['kind'] ?? null) === 'compiled_view' && is_string($callsite['template_file'] ?? null)) {
            return 'Blade '.$callsite['template_file'].', compiled '.$exact;
        }

        return $exact;
    };
    $sourceShortLabel = static function (mixed $callsite): string {
        if (! is_array($callsite) || ! is_string($callsite['file'] ?? null) || $callsite['file'] === '') {
            return '—';
        }

        if (($callsite['kind'] ?? null) === 'compiled_view' && is_string($callsite['template_file'] ?? null)) {
            return basename(str_replace('\\', '/', $callsite['template_file']));
        }

        $line = is_numeric($callsite['line'] ?? null) && (int) $callsite['line'] > 0
            ? (int) $callsite['line']
            : null;

        return basename(str_replace('\\', '/', $callsite['file'])).($line === null ? '' : ':'.$line);
    };
    $sourceCopy = static function (mixed $callsite) use ($sourceLocation): ?string {
        if (! is_array($callsite)) {
            return null;
        }

        if (($callsite['kind'] ?? null) === 'compiled_view' && is_string($callsite['template_file'] ?? null)) {
            return $callsite['template_file'];
        }

        return $sourceLocation($callsite);
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
            <h3
                data-ndb-model-class
                class="ndb:min-w-0 ndb:break-all ndb:text-sm ndb:font-bold ndb:leading-5 ndb:text-zinc-950 ndb:dark:text-white"
            >
                {{ $group['model'] }}
            </h3>
        </x-slot:title>
        <x-slot:aside></x-slot:aside>
        <x-slot:metadata class="ndb:gap-x-8 ndb:gap-y-2">
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
        @foreach (['records' => 'Records', 'source' => 'Source'] as $tab => $label)
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
        <template x-if="modelDetailTab === 'records'">
            <div
                data-ndb-model-detail-panel="records"
                class="ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
            >
                @if ($retrievalCount > 0)
                    <section
                        data-ndb-model-records
                        class="ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
                    >
                        <x-newdebugbar::inspector-explanation
                            title="How this model was loaded"
                            description="Each row is one record Laravel loaded during this request. Retrieved shows how often it was loaded. If the count is above 1, check whether those repeated loads are expected."
                        />

                        <div class="ndb:mt-3 ndb:border-y ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
                            <div class="ndb:hidden ndb:grid-cols-[minmax(8rem,1fr)_5.5rem_minmax(10rem,1.25fr)] ndb:gap-3 ndb:border-b ndb:border-zinc-200/90 ndb:py-2 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400 ndb:dark:border-zinc-800 ndb:sm:grid">
                                <span>Identifier</span>
                                <span class="ndb:text-right">Retrieved</span>
                                <span>Source</span>
                            </div>

                            <div class="ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800">
                                @foreach ($group['records'] ?? [] as $record)
                                    @php
                                        $recordSource = $record['sources'][0]['callsite'] ?? null;
                                        $recordSourceCopy = $sourceCopy($recordSource);
                                        $recordLoads = (int) ($record['loads'] ?? 0);
                                        $recordKey = $record['key'] ?? null;
                                    @endphp
                                    <article
                                        data-ndb-model-record
                                        data-ndb-model-record-retrievals="{{ $recordLoads }}"
                                        class="ndb:grid ndb:min-w-0 ndb:gap-2 ndb:border-l-0 ndb:bg-transparent ndb:px-0 ndb:py-2.5 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white ndb:sm:grid-cols-[minmax(8rem,1fr)_5.5rem_minmax(10rem,1.25fr)] ndb:sm:items-center ndb:sm:gap-3"
                                    >
                                        <p @class([
                                            'ndb:min-w-0 ndb:break-all ndb:text-[11px] ndb:font-semibold',
                                            'ndb:font-mono ndb:tabular-nums' => is_numeric($recordKey),
                                        ])>
                                            {{ (string) $recordKey }}
                                        </p>
                                        <span @class([
                                            'ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:sm:text-right',
                                            'ndb:text-amber-700 ndb:dark:text-amber-300' => $recordLoads > 1,
                                            'ndb:text-zinc-600 ndb:dark:text-zinc-300' => $recordLoads === 1,
                                        ])>
                                            <span class="ndb:text-zinc-400 ndb:sm:hidden">Retrieved </span
                                            >{{ number_format($recordLoads) }}
                                        </span>
                                        @if ($recordSourceCopy !== null)
                                            <x-newdebugbar::inspector-source-link
                                                :copy="$recordSourceCopy"
                                                :title="$sourceTitle($recordSource)"
                                                class="ndb:justify-self-start ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                            >
                                                <span class="ndb:sm:hidden">Source </span
                                                >{{ $sourceShortLabel($recordSource) }}
                                            </x-newdebugbar::inspector-source-link>
                                        @else
                                            <span class="ndb:text-[11px] ndb:text-zinc-400">—</span>
                                        @endif
                                    </article>
                                @endforeach

                                @if ($unidentifiedCount > 0)
                                    <article
                                        data-ndb-model-missing-identifiers
                                        class="ndb:grid ndb:min-w-0 ndb:gap-2 ndb:border-l-0 ndb:bg-transparent ndb:px-0 ndb:py-2.5 ndb:sm:grid-cols-[minmax(8rem,1fr)_5.5rem_minmax(10rem,1.25fr)] ndb:sm:items-center ndb:sm:gap-3"
                                    >
                                        <p class="ndb:text-[11px] ndb:font-semibold">—</p>
                                        <span class="ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-600 ndb:dark:text-zinc-300 ndb:sm:text-right">
                                            <span class="ndb:text-zinc-400 ndb:sm:hidden">Retrieved </span
                                            >{{ number_format($unidentifiedCount) }}
                                        </span>
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
                                A dash means the model identifier was unavailable. These retrievals are excluded from
                                the reload count.
                            </p>
                        @endif
                    </section>
                @endif

                @if ($writeOperations !== [])
                    <section
                        data-ndb-model-write-table
                        @class([
                            'ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white',
                            'ndb:mt-5' => $retrievalCount > 0,
                        ])
                    >
                        <x-newdebugbar::inspector-explanation
                            title="How this model changed"
                            description="Each row is one create, update, or delete completed during this request. If a write is unexpected, check whether the model is being saved more than once or earlier than intended."
                        />

                        <div class="ndb:mt-3 ndb:border-y ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
                            <div class="ndb:hidden ndb:grid-cols-[minmax(6rem,0.7fr)_minmax(5rem,0.55fr)_minmax(8rem,1.25fr)] ndb:gap-3 ndb:border-b ndb:border-zinc-200/90 ndb:py-2 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400 ndb:dark:border-zinc-800 ndb:sm:grid">
                                <span>Operation</span>
                                <span>Record</span>
                                <span>Source</span>
                            </div>

                            <div class="ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800">
                                @foreach ($writeOperations as $operation)
                                    @php
                                        $writeKey = $operation['key'] ?? null;
                                        $writeSource = $operation['callsite'] ?? null;
                                        $writeSourceCopy = $sourceCopy($writeSource);
                                    @endphp
                                    <article
                                        data-ndb-model-write-operation
                                        class="ndb:grid ndb:min-w-0 ndb:gap-2 ndb:border-l-0 ndb:bg-transparent ndb:px-0 ndb:py-2.5 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white ndb:sm:grid-cols-[minmax(6rem,0.7fr)_minmax(5rem,0.55fr)_minmax(8rem,1.25fr)] ndb:sm:items-center ndb:sm:gap-3"
                                    >
                                        <span class="ndb:text-[11px] ndb:font-semibold">
                                            <span class="ndb:text-zinc-400 ndb:sm:hidden">Operation </span
                                            >{{ $formatEvent((string) ($operation['event'] ?? 'changed')) }}
                                        </span>
                                        <span @class([
                                            'ndb:min-w-0 ndb:break-all ndb:text-[11px] ndb:font-semibold',
                                            'ndb:font-mono ndb:tabular-nums' => is_numeric($writeKey),
                                        ])>
                                            <span class="ndb:font-sans ndb:text-zinc-400 ndb:sm:hidden">Record </span
                                            >{{ $writeKey === null || $writeKey === '' ? '—' : (string) $writeKey }}
                                        </span>
                                        @if ($writeSourceCopy !== null)
                                            <x-newdebugbar::inspector-source-link
                                                :copy="$writeSourceCopy"
                                                :title="$sourceTitle($writeSource)"
                                                class="ndb:justify-self-start ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                            >
                                                <span class="ndb:sm:hidden">Source </span
                                                >{{ $sourceShortLabel($writeSource) }}
                                            </x-newdebugbar::inspector-source-link>
                                        @else
                                            <span class="ndb:text-[11px] ndb:text-zinc-400">—</span>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </div>

                        @if ($hiddenWriteCount > 0)
                            <p class="ndb:mt-2 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                Showing {{ number_format(count($writeOperations)) }} of {{ number_format($changeCount) }} writes.
                            </p>
                        @endif
                    </section>
                @endif

                @if ($retrievalCount === 0 && $writeOperations === [])
                    <x-newdebugbar::empty-state label="No model retrievals were captured for this context." />
                @endif
            </div>
        </template>

        <template x-if="modelDetailTab === 'source'">
            <div
                data-ndb-model-detail-panel="source"
                class="ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
            >
                <section
                    data-ndb-model-sources
                    class="ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
                >
                    @if (($group['sources'] ?? []) !== [])
                        <div
                            data-ndb-model-source-list
                            class="ndb:border-y ndb:border-zinc-200/90 ndb:dark:border-zinc-800"
                        >
                            <div
                                data-ndb-model-source-heading
                                aria-hidden="true"
                                class="ndb:hidden ndb:grid-cols-[minmax(0,1fr)_10rem] ndb:gap-3 ndb:border-b ndb:border-zinc-200/90 ndb:py-2 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400 ndb:dark:border-zinc-800 ndb:sm:grid"
                            >
                                <span>Source</span>
                                <span class="ndb:text-right">Activity</span>
                            </div>

                            <div class="ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800">
                                @foreach ($group['sources'] as $source)
                                    @php
                                        $callsite = $source['callsite'];
                                        $sourcePath = $sourceLocation($callsite);
                                        $isCompiledView = ($callsite['kind'] ?? null) === 'compiled_view';
                                        $templateFile = is_string($callsite['template_file'] ?? null) ? $callsite['template_file'] : null;
                                    @endphp
                                    <article
                                        data-ndb-model-source
                                        class="ndb:grid ndb:min-w-0 ndb:gap-2 ndb:border-l-0 ndb:bg-transparent ndb:px-0 ndb:py-3 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white ndb:sm:grid-cols-[minmax(0,1fr)_10rem] ndb:sm:items-start ndb:sm:gap-3"
                                    >
                                        <div class="ndb:min-w-0">
                                            @if ($isCompiledView && $templateFile !== null)
                                                <p
                                                    data-ndb-model-compiled-source
                                                    class="ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400"
                                                >
                                                    Blade template
                                                </p>
                                                <x-newdebugbar::inspector-source-link
                                                    data-ndb-model-source-path="template"
                                                    :copy="$templateFile"
                                                    class="ndb:mt-0.5 ndb:break-all ndb:text-xs"
                                                >
                                                    {{ $templateFile }}
                                                </x-newdebugbar::inspector-source-link>
                                                <p class="ndb:mt-2 ndb:text-[11px] ndb:text-zinc-400">
                                                    Compiled location
                                                </p>
                                                <p
                                                    data-ndb-model-source-path="compiled"
                                                    class="ndb:mt-0.5 ndb:break-all ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                                >
                                                    {{ $sourcePath ?? 'Source unavailable' }}
                                                </p>
                                            @else
                                                @if ($sourcePath !== null)
                                                    <x-newdebugbar::inspector-source-link
                                                        data-ndb-model-source-path="application"
                                                        :copy="$sourcePath"
                                                        class="ndb:break-all ndb:text-xs"
                                                    >
                                                        {{ $sourcePath }}
                                                    </x-newdebugbar::inspector-source-link>
                                                @else
                                                    <span class="ndb:text-[11px] ndb:text-zinc-400">Source unavailable</span>
                                                @endif
                                            @endif
                                        </div>
                                        <div class="ndb:sm:text-right">
                                            <p class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400 ndb:sm:hidden">
                                                Activity
                                            </p>
                                            <p class="ndb:mt-0.5 ndb:text-[11px] ndb:text-zinc-600 ndb:dark:text-zinc-300 ndb:sm:mt-0">
                                                {{ $formatActivity((int) ($source['retrieval_count'] ?? 0), (int) ($source['change_count'] ?? 0)) }}
                                            </p>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
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
                                Use the model identity and nearby application activity to narrow the location.
                            </p>
                        </div>
                    @endif
                </section>
            </div>
        </template>
    </div>
</div>
