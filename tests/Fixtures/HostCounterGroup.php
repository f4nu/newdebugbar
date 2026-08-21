<?php

namespace NewDebugBar\Tests\Fixtures;

use Livewire\Component;

/** Provides a host-owned parent with a nested Livewire component. */
final class HostCounterGroup extends Component
{
    public string $heading = 'Counter group';

    public function render(): string
    {
        return <<<'HTML'
            <section data-testid="host-counter-group">
                <h2>{{ $heading }}</h2>
                <livewire:host-counter :key="'nested-host-counter'" />
            </section>
            HTML;
    }
}
