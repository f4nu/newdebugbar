<?php

namespace NewDebugBar\Collectors;

final class QueryCollector extends AbstractCollector
{
    public function key(): string
    {
        return 'queries';
    }

    public function label(): string
    {
        return 'Queries';
    }

    public function summary(): array
    {
        return [
            ...parent::summary(),
            'duration_ms' => round(array_sum(array_column($this->items, 'duration_ms')), 2),
        ];
    }
}
