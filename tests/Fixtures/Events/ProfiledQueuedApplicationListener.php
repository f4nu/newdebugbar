<?php

namespace NewDebugBar\Tests\Fixtures\Events;

use Illuminate\Contracts\Queue\ShouldQueue;

final class ProfiledQueuedApplicationListener implements ShouldQueue
{
    public function handle(ProfiledApplicationEvent $event): void {}
}
