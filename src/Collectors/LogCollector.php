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
        $levels = array_count_values(array_column($this->items, 'level'));

        return [
            ...parent::summary(),
            'errors' => ($levels['error'] ?? 0) + ($levels['critical'] ?? 0) + ($levels['alert'] ?? 0) + ($levels['emergency'] ?? 0),
        ];
    }
}
