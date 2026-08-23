{{-- Renders logical Laravel notifications with channel-level delivery diagnostics. --}}
@php
    $capturedNotificationItems = array_values($section['payload']['items'] ?? []);
    $notificationSummary = $section['summary'];
    $retainedMailIds = collect($profile['sections']['mail']['payload']['items'] ?? [])
        ->pluck('transport_message_id')
        ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
        ->values()
        ->all();
    $notificationGroups = collect($capturedNotificationItems)
        ->groupBy(fn (array $item, int $index): string => (string) ($item['group_id'] ?? 'notification-'.$index))
        ->values()
        ->map(function ($attempts, int $groupIndex) use ($retainedMailIds): array {
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
                ->map(function (array $attempt, int $attemptIndex): array {
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

                    if ($mailMessageId !== null) {
                        $responseSummary = 'Mail message '.$mailMessageId;
                    } elseif (is_array($response) && is_scalar($response['message_id'] ?? null)) {
                        $responseSummary = 'Message '.(string) $response['message_id'];
                    } elseif (is_array($response) && is_scalar($response['provider'] ?? null)) {
                        $responseSummary = (string) $response['provider'].' response';
                    } elseif (is_string($attempt['response_type'] ?? null)) {
                        $responseSummary = class_basename($attempt['response_type']);
                    }

                    return [
                        'execution' => $attemptIndex + 1,
                        'channel' => $channel,
                        'channel_label' => \Illuminate\Support\Str::headline(str_replace(['-', '_'], ' ', $channel)),
                        'status' => $status,
                        'status_label' => $status === 'failed' ? 'Failed' : 'Sent',
                        'duration_ms' => (float) ($attempt['duration_ms'] ?? 0),
                        'response_type' => is_string($attempt['response_type'] ?? null) ? $attempt['response_type'] : null,
                        'response' => $response,
                        'response_summary' => $responseSummary,
                        'failure_data' => $failureData,
                        'exception_class' => is_string($attempt['exception_class'] ?? null) ? $attempt['exception_class'] : null,
                        'exception_message' => is_string($attempt['exception_message'] ?? null) ? $attempt['exception_message'] : null,
                        'exception_location' => is_array($attempt['exception_location'] ?? null) ? $attempt['exception_location'] : null,
                        'mail_message_id' => $mailMessageId,
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
                default => 'Partially sent',
            };
            $recipientLabel = class_basename($notifiableType);

            if (is_scalar($notifiableId) && (string) $notifiableId !== '') {
                $recipientLabel .= ' #'.(string) $notifiableId;
            }

            $channels = array_values(array_unique(array_column($deliveries, 'channel_label')));
            $mailMessageId = collect($deliveries)->pluck('mail_message_id')->first(fn (mixed $id): bool => is_string($id) && $id !== '');
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
                'queued' => (bool) ($first['queued'] ?? false),
                'locale' => is_string($first['locale'] ?? null) ? $first['locale'] : null,
                'notifiable_type' => $notifiableType,
                'notifiable_id' => $notifiableId,
                'recipient_label' => $recipientLabel,
                'routes' => is_array($first['routes'] ?? null) ? $first['routes'] : [],
                'notification_data' => $notificationData,
                'notification_source' => $notificationSource,
                'source_label' => $sourceLabel,
                'callsite' => $callsite,
                'callsite_label' => $callsiteLabel,
                'callsite_short_label' => $callsiteShortLabel,
                'stack' => $stack,
                'mail_message_id' => $mailMessageId,
                'mail_available' => is_string($mailMessageId) && in_array($mailMessageId, $retainedMailIds, true),
                'search' => mb_strtolower(implode(' ', array_filter([
                    $notificationClass,
                    $notifiableType,
                    is_scalar($notifiableId) ? (string) $notifiableId : null,
                    implode(' ', $channels),
                    $statusLabel,
                    $callsite === null ? null : $callsite['file'],
                    ...array_column($deliveries, 'exception_message'),
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
    x-init="initializeNotifications(JSON.parse(atob($el.querySelector('[data-ndb-notification-payload]').textContent)))"
    class="ndb:space-y-4 ndb:lg:flex ndb:lg:min-h-0 ndb:lg:flex-1 ndb:lg:flex-col ndb:lg:space-y-0"
>
    <script type="application/json" data-ndb-notification-payload>{{ base64_encode(\Illuminate\Support\Js::encode($notificationGroups)) }}</script>

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
                                <span
                                    class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold"
                                    >{{ $notification['label'] }}</span
                                >
                                <span
                                    class="ndb:mt-1 ndb:block ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                >
                                    To {{ $notification['recipient_label'] }}
                                </span>
                                <span class="ndb:mt-1 ndb:block ndb:truncate ndb:text-[11px] ndb:text-zinc-400">
                                    {{ $notification['channels_label'] }}
                                </span>
                            </span>
                            <span class="ndb:text-right">
                                <span
                                    @class ([
                                        'ndb:block ndb:text-[11px] ndb:font-bold',
                                        'ndb:text-emerald-600 ndb:dark:text-emerald-300' => $notification['status'] === 'sent',
                                        'ndb:text-amber-600 ndb:dark:text-amber-300' => $notification['status'] === 'partial',
                                        'ndb:text-red-600 ndb:dark:text-red-300' => $notification['status'] === 'failed',
                                    ])
                                >
                                    {{ $notification['status_label'] }}
                                </span>
                                <span
                                    class="ndb:mt-1 ndb:block ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
                                >
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
                        <header class="ndb:border-b ndb:border-zinc-200/90 ndb:p-4 ndb:dark:border-zinc-800">
                            <div class="ndb:flex ndb:min-w-0 ndb:items-start ndb:justify-between ndb:gap-3">
                                <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-2">
                                    <span
                                        data-ndb-notification-status
                                        :class="{
                                            'ndb:bg-emerald-100 ndb:text-emerald-700 ndb:dark:bg-emerald-950 ndb:dark:text-emerald-300':
                                                selectedNotification.status === 'sent',
                                            'ndb:bg-amber-100 ndb:text-amber-700 ndb:dark:bg-amber-950 ndb:dark:text-amber-300':
                                                selectedNotification.status === 'partial',
                                            'ndb:bg-red-100 ndb:text-red-700 ndb:dark:bg-red-950 ndb:dark:text-red-300':
                                                selectedNotification.status === 'failed',
                                        }"
                                        class="ndb:rounded-md ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-bold"
                                        x-text="selectedNotification.status_label"
                                    ></span>
                                    <span
                                        x-show="selectedNotification.queued"
                                        class="ndb:rounded-md ndb:bg-zinc-100 ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-400"
                                    >
                                        Queueable
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    data-ndb-notification-view-mail
                                    x-show.important="selectedNotification.mail_available"
                                    @click="openNotificationMail(selectedNotification.mail_message_id)"
                                    class="ndb:inline-flex ndb:h-8 ndb:shrink-0 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-2.5 ndb:text-[11px] ndb:font-bold ndb:text-zinc-700 ndb:transition ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-200 ndb:dark:hover:bg-zinc-800"
                                >
                                    <x-newdebugbar::icon name="mail" size="3.5" />
                                    View email
                                </button>
                            </div>
                            <h3
                                data-ndb-notification-detail-title
                                class="ndb:mt-3 ndb:break-all ndb:text-base ndb:font-bold ndb:leading-6"
                                x-text="selectedNotification.label"
                            ></h3>

                            <div
                                data-ndb-notification-metadata
                                class="ndb:mt-2 ndb:overflow-hidden ndb:rounded-lg ndb:bg-zinc-50/85 ndb:ring-1 ndb:ring-inset ndb:ring-zinc-200/70 ndb:dark:bg-zinc-900/65 ndb:dark:ring-zinc-800"
                            >
                                <dl
                                    class="ndb:grid ndb:grid-cols-2 ndb:border-b ndb:border-zinc-200/70 ndb:dark:border-zinc-800"
                                >
                                    <div
                                        class="ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-1.5 ndb:border-r ndb:border-zinc-200/70 ndb:px-2.5 ndb:py-1.5 ndb:dark:border-zinc-800"
                                    >
                                        <dt
                                            class="ndb:shrink-0 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:dark:text-indigo-300"
                                        >
                                            To
                                        </dt>
                                        <dd
                                            :title="selectedNotification.notifiable_type"
                                            class="ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                            x-text="selectedNotification.recipient_label"
                                        ></dd>
                                    </div>
                                    <div
                                        class="ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-1.5 ndb:px-2.5 ndb:py-1.5"
                                    >
                                        <dt class="ndb:shrink-0 ndb:text-[11px] ndb:font-medium ndb:text-zinc-400">
                                            Via
                                        </dt>
                                        <dd
                                            :title="selectedNotification.channels_label"
                                            class="ndb:truncate ndb:text-[11px] ndb:font-medium ndb:text-zinc-600 ndb:dark:text-zinc-300"
                                            x-text="selectedNotification.channels_label"
                                        ></dd>
                                    </div>
                                </dl>
                                <dl class="ndb:grid ndb:grid-cols-3">
                                    <div
                                        class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-1.5 ndb:border-r ndb:border-zinc-200/70 ndb:px-1.5 ndb:py-1.5 ndb:dark:border-zinc-800"
                                    >
                                        <span
                                            class="ndb:flex ndb:size-6 ndb:shrink-0 ndb:items-center ndb:justify-center ndb:rounded-md ndb:bg-indigo-100/80 ndb:text-indigo-600 ndb:dark:bg-indigo-950/70 ndb:dark:text-indigo-300"
                                        >
                                            <x-newdebugbar::icon name="clock" size="3" />
                                        </span>
                                        <div class="ndb:min-w-0">
                                            <dt class="ndb:text-[11px] ndb:font-medium ndb:leading-3 ndb:text-zinc-400">
                                                Runtime
                                            </dt>
                                            <dd
                                                class="ndb:truncate ndb:text-[11px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                                x-text="selectedNotification.duration_ms.toFixed(2) + ' ms'"
                                            ></dd>
                                        </div>
                                    </div>
                                    <div
                                        class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-1.5 ndb:border-r ndb:border-zinc-200/70 ndb:px-1.5 ndb:py-1.5 ndb:dark:border-zinc-800"
                                    >
                                        <span
                                            class="ndb:flex ndb:size-6 ndb:shrink-0 ndb:items-center ndb:justify-center ndb:rounded-md ndb:bg-emerald-100/80 ndb:text-emerald-600 ndb:dark:bg-emerald-950/70 ndb:dark:text-emerald-300"
                                        >
                                            <x-newdebugbar::icon name="activity" size="3" />
                                        </span>
                                        <div class="ndb:min-w-0">
                                            <dt class="ndb:text-[11px] ndb:font-medium ndb:leading-3 ndb:text-zinc-400">
                                                Attempts
                                            </dt>
                                            <dd
                                                class="ndb:truncate ndb:text-[11px] ndb:font-bold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                                x-text="selectedNotification.delivery_count"
                                            ></dd>
                                        </div>
                                    </div>
                                    <div
                                        class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-1.5 ndb:px-1.5 ndb:py-1.5"
                                    >
                                        <span
                                            class="ndb:flex ndb:size-6 ndb:shrink-0 ndb:items-center ndb:justify-center ndb:rounded-md ndb:bg-amber-100/80 ndb:text-amber-700 ndb:dark:bg-amber-950/70 ndb:dark:text-amber-300"
                                        >
                                            <x-newdebugbar::icon name="code" size="3" />
                                        </span>
                                        <div class="ndb:min-w-0">
                                            <dt class="ndb:text-[11px] ndb:font-medium ndb:leading-3 ndb:text-zinc-400">
                                                Source
                                            </dt>
                                            <dd
                                                :title="selectedNotification.callsite_label"
                                                class="ndb:truncate ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                                                x-text="selectedNotification.callsite_short_label"
                                            ></dd>
                                        </div>
                                    </div>
                                </dl>
                            </div>
                        </header>

                        <div
                            class="ndb:flex ndb:flex-wrap ndb:items-center ndb:justify-between ndb:gap-2 ndb:border-b ndb:border-zinc-200/90 ndb:px-4 ndb:py-2.5 ndb:dark:border-zinc-800"
                        >
                            <x-newdebugbar::filter-tabs label="Notification detail" class="ndb:min-w-0">
                                @foreach (['delivery' => ['Delivery', 'activity'], 'data' => ['Data', 'database'], 'source' => ['Source', 'code']] as $tab => [$label, $icon])
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
                                    notificationDetailTab === 'data' && selectedNotification.delivery_count > 1
                                "
                                class="ndb:relative ndb:ml-auto ndb:shrink-0"
                            >
                                <span class="ndb:sr-only">Choose notification channel data</span>
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
                        <x-newdebugbar::notification-data-panel />
                        <x-newdebugbar::notification-source-panel />
                    </div>
                </template>
            </section>
        </div>
    @else
        <x-newdebugbar::empty-state label="No notifications were sent." />
    @endif
</div>
