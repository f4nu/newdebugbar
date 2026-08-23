<?php

use NewDebugBar\Collectors\NotificationCollector;
use NewDebugBar\Support\Redactor;

it('pairs delivery phases and groups channel attempts by notification', function () {
    $collector = new NotificationCollector(new Redactor, maxItems: 5);

    $collector->record([
        'phase' => 'sending',
        'attempt_id' => 'notification-1|mail',
        'group_id' => 'notification-1',
        'notification' => 'App\\Notifications\\JourneyReady',
        'channel' => 'mail',
        'callsite' => ['file' => 'app/Actions/SendJourney.php', 'line' => 24],
    ]);
    $collector->record([
        'phase' => 'sent',
        'attempt_id' => 'notification-1|mail',
        'group_id' => 'notification-1',
        'status' => 'sent',
        'notification' => 'App\\Notifications\\JourneyReady',
        'channel' => 'mail',
        'response' => ['message_id' => 'mail-1'],
        'callsite' => ['file' => 'vendor/laravel/framework.php', 'line' => 100],
    ]);
    $collector->record([
        'phase' => 'sending',
        'attempt_id' => 'notification-1|sms',
        'group_id' => 'notification-1',
        'notification' => 'App\\Notifications\\JourneyReady',
        'channel' => 'sms',
    ]);
    $collector->record([
        'phase' => 'failed',
        'attempt_id' => 'notification-1|sms',
        'group_id' => 'notification-1',
        'status' => 'failed',
        'notification' => 'App\\Notifications\\JourneyReady',
        'channel' => 'sms',
        'failure_data' => ['reason' => 'The phone number is not verified.'],
    ]);

    expect($collector->summary())
        ->count->toBe(2)
        ->notification_count->toBe(1)
        ->failed_notification_count->toBe(1)
        ->sent_count->toBe(1)
        ->failed_count->toBe(1)
        ->duration_ms->toBeFloat()
        ->and($collector->payload()['items'])
        ->toHaveCount(2)
        ->and($collector->payload()['items'][0])
        ->channel->toBe('mail')
        ->duration_ms->toBeFloat()
        ->callsite->toBe(['file' => 'app/Actions/SendJourney.php', 'line' => 24])
        ->response->toBe(['message_id' => 'mail-1'])
        ->and($collector->payload()['items'][1])
        ->channel->toBe('sms')
        ->status->toBe('failed')
        ->failure_data->reason->toBe('The phone number is not verified.');
});

it('counts dropped deliveries and their logical notifications', function () {
    $collector = new NotificationCollector(new Redactor, maxItems: 1);

    $collector->record([
        'status' => 'sent',
        'group_id' => 'notification-1',
        'notification' => 'App\\Notifications\\JourneyReady',
        'channel' => 'mail',
        'duration_ms' => 1.25,
    ]);
    $collector->record([
        'status' => 'failed',
        'group_id' => 'notification-2',
        'notification' => 'App\\Notifications\\JourneyDelayed',
        'channel' => 'sms',
        'duration_ms' => 2.75,
    ]);

    expect($collector->summary())->toBe([
        'count' => 2,
        'retained_count' => 1,
        'dropped_count' => 1,
        'truncated' => true,
        'notification_count' => 2,
        'failed_notification_count' => 1,
        'sent_count' => 1,
        'failed_count' => 1,
        'duration_ms' => 4.0,
    ])->and($collector->payload()['items'])->toHaveCount(1);
});
