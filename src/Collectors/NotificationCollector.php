<?php

namespace NewDebugBar\Collectors;

/** Counts notification outcomes while retaining only class and channel names. */
final class NotificationCollector extends AbstractCollector
{
    public function key(): string
    {
        return 'notifications';
    }

    public function label(): string
    {
        return 'Notifications';
    }

    public function summary(): array
    {
        return [
            ...parent::summary(),
            'sent_count' => (int) ($this->totals['sent_count'] ?? 0),
            'failed_count' => (int) ($this->totals['failed_count'] ?? 0),
        ];
    }

    protected function track(array $item): void
    {
        $status = $item['status'] ?? null;
        $this->totals['sent_count'] = ($this->totals['sent_count'] ?? 0) + ($status === 'sent' ? 1 : 0);
        $this->totals['failed_count'] = ($this->totals['failed_count'] ?? 0) + ($status === 'failed' ? 1 : 0);
    }
}
