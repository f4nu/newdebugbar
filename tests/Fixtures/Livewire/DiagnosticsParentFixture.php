<?php

namespace NewDebugBar\Tests\Fixtures\Livewire;

use Livewire\Component;

/** Provides a real parent and child mount relationship for diagnostics tests. */
final class DiagnosticsParentFixture extends Component
{
    public function render(): string
    {
        return <<<'HTML'
            <div data-testid="diagnostics-parent">
                <livewire:diagnostics-child />
            </div>
            HTML;
    }
}
