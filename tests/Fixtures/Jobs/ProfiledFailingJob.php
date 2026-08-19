<?php

namespace NewDebugBar\Tests\Fixtures\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

final class ProfiledFailingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $privateValue) {}

    public function handle(): void
    {
        throw new \RuntimeException('private failure message');
    }
}
