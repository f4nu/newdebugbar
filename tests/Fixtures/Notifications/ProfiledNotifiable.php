<?php

namespace NewDebugBar\Tests\Fixtures\Notifications;

final class ProfiledNotifiable
{
    public function __construct(public string $privateAddress) {}
}
