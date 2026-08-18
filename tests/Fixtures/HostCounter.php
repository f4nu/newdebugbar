<?php

namespace NewDebugBar\Tests\Fixtures;

use Livewire\Component;

/** Provides one small host-owned component for package compatibility tests. */
final class HostCounter extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }

    public function render(): string
    {
        return <<<'HTML'
            <section data-testid="host-counter">
                <button type="button" wire:click="increment">Increment</button>
                <output data-testid="host-counter-value">{{ $count }}</output>
            </section>
            HTML;
    }
}
