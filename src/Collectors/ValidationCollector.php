<?php

namespace NewDebugBar\Collectors;

/** Captures validation failure shape and attaches the rendered response status. */
final class ValidationCollector extends AbstractCollector
{
    private ?int $lastRetainedPosition = null;

    public function key(): string
    {
        return 'validation';
    }

    public function label(): string
    {
        return 'Validation';
    }

    public function reset(): void
    {
        parent::reset();
        $this->lastRetainedPosition = null;
    }

    public function record(array $item): void
    {
        $position = count($this->items);
        parent::record($item);

        if (isset($this->items[$position])) {
            $this->lastRetainedPosition = $position;
        }
    }

    public function attachResponseStatus(int $status): void
    {
        if ($this->lastRetainedPosition !== null && isset($this->items[$this->lastRetainedPosition])) {
            $this->items[$this->lastRetainedPosition]['response_status'] = $status;
        }
    }
}
