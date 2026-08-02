<?php

namespace NewDebugBar\Collectors;

final class LogCollector extends AbstractCollector
{
    public function key(): string
    {
        return 'logs';
    }

    public function label(): string
    {
        return 'Logs';
    }

    public function summary(): array
    {
        return [
            ...parent::summary(),
            'errors' => $this->totals['errors'] ?? 0,
        ];
    }

    protected function track(array $item): void
    {
        if (in_array($item['level'] ?? null, ['error', 'critical', 'alert', 'emergency'], true)) {
            $this->totals['errors'] = ($this->totals['errors'] ?? 0) + 1;
        }
    }
}
