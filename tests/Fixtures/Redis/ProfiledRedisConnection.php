<?php

namespace NewDebugBar\Tests\Fixtures\Redis;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Redis\Connections\Connection;
use RuntimeException;

final class ProfiledRedisConnection extends Connection
{
    public function __construct(?Dispatcher $events = null)
    {
        $this->client = new class
        {
            public function get(string $key): string
            {
                return 'private Redis result';
            }

            public function hget(string $key, string $field): never
            {
                throw new RuntimeException('private Redis failure');
            }
        };
        $this->setName('default');

        if ($events !== null) {
            $this->setEventDispatcher($events);
        }
    }

    public function createSubscription($channels, Closure $callback, $method = 'subscribe'): void {}
}
