<?php

namespace NewDebugBar\Tests\Fixtures\Events;

final class ProfiledApplicationEvent
{
    public function __construct(
        public readonly string $trip = 'kyoto-autumn',
        public readonly array $changes = ['itinerary', 'bookings'],
    ) {}
}
