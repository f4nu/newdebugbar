<?php

namespace NewDebugBar\Collectors;

/** Summarizes cache reads and writes for the current request. */
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
        return [
            ...parent::summary(),
            'hits' => $this->totals['hit'] ?? 0,
            'misses' => $this->totals['miss'] ?? 0,
            'writes' => $this->totals['write'] ?? 0,
        ];
    }

    protected function track(array $item): void
    {
        $operation = (string) ($item['operation'] ?? '');
        $this->totals[$operation] = ($this->totals[$operation] ?? 0) + 1;
    }
}
