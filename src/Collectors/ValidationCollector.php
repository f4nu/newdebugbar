<?php

namespace NewDebugBar\Collectors;

/** Captures validation failure shape and attaches the rendered response status. */
final class ValidationCollector extends AbstractCollector
{
    public function key(): string
    {
        return 'validation';
    }

    public function label(): string
    {
        return 'Validation';
    }

    public function hasFailures(): bool
    {
        return $this->retainedCount() + $this->dropped > 0;
    }

    public function attachResponseStatus(int $status): void
    {
        foreach ($this->items as &$item) {
            $item['response_status'] = $status;
        }

        unset($item);
    }
}
