<?php

namespace NewDebugBar\Collectors;

use NewDebugBar\Contracts\Collector;
use NewDebugBar\Support\Redactor;

abstract class AbstractCollector implements Collector
{
    /** @var array<int, array<string, mixed>> */
    protected array $items = [];

    protected int $dropped = 0;

    /** @var array<string, int|float> */
    protected array $totals = [];

    public function __construct(
        protected readonly Redactor $redactor,
        protected readonly int $maxItems,
    ) {}

    public function reset(): void
    {
        $this->items = [];
        $this->dropped = 0;
        $this->totals = [];
    }

    public function record(array $item): void
    {
        /** @var array<string, mixed> $safeItem */
        $safeItem = $this->redactor->clean($item);
        $this->track($safeItem);

        if (count($this->items) >= $this->maxItems) {
            $this->dropped++;

            return;
        }

        $this->items[] = $safeItem;
    }

    public function summary(): array
    {
        return ['count' => count($this->items) + $this->dropped];
    }

    public function payload(): array
    {
        return [
            'items' => $this->items,
            'dropped' => $this->dropped,
        ];
    }

    /** @param array<string, mixed> $item */
    protected function track(array $item): void {}
}
