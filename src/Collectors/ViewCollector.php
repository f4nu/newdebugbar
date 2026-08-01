<?php

namespace NewDebugBar\Collectors;

final class ViewCollector extends AbstractCollector
{
    public function key(): string
    {
        return 'views';
    }

    public function label(): string
    {
        return 'Views';
    }
}
