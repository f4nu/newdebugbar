<?php

namespace NewDebugBar\Collectors;

use NewDebugBar\Support\Redactor;

/** Collects database queries and totals their duration. */
final class QueryCollector extends AbstractCollector
{
    public function __construct(
        Redactor $redactor,
        int $maxItems,
        private readonly string $bindingPolicy = 'safe',
    ) {
        parent::__construct($redactor, $maxItems);
    }

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

    public function record(array $item): void
    {
        if (isset($item['sql']) && is_string($item['sql'])) {
            $item['sql'] = $this->redactor->cleanSql($item['sql']);
        }

        if (array_key_exists('bindings', $item)) {
            $bindings = $item['bindings'];
            $item['bindings'] = $this->redactor->cleanBindings(
                is_array($bindings) ? $bindings : [],
                $this->bindingPolicy,
            );
        }

        parent::record($item);
    }

    protected function track(array $item): void
    {
        $this->totals['duration_ms'] = ($this->totals['duration_ms'] ?? 0) + (float) ($item['duration_ms'] ?? 0);
    }
}
