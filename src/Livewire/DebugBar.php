<?php

namespace NewDebugBar\Livewire;

use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use NewDebugBar\Analysis\ProfileComparator;
use NewDebugBar\Presentation\ProfilePresenter;
use NewDebugBar\Presentation\ProfileSummaryPresenter;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Support\QueryExplainer;

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

    /** @var list<array<string, mixed>> */
    #[Locked]
    public array $history = [];

    /** @var array<string, mixed> */
    #[Locked]
    public array $comparison = [];

    #[Locked]
    public ?string $comparisonProfileId = null;

    /** @var array<int, array<string, mixed>> */
    #[Locked]
    public array $queryExplains = [];

    /** @var array<int, string> */
    #[Locked]
    public array $queryExplainErrors = [];

    #[Locked]
    public ?string $discoveredProfileId = null;

    public function mount(string $profileId, ProfileStore $store, ProfilePresenter $presenter): void
    {
        $this->profileId = $profileId;
        $this->summary = $this->makeSummary($presenter->present($store->get($profileId) ?? []));
    }

    public function loadDetails(
        ProfileStore $store,
        ProfilePresenter $presenter,
        ProfileSummaryPresenter $summaries,
    ): void {
        abort_if($store->get($this->profileId) === null, 404);

        $this->detailsLoaded = true;
        $this->refreshHistoryData($store, $presenter, $summaries);
        $this->dispatch('new-debug-bar-content-updated');
    }

    public function compareWith(
        string $profileId,
        ProfileStore $store,
        ProfilePresenter $presenter,
        ProfileComparator $comparator,
    ): void {
        abort_unless($this->validProfileId($profileId), 422);
        $current = $store->get($this->profileId);
        $baseline = $store->get($profileId);
        abort_if($current === null || $baseline === null, 404);

        $current = $presenter->present($current);
        $baseline = $presenter->present($baseline);
        abort_unless(
            ($current['sections']['request']['payload']['path'] ?? null)
                === ($baseline['sections']['request']['payload']['path'] ?? null),
            422,
        );

        $this->comparisonProfileId = $profileId;
        $this->comparison = $comparator->compare($baseline, $current);
        $this->dispatch('new-debug-bar-content-updated');
    }

    public function clearComparison(): void
    {
        $this->comparisonProfileId = null;
        $this->comparison = [];
        $this->dispatch('new-debug-bar-content-updated');
    }

    public function discoverProfile(
        string $profileId,
        ProfileStore $store,
        ProfilePresenter $presenter,
        ProfileSummaryPresenter $summaries,
    ): void {
        abort_unless($this->validProfileId($profileId), 422);
        abort_if($store->get($profileId) === null, 404);
        $this->discoveredProfileId = $profileId;

        if ($this->detailsLoaded) {
            $this->refreshHistoryData($store, $presenter, $summaries);
            $this->dispatch('new-debug-bar-content-updated');
        }
    }

    public function explainQuery(
        int $execution,
        ProfileStore $store,
        ProfilePresenter $presenter,
        QueryExplainer $explainer,
    ): void {
        abort_unless($execution > 0, 422);
        $profile = $presenter->present($store->get($this->profileId) ?? []);
        $query = collect($profile['sections']['queries']['payload']['items'] ?? [])
            ->firstWhere('execution', $execution);
        abort_unless(is_array($query), 404);

        try {
            $this->queryExplains[$execution] = $explainer->explain($query);
            unset($this->queryExplainErrors[$execution]);
        } catch (InvalidArgumentException $exception) {
            unset($this->queryExplains[$execution]);
            $this->queryExplainErrors[$execution] = $exception->getMessage();
        }

        $this->dispatch('new-debug-bar-content-updated');
    }

    public function switchProfile(string $profileId, ProfileStore $store, ProfilePresenter $presenter): void
    {
        abort_unless($this->validProfileId($profileId), 422);
        $profile = $store->get($profileId);
        abort_if($profile === null, 404);

        $this->profileId = $profileId;
        $this->summary = $this->makeSummary($presenter->present($profile));
        $this->detailsLoaded = false;
        $this->history = [];
        $this->comparison = [];
        $this->comparisonProfileId = null;
        $this->queryExplains = [];
        $this->queryExplainErrors = [];
        $this->discoveredProfileId = null;
        $this->dispatch('new-debug-bar-profile-switched', summary: $this->summary);
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
        $findings = is_array($profile['findings'] ?? null) ? $profile['findings'] : [];
        $findingCounts = [];
        $sectionLinks = [];
        $sectionCounts = [];

        foreach ($findings as $finding) {
            $sectionKey = is_array($finding) ? ($finding['section'] ?? null) : null;

            if (is_string($sectionKey)) {
                $findingCounts[$sectionKey] = ($findingCounts[$sectionKey] ?? 0) + 1;
            }
        }

        foreach ($sections as $key => $section) {
            $count = $section['summary']['count'] ?? null;
            $dropped = max(
                (int) ($section['summary']['dropped_count'] ?? 0),
                (int) ($section['payload']['dropped'] ?? 0),
            );
            $secondaryDropped = (int) ($section['payload']['transaction_dropped'] ?? 0);
            $truncated = (bool) ($section['summary']['truncated'] ?? $section['payload']['truncated'] ?? false)
                || $dropped > 0
                || $secondaryDropped > 0;
            $incomplete = (bool) ($section['payload']['incomplete'] ?? false);
            $findingCount = $findingCounts[$key] ?? 0;
            $attention = $findingCount > 0 || $truncated || $incomplete;
            $sectionLinks[] = [
                'key' => $key,
                'label' => $section['label'] ?? ucfirst($key),
                'count' => $count,
                'active' => $count === null || (int) $count > 0 || $attention,
                'attention' => $attention,
                'finding_count' => $findingCount,
                'truncated' => $truncated,
                'incomplete' => $incomplete,
            ];
            $sectionCounts[$key] = $count;
        }

        $sectionLinks[] = [
            'key' => 'history',
            'label' => 'History',
            'count' => null,
            'active' => true,
            'attention' => false,
            'finding_count' => 0,
            'truncated' => false,
            'incomplete' => false,
        ];
        $sectionCounts['history'] = null;

        $status = (int) ($sections['request']['summary']['status'] ?? 0);
        $exceptionCount = (int) ($sections['exceptions']['summary']['count'] ?? 0);
        $querySummary = $sections['queries']['summary'] ?? [];

        return [
            'profile_id' => $profile['id'] ?? $this->profileId,
            'theme' => config('new-debug-bar.theme', 'system'),
            'environment' => (string) ($profile['environment'] ?? app()->environment()),
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
            'warning' => $status >= 400 || $exceptionCount > 0 || $findings !== [],
            'sections' => $sectionLinks,
            'section_counts' => $sectionCounts,
        ];
    }

    private function refreshHistoryData(
        ProfileStore $store,
        ProfilePresenter $presenter,
        ProfileSummaryPresenter $summaries,
    ): void {
        $currentPath = $this->summary['path'] ?? null;
        $history = [];

        foreach ($store->recent() as $profile) {
            try {
                $summary = $summaries->present($presenter->present($profile));
            } catch (\Throwable) {
                continue;
            }

            $summary['is_current'] = ($summary['id'] ?? null) === $this->profileId;
            $summary['comparable'] = ! $summary['is_current'] && ($summary['path'] ?? null) === $currentPath;

            if ($summary['is_current']) {
                array_unshift($history, $summary);
            } else {
                $history[] = $summary;
            }
        }

        $this->history = $history;
    }

    private function validProfileId(string $profileId): bool
    {
        return preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i', $profileId) === 1;
    }
}
