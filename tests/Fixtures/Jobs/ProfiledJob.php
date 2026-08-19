<?php

namespace NewDebugBar\Tests\Fixtures\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

final class ProfiledJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $privateValue) {}

    public function handle(): void {}
}
