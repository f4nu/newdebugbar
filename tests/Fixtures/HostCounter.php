<?php

namespace NewDebugBar\Tests\Fixtures;

use Livewire\Attributes\Locked;
use Livewire\Component;

/** Provides one small host-owned component for package compatibility tests. */
final class HostCounter extends Component
{
    public int $count = 0;

    public array $settings = [
        'step' => 1,
        'enabled' => true,
    ];

    #[Locked]
    public string $fixedLabel = 'Host counter';

    public function increment(): void
    {
        $this->count += $this->settings['step'];
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
