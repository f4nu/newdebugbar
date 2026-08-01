<?php

namespace NewDebugBar\Collectors;

final class CacheCollector extends AbstractCollector
{
    public function key(): string
    {
        return 'cache';
    }

    public function label(): string
    {
        return 'Cache';
    }

    public function summary(): array
    {
        $operations = array_count_values(array_column($this->items, 'operation'));

        return [
            ...parent::summary(),
            'hits' => $operations['hit'] ?? 0,
            'misses' => $operations['miss'] ?? 0,
            'writes' => $operations['write'] ?? 0,
        ];
    }
}
