<?php

namespace NewDebugBar\Tests\Fixtures\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

/** Provides an observed Livewire event recipient for diagnostics tests. */
final class DiagnosticsChildFixture extends Component
{
    public int $checkIns = 0;

    #[On('vendor-checked-in')]
    public function receiveCheckIn(string $vendor): void
    {
        $this->checkIns++;
    }

    public function render(): string
    {
        return '<div data-testid="diagnostics-child">{{ $checkIns }}</div>';
    }
}
