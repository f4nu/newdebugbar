@php
    $mailMessage = [
        'subject' => 'Your Kyoto itinerary is ready',
        'to' => ['elise@example.com', 'theo@example.com'],
        'from' => ['Morrow <journeys@morrow.test>'],
        'cc' => [],
        'bcc' => [],
        'reply_to' => ['concierge@morrow.test'],
        'sender' => 'journeys@morrow.test',
        'return_path' => 'bounces@morrow.test',
        'date' => 'Tue, 25 Aug 2026 10:24:13 +0200',
        'transport_message_id' => '<kyoto-4821@morrow.test>',
        'mailer' => 'transactional',
        'transport' => 'smtp',
        'connection' => 'mailpit',
        'queue' => null,
        'job_id' => null,
        'delay_seconds' => 0,
        'status' => 'sent',
        'status_label' => 'Sent',
        'duration_ms' => 42.18,
        'delivery_label' => 'SMTP via mailpit',
        'lifecycle' => 'request',
        'attachment_count' => 1,
        'attachment_summary_label' => '1 attachment',
        'attachments' => [[
            'name' => 'kyoto-itinerary.pdf',
            'content_type' => 'application/pdf',
            'size_label' => '184 KB',
            'download_url' => '#',
        ]],
        'attachment_bodies_omitted' => 0,
        'attachment_metadata_omitted' => 0,
        'addresses_omitted' => 0,
        'truncated' => false,
        'headers' => "Message-ID: <kyoto-4821@morrow.test>\nContent-Type: text/html; charset=UTF-8",
        'source' => 'App\\Mail\\TripWorkspaceReady',
        'callsite_label' => 'app/Actions/Trips/RefreshTripWorkspace.php:87',
        'callsite_short_label' => 'RefreshTripWorkspace.php:87',
        'stack' => [
            ['file' => 'app/Actions/Trips/RefreshTripWorkspace.php', 'line' => 87, 'function' => 'RefreshTripWorkspace->sendMail'],
        ],
        'has_html' => true,
        'has_text' => true,
        'eml_url' => '#',
        'related_profile_id' => null,
        'related_section' => null,
        'related_label' => 'Open queued job',
    ];

    $deliveries = [
        [
            'channel' => 'mail',
            'channel_label' => 'Mail',
            'destination_resolved' => true,
            'destination_label' => 'elise@example.com',
            'destination_summary_label' => 'elise@example.com',
            'destination_labels' => ['elise@example.com'],
            'status' => 'sent',
            'status_label' => 'Sent',
            'duration_ms' => 38.42,
            'delay_seconds' => 0,
            'failure_message' => null,
            'exception_class' => null,
            'exception_location' => null,
            'evidence_summary' => 'The mail transport accepted the message.',
            'mail_available' => true,
            'mail_message_id' => 14,
            'response' => ['message_id' => 'kyoto-4821'],
            'response_type' => 'Transport response',
            'failure_data' => null,
        ],
        [
            'channel' => 'database',
            'channel_label' => 'Database',
            'destination_resolved' => true,
            'destination_label' => 'App\\Models\\Traveler #24',
            'destination_summary_label' => 'Traveler #24',
            'destination_labels' => ['Traveler #24'],
            'status' => 'sent',
            'status_label' => 'Stored',
            'duration_ms' => 1.76,
            'delay_seconds' => 0,
            'failure_message' => null,
            'exception_class' => null,
            'exception_location' => null,
            'evidence_summary' => 'Laravel stored the notification record.',
            'mail_available' => false,
            'mail_message_id' => null,
            'response' => ['id' => '7d81f3be-57d0-4d55-a3cb-8e84870df764'],
            'response_type' => 'Database record',
            'failure_data' => null,
        ],
    ];

    $notification = [
        'label' => 'Trip workspace refreshed',
        'notification' => 'App\\Notifications\\TripWorkspaceRefreshed',
        'notification_id' => '7d81f3be-57d0-4d55-a3cb-8e84870df764',
        'notification_source' => ['file' => 'app/Notifications/TripWorkspaceRefreshed.php', 'line' => 12],
        'recipient_label' => 'Elise Martin',
        'recipient_context_label' => 'Traveler #24',
        'notifiable_type' => 'App\\Models\\Traveler',
        'deliveries' => $deliveries,
        'delivery_count' => 2,
        'channel_count_label' => '2 channels',
        'status' => 'sent',
        'status_label' => 'Sent',
        'duration_ms' => 40.18,
        'delay_seconds' => 0,
        'execution_mode_label' => 'Synchronous',
        'lifecycle' => 'request',
        'locale' => 'en',
        'notification_data' => ['trip_id' => 1, 'title' => 'Kyoto in autumn'],
        'routes' => [],
        'callsite_label' => 'app/Actions/Trips/RefreshTripWorkspace.php:150',
        'callsite_short_label' => 'RefreshTripWorkspace.php:150',
        'stack' => [
            ['file' => 'app/Actions/Trips/RefreshTripWorkspace.php', 'line' => 150, 'function' => 'RefreshTripWorkspace->notify'],
        ],
        'related_profile_id' => null,
        'related_section' => null,
        'related_label' => 'Open queued job',
    ];
@endphp

<div
    x-data="{
        selectedMailMessage: @js($mailMessage),
        mailDetailTab: 'message',
        selectedNotification: @js($notification),
        notificationDetailOpen: true,
        notificationDetailTab: 'delivery',
        notificationChannel: 'mail',
        get selectedNotificationDelivery() {
            return this.selectedNotification.deliveries.find((delivery) => delivery.channel === this.notificationChannel) ?? null;
        },
        formatMailAddresses(addresses) { return addresses.join(', '); },
        setMailDetailTab(tab) { this.mailDetailTab = tab; },
        mailPreviewUrl() { return '#'; },
        openRelatedProfile() {},
        setNotificationDetailTab(tab) { this.notificationDetailTab = tab; },
        setNotificationChannel(channel) { this.notificationChannel = channel; },
        formatNotificationEvidence(value, fallback = 'No evidence was captured.') {
            return value == null ? fallback : JSON.stringify(value, null, 2);
        },
        openNotificationMail() {},
    }"
    class="ndb:space-y-5"
>
    @component('newdebugbar::studio.component', ['component' => 'mail-actions', 'components' => $components])
        <div
            x-init="$nextTick(() => ($el.querySelector('[data-ndb-mail-actions]').open = true))"
            class="ndb:flex ndb:min-h-48 ndb:justify-end ndb:p-2"
        >
            <x-newdebugbar::mail-actions />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'mail-header', 'components' => $components])
        <x-newdebugbar::mail-header />
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'mail-message-details', 'components' => $components])
        <x-newdebugbar::mail-message-details />
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'mail-source-panel', 'components' => $components])
        <div x-data="{ mailDetailTab: 'source' }">
            <x-newdebugbar::mail-source-panel />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'notification-delivery-panel', 'components' => $components])
        <div x-data="{ notificationDetailTab: 'delivery' }">
            <x-newdebugbar::notification-delivery-panel />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'notification-detail', 'components' => $components])
        <div class="ndb:min-h-[32rem] ndb:overflow-hidden ndb:border ndb:border-zinc-200/90 ndb:bg-white ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950">
            <x-newdebugbar::notification-detail />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'notification-header', 'components' => $components])
        <x-newdebugbar::notification-header />
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'notification-payload-panel', 'components' => $components])
        <div x-data="{ notificationDetailTab: 'payload' }">
            <x-newdebugbar::notification-payload-panel />
        </div>
    @endcomponent

    @component('newdebugbar::studio.component', ['component' => 'notification-source-panel', 'components' => $components])
        <div x-data="{ notificationDetailTab: 'source' }">
            <x-newdebugbar::notification-source-panel />
        </div>
    @endcomponent
</div>
