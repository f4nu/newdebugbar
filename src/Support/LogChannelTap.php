<?php

namespace NewDebugBar\Support;

use Illuminate\Log\Logger;

/** Adds a no-op Monolog processor that remembers the configured Laravel channel. */
final class LogChannelTap
{
    public function __construct(private readonly LogChannelTracker $tracker) {}

    public function __invoke(Logger $logger, string $channel): void
    {
        $underlying = $logger->getLogger();

        if (! method_exists($underlying, 'pushProcessor')) {
            return;
        }

        $underlying->pushProcessor(function (mixed $record) use ($channel): mixed {
            $this->tracker->remember($channel, $record);

            return $record;
        });
    }
}
