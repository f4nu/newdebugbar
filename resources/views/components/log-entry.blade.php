@props(['entry'])

@php
    $level = (string) ($entry['level'] ?? 'log');
    $attention = (bool) ($entry['attention'] ?? false);
    $repeatCount = max(1, (int) ($entry['repeat_count'] ?? 1));
    $firstSequence = (int) ($entry['first_sequence'] ?? $entry['sequence'] ?? 1);
    $lastSequence = (int) ($entry['last_sequence'] ?? $firstSequence);
    $firstAt = $entry['first_at_ms'] ?? $entry['at_ms'] ?? null;
    $lastAt = $entry['last_at_ms'] ?? $firstAt;
    $contextFields = array_values($entry['context_fields'] ?? []);
    $callsite = is_array($entry['callsite'] ?? null) ? $entry['callsite'] : null;
    $relatedException = is_array($entry['related_exception'] ?? null) ? $entry['related_exception'] : null;
    $stack = array_values(is_array($entry['stack'] ?? null) ? $entry['stack'] : []);
    $occurrences = array_values(is_array($entry['occurrences'] ?? null) ? $entry['occurrences'] : []);
    $sourceLabel = isset($callsite['file'], $callsite['line']) ? $callsite['file'].':'.$callsite['line'] : null;
    $sourceShortLabel = $sourceLabel === null
        ? '—'
        : basename(str_replace('\\', '/', (string) $callsite['file'])).':'.$callsite['line'];
    $channelLabel = is_string($entry['channel_label'] ?? null) && $entry['channel_label'] !== ''
        ? $entry['channel_label']
        : '—';
    $recordLabel = $repeatCount === 1 ? '#'.$firstSequence : '#'.$firstSequence.'–#'.$lastSequence;
    $requestTimeLabel = $firstAt === null
        ? '—'
        : '+'.number_format((float) $firstAt, 3).' ms';
    $lastRequestTimeLabel = $lastAt === null
        ? '—'
        : '+'.number_format((float) $lastAt, 3).' ms';
    $requestTimeRange = $repeatCount > 1 && $lastAt !== null && $lastAt !== $firstAt
        ? $requestTimeLabel.' to '.$lastRequestTimeLabel
        : $requestTimeLabel;
    $wallTime = null;

    if (is_string($entry['first_occurred_at'] ?? null) && $entry['first_occurred_at'] !== '') {
        try {
            $wallTime = new DateTimeImmutable($entry['first_occurred_at']);
        } catch (Throwable) {
            $wallTime = null;
        }
    }

    $severityClasses = match ($level) {
        'info' => 'ndb:text-blue-700 ndb:dark:text-blue-300',
        'notice' => 'ndb:text-violet-700 ndb:dark:text-violet-300',
        'warning' => 'ndb:text-amber-700 ndb:dark:text-amber-300',
        'error', 'critical', 'alert', 'emergency' => 'ndb:text-red-700 ndb:dark:text-red-300',
        default => 'ndb:text-zinc-500 ndb:dark:text-zinc-400',
    };
@endphp

<article
    data-ndb-log-entry
    data-ndb-log-level="{{ $level }}"
    data-ndb-log-attention="{{ $attention ? 'true' : 'false' }}"
    data-ndb-log-channel="{{ $entry['channel_filter'] ?? '__unknown__' }}"
    data-ndb-log-search-text="{{ $entry['search'] ?? '' }}"
    data-ndb-log-record-count="{{ $repeatCount }}"
    data-ndb-log-first-sequence="{{ $firstSequence }}"
    wire:key="log-entry-{{ $firstSequence }}"
    x-id="['newdebugbar-log-details-trigger', 'newdebugbar-log-details-popover', 'newdebugbar-log-details-title']"
    @keydown.escape="
        if (logDetailSequence === {{ $firstSequence }}) {
            $event.stopPropagation();
            $event.preventDefault();
            logDetailSequence = null;
            document.getElementById($id('newdebugbar-log-details-trigger'))?.focus();
        }
    "
    class="ndb:border-0 ndb:bg-transparent ndb:p-0"
>
    <div
        data-ndb-log-summary
        :class="logDetailSequence === {{ $firstSequence }}
            ? 'ndb:bg-indigo-50/55 ndb:dark:bg-indigo-950/25'
            : 'ndb:hover:bg-zinc-50/75 ndb:dark:hover:bg-zinc-900/55'"
        class="ndb:grid ndb:grid-cols-[4.75rem_minmax(0,1fr)_auto] ndb:items-start ndb:gap-x-3 ndb:px-3 ndb:py-3 ndb:text-xs ndb:text-zinc-900 ndb:transition-colors ndb:sm:grid-cols-[5.5rem_minmax(0,1fr)_9.5rem_11rem_5.75rem] ndb:sm:px-4 ndb:sm:py-3.5 ndb:dark:text-zinc-100"
    >
        <span class="ndb:row-span-3 ndb:min-w-0 ndb:sm:row-span-1">
            <span
                data-ndb-log-severity
                class="ndb:block ndb:bg-transparent ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:leading-4 ndb:tracking-[0.08em] {{ $severityClasses }}"
            >{{ $entry['level_label'] ?? ucfirst($level) }}</span>
        </span>

        <span
            data-ndb-log-message
            class="ndb:col-start-2 ndb:block ndb:min-w-0 ndb:whitespace-pre-wrap ndb:break-words ndb:text-xs ndb:font-semibold ndb:leading-5 ndb:[overflow-wrap:anywhere] ndb:sm:row-start-1 ndb:sm:text-[13px]"
        >{{ ($entry['message'] ?? '') === '' ? '—' : $entry['message'] }}</span>

        <span class="ndb:col-start-2 ndb:row-start-2 ndb:mt-1.5 ndb:min-w-0 ndb:text-[11px] ndb:leading-4 ndb:text-zinc-500 ndb:sm:col-start-3 ndb:sm:row-start-1 ndb:sm:mt-0 ndb:dark:text-zinc-400">
            <span class="ndb:block ndb:font-mono ndb:font-semibold ndb:tabular-nums ndb:text-zinc-600 ndb:dark:text-zinc-300">{{ $recordLabel }}</span>
            <span class="ndb:mt-0.5 ndb:block ndb:tabular-nums">{{ $requestTimeLabel }}</span>
            @if ($repeatCount > 1 && $lastAt !== null && $lastAt !== $firstAt)
                <span class="ndb:block ndb:tabular-nums">to {{ $lastRequestTimeLabel }}</span>
            @endif
            @if ($repeatCount > 1)
                <span data-ndb-log-repeat-label class="ndb:mt-0.5 ndb:block ndb:bg-transparent ndb:font-medium">
                    {{ $repeatCount }} records
                </span>
            @endif
        </span>

        <span class="ndb:col-start-2 ndb:row-start-3 ndb:mt-1.5 ndb:min-w-0 ndb:text-[11px] ndb:leading-4 ndb:text-zinc-500 ndb:sm:col-start-4 ndb:sm:row-start-1 ndb:sm:mt-0 ndb:dark:text-zinc-400">
            <span
                class="ndb:block ndb:truncate ndb:font-semibold ndb:text-zinc-600 ndb:dark:text-zinc-300"
                title="{{ $channelLabel }}"
            >{{ $channelLabel }}</span>
            <span
                class="ndb:mt-0.5 ndb:block ndb:break-words ndb:[overflow-wrap:anywhere]"
                title="{{ $sourceLabel ?? '—' }}"
            >{{ $sourceShortLabel }}</span>
        </span>

        <button
            type="button"
            data-ndb-log-details-trigger
            :id="$id('newdebugbar-log-details-trigger')"
            :aria-controls="$id('newdebugbar-log-details-popover')"
            :aria-expanded="logDetailSequence === {{ $firstSequence }}"
            @click.stop="logDetailSequence = logDetailSequence === {{ $firstSequence }} ? null : {{ $firstSequence }}"
            :class="logDetailSequence === {{ $firstSequence }}
                ? 'ndb:bg-indigo-100/75 ndb:dark:bg-indigo-950/70'
                : 'ndb:hover:bg-indigo-50 ndb:dark:hover:bg-indigo-950/50'"
            class="ndb:col-start-3 ndb:row-span-3 ndb:row-start-1 ndb:inline-flex ndb:h-8 ndb:items-center ndb:justify-self-end ndb:rounded-lg ndb:px-2.5 ndb:py-1.5 ndb:text-[11px] ndb:font-bold ndb:text-indigo-700 ndb:transition ndb:focus-visible:relative ndb:focus-visible:z-10 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:sm:col-start-5 ndb:sm:row-span-1 ndb:dark:text-indigo-300"
        >
            View details
        </button>
    </div>

    <template x-if="logDetailSequence === {{ $firstSequence }}">
        <template x-teleport="#newdebugbar">
            <x-newdebugbar::popover-surface
                :anchored="true"
                x-anchor.bottom-end.offset.12.fixed="document.getElementById($id('newdebugbar-log-details-trigger'))"
                @click.outside="logDetailSequence = null"
                @keydown.escape.stop.prevent="
                    logDetailSequence = null;
                    document.getElementById($id('newdebugbar-log-details-trigger'))?.focus();
                "
                data-ndb-log-details-popover
                ::id="$id('newdebugbar-log-details-popover')"
                ::aria-labelledby="$id('newdebugbar-log-details-title')"
                ::style="{ visibility: $anchor.x !== 0 || $anchor.y !== 0 ? 'visible' : 'hidden' }"
                role="region"
                width-class="ndb:w-[min(44rem,calc(100vw-3rem))]"
                surface-class="ndb:p-0"
                arrow-class="ndb:hidden"
                class="ndb:pointer-events-auto ndb:border-0 ndb:bg-transparent ndb:p-0 ndb:text-inherit"
            >
                <header class="ndb:border-b ndb:border-zinc-200/90 ndb:px-4 ndb:py-3 ndb:dark:border-zinc-800">
                    <p
                        data-ndb-log-details-title
                        ::id="$id('newdebugbar-log-details-title')"
                        class="ndb:bg-transparent ndb:text-[13px] ndb:font-bold ndb:text-zinc-900 ndb:dark:text-zinc-100"
                    >
                        Log {{ $recordLabel }}
                    </p>
                    <div class="ndb:mt-1.5 ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-x-4 ndb:gap-y-1 ndb:text-[11px]">
                        <span class="ndb:font-bold ndb:uppercase ndb:tracking-[0.08em] {{ $severityClasses }}">
                            {{ $entry['level_label'] ?? ucfirst($level) }}
                        </span>
                        <span class="ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $channelLabel }}</span>
                    </div>
                </header>

                <div data-ndb-log-detail class="ndb-scrollbar ndb:max-h-80 ndb:overflow-y-auto ndb:overscroll-contain">
                    <div class="ndb:space-y-5 ndb:px-4 ndb:py-4 ndb:sm:py-5">
                        @if ($relatedException !== null)
                            @php($exceptionSource = isset($relatedException['file'], $relatedException['line']) ? $relatedException['file'].':'.$relatedException['line'] : null)
                            <section
                                data-ndb-log-related-exception
                                class="ndb:border-y ndb:border-red-200/80 ndb:bg-transparent ndb:py-3 ndb:dark:border-red-950"
                                aria-label="Related exception"
                            >
                                <div class="ndb:grid ndb:min-w-0 ndb:items-start ndb:gap-2.5 ndb:sm:grid-cols-[8.5rem_minmax(0,1fr)_auto] ndb:sm:gap-4">
                                    <h3 class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-red-700 ndb:dark:text-red-300">
                                        Related exception
                                    </h3>
                                    <div class="ndb:min-w-0">
                                        <p class="ndb:break-words ndb:font-mono ndb:text-[11px] ndb:font-semibold">
                                            {{ $relatedException['class'] ?? '—' }}
                                        </p>
                                        <p class="ndb:mt-1 ndb:break-words ndb:text-xs ndb:font-medium ndb:leading-5 ndb:[overflow-wrap:anywhere]">
                                            <span class="ndb:whitespace-pre-wrap">{{ ($relatedException['message'] ?? '') === '' ? '—' : $relatedException['message'] }}</span>
                                        </p>
                                        @if ($exceptionSource !== null)
                                            <p class="ndb:mt-1 ndb:break-all ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                                {{ $exceptionSource }}
                                            </p>
                                        @endif
                                    </div>
                                    <button
                                        type="button"
                                        data-ndb-log-review-exception
                                        @click="navigateToSection('exceptions')"
                                        class="ndb:inline-flex ndb:h-auto ndb:self-start ndb:justify-self-start ndb:bg-transparent ndb:px-0 ndb:py-0 ndb:text-xs ndb:font-bold ndb:leading-4 ndb:text-indigo-700 ndb:underline-offset-4 ndb:hover:bg-transparent ndb:hover:underline ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:sm:justify-self-end ndb:dark:text-indigo-300"
                                    >
                                        Review in Exceptions
                                    </button>
                                </div>
                            </section>
                        @endif

                        @if ($contextFields !== [])
                            <section
                                data-ndb-log-context
                                class="ndb:bg-transparent ndb:p-0 ndb:text-inherit"
                                aria-label="Log context"
                            >
                                <h3 class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    Context
                                </h3>
                                <dl class="ndb:mt-2 ndb:divide-y ndb:divide-zinc-200/90 ndb:border-y ndb:border-zinc-200/90 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                                    @foreach ($contextFields as $field)
                                        <div class="ndb:grid ndb:min-w-0 ndb:gap-1.5 ndb:py-2.5 ndb:sm:grid-cols-[minmax(8rem,0.6fr)_minmax(0,1.8fr)] ndb:sm:gap-4">
                                            <dt class="ndb:break-words ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                                {{ $field['key'] }}
                                            </dt>
                                            <dd class="ndb:min-w-0 ndb:text-xs ndb:leading-5">
                                                @if ($field['structured'])
                                                    <x-newdebugbar::code-block
                                                        language="json"
                                                        class="ndb:max-w-full"
                                                    >{{ json_encode($field['value'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}</x-newdebugbar::code-block>
                                                @else
                                                    <span class="ndb:whitespace-pre-wrap ndb:break-words ndb:[overflow-wrap:anywhere]">{{ $field['preview'] }}</span>
                                                @endif
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </section>
                        @endif

                        <section
                            data-ndb-log-timing
                            class="ndb:bg-transparent ndb:p-0 ndb:text-inherit"
                            aria-label="Log timing"
                        >
                            <h3 class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                Order and time
                            </h3>
                            <dl class="ndb:mt-2 ndb:grid ndb:gap-x-6 ndb:gap-y-3 ndb:text-xs ndb:sm:grid-cols-3">
                                <div>
                                    <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                        Order
                                    </dt>
                                    <dd class="ndb:mt-1 ndb:font-semibold ndb:tabular-nums">{{ $recordLabel }}</dd>
                                </div>
                                <div>
                                    <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                        From request start
                                    </dt>
                                    <dd class="ndb:mt-1 ndb:font-semibold ndb:tabular-nums">{{ $requestTimeRange }}</dd>
                                </div>
                                <div>
                                    <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                        Captured at
                                    </dt>
                                    <dd
                                        class="ndb:mt-1 ndb:font-semibold ndb:tabular-nums"
                                        @if ($wallTime !== null) title="{{ $wallTime->format(DateTimeInterface::ATOM) }}" @endif
                                    >
                                        {{ $wallTime?->format('H:i:s.v') ?? '—' }}
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section
                            data-ndb-log-source
                            class="ndb:bg-transparent ndb:p-0 ndb:text-inherit"
                            aria-label="Log source"
                        >
                            <h3 class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                Source
                            </h3>
                            <p class="ndb:mt-2 ndb:break-all ndb:font-mono ndb:text-xs ndb:font-semibold">
                                {{ $sourceLabel ?? '—' }}
                            </p>
                            @if ($stack !== [])
                                <p class="ndb:mt-3 ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    Call stack
                                </p>
                                <ol class="ndb:mt-1.5 ndb:list-none ndb:divide-y ndb:divide-zinc-200/90 ndb:border-y ndb:border-zinc-200/90 ndb:p-0 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                                    @foreach ($stack as $frame)
                                        @php($frameSource = isset($frame['file'], $frame['line']) ? $frame['file'].':'.$frame['line'] : ($frame['file'] ?? '—'))
                                        <li class="ndb:grid ndb:min-w-0 ndb:gap-1 ndb:py-2 ndb:text-[11px] ndb:sm:grid-cols-[minmax(0,1.5fr)_minmax(7rem,0.7fr)] ndb:sm:gap-3">
                                            <code class="ndb:break-all">{{ $frameSource }}</code>
                                            <span class="ndb:break-words ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $frame['function'] ?? '—' }}</span>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </section>

                        @if ($repeatCount > 1)
                            <section data-ndb-log-occurrences aria-label="Repeated log occurrences">
                                <h3 class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    Occurrences
                                </h3>
                                <ol class="ndb:mt-2 ndb:list-none ndb:divide-y ndb:divide-zinc-200/90 ndb:border-y ndb:border-zinc-200/90 ndb:p-0 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                                    @foreach ($occurrences as $occurrence)
                                        <li class="ndb:grid ndb:grid-cols-[5rem_minmax(0,1fr)] ndb:gap-3 ndb:py-2 ndb:text-[11px]">
                                            <span class="ndb:font-mono ndb:font-semibold">#{{ $occurrence['sequence'] }}</span>
                                            <span class="ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                                {{ $occurrence['at_ms'] === null ? '—' : '+'.number_format((float) $occurrence['at_ms'], 3).' ms' }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ol>
                            </section>
                        @endif
                    </div>
                </div>
            </x-newdebugbar::popover-surface>
        </template>
    </template>
</article>
