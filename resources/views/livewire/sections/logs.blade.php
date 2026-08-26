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
    <div x-init="initializeLogs()" class="ndb:flex ndb:min-h-0 ndb:flex-1 ndb:flex-col">
        <x-newdebugbar::inspector-workspace mode="stream" frame="top" data-ndb-log-workspace>
            <x-slot:controls data-ndb-log-controls>
                <x-newdebugbar::inspector-list-controls :show-search="true">
                    <x-slot:leading>
                        <p
                            data-ndb-log-visible-summary
                            aria-live="polite"
                            class="ndb:text-xs ndb:font-semibold ndb:text-zinc-600 ndb:dark:text-zinc-300"
                        >
                            <span x-text="visibleLogCount"></span>
                            <span x-text="visibleLogCount === 1 ? 'record' : 'records'"></span>
                            in
                            <span x-text="visibleLogGroupCount"></span>
                            <span x-text="visibleLogGroupCount === 1 ? 'entry' : 'entries'"></span>
                        </p>
                    </x-slot:leading>

                    <x-slot:search>
                        <x-newdebugbar::search-field
                            label="Search logs"
                            placeholder="Message, context, channel, or source"
                            data-ndb-log-search
                            x-model="logSearch"
                            @input.debounce.100ms="applyLogFilters()"
                        />
                    </x-slot:search>

                    <x-slot:filter>
                        <x-newdebugbar::select-field
                            label="Filter logs by severity"
                            data-ndb-log-level-select
                            x-model="logLevel"
                            @change="setLogLevel($event.target.value)"
                        >
                            <option value="all">All severities ({{ $summary['count'] ?? count($groups) }})</option>
                            <option value="attention">Needs attention ({{ $summary['attention_count'] ?? 0 }})</option>
                            @foreach ($logLevels as $level)
                                <option value="{{ $level }}">{{ ucfirst($level) }} ({{ $levelCounts[$level] }})</option>
                            @endforeach
                        </x-newdebugbar::select-field>
                    </x-slot:filter>

                    <x-slot:secondaryFilter>
                        <x-newdebugbar::select-field
                            label="Filter logs by channel"
                            data-ndb-log-channel-select
                            x-model="logChannel"
                            @change="setLogChannel($event.target.value)"
                        >
                            <option value="all">All channels ({{ $summary['count'] ?? count($groups) }})</option>
                            @foreach ($channelCounts as $channel => $count)
                                @php
                                    $channelLabel = (string) ($channelLabels[$channel] ?? $channel);
                                    $channelLabel = in_array(strtolower($channelLabel), ['', 'null', '__unknown__'], true)
                                        ? 'No channel'
                                        : $channelLabel;
                                @endphp
                                <option value="{{ $channel }}">{{ $channelLabel }} ({{ $count }})</option>
                            @endforeach
                        </x-newdebugbar::select-field>
                    </x-slot:secondaryFilter>
                </x-newdebugbar::inspector-list-controls>
            </x-slot:controls>

            <x-slot:header
                aria-hidden="true"
                class="ndb:hidden ndb:grid-cols-[5.5rem_minmax(0,1fr)_9.5rem_11rem_5.75rem] ndb:gap-x-3 ndb:px-4 ndb:py-2 ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:sm:grid"
            >
                <span>Severity</span>
                <span>Message</span>
                <span>Order and time</span>
                <span>Channel and source</span>
                <span class="ndb:text-right">Details</span>
            </x-slot:header>

            <x-slot:body
                x-ref="logList"
                data-ndb-log-list
                class="ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800"
            >
                @foreach ($groups as $entry)
                    <x-newdebugbar::log-entry :entry="$entry" />
                @endforeach
            </x-slot:body>

            <x-slot:empty x-cloak x-show.important="visibleLogGroupCount === 0" data-ndb-log-filter-empty>
                <x-newdebugbar::empty-state label="No logs match these filters." />
            </x-slot:empty>
        </x-newdebugbar::inspector-workspace>
    </div>
@else
    <div data-ndb-log-empty>
        <x-newdebugbar::empty-state label="No log records were captured for this request." success />
    </div>
@endif
