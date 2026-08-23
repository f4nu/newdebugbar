{{-- Renders logical Laravel notifications with channel-level delivery diagnostics. --}}
@php
    $capturedNotificationItems = array_values($section['payload']['items'] ?? []);
    $notificationSummary = $section['summary'];
    $retainedMailIds = collect($profile['sections']['mail']['payload']['items'] ?? [])
        ->pluck('transport_message_id')
        ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
        ->values()
        ->all();
    $formatNotificationDestinations = static function (mixed $destination): array {
        if (is_scalar($destination)) {
            $label = trim((string) $destination);

            return $label === '' ? [] : [$label];
        }

        if (! is_array($destination) || $destination === []) {
            return [];
        }

        if (is_string($destination['type'] ?? null)) {
            $name = is_string($destination['name'] ?? null) && trim($destination['name']) !== ''
                ? trim($destination['name'])
                : null;
            $type = class_basename($destination['type']);
            $id = is_scalar($destination['id'] ?? null) ? (string) $destination['id'] : null;
            $context = $type.($id === null || $id === '' ? '' : ' #'.$id);

            return [$name === null || $name === $context ? $context : $name.' ('.$context.')'];
        }

        $parts = [];

        foreach ($destination as $key => $value) {
            if (is_string($key) && str_contains($key, '@')) {
                $name = is_scalar($value) ? trim((string) $value) : '';
                $parts[] = $name === '' ? $key : $name.' <'.$key.'>';
            } elseif (is_scalar($value) && trim((string) $value) !== '') {
                $parts[] = trim((string) $value);
            }
        }

        if ($parts !== []) {
            return $parts;
        }

        $encoded = json_encode($destination, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($encoded) ? [$encoded] : [];
    };
    $notificationGroups = collect($capturedNotificationItems)
        ->groupBy(fn (array $item, int $index): string => (string) ($item['group_id'] ?? 'notification-'.$index))
        ->values()
        ->map(function ($attempts, int $groupIndex) use ($retainedMailIds, $formatNotificationDestinations): array {
            $attempts = collect($attempts)->values();
            $first = $attempts->first();
            $notificationClass = (string) ($first['notification'] ?? 'Notification');
            $notifiableType = (string) ($first['notifiable_type'] ?? 'Notifiable');
            $notifiableId = $first['notifiable_id'] ?? null;
            $notificationSource = is_array($first['notification_source'] ?? null)
                ? $first['notification_source']
                : null;
            $notificationData = is_array($first['notification_data'] ?? null)
                ? $first['notification_data']
                : [];
            $deliveries = $attempts
                ->map(function (array $attempt, int $attemptIndex) use ($formatNotificationDestinations, $retainedMailIds): array {
                    $callsite = is_array($attempt['callsite'] ?? null) ? $attempt['callsite'] : null;
                    $stack = array_values(is_array($attempt['stack'] ?? null) ? $attempt['stack'] : []);
                    $channel = (string) ($attempt['channel'] ?? 'unknown');
                    $status = ($attempt['status'] ?? 'sent') === 'failed' ? 'failed' : 'sent';
                    $response = $attempt['response'] ?? null;
                    $failureData = is_array($attempt['failure_data'] ?? null) ? $attempt['failure_data'] : [];
                    $mailMessageId = is_string($attempt['mail_message_id'] ?? null)
                        ? $attempt['mail_message_id']
                        : null;
                    $responseSummary = null;
                    $failureMessage = is_string($attempt['exception_message'] ?? null)
                        ? $attempt['exception_message']
                        : null;

                    if ($failureMessage === null) {
                        foreach (['reason', 'message', 'error', 'detail'] as $failureKey) {
                            if (is_scalar($failureData[$failureKey] ?? null)) {
                                $failureMessage = trim((string) $failureData[$failureKey]);

                                break;
                            }
                        }
                    }

                    if ($mailMessageId !== null) {
                        $responseSummary = 'Mail message '.$mailMessageId;
                    } elseif (is_array($response) && is_scalar($response['message_id'] ?? null)) {
                        $responseSummary = 'Message '.(string) $response['message_id'];
                    } elseif (is_array($response) && is_scalar($response['provider'] ?? null)) {
                        $responseSummary = (string) $response['provider'].' response';
                    } elseif (is_string($attempt['response_type'] ?? null)) {
                        $responseSummary = class_basename($attempt['response_type']);
                    }

                    $destinationLabels = $formatNotificationDestinations($attempt['destination'] ?? null);
                    $destinationLabel = implode(', ', $destinationLabels);
                    $destinationCount = count($destinationLabels);
                    $channelLabel = str_replace(
                        ['Sms', 'Mms', 'Api', 'Url'],
                        ['SMS', 'MMS', 'API', 'URL'],
                        \Illuminate\Support\Str::headline(str_replace(['-', '_'], ' ', $channel)),
                    );
                    $evidenceSummary = match (true) {
                        $status === 'failed' => null,
                        $channel === 'mail' && $mailMessageId !== null => 'Mail transport accepted the message.',
                        $channel === 'database' => 'Notification stored in the database.',
                        $response !== null => 'Channel returned a provider response.',
                        default => 'Channel completed without throwing.',
                    };

                    return [
                        'execution' => $attemptIndex + 1,
                        'channel' => $channel,
                        'channel_label' => $channelLabel,
                        'status' => $status,
                        'status_label' => $status === 'failed' ? 'Failed' : 'Sent to channel',
                        'duration_ms' => (float) ($attempt['duration_ms'] ?? 0),
                        'destination' => $attempt['destination'] ?? null,
                        'destination_labels' => $destinationLabels,
                        'destination_label' => $destinationLabel === '' ? 'No destination resolved' : $destinationLabel,
                        'destination_summary_label' => match (true) {
                            $destinationCount === 0 => 'No destination resolved',
                            $destinationCount === 1 => $destinationLabel,
                            $channel === 'mail' => $destinationCount.' recipients',
                            default => $destinationCount.' destinations',
                        },
                        'destination_resolved' => $destinationCount > 0,
                        'response_type' => is_string($attempt['response_type'] ?? null) ? $attempt['response_type'] : null,
                        'response' => $response,
                        'response_summary' => $responseSummary,
                        'evidence_summary' => $evidenceSummary,
                        'failure_data' => $failureData,
                        'failure_message' => $failureMessage,
                        'exception_class' => is_string($attempt['exception_class'] ?? null) ? $attempt['exception_class'] : null,
                        'exception_message' => is_string($attempt['exception_message'] ?? null) ? $attempt['exception_message'] : null,
                        'exception_location' => is_array($attempt['exception_location'] ?? null) ? $attempt['exception_location'] : null,
                        'mail_message_id' => $mailMessageId,
                        'mail_available' => is_string($mailMessageId) && in_array($mailMessageId, $retainedMailIds, true),
                        'callsite' => $callsite,
                        'callsite_label' => $callsite === null ? 'Source unavailable' : $callsite['file'].':'.$callsite['line'],
                        'stack' => $stack,
                    ];
                })
                ->all();
            $sentCount = count(array_filter($deliveries, fn (array $delivery): bool => $delivery['status'] === 'sent'));
            $failedCount = count($deliveries) - $sentCount;
            $status = match (true) {
                $failedCount === 0 => 'sent',
                $sentCount === 0 => 'failed',
                default => 'partial',
            };
            $statusLabel = match ($status) {
                'sent' => 'Sent',
                'failed' => 'Failed',
                default => 'Needs attention',
            };
            $recipientTypeLabel = class_basename($notifiableType);

            if (is_scalar($notifiableId) && (string) $notifiableId !== '') {
                $recipientTypeLabel .= ' #'.(string) $notifiableId;
            }

            $recipientName = is_string($first['notifiable_name'] ?? null) && trim($first['notifiable_name']) !== ''
                ? trim($first['notifiable_name'])
                : null;
            $recipientLabel = $recipientName ?? $recipientTypeLabel;
            $recipientContextLabel = $recipientName === null ? null : $recipientTypeLabel;

            $channels = array_values(array_unique(array_column($deliveries, 'channel_label')));
            $deliverySummary = implode(', ', array_map(
                fn (array $delivery): string => $delivery['channel_label'].' '.($delivery['status'] === 'failed' ? 'failed' : 'sent'),
                $deliveries,
            ));
            $callsite = collect($deliveries)->pluck('callsite')->first(fn (mixed $value): bool => is_array($value));
            $stack = collect($deliveries)->pluck('stack')->first(fn (mixed $value): bool => is_array($value) && $value !== []) ?? [];
            $notificationId = is_string($first['notification_id'] ?? null) ? $first['notification_id'] : null;
            $sourceLabel = $notificationSource === null
                ? 'Unavailable'
                : basename(str_replace('\\', '/', $notificationSource['file'])).':'.$notificationSource['line'];
            $callsiteLabel = $callsite === null ? 'Source unavailable' : $callsite['file'].':'.$callsite['line'];
            $callsiteShortLabel = $callsite === null
                ? 'Unavailable'
                : basename(str_replace('\\', '/', $callsite['file'])).':'.$callsite['line'];

            return [
                'execution' => $groupIndex + 1,
                'group_id' => (string) ($first['group_id'] ?? 'notification-'.$groupIndex),
                'notification_id' => $notificationId,
                'notification' => $notificationClass,
                'label' => class_basename($notificationClass),
                'status' => $status,
                'status_label' => $statusLabel,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'duration_ms' => (float) collect($deliveries)->sum('duration_ms'),
                'delivery_count' => count($deliveries),
                'deliveries' => $deliveries,
                'channels' => $channels,
                'channels_label' => implode(', ', $channels),
                'channel_count_label' => count($channels).' '.\Illuminate\Support\Str::plural('channel', count($channels)),
                'delivery_summary' => $deliverySummary,
                'queueable' => (bool) ($first['queueable'] ?? false),
                'queue_connection' => is_string($first['queue_connection'] ?? null) ? $first['queue_connection'] : null,
                'queue_name' => is_string($first['queue_name'] ?? null) ? $first['queue_name'] : null,
                'execution_mode_label' => (bool) ($first['queueable'] ?? false)
                    ? 'Queueable'.(is_string($first['queue_name'] ?? null) ? ' on '.$first['queue_name'] : '')
                    : 'Synchronous',
                'locale' => is_string($first['locale'] ?? null) ? $first['locale'] : null,
                'notifiable_type' => $notifiableType,
                'notifiable_id' => $notifiableId,
                'recipient_name' => $recipientName,
                'recipient_label' => $recipientLabel,
                'recipient_context_label' => $recipientContextLabel,
                'routes' => is_array($first['routes'] ?? null) ? $first['routes'] : [],
                'notification_data' => $notificationData,
                'notification_source' => $notificationSource,
                'source_label' => $sourceLabel,
                'callsite' => $callsite,
                'callsite_label' => $callsiteLabel,
                'callsite_short_label' => $callsiteShortLabel,
                'stack' => $stack,
                'search' => mb_strtolower(implode(' ', array_filter([
                    $notificationClass,
                    $notifiableType,
                    is_scalar($notifiableId) ? (string) $notifiableId : null,
                    $recipientName,
                    $recipientLabel,
                    implode(' ', $channels),
                    $deliverySummary,
                    $statusLabel,
                    $callsite === null ? null : $callsite['file'],
                    ...array_column($deliveries, 'destination_label'),
                    ...array_column($deliveries, 'failure_message'),
                ]))),
            ];
        })
        ->all();
    $notificationFilters = [
        'all' => ['All', count($notificationGroups)],
        'attention' => ['Needs attention', count(array_filter($notificationGroups, fn (array $item): bool => $item['status'] !== 'sent'))],
        'sent' => ['Sent', count(array_filter($notificationGroups, fn (array $item): bool => $item['status'] === 'sent'))],
    ];
@endphp

<div
    data-ndb-notifications
    x-init="
        initializeNotifications(
            JSON.parse(atob($el.querySelector('[data-ndb-notification-payload]').textContent.trim())),
        )
    "
    class="ndb:space-y-4 ndb:lg:flex ndb:lg:min-h-0 ndb:lg:flex-1 ndb:lg:flex-col ndb:lg:space-y-0"
>
    <script type="application/json" data-ndb-notification-payload>
        {{ base64_encode(\Illuminate\Support\Js::encode($notificationGroups)) }}
    </script>

    @if ($notificationGroups !== [])
        <div
            data-ndb-notification-workspace
            class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/45 ndb:lg:grid ndb:lg:min-h-0 ndb:lg:flex-1 ndb:lg:grid-cols-[minmax(18rem,0.72fr)_minmax(0,1.68fr)] ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/35"
        >
            <div
                :class="notificationDetailOpen ? 'ndb:hidden ndb:lg:flex' : 'ndb:flex'"
                class="ndb:min-h-0 ndb:flex-col ndb:border-b ndb:border-zinc-200/90 ndb:lg:border-r ndb:lg:border-b-0 ndb:dark:border-zinc-800"
            >
                <div class="ndb:space-y-3 ndb:border-b ndb:border-zinc-200/90 ndb:p-3 ndb:dark:border-zinc-800">
                    <div class="ndb:flex ndb:items-start ndb:justify-between ndb:gap-3">
                        <p
                            data-ndb-notification-summary
                            class="ndb:min-w-0 ndb:text-xs ndb:font-semibold ndb:text-zinc-600 ndb:dark:text-zinc-300"
                        >
                            <span data-ndb-notification-summary-count class="ndb:block">
                                {{ number_format((int) ($notificationSummary['notification_count'] ?? count($notificationGroups))) }} {{ \Illuminate\Support\Str::plural('notification', (int) ($notificationSummary['notification_count'] ?? count($notificationGroups))) }}
                            </span>
                            <span
                                data-ndb-notification-summary-runtime
                                class="ndb:mt-0.5 ndb:block ndb:text-[11px] ndb:font-medium ndb:tabular-nums ndb:text-zinc-400"
                            >
                                {{ number_format((float) ($notificationSummary['duration_ms'] ?? 0), 2) }} ms total
                            </span>
                        </p>

                        <label class="ndb:relative ndb:shrink-0">
                            <span class="ndb:sr-only">Filter captured notifications</span>
                            <select
                                data-ndb-notification-filter
                                x-model="notificationFilter"
                                @change="setNotificationFilter($event.target.value)"
                                class="ndb:h-8 ndb:appearance-none ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/75 ndb:pr-8 ndb:pl-2.5 ndb:text-[11px] ndb:font-semibold ndb:outline-none ndb:transition ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900"
                            >
                                @foreach ($notificationFilters as $filter => [$label, $count])
                                    <option value="{{ $filter }}">{{ $label }} ({{ $count }})</option>
                                @endforeach
                            </select>
                            <x-newdebugbar::icon
                                name="chevron-down"
                                class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-2.5 ndb:size-3 ndb:-translate-y-1/2 ndb:text-zinc-400"
                            />
                        </label>
                    </div>

                    @if (count($notificationGroups) > 5)
                        <label class="ndb:relative ndb:block">
                            <span class="ndb:sr-only">Search captured notifications</span>
                            <input
                                data-ndb-notification-search
                                x-model="notificationSearch"
                                @input.debounce.100ms="applyNotificationView()"
                                type="search"
                                placeholder="Search notification or recipient"
                                class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-9 ndb:pl-3 ndb:text-xs ndb:outline-none ndb:transition ndb:placeholder:text-zinc-400 ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                            />
                            <x-newdebugbar::icon
                                name="search"
                                class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-3 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
                            />
                        </label>
                    @endif
                </div>

                <div
                    x-ref="notificationList"
                    data-ndb-notification-list
                    class="ndb-scrollbar ndb:min-h-0 ndb:flex-1 ndb:divide-y ndb:divide-zinc-200/80 ndb:overflow-y-auto ndb:dark:divide-zinc-800"
                >
                    @foreach ($notificationGroups as $notification)
                        <button
                            type="button"
                            data-ndb-notification-item="{{ $notification['execution'] }}"
                            data-ndb-execution="{{ $notification['execution'] }}"
                            data-ndb-status="{{ $notification['status'] }}"
                            data-ndb-search="{{ $notification['search'] }}"
                            @click="selectNotification({{ $notification['execution'] }})"
                            :aria-pressed="notificationSelected === {{ $notification['execution'] }}"
                            :class="notificationSelected === {{ $notification['execution'] }}
                                ? 'ndb:bg-indigo-50/65 ndb:dark:bg-indigo-950/20'
                                : 'ndb:hover:bg-zinc-50/80 ndb:dark:hover:bg-zinc-900/60'"
                            class="ndb:grid ndb:h-auto ndb:w-full ndb:grid-cols-[minmax(0,1fr)_auto] ndb:items-start ndb:gap-3 ndb:px-3 ndb:py-3 ndb:text-left ndb:transition-colors ndb:focus-visible:relative ndb:focus-visible:z-10 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
                        >
                            <span class="ndb:min-w-0">
                                <span class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold">{{ $notification['label'] }}</span>
                                <span class="ndb:mt-1 ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-1.5 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                    <span class="ndb:truncate">To {{ $notification['recipient_label'] }}</span>
                                    @if ($notification['recipient_context_label'] !== null)
                                        <span class="ndb:shrink-0 ndb:text-zinc-400">
                                            {{ $notification['recipient_context_label'] }}
                                        </span>
                                    @endif
                                </span>
                                <span
                                    data-ndb-notification-outcomes
                                    class="ndb:mt-1 ndb:flex ndb:flex-wrap ndb:gap-x-2 ndb:gap-y-0.5 ndb:text-[11px]"
                                >
                                    @foreach ($notification['deliveries'] as $delivery)
                                        <span @class(['ndb:font-medium',
                                            'ndb:text-zinc-400' => $delivery['status'] === 'sent',
                                            'ndb:text-red-600 ndb:dark:text-red-300' => $delivery['status'] === 'failed',
                                        ])>
                                            {{ $delivery['channel_label'] }} {{ $delivery['status'] === 'failed' ? 'failed' : 'sent' }}
                                        </span>
                                    @endforeach
                                </span>
                            </span>
                            <span class="ndb:text-right">
                                <span @class([
                                    'ndb:block ndb:text-[11px] ndb:font-bold',
                                    'ndb:text-emerald-600 ndb:dark:text-emerald-300' => $notification['status'] === 'sent',
                                    'ndb:text-amber-600 ndb:dark:text-amber-300' => $notification['status'] === 'partial',
                                    'ndb:text-red-600 ndb:dark:text-red-300' => $notification['status'] === 'failed',
                                ])>
                                    {{ $notification['status_label'] }}
                                </span>
                                <span class="ndb:mt-1 ndb:block ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400">
                                    {{ number_format($notification['duration_ms'], 2) }} ms
                                </span>
                            </span>
                        </button>
                    @endforeach
                </div>

                <div x-show.important="visibleNotificationCount === 0" class="ndb:p-3">
                    <x-newdebugbar::empty-state label="No notifications match these filters." />
                </div>
            </div>

            <section
                x-ref="notificationDetail"
                data-ndb-notification-detail
                aria-live="polite"
                aria-label="Selected notification details"
                tabindex="0"
                :class="notificationDetailOpen ? 'ndb:flex' : 'ndb:hidden ndb:lg:flex'"
                class="ndb-scrollbar ndb:min-h-[32rem] ndb:min-w-0 ndb:flex-col ndb:scroll-mt-20 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:lg:min-h-0 ndb:lg:overflow-y-auto"
            >
                <button
                    type="button"
                    data-ndb-notification-detail-back
                    @click="notificationDetailOpen = false"
                    class="ndb:m-2 ndb:inline-flex ndb:h-auto ndb:w-fit ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:p-2 ndb:text-xs ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:lg:hidden ndb:dark:text-indigo-300"
                >
                    <x-newdebugbar::icon name="chevron-down" size="3.5" class="ndb:rotate-90" />
                    Notifications
                </button>

                <template x-if="selectedNotification">
                    <div class="ndb:flex ndb:flex-col">
                        <x-newdebugbar::notification-header />

                        <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:justify-between ndb:gap-2 ndb:border-b ndb:border-zinc-200/90 ndb:px-4 ndb:py-2.5 ndb:dark:border-zinc-800">
                            <x-newdebugbar::filter-tabs label="Notification detail" class="ndb:min-w-0">
                                @foreach (['delivery' => ['Delivery', 'activity'], 'payload' => ['Payload', 'database'], 'source' => ['Source', 'code']] as $tab => [$label, $icon])
                                    <x-newdebugbar::filter-tab
                                        data-ndb-notification-detail-tab="{{ $tab }}"
                                        @click="setNotificationDetailTab({{ \Illuminate\Support\Js::from($tab) }})"
                                        ::aria-pressed="notificationDetailTab === {{ \Illuminate\Support\Js::from($tab) }}"
                                        aria-label="{{ $label }}"
                                        class="ndb:h-auto"
                                    >
                                        <x-newdebugbar::icon
                                            name="{{ $icon }}"
                                            size="3.5"
                                            data-ndb-notification-detail-tab-icon="{{ $tab }}"
                                            class="ndb:sm:hidden"
                                        />
                                        <span class="ndb:hidden ndb:sm:inline">{{ $label }}</span>
                                    </x-newdebugbar::filter-tab>
                                @endforeach
                            </x-newdebugbar::filter-tabs>

                            <label
                                data-ndb-notification-channel-control
                                x-show.important="
                                    notificationDetailTab === 'payload' && selectedNotification.delivery_count > 1
                                "
                                class="ndb:relative ndb:ml-auto ndb:shrink-0"
                            >
                                <span class="ndb:sr-only">Choose notification channel payload</span>
                                <select
                                    data-ndb-notification-channel
                                    x-model="notificationChannel"
                                    @change="setNotificationChannel($event.target.value)"
                                    class="ndb:h-8 ndb:max-w-44 ndb:appearance-none ndb:truncate ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/75 ndb:pr-8 ndb:pl-2.5 ndb:text-[11px] ndb:font-semibold ndb:outline-none ndb:transition ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900"
                                >
                                    <template
                                        x-for="delivery in selectedNotification.deliveries"
                                        :key="delivery.channel"
                                    >
                                        <option :value="delivery.channel" x-text="delivery.channel_label"></option>
                                    </template>
                                </select>
                                <x-newdebugbar::icon
                                    name="chevron-down"
                                    class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-2.5 ndb:size-3 ndb:-translate-y-1/2 ndb:text-zinc-400"
                                />
                            </label>
                        </div>

                        <x-newdebugbar::notification-delivery-panel />
                        <x-newdebugbar::notification-payload-panel />
                        <x-newdebugbar::notification-source-panel />
                    </div>
                </template>
            </section>
        </div>
    @else
        <x-newdebugbar::empty-state label="No notifications were sent." />
    @endif
</div>
