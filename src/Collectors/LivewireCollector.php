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

        if ($phase === 'initial') {
            parent::record($item);

            return;
        }

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

    public function summary(): array
    {
        $summary = parent::summary();

        return [
            ...$summary,
            'initial_render_count' => (int) ($this->totals['initial'] ?? 0),
            'update_count' => (int) ($this->totals['update'] ?? 0),
            'component_count' => count($this->totals['components'] ?? []),
        ];
    }

    protected function track(array $item): void
    {
        $kind = (string) ($item['kind'] ?? 'update');
        $this->totals[$kind] = ($this->totals[$kind] ?? 0) + 1;

        $component = $item['component'] ?? null;

        if (is_string($component) && $component !== '') {
            $this->totals['components'][$component] = true;
        }
    }
}
