<?php

namespace NewDebugBar\Tests\Fixtures\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/** Sends deterministic mail when Laravel runs a job after the response. */
final class ProfiledAfterResponseMailJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        DB::select('select 42 as after_response_job');
        Mail::raw('After-response job body', fn ($message) => $message
            ->to('worker@example.test')
            ->subject('After-response job mail'));
    }
}
