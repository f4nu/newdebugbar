<?php

namespace NewDebugBar\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use NewDebugBar\Presentation\ProfilePresenter;
use NewDebugBar\Storage\ProfileStore;

/** Loads a request summary first and defers full inspector data. */
final class DebugBar extends Component
{
    #[Locked]
    public string $profileId;

    /** @var array<string, mixed> */
    #[Locked]
    public array $summary = [];

    #[Locked]
    public bool $detailsLoaded = false;

    public function mount(string $profileId, ProfileStore $store, ProfilePresenter $presenter): void
    {
        $this->profileId = $profileId;
        $this->summary = $this->makeSummary($presenter->present($store->get($profileId) ?? []));
    }

    public function loadDetails(ProfileStore $store): void
    {
        abort_if($store->get($this->profileId) === null, 404);

        $this->detailsLoaded = true;
    }

    /** @return array<string, mixed> */
    #[Computed]
    public function profile(): array
    {
        if (! $this->detailsLoaded) {
            return [];
        }

        return app(ProfilePresenter::class)->present(app(ProfileStore::class)->get($this->profileId) ?? []);
    }

    public function render(): View
    {
        return view('new-debug-bar::livewire.debug-bar');
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function makeSummary(array $profile): array
    {
        $sections = $profile['sections'] ?? [];
        $sectionLinks = [];
        $sectionCounts = [];

        foreach ($sections as $key => $section) {
            $count = $section['summary']['count'] ?? null;
            $sectionLinks[] = [
                'key' => $key,
                'label' => $section['label'] ?? ucfirst($key),
                'count' => $count,
            ];
            $sectionCounts[$key] = $count;
        }

        $status = (int) ($sections['request']['summary']['status'] ?? 0);
        $exceptionCount = (int) ($sections['exceptions']['summary']['count'] ?? 0);
        $querySummary = $sections['queries']['summary'] ?? [];

        return [
            'theme' => config('new-debug-bar.theme', 'system'),
            'environment' => strtoupper((string) ($profile['environment'] ?? app()->environment())),
            'method' => $sections['request']['summary']['method'] ?? 'GET',
            'path' => $sections['request']['payload']['path'] ?? '/',
            'status' => $status,
            'duration_ms' => $profile['metrics']['duration_ms'] ?? 0,
            'memory_mb' => $profile['metrics']['peak_memory_mb'] ?? 0,
            'query_count' => $sections['queries']['summary']['count'] ?? 0,
            'query_duration_ms' => $sections['queries']['summary']['duration_ms'] ?? 0,
            'exception_count' => $exceptionCount,
            'slow_query_count' => $querySummary['slow_count'] ?? 0,
            'duplicate_query_count' => $querySummary['repeated_pattern_count'] ?? 0,
            'extra_query_count' => $querySummary['extra_execution_count'] ?? 0,
            'warning' => $status >= 400 || $exceptionCount > 0 || ($querySummary['slow_count'] ?? 0) > 0,
            'sections' => $sectionLinks,
            'section_counts' => $sectionCounts,
        ];
    }
}
