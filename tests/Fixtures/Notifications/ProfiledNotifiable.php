<?php

namespace NewDebugBar\Tests\Fixtures\Notifications;

use Illuminate\Notifications\Notifiable;

final class ProfiledNotifiable
{
    use Notifiable;

    /** @param string|array<string, string> $privateAddress */
    public function __construct(
        public string|array $privateAddress,
        public int $id = 1042,
        public string $name = 'Elise Martin',
    ) {}

    public function getKey(): int
    {
        return $this->id;
    }

    /** @return string|array<string, string> */
    public function routeNotificationForMail(): string|array
    {
        return $this->privateAddress;
    }

    public function routeNotificationForProfiledSms(): string
    {
        return '+32 470 12 34 56';
    }

    public function routeNotificationForProfiledPush(): string
    {
        return 'device:journey-1042';
    }
}
