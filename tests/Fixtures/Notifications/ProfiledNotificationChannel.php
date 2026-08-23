<?php

namespace NewDebugBar\Tests\Fixtures\Notifications;

use RuntimeException;

final class ProfiledNotificationChannel
{
    public function __construct(private readonly bool $fails = false) {}

    /** @return array<string, string> */
    public function send(ProfiledNotifiable $notifiable, ProfiledNotification $notification): array
    {
        if ($this->fails) {
            throw new RuntimeException('Traveler phone number is not verified.');
        }

        return [
            'provider' => 'Profiled Push',
            'message_id' => 'push-'.$notifiable->getKey(),
        ];
    }
}
