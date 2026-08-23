<?php

namespace NewDebugBar\Tests\Fixtures\Notifications;

use Illuminate\Notifications\Notifiable;

final class ProfiledNotifiable
{
    use Notifiable;

    public function __construct(
        public string $privateAddress,
        public int $id = 1042,
    ) {}

    public function getKey(): int
    {
        return $this->id;
    }

    public function routeNotificationForMail(): string
    {
        return $this->privateAddress;
    }
}
