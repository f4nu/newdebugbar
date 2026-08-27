<?php

namespace NewDebugBar\Tests\Fixtures\Redis;

final class ProfiledRedisCaller
{
    public function read(ProfiledRedisConnection $connection): string
    {
        return $connection->get('private-client-key');
    }

    public function readHash(ProfiledRedisConnection $connection): mixed
    {
        return $connection->hget('private-client-hash', 'private-client-field');
    }
}
