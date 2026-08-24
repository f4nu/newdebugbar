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
        $channelLabels[$entry['channel_filter'] ?? '__unknown__'] = $entry['channel_label'] ?? 'Channel unavailable';
    }

    uksort($channelCounts, static fn (string $left, string $right): int => strnatcasecmp(
        $channelLabels[$left] ?? $left,
        $channelLabels[$right] ?? $right,
    ));
@endphp

@if ($groups !== [])
    <div x-init="initializeLogs()" class="ndb:space-y-3">
        <div
            data-ndb-log-controls
            class="ndb:space-y-3 ndb:border-b ndb:border-zinc-200 ndb:pb-3 ndb:dark:border-zinc-800"
        >
            <div>
                <p class="ndb:mb-1.5 ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                    Severity
                </p>
                <x-newdebugbar::filter-tabs label="Filter logs by severity">
                    <x-newdebugbar::filter-tab
                        data-ndb-log-filter="all"
                        @click="setLogLevel('all')"
                        ::aria-pressed="logLevel === 'all'"
                    >
                        All
                        <span
                            data-ndb-log-filter-count="all"
                            class="ndb:text-[10px] ndb:tabular-nums ndb:opacity-70"
                        >{{ $summary['count'] ?? count($groups) }}</span>
                    </x-newdebugbar::filter-tab>
                    @if (($summary['attention_count'] ?? 0) > 0)
                        <x-newdebugbar::filter-tab
                            data-ndb-log-filter="attention"
                            @click="setLogLevel('attention')"
                            ::aria-pressed="logLevel === 'attention'"
                        >
                            Needs attention
                            <span
                                data-ndb-log-filter-count="attention"
                                class="ndb:text-[10px] ndb:tabular-nums ndb:opacity-70"
                            >{{ $summary['attention_count'] }}</span>
                        </x-newdebugbar::filter-tab>
                    @endif
                    @foreach ($logLevels as $level)
                        <x-newdebugbar::filter-tab
                            data-ndb-log-filter="{{ $level }}"
                            @click="setLogLevel({{ \Illuminate\Support\Js::from($level) }})"
                            ::aria-pressed="logLevel === {{ \Illuminate\Support\Js::from($level) }}"
                        >
                            {{ ucfirst($level) }}
                            <span
                                data-ndb-log-filter-count="{{ $level }}"
                                class="ndb:text-[10px] ndb:tabular-nums ndb:opacity-70"
                            >{{ $levelCounts[$level] }}</span>
                        </x-newdebugbar::filter-tab>
                    @endforeach
                </x-newdebugbar::filter-tabs>
            </div>

            <div class="ndb:grid ndb:gap-3 ndb:sm:grid-cols-[minmax(0,1fr)_minmax(12rem,0.4fr)] ndb:sm:items-end">
                <label class="ndb:min-w-0">
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
                    <span class="ndb:mb-1.5 ndb:block ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Channel</span>
                    <select
                        data-ndb-log-channel-select
                        x-model="logChannel"
                        @change="setLogChannel($event.target.value)"
                        class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:font-semibold ndb:text-zinc-700 ndb:outline-none ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-100 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70 ndb:dark:text-zinc-300 ndb:dark:focus:border-indigo-600 ndb:dark:focus:ring-indigo-950"
                    >
                        <option value="all">All channels ({{ $summary['count'] ?? count($groups) }})</option>
                        @foreach ($channelCounts as $channel => $count)
                            <option value="{{ $channel }}">
                                {{ $channelLabels[$channel] ?? $channel }} ({{ $count }})
                            </option>
                        @endforeach
                    </select>
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

        <div x-ref="logList" data-ndb-log-list class="ndb:space-y-2.5">
            @foreach ($groups as $entry)
                <x-newdebugbar::log-entry :entry="$entry" />
            @endforeach
        </div>

        <div x-cloak x-show.important="visibleLogGroupCount === 0" data-ndb-log-filter-empty>
            <x-newdebugbar::empty-state label="No logs match these filters." />
        </div>
    </div>
@else
    <div data-ndb-log-empty>
        <x-newdebugbar::empty-state label="No log records were captured for this request." success />
    </div>
@endif
