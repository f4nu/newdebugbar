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
    $contextPreview = array_slice($contextFields, 0, 3);
    $callsite = is_array($entry['callsite'] ?? null) ? $entry['callsite'] : null;
    $relatedException = is_array($entry['related_exception'] ?? null) ? $entry['related_exception'] : null;
    $stack = array_values(is_array($entry['stack'] ?? null) ? $entry['stack'] : []);
    $occurrences = array_values(is_array($entry['occurrences'] ?? null) ? $entry['occurrences'] : []);
    $contextJson = json_encode(
        $entry['context'] ?? [],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
    ) ?: '{}';
    $rawRecord = array_intersect_key($entry, array_flip([
        'level',
        'message',
        'channel',
        'context',
        'callsite',
        'stack',
        'related_exception',
        'occurred_at',
        'at_ms',
        'lifecycle',
    ]));
    $rawJson = json_encode(
        $rawRecord,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
    ) ?: '{}';
    $sourceLabel = $callsite === null ? null : $callsite['file'].':'.$callsite['line'];
    $recordLabel = $repeatCount === 1 ? '#'.$firstSequence : '#'.$firstSequence.'–#'.$lastSequence;
    $requestTimeLabel = $firstAt === null
        ? 'Not captured'
        : '+'.number_format((float) $firstAt, 3).' ms';
    $requestTimeRange = $repeatCount > 1 && $lastAt !== null && $lastAt !== $firstAt
        ? $requestTimeLabel.' to +'.number_format((float) $lastAt, 3).' ms'
        : $requestTimeLabel;
    $wallTime = null;

    if (is_string($entry['first_occurred_at'] ?? null) && $entry['first_occurred_at'] !== '') {
        try {
            $wallTime = new DateTimeImmutable($entry['first_occurred_at']);
        } catch (Throwable) {
            $wallTime = null;
        }
    }

    $rowClasses = match ($level) {
        'warning' => 'ndb:border-amber-200 ndb:bg-amber-50/25 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/10',
        'error', 'critical', 'alert', 'emergency' => 'ndb:border-red-200 ndb:bg-red-50/25 ndb:dark:border-red-950 ndb:dark:bg-red-950/10',
        default => 'ndb:border-zinc-200 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/25',
    };
    $badgeClasses = match ($level) {
        'debug' => 'ndb:bg-zinc-100 ndb:text-zinc-600 ndb:dark:bg-zinc-800 ndb:dark:text-zinc-300',
        'info' => 'ndb:bg-blue-50 ndb:text-blue-700 ndb:dark:bg-blue-950/70 ndb:dark:text-blue-300',
        'notice' => 'ndb:bg-violet-50 ndb:text-violet-700 ndb:dark:bg-violet-950/70 ndb:dark:text-violet-300',
        'warning' => 'ndb:bg-amber-100 ndb:text-amber-800 ndb:dark:bg-amber-950 ndb:dark:text-amber-300',
        'error', 'critical', 'alert', 'emergency' => 'ndb:bg-red-100 ndb:text-red-700 ndb:dark:bg-red-950 ndb:dark:text-red-300',
        default => 'ndb:bg-zinc-100 ndb:text-zinc-600 ndb:dark:bg-zinc-800 ndb:dark:text-zinc-300',
    };
@endphp

<details
    data-ndb-log-entry
    data-ndb-log-level="{{ $level }}"
    data-ndb-log-attention="{{ $attention ? 'true' : 'false' }}"
    data-ndb-log-channel="{{ $entry['channel_filter'] ?? '__unknown__' }}"
    data-ndb-log-search-text="{{ $entry['search'] ?? '' }}"
    data-ndb-log-record-count="{{ $repeatCount }}"
    data-ndb-log-first-sequence="{{ $firstSequence }}"
    wire:key="log-entry-{{ $firstSequence }}"
    class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:p-0 {{ $rowClasses }}"
>
    <summary
        data-ndb-log-summary
        class="ndb:grid ndb:cursor-pointer ndb:list-none ndb:grid-cols-[auto_minmax(0,1fr)_auto] ndb:items-start ndb:gap-x-3 ndb:px-3 ndb:py-3 ndb:text-xs ndb:text-zinc-900 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-[-2px] ndb:focus-visible:outline-indigo-500 ndb:sm:px-4 ndb:dark:text-zinc-100"
    >
        <span
            data-ndb-log-severity
            class="ndb:mt-0.5 ndb:inline-flex ndb:min-w-16 ndb:justify-center ndb:rounded-md ndb:px-2 ndb:py-1 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-[0.08em] {{ $badgeClasses }}"
        >
            {{ $entry['level_label'] ?? ucfirst($level) }}
        </span>

        <span class="ndb:min-w-0">
            <span
                data-ndb-log-message
                class="ndb:block ndb:whitespace-pre-wrap ndb:break-words ndb:text-xs ndb:font-semibold ndb:leading-5 ndb:[overflow-wrap:anywhere] ndb:sm:text-[13px]"
            >{{ $entry['message'] === '' ? 'No message was captured.' : $entry['message'] }}</span>

            <span class="ndb:mt-2 ndb:flex ndb:flex-wrap ndb:gap-x-4 ndb:gap-y-1 ndb:text-[11px] ndb:leading-4 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                <span><span class="ndb:font-semibold ndb:text-zinc-400">Record</span> {{ $recordLabel }}</span>
                <span><span class="ndb:font-semibold ndb:text-zinc-400">Request</span> {{ $requestTimeRange }}</span>
                <span><span class="ndb:font-semibold ndb:text-zinc-400">Channel</span>
                    {{ $entry['channel_label'] }}</span>
                <span class="ndb:min-w-0">
                    <span class="ndb:font-semibold ndb:text-zinc-400">Source</span>
                    <span
                        class="ndb:break-all"
                        title="{{ $entry['callsite_label'] }}"
                    >{{ $entry['callsite_short_label'] }}</span>
                </span>
            </span>

            @if ($contextPreview !== [])
                <span data-ndb-log-context-preview class="ndb:mt-2 ndb:flex ndb:flex-wrap ndb:gap-1.5">
                    @foreach ($contextPreview as $field)
                        <span class="ndb:max-w-full ndb:rounded-md ndb:bg-zinc-100/80 ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:text-zinc-600 ndb:dark:bg-zinc-900/80 ndb:dark:text-zinc-300">
                            <span class="ndb:font-mono ndb:font-semibold">{{ $field['key'] }}</span>
                            <span class="ndb:break-words ndb:[overflow-wrap:anywhere]">{{ $field['preview'] }}</span>
                        </span>
                    @endforeach
                    @if (count($contextFields) > count($contextPreview))
                        <span class="ndb:rounded-md ndb:bg-zinc-100/80 ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:bg-zinc-900/80 ndb:dark:text-zinc-400">
                            {{ count($contextFields) - count($contextPreview) }} more
                        </span>
                    @endif
                </span>
            @endif

            @if ($attention || $repeatCount > 1)
                <span class="ndb:mt-2 ndb:flex ndb:flex-wrap ndb:gap-1.5 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-[0.08em]">
                    @if ($attention)
                        <span
                            data-ndb-log-attention-label
                            class="ndb:rounded-md ndb:bg-amber-100 ndb:px-2 ndb:py-1 ndb:text-amber-800 ndb:dark:bg-amber-950 ndb:dark:text-amber-300"
                        >
                            Needs attention
                        </span>
                    @endif
                    @if ($repeatCount > 1)
                        <span
                            data-ndb-log-repeat-label
                            class="ndb:rounded-md ndb:bg-zinc-100 ndb:px-2 ndb:py-1 ndb:text-zinc-600 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"
                        >
                            {{ $repeatCount }} records
                        </span>
                    @endif
                </span>
            @endif
        </span>

        <x-newdebugbar::icon
            name="chevron-down"
            class="ndb-details-chevron ndb:mt-1 ndb:size-3.5 ndb:text-zinc-400 ndb:transition-transform"
        />
    </summary>

    <div
        data-ndb-log-detail
        class="ndb:border-t ndb:border-zinc-200 ndb:bg-white/50 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/35"
    >
        <div
            data-ndb-log-actions
            class="ndb:flex ndb:flex-wrap ndb:gap-2 ndb:border-b ndb:border-zinc-200 ndb:px-3 ndb:py-2.5 ndb:dark:border-zinc-800 ndb:sm:px-4"
        >
            <button
                type="button"
                data-ndb-copy-log-message="{{ $firstSequence }}"
                @click="copyText(@js($entry['message']))"
                class="ndb:inline-flex ndb:h-8 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:bg-zinc-100 ndb:px-2.5 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-600 ndb:hover:bg-zinc-200 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300 ndb:dark:hover:bg-zinc-800"
            >
                <x-newdebugbar::icon name="copy" size="3.5" /> Copy message
            </button>
            <button
                type="button"
                data-ndb-copy-log-context="{{ $firstSequence }}"
                @click="copyText(@js($contextJson))"
                class="ndb:inline-flex ndb:h-8 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:bg-zinc-100 ndb:px-2.5 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-600 ndb:hover:bg-zinc-200 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300 ndb:dark:hover:bg-zinc-800"
            >
                <x-newdebugbar::icon name="copy" size="3.5" /> Copy context
            </button>
            @if ($sourceLabel !== null)
                <button
                    type="button"
                    data-ndb-copy-log-source="{{ $firstSequence }}"
                    @click="copyText(@js($sourceLabel))"
                    class="ndb:inline-flex ndb:h-8 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:bg-zinc-100 ndb:px-2.5 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-600 ndb:hover:bg-zinc-200 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300 ndb:dark:hover:bg-zinc-800"
                >
                    <x-newdebugbar::icon name="copy" size="3.5" /> Copy source
                </button>
            @endif
        </div>

        <div class="ndb:space-y-5 ndb:px-3 ndb:py-4 ndb:sm:px-4">
            <section data-ndb-log-context class="ndb:bg-transparent ndb:p-0 ndb:text-inherit" aria-label="Log context">
                <h3 class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                    Context
                </h3>
                @if ($contextFields !== [])
                    <dl class="ndb:mt-2 ndb:divide-y ndb:divide-zinc-200 ndb:overflow-hidden ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
                        @foreach ($contextFields as $field)
                            <div class="ndb:grid ndb:min-w-0 ndb:gap-1.5 ndb:px-3 ndb:py-2.5 ndb:sm:grid-cols-[minmax(8rem,0.6fr)_minmax(0,1.8fr)] ndb:sm:gap-4">
                                <dt class="ndb:break-words ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                    {{ $field['key'] }}
                                </dt>
                                <dd class="ndb:min-w-0 ndb:text-xs ndb:leading-5">
                                    @if ($field['structured'])
                                        <pre class="ndb-scrollbar ndb:overflow-x-auto ndb:whitespace-pre ndb:rounded-lg ndb:bg-zinc-100/75 ndb:p-2.5 ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-zinc-700 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"><code>{{ json_encode($field['value'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}</code></pre>
                                    @else
                                        <span class="ndb:whitespace-pre-wrap ndb:break-words ndb:[overflow-wrap:anywhere]">{{ $field['preview'] }}</span>
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @else
                    <p data-ndb-log-context-empty class="ndb:mt-2 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        No context was captured for this record.
                    </p>
                @endif
            </section>

            @if ($relatedException !== null)
                @php($exceptionSource = isset($relatedException['file'], $relatedException['line']) ? $relatedException['file'].':'.$relatedException['line'] : null)
                <section
                    data-ndb-log-related-exception
                    class="ndb:rounded-xl ndb:border ndb:border-red-200 ndb:bg-red-50/60 ndb:p-3 ndb:dark:border-red-950 ndb:dark:bg-red-950/20"
                    aria-label="Related exception"
                >
                    <div class="ndb:flex ndb:flex-wrap ndb:items-start ndb:gap-3">
                        <span class="ndb:grid ndb:size-8 ndb:shrink-0 ndb:place-items-center ndb:rounded-lg ndb:bg-red-100 ndb:text-red-700 ndb:dark:bg-red-950 ndb:dark:text-red-300">
                            <x-newdebugbar::icon name="warning" size="4" />
                        </span>
                        <div class="ndb:min-w-0 ndb:flex-1">
                            <h3 class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-red-700 ndb:dark:text-red-300">
                                Related exception
                            </h3>
                            <p class="ndb:mt-1 ndb:break-words ndb:font-mono ndb:text-[11px] ndb:font-semibold">
                                {{ $relatedException['class'] ?? 'Exception class unavailable' }}
                            </p>
                            <p class="ndb:mt-1 ndb:whitespace-pre-wrap ndb:break-words ndb:text-xs ndb:font-medium ndb:leading-5 ndb:[overflow-wrap:anywhere]">
                                {{ $relatedException['message'] ?? 'No exception message was captured.' }}
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
                            class="ndb:inline-flex ndb:h-8 ndb:items-center ndb:rounded-lg ndb:bg-red-100 ndb:px-2.5 ndb:text-[11px] ndb:font-bold ndb:text-red-700 ndb:hover:bg-red-200 ndb:focus-visible:outline-2 ndb:focus-visible:outline-red-500 ndb:dark:bg-red-950 ndb:dark:text-red-300"
                        >
                            Review in Exceptions
                        </button>
                    </div>
                </section>
            @endif

            <section data-ndb-log-timing class="ndb:bg-transparent ndb:p-0 ndb:text-inherit" aria-label="Log timing">
                <h3 class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                    Order and time
                </h3>
                <dl class="ndb:mt-2 ndb:grid ndb:gap-2 ndb:text-xs ndb:sm:grid-cols-3">
                    <div class="ndb:rounded-lg ndb:bg-zinc-100/70 ndb:px-3 ndb:py-2 ndb:dark:bg-zinc-900/70">
                        <dt class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                            Record
                        </dt>
                        <dd class="ndb:mt-1 ndb:font-semibold ndb:tabular-nums">{{ $recordLabel }}</dd>
                    </div>
                    <div class="ndb:rounded-lg ndb:bg-zinc-100/70 ndb:px-3 ndb:py-2 ndb:dark:bg-zinc-900/70">
                        <dt class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                            From request start
                        </dt>
                        <dd class="ndb:mt-1 ndb:font-semibold ndb:tabular-nums">{{ $requestTimeRange }}</dd>
                    </div>
                    <div class="ndb:rounded-lg ndb:bg-zinc-100/70 ndb:px-3 ndb:py-2 ndb:dark:bg-zinc-900/70">
                        <dt class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                            Captured at
                        </dt>
                        <dd
                            class="ndb:mt-1 ndb:font-semibold ndb:tabular-nums"
                            @if ($wallTime !== null) title="{{ $wallTime->format(DateTimeInterface::ATOM) }}" @endif
                        >
                            {{ $wallTime?->format('H:i:s.v') ?? 'Not captured' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section data-ndb-log-source class="ndb:bg-transparent ndb:p-0 ndb:text-inherit" aria-label="Log source">
                <h3 class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                    Application source
                </h3>
                @if ($sourceLabel !== null)
                    <p class="ndb:mt-2 ndb:break-all ndb:font-mono ndb:text-xs ndb:font-semibold">{{ $sourceLabel }}</p>
                @else
                    <p class="ndb:mt-2 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        No application source was captured.
                    </p>
                @endif
                @if ($stack !== [])
                    <ol class="ndb:mt-2 ndb:list-none ndb:space-y-1.5 ndb:p-0">
                        @foreach ($stack as $frame)
                            <li class="ndb:grid ndb:min-w-0 ndb:gap-1 ndb:rounded-lg ndb:bg-zinc-100/60 ndb:px-3 ndb:py-2 ndb:text-[11px] ndb:sm:grid-cols-[minmax(0,1.5fr)_minmax(7rem,0.7fr)] ndb:sm:gap-3 ndb:dark:bg-zinc-900/60">
                                <code class="ndb:break-all">{{ ($frame['file'] ?? 'Unknown file').':'.($frame['line'] ?? '?') }}</code>
                                <span class="ndb:break-words ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $frame['function'] ?? 'Unknown function' }}</span>
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
                    <ol class="ndb:mt-2 ndb:grid ndb:list-none ndb:gap-2 ndb:p-0 ndb:sm:grid-cols-2 ndb:lg:grid-cols-3">
                        @foreach ($occurrences as $occurrence)
                            <li class="ndb:rounded-lg ndb:bg-zinc-100/70 ndb:px-3 ndb:py-2 ndb:text-[11px] ndb:dark:bg-zinc-900/70">
                                <span class="ndb:font-semibold">Record #{{ $occurrence['sequence'] }}</span>
                                <span class="ndb:mt-0.5 ndb:block ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                    {{ $occurrence['at_ms'] === null ? 'Request time not captured' : '+'.number_format((float) $occurrence['at_ms'], 3).' ms' }}
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            <details
                data-ndb-log-raw
                class="ndb:group/raw ndb:border-t ndb:border-zinc-200 ndb:bg-transparent ndb:p-0 ndb:pt-3 ndb:dark:border-zinc-800"
            >
                <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-2 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500">
                    <span class="ndb:flex-1">Raw captured record</span>
                    <x-newdebugbar::icon
                        name="chevron-down"
                        class="ndb-details-chevron ndb:size-3.5 ndb:transition-transform"
                    />
                </summary>
                <pre class="ndb-code ndb-scrollbar ndb:mt-3 ndb:max-w-full"><code data-ndb-language="json">{{ $rawJson }}</code></pre>
            </details>
        </div>
    </div>
</details>
