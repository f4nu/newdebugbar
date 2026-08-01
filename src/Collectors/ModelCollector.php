<?php

namespace NewDebugBar\Collectors;

final class ModelCollector extends AbstractCollector
{
    public function key(): string
    {
        return 'models';
    }

    public function label(): string
    {
        return 'Models';
    }
}
