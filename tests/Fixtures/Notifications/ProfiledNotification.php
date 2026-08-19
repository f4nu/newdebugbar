<?php

namespace NewDebugBar\Tests\Fixtures\Notifications;

use Illuminate\Notifications\Notification;

final class ProfiledNotification extends Notification
{
    public function __construct(public string $privateValue) {}
}
