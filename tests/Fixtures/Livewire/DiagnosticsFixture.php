<?php

namespace NewDebugBar\Tests\Fixtures\Livewire;

use Livewire\Attributes\Renderless;
use Livewire\Component;

/** Provides deterministic property, action, validation, event, and render states for diagnostics tests. */
final class DiagnosticsFixture extends Component
{
    public string $search = '';

    public string $password = 'initial-secret';

    public int $reviewScore = 0;

    public function saveReview(int $score = 1): void
    {
        $this->reviewScore += $score;
    }

    public function validateReview(): void
    {
        $this->validate(['search' => ['required', 'min:3']]);
    }

    public function announceCheckIn(): void
    {
        $this->dispatch('vendor-checked-in', vendor: 'Northline Ceramics');
    }

    #[Renderless]
    public function recordHeartbeat(): void
    {
        $this->reviewScore++;
    }

    public function render(): string
    {
        return <<<'HTML'
            <section data-testid="diagnostics-fixture">
                <label>
                    Search
                    <input type="search" wire:model.live="search">
                </label>
                <button type="button" wire:click="saveReview(5)">Save review</button>
                <p>{{ $search }}</p>
                <output>{{ $reviewScore }}</output>
            </section>
            HTML;
    }
}
