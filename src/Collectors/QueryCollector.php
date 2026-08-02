<?php

namespace NewDebugBar\Collectors;

/** Collects database queries and totals their duration. */
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
            'duration_ms' => round($this->totals['duration_ms'] ?? 0, 2),
        ];
    }

    protected function track(array $item): void
    {
        $this->totals['duration_ms'] = ($this->totals['duration_ms'] ?? 0) + (float) ($item['duration_ms'] ?? 0);
    }
}
