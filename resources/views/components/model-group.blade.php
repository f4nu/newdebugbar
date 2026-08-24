{{-- Renders one model context as a selection row for the focused inspector. --}}
@props(['group', 'index'])

@php
    $shortName = class_basename($group['model']);
    $retrievalCount = (int) ($group['load_count'] ?? 0);
    $changeCount = (int) ($group['change_count'] ?? 0);
    $repeatCount = (int) ($group['repeated_load_count'] ?? 0);
    $recordCount = (int) ($group['record_count'] ?? 0);
    $connection = is_string($group['connection'] ?? null) && $group['connection'] !== '' ? $group['connection'] : '—';
    $table = is_string($group['table'] ?? null) && $group['table'] !== '' ? $group['table'] : '—';
    $primarySource = $group['sources'][0]['callsite'] ?? null;
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
@endphp

<button
    type="button"
    data-ndb-model-group
    data-ndb-model-short-name="{{ $shortName }}"
    data-ndb-model-retrievals="{{ $retrievalCount }}"
    data-ndb-model-records="{{ $recordCount }}"
    data-ndb-model-repeats="{{ $repeatCount }}"
    data-ndb-model-writes="{{ $changeCount }}"
    wire:key="model-group-{{ $index }}"
    aria-controls="newdebugbar-model-detail"
    @click="selectModelGroup({{ $index }})"
    class="ndb:grid ndb:h-auto ndb:w-full ndb:min-w-0 ndb:cursor-pointer ndb:grid-cols-[minmax(0,1fr)_auto] ndb:items-center ndb:gap-x-3 ndb:gap-y-2 ndb:bg-transparent ndb:px-3 ndb:py-3 ndb:text-left ndb:text-xs ndb:text-zinc-950 ndb:transition-colors ndb:hover:bg-zinc-50/70 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500 ndb:dark:text-white ndb:dark:hover:bg-zinc-900/55 ndb:sm:grid-cols-[minmax(10rem,1.35fr)_5.5rem_4.75rem_7rem_minmax(8rem,1fr)_1rem] ndb:sm:px-4"
>
    <span class="ndb:col-start-1 ndb:row-start-1 ndb:min-w-0">
        <span data-ndb-model-name class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold">{{ $shortName }}</span>
        <span
            class="ndb:mt-0.5 ndb:block ndb:truncate ndb:font-mono ndb:text-[11px] ndb:text-zinc-400"
            title="{{ $connection }}, {{ $table }}"
        >
            {{ $connection }}, {{ $table }}
        </span>
    </span>

    <span class="ndb:col-span-2 ndb:row-start-2 ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:gap-x-4 ndb:gap-y-1 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400 ndb:sm:hidden">
        <span><span class="ndb:text-zinc-400">Retrieved</span>
            <strong
                class="ndb:font-semibold ndb:tabular-nums ndb:text-zinc-700 ndb:dark:text-zinc-300"
                >{{ number_format($retrievalCount) }}</strong
            ></span>
        <span><span class="ndb:text-zinc-400">Writes</span>
            <strong
                class="ndb:font-semibold ndb:tabular-nums ndb:text-zinc-700 ndb:dark:text-zinc-300"
                >{{ number_format($changeCount) }}</strong
            ></span>
        <span>
            <span class="ndb:text-zinc-400">Extra retrievals</span>
            <strong
                @class([
                    'ndb:font-semibold ndb:tabular-nums',
                    'ndb:text-amber-700 ndb:dark:text-amber-300' => $repeatCount > 0,
                    'ndb:text-zinc-700 ndb:dark:text-zinc-300' => $repeatCount === 0,
                ])
            >{{ number_format($repeatCount) }}</strong>
        </span>
        <span class="ndb:min-w-0 ndb:truncate" title="{{ $sourceTitle($primarySource) }}">
            <span class="ndb:text-zinc-400">Source</span>
            <code class="ndb:text-[11px] ndb:text-zinc-600 ndb:dark:text-zinc-300">{{ $sourceShortLabel($primarySource) }}</code>
        </span>
    </span>

    <span
        data-ndb-model-retrieved-column
        class="ndb:col-start-2 ndb:row-start-1 ndb:hidden ndb:text-right ndb:font-semibold ndb:tabular-nums ndb:sm:block"
    >
        {{ number_format($retrievalCount) }}
    </span>
    <span
        data-ndb-model-write-column
        class="ndb:col-start-3 ndb:row-start-1 ndb:hidden ndb:text-right ndb:font-semibold ndb:tabular-nums ndb:sm:block"
    >
        {{ number_format($changeCount) }}
    </span>
    <span
        data-ndb-model-extra-column
        @class([
            'ndb:col-start-4 ndb:row-start-1 ndb:hidden ndb:text-right ndb:font-semibold ndb:tabular-nums ndb:sm:block',
            'ndb:text-amber-700 ndb:dark:text-amber-300' => $repeatCount > 0,
            'ndb:text-zinc-500 ndb:dark:text-zinc-400' => $repeatCount === 0,
        ])
    >{{ number_format($repeatCount) }}</span>
    <code
        data-ndb-model-source-column
        class="ndb:col-start-5 ndb:row-start-1 ndb:hidden ndb:min-w-0 ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400 ndb:sm:block"
        title="{{ $sourceTitle($primarySource) }}"
    >{{ $sourceShortLabel($primarySource) }}</code>
    <x-newdebugbar::icon
        name="chevron-down"
        class="ndb:col-start-2 ndb:row-start-1 ndb:size-3.5 ndb:-rotate-90 ndb:text-zinc-400 ndb:sm:col-start-6"
    />
</button>
