<?php

namespace NewDebugBar\Collectors;

final class ExceptionCollector extends AbstractCollector
{
    public function key(): string
    {
        return 'exceptions';
    }

    public function label(): string
    {
        return 'Exceptions';
    }
}
