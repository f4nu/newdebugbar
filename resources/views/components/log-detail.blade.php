@props(['entry'])

@php
    $level = (string) ($entry['level'] ?? 'log');
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
    $channelLabel = (string) ($entry['channel_label'] ?? 'No channel');
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

<div class="ndb:flex ndb:flex-col">
    <x-newdebugbar::inspector-detail-header layout="wrap">
        <x-slot:title>
            <div class="ndb:min-w-0">
                <h3
                    data-ndb-log-details-title
                    class="ndb:bg-transparent ndb:whitespace-pre-wrap ndb:break-words ndb:text-sm ndb:font-bold ndb:leading-5 ndb:text-zinc-900 ndb:[overflow-wrap:anywhere] ndb:dark:text-zinc-100"
                >{{ ($entry['message'] ?? '') === '' ? '—' : $entry['message'] }}</h3>
                <p class="ndb:mt-1 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400">
                    Log {{ $recordLabel }}
                </p>
            </div>
        </x-slot:title>
    </x-newdebugbar::inspector-detail-header>

    <div data-ndb-log-detail-groups class="ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800">
        <section data-ndb-log-detail-group="summary" class="ndb:p-4">
            <x-newdebugbar::inspector-facts columns="4" :bordered="false">
                <x-newdebugbar::inspector-fact label="Severity">
                    <x-slot:value class="ndb:text-xs ndb:font-bold {{ $severityClasses }}">
                        {{ $entry['level_label'] ?? ucfirst($level) }}
                    </x-slot:value>
                </x-newdebugbar::inspector-fact>
                <x-newdebugbar::inspector-fact label="Channel">
                    <x-slot:value class="ndb:truncate ndb:text-xs ndb:font-semibold" title="{{ $channelLabel }}">
                        {{ $channelLabel }}
                    </x-slot:value>
                </x-newdebugbar::inspector-fact>
                <x-newdebugbar::inspector-fact label="From request start">
                    <x-slot:value class="ndb:text-xs ndb:font-semibold ndb:tabular-nums">
                        {{ $requestTimeRange }}
                    </x-slot:value>
                </x-newdebugbar::inspector-fact>
                <x-newdebugbar::inspector-fact label="Captured at">
                    <x-slot:value
                        class="ndb:text-xs ndb:font-semibold ndb:tabular-nums"
                        title="{{ $wallTime?->format(DateTimeInterface::ATOM) ?? '' }}"
                    >
                        {{ $wallTime?->format('H:i:s.v') ?? '—' }}
                    </x-slot:value>
                </x-newdebugbar::inspector-fact>
            </x-newdebugbar::inspector-facts>
        </section>

        @if ($relatedException !== null)
            @php($exceptionSource = isset($relatedException['file'], $relatedException['line']) ? $relatedException['file'].':'.$relatedException['line'] : null)
            <section
                data-ndb-log-detail-group="related-exception"
                data-ndb-log-related-exception
                class="ndb:bg-transparent ndb:p-4"
                aria-label="Related exception"
            >
                <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:justify-between ndb:gap-3">
                    <h4 class="ndb:text-xs ndb:font-bold ndb:text-red-700 ndb:dark:text-red-300">Related exception</h4>
                    <x-newdebugbar::inspector-action
                        icon="external-link"
                        data-ndb-log-review-exception
                        @click="navigateToSection('exceptions')"
                        class="ndb:bg-transparent"
                    >
                        Review in Exceptions
                    </x-newdebugbar::inspector-action>
                </div>
                <div class="ndb:mt-3 ndb:min-w-0">
                    <code class="ndb:block ndb:break-words ndb:bg-transparent ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:text-zinc-900 ndb:dark:text-zinc-100">
                        {{ $relatedException['class'] ?? '—' }}
                    </code>
                    <p class="ndb:mt-1 ndb:whitespace-pre-wrap ndb:break-words ndb:bg-transparent ndb:text-xs ndb:font-medium ndb:leading-5 ndb:text-zinc-700 ndb:[overflow-wrap:anywhere] ndb:dark:text-zinc-200">{{ ($relatedException['message'] ?? '') === '' ? '—' : trim((string) $relatedException['message']) }}</p>
                    @if ($exceptionSource !== null)
                        <p class="ndb:mt-1 ndb:break-all ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                            {{ $exceptionSource }}
                        </p>
                    @endif
                </div>
            </section>
        @endif

        @if ($contextFields !== [])
            <section data-ndb-log-detail-group="context" data-ndb-log-context aria-label="Log context" class="ndb:bg-transparent ndb:p-4">
                <h4 class="ndb:text-xs ndb:font-bold ndb:text-zinc-800 ndb:dark:text-zinc-100">Context</h4>
                <x-newdebugbar::inspector-definition-list class="ndb:mt-2">
                    @foreach ($contextFields as $field)
                        <x-newdebugbar::inspector-definition-row :label="$field['key']">
                            <x-slot:value>
                                @if ($field['structured'])
                                    <x-newdebugbar::code-block
                                        language="json"
                                        class="ndb:max-w-full"
                                    >{{ json_encode($field['value'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}</x-newdebugbar::code-block>
                                @else
                                    <span class="ndb:break-words ndb:[overflow-wrap:anywhere]">{{ $field['preview'] }}</span>
                                @endif
                            </x-slot:value>
                        </x-newdebugbar::inspector-definition-row>
                    @endforeach
                </x-newdebugbar::inspector-definition-list>
            </section>
        @endif

        @if ($repeatCount > 1)
            <section data-ndb-log-detail-group="occurrences" data-ndb-log-occurrences aria-label="Repeated log occurrences" class="ndb:p-4">
                <div class="ndb:flex ndb:items-baseline ndb:justify-between ndb:gap-3 ndb:border-b ndb:border-zinc-200/90 ndb:pb-2 ndb:dark:border-zinc-800">
                    <h4 class="ndb:text-xs ndb:font-bold ndb:text-zinc-800 ndb:dark:text-zinc-100">Occurrences</h4>
                    <span class="ndb:text-[11px] ndb:font-medium ndb:tabular-nums ndb:text-zinc-400">
                        {{ $repeatCount }} records
                    </span>
                </div>
                <ol class="ndb:list-none ndb:divide-y ndb:divide-zinc-200/90 ndb:p-0 ndb:dark:divide-zinc-800">
                    @foreach ($occurrences as $occurrence)
                        <li class="ndb:grid ndb:grid-cols-[5rem_minmax(0,1fr)] ndb:gap-3 ndb:py-2 ndb:text-[11px]">
                            <span class="ndb:font-semibold ndb:tabular-nums">#{{ $occurrence['sequence'] }}</span>
                            <span class="ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                {{ $occurrence['at_ms'] === null ? '—' : '+'.number_format((float) $occurrence['at_ms'], 3).' ms' }}
                            </span>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        <section data-ndb-log-detail-group="source" data-ndb-log-source class="ndb:bg-transparent ndb:p-0">
            <h4 class="ndb:px-4 ndb:pt-4 ndb:text-xs ndb:font-bold ndb:text-zinc-800 ndb:dark:text-zinc-100">Source</h4>
            <x-newdebugbar::inspector-source-panel
                :frames="\Illuminate\Support\Js::from($stack)"
                columns="1"
                empty-label="No application stack was captured for this log entry."
                class="ndb:bg-transparent ndb:pt-2"
            >
                <x-newdebugbar::inspector-source-fact label="Application call site">
                    <x-slot:value>
                        @if ($sourceLabel !== null)
                            <x-newdebugbar::inspector-source-link :copy="$sourceLabel">
                                <x-slot:value>{{ $sourceLabel }}</x-slot:value>
                            </x-newdebugbar::inspector-source-link>
                        @else
                            <span>—</span>
                        @endif
                    </x-slot:value>
                </x-newdebugbar::inspector-source-fact>
            </x-newdebugbar::inspector-source-panel>
        </section>
    </div>
</div>
