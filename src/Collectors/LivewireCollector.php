<?php

namespace NewDebugBar\Collectors;

/** Merges safe request and response facts for each Livewire component update. */
final class LivewireCollector extends AbstractCollector
{
    /** @var array<int, int> */
    private array $positions = [];

    public function key(): string
    {
        return 'livewire';
    }

    public function label(): string
    {
        return 'Livewire';
    }

    public function reset(): void
    {
        parent::reset();
        $this->positions = [];
    }

    public function record(array $item): void
    {
        $phase = $item['phase'] ?? 'request';
        $requestIndex = (int) ($item['request_index'] ?? -1);
        unset($item['phase'], $item['request_index']);

        if ($phase === 'response') {
            $position = $this->positions[$requestIndex] ?? null;

            if ($position !== null && isset($this->items[$position])) {
                $safe = $this->redactor->clean($item);
                $this->items[$position] = [...$this->items[$position], ...$safe];
            }

            return;
        }

        $position = count($this->items);
        parent::record($item);

        if (isset($this->items[$position])) {
            $this->positions[$requestIndex] = $position;
        }
    }
}
