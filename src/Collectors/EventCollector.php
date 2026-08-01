<?php

namespace NewDebugBar\Collectors;

final class EventCollector extends AbstractCollector
{
    public function key(): string
    {
        return 'events';
    }

    public function label(): string
    {
        return 'Events';
    }
}
