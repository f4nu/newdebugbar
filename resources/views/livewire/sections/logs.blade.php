{{-- Renders a chronological diagnostic stream with structured log details. --}}
@php
    $groups = array_values($section['payload']['groups'] ?? $section['payload']['items'] ?? []);
    $summary = $section['summary'] ?? [];
    $levelCounts = is_array($summary['levels'] ?? null) ? $summary['levels'] : [];
    $channelCounts = is_array($summary['channels'] ?? null) ? $summary['channels'] : [];
    $levelOrder = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency', 'log'];
    $logLevels = array_values(array_filter(
        array_unique([...$levelOrder, ...array_keys($levelCounts)]),
        fn (string $level): bool => ($levelCounts[$level] ?? 0) > 0,
    ));
    $channelLabels = [];

    foreach ($groups as $entry) {
        $channelLabels[$entry['channel_filter'] ?? '__unknown__'] = $entry['channel_label'] ?? '—';
    }

    uksort($channelCounts, static fn (string $left, string $right): int => strnatcasecmp(
        $channelLabels[$left] ?? $left,
        $channelLabels[$right] ?? $right,
    ));
@endphp

@if ($groups !== [])
    <div x-init="initializeLogs()">
        <div
            data-ndb-log-controls
            class="ndb:space-y-3 ndb:border-b ndb:border-zinc-200/90 ndb:pb-3 ndb:dark:border-zinc-800"
        >
            <div class="ndb:grid ndb:grid-cols-2 ndb:gap-2 ndb:sm:grid-cols-[minmax(16rem,1fr)_minmax(10rem,0.36fr)_minmax(10rem,0.36fr)] ndb:sm:items-end ndb:sm:gap-3">
                <label class="ndb:col-span-2 ndb:min-w-0 ndb:sm:col-span-1">
                    <span class="ndb:mb-1.5 ndb:block ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Search logs</span>
                    <span class="ndb:relative ndb:block">
                        <x-newdebugbar::icon
                            name="search"
                            size="4"
                            class="ndb:pointer-events-none ndb:absolute ndb:left-3 ndb:top-2.5 ndb:text-zinc-400"
                        />
                        <input
                            data-ndb-log-search
                            x-model="logSearch"
                            @input.debounce.100ms="applyLogFilters()"
                            type="search"
                            placeholder="Message, context, channel, or source"
                            class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-3 ndb:pl-9 ndb:text-xs ndb:outline-none ndb:placeholder:text-zinc-400 ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-100 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70 ndb:dark:focus:border-indigo-600 ndb:dark:focus:ring-indigo-950"
                        />
                    </span>
                </label>
                <label class="ndb:min-w-0">
                    <span class="ndb:mb-1.5 ndb:block ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Severity</span>
                    <span class="ndb:relative ndb:block">
                        <select
                            data-ndb-log-level-select
                            x-model="logLevel"
                            @change="setLogLevel($event.target.value)"
                            class="ndb:h-9 ndb:w-full ndb:appearance-none ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-8 ndb:pl-3 ndb:text-xs ndb:font-semibold ndb:text-zinc-700 ndb:outline-none ndb:transition ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70 ndb:dark:text-zinc-300"
                        >
                            <option value="all">All severities ({{ $summary['count'] ?? count($groups) }})</option>
                            <option value="attention">Needs attention ({{ $summary['attention_count'] ?? 0 }})</option>
                            @foreach ($logLevels as $level)
                                <option value="{{ $level }}">{{ ucfirst($level) }} ({{ $levelCounts[$level] }})</option>
                            @endforeach
                        </select>
                        <x-newdebugbar::icon
                            name="chevron-down"
                            class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-2.5 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
                        />
                    </span>
                </label>
                <label class="ndb:min-w-0">
                    <span class="ndb:mb-1.5 ndb:block ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Channel</span>
                    <span class="ndb:relative ndb:block">
                        <select
                            data-ndb-log-channel-select
                            x-model="logChannel"
                            @change="setLogChannel($event.target.value)"
                            class="ndb:h-9 ndb:w-full ndb:appearance-none ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-8 ndb:pl-3 ndb:text-xs ndb:font-semibold ndb:text-zinc-700 ndb:outline-none ndb:transition ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70 ndb:dark:text-zinc-300"
                        >
                            <option value="all">All channels ({{ $summary['count'] ?? count($groups) }})</option>
                            @foreach ($channelCounts as $channel => $count)
                                <option value="{{ $channel }}">
                                    {{ $channelLabels[$channel] ?? $channel }} ({{ $count }})
                                </option>
                            @endforeach
                        </select>
                        <x-newdebugbar::icon
                            name="chevron-down"
                            class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-2.5 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
                        />
                    </span>
                </label>
            </div>

            <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:justify-between ndb:gap-2 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400">
                <p data-ndb-log-visible-summary aria-live="polite">
                    <span x-text="visibleLogCount"></span>
                    <span x-text="visibleLogCount === 1 ? 'record' : 'records'"></span>
                    in
                    <span x-text="visibleLogGroupCount"></span>
                    <span x-text="visibleLogGroupCount === 1 ? 'entry' : 'entries'"></span>
                </p>
                <p data-ndb-log-order>Oldest first</p>
            </div>
        </div>

        <div
            aria-hidden="true"
            class="ndb:hidden ndb:grid-cols-[5.5rem_minmax(0,1fr)_9.5rem_11rem_1rem] ndb:gap-x-3 ndb:px-4 ndb:py-2 ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:sm:grid"
        >
            <span>Severity</span>
            <span>Message</span>
            <span>Order and time</span>
            <span>Channel and source</span>
            <span></span>
        </div>

        <div
            x-ref="logList"
            data-ndb-log-list
            class="ndb:divide-y ndb:divide-zinc-200/90 ndb:border-y ndb:border-zinc-200/90 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800"
        >
            @foreach ($groups as $entry)
                <x-newdebugbar::log-entry :entry="$entry" />
            @endforeach
        </div>

        <div x-cloak x-show.important="visibleLogGroupCount === 0" data-ndb-log-filter-empty class="ndb:pt-3">
            <x-newdebugbar::empty-state label="No logs match these filters." />
        </div>
    </div>
@else
    <div data-ndb-log-empty>
        <x-newdebugbar::empty-state label="No log records were captured for this request." success />
    </div>
@endif
