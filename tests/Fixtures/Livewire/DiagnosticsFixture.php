<?php

namespace NewDebugBar\Tests\Fixtures\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function saveReviewWithWork(): void
    {
        DB::select('select ? as review_score', [5]);
        Log::info('Review saved by Livewire.');
        $this->reviewScore = 5;
    }

    public function loadReviewOptions(): void
    {
        usleep(225_000);
        $this->reviewScore = 3;
    }

    public function validateReview(): void
    {
        $this->validate(['search' => ['required', 'min:3']]);
    }

    public function announceCheckIn(): void
    {
        $this->dispatch('vendor-checked-in', vendor: 'Northline Ceramics');
    }

    public function goToVendor(): void
    {
        $this->redirect('/vendors?token=private-vendor-token');
    }

    public function downloadReport(): StreamedResponse
    {
        return response()->streamDownload(
            static fn () => print 'private report body',
            'review-report.txt',
            ['Content-Type' => 'text/plain'],
        );
    }

    public function failReview(): never
    {
        throw new RuntimeException('Review action failed.');
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
                <button type="button" data-testid="slow-review" wire:click="loadReviewOptions">Load review options</button>
                <button type="button" data-testid="announce-check-in" wire:click="announceCheckIn">Announce check-in</button>
                <p>{{ $search }}</p>
                <output>{{ $reviewScore }}</output>
            </section>
            HTML;
    }
}
