<?php

namespace NewDebugBar\Livewire;

use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use NewDebugBar\Analysis\ProfileComparator;
use NewDebugBar\Presentation\ProfilePresenter;
use NewDebugBar\Presentation\ProfileSummaryPresenter;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Support\QueryExplainer;

/** Loads a request summary first and defers full inspector data. */
final class DebugBar extends Component
{
    /** @var array<string, string> */
    private const SECTION_DESCRIPTIONS = [
        'overview' => 'Review the important request activity and the runtime behind it.',
        'authorization' => 'Review authorization decisions, their results, handlers, and call sites.',
        'cache' => 'Review cache reads and writes, including hits, misses, keys, and timing.',
        'events' => 'See which events Laravel dispatched, where they came from, and how they were handled.',
        'exceptions' => 'Inspect reported exceptions, application frames, and the code path that failed.',
        'http_client' => 'Review outbound HTTP requests, responses, timing, and their source.',
        'history' => 'Inspect recent requests, background work, and earlier pages. Compare requests that use the same path.',
        'lifecycle' => 'See how long Laravel spent in each measured request lifecycle stage.',
        'livewire' => 'Inspect the Livewire action, component changes, events, and outcome for this request.',
        'logs' => 'Review log messages, their context, and the application code that wrote them.',
        'mail' => 'Inspect mail created during the request, including recipients, metadata, and previews.',
        'messages' => 'Review developer messages, their context, and when they were recorded.',
        'models' => 'See which Eloquent models this request loaded or changed. Find repeated record loads, unexpected writes, and when the work happened. Repeated means extra retrievals after a record’s first load.',
        'notifications' => 'Inspect notifications sent during the request and the channels they used.',
        'queries' => 'Find repeated work, slow SQL, and the application code that triggered it.',
        'queue' => 'Review queued work, its connection and queue, and what happened during dispatch.',
        'redis' => 'Inspect direct Redis commands, their keys, connections, and timing.',
        'request' => 'Follow the request from the incoming URL through routing, middleware, and the response.',
        'timeline' => 'Follow important work in the order it happened across the request.',
        'validation' => 'Review validation failures, affected fields, rules, and messages.',
        'views' => 'See which Blade templates rendered and the data each received. Use this to spot missing variables, unexpected partials, and repeated renders.',
    ];

    #[Locked]
    public string $profileId;

    #[Locked]
    public string $currentProfileId;

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

    public function mount(
        string $profileId,
        ProfileStore $store,
        ProfilePresenter $presenter,
        ProfileSummaryPresenter $summaries,
    ): void {
        $this->profileId = $profileId;
        $this->currentProfileId = $profileId;
        $profile = $presenter->present($store->get($profileId) ?? []);
        $this->summary = $this->makeSummary($profile, $summaries);
        $this->detailsLoaded = (int) ($this->summary['status'] ?? 0) >= 400
            || (int) ($this->summary['exception_count'] ?? 0) > 0;
    }

    public function loadDetails(
        ProfileStore $store,
        ProfilePresenter $presenter,
        ProfileSummaryPresenter $summaries,
    ): void {
        abort_if($store->get($this->profileId) === null, 404);

        $this->detailsLoaded = true;
        $this->refreshHistoryData($store, $presenter, $summaries);
        $this->dispatch('newdebugbar-content-updated');
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
        $this->dispatch('newdebugbar-content-updated');
    }

    public function clearComparison(): void
    {
        $this->comparisonProfileId = null;
        $this->comparison = [];
        $this->dispatch('newdebugbar-content-updated');
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
            $this->dispatch('newdebugbar-content-updated');
        }
    }

    public function refreshProfileTrace(
        string $profileId,
        ProfileStore $store,
        ProfilePresenter $presenter,
        ProfileSummaryPresenter $summaries,
    ): void {
        if (! $this->validProfileId($profileId)) {
            return;
        }

        $profile = $store->get($profileId);

        if ($profile === null) {
            return;
        }

        if ($profileId !== $this->profileId) {
            $this->discoveredProfileId = $profileId;

            if ($this->detailsLoaded) {
                $this->refreshHistoryData($store, $presenter, $summaries);
                $this->dispatch('newdebugbar-content-updated');
            }

            return;
        }

        unset($this->profile);
        $this->summary = $this->makeSummary($presenter->present($profile), $summaries);
        $this->dispatch('newdebugbar-content-updated');
    }

    #[Renderless]
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

        $this->dispatch(
            'newdebugbar-query-explained',
            execution: $execution,
            explain: $this->queryExplains[$execution] ?? null,
            error: $this->queryExplainErrors[$execution] ?? null,
        );
    }

    public function switchProfile(
        string $profileId,
        ProfileStore $store,
        ProfilePresenter $presenter,
        ProfileSummaryPresenter $summaries,
    ): void {
        $this->activateProfile($profileId, true, $store, $presenter, $summaries);
    }

    public function selectProfile(
        string $profileId,
        ProfileStore $store,
        ProfilePresenter $presenter,
        ProfileSummaryPresenter $summaries,
    ): void {
        $this->activateProfile($profileId, false, $store, $presenter, $summaries);
    }

    public function returnToCurrent(
        ProfileStore $store,
        ProfilePresenter $presenter,
        ProfileSummaryPresenter $summaries,
    ): void {
        $this->activateProfile($this->currentProfileId, false, $store, $presenter, $summaries);
    }

    private function activateProfile(
        string $profileId,
        bool $makeCurrent,
        ProfileStore $store,
        ProfilePresenter $presenter,
        ProfileSummaryPresenter $summaries,
    ): void {
        abort_unless($this->validProfileId($profileId), 422);
        $profile = $store->get($profileId);
        abort_if($profile === null, 404);

        $this->profileId = $profileId;

        if ($makeCurrent) {
            $this->currentProfileId = $profileId;
        }

        $this->summary = $this->makeSummary($presenter->present($profile), $summaries);
        $this->detailsLoaded = false;
        $this->history = [];
        $this->comparison = [];
        $this->comparisonProfileId = null;
        $this->queryExplains = [];
        $this->queryExplainErrors = [];
        $this->discoveredProfileId = null;
        $this->dispatch('newdebugbar-profile-switched', summary: $this->summary);
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
        return view('newdebugbar::livewire.debug-bar');
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function makeSummary(array $profile, ProfileSummaryPresenter $summaries): array
    {
        $sections = $profile['sections'] ?? [];
        $findings = is_array($profile['findings'] ?? null) ? $profile['findings'] : [];
        $summary = $summaries->present($profile);
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
            $dropped = (int) ($section['summary']['dropped_count'] ?? 0);
            $secondaryDropped = (int) ($section['summary']['transaction_dropped_count'] ?? 0);
            $truncated = (bool) ($section['summary']['truncated'] ?? false)
                || $dropped > 0
                || $secondaryDropped > 0;
            $incomplete = (bool) ($section['payload']['incomplete'] ?? false);
            $findingCount = $findingCounts[$key] ?? 0;
            $attention = $findingCount > 0 || $truncated || $incomplete;
            $sectionLinks[] = [
                'key' => $key,
                'label' => $section['label'] ?? ucfirst($key),
                'description' => $this->sectionDescription((string) $key, (string) ($section['label'] ?? ucfirst($key)), $profile),
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
            'description' => self::SECTION_DESCRIPTIONS['history'],
            'count' => null,
            'active' => true,
            'attention' => false,
            'finding_count' => 0,
            'truncated' => false,
            'incomplete' => false,
        ];
        $sectionCounts['history'] = null;

        return [
            ...$summary,
            'id' => $summary['id'] ?? $this->profileId,
            'is_current_profile' => ($summary['id'] ?? $this->profileId) === $this->currentProfileId,
            'theme' => config('newdebugbar.theme', 'system'),
            'environment' => (string) ($summary['environment'] ?? app()->environment()),
            'method' => $summary['method'] ?? 'GET',
            'path' => $summary['path'] ?? '/',
            'sections' => $sectionLinks,
            'section_counts' => $sectionCounts,
        ];
    }

    /** @param array<string, mixed> $profile */
    private function sectionDescription(string $key, string $label, array $profile): string
    {
        if (
            $key === 'lifecycle'
            && ($profile['sections']['request']['payload']['timing_scope'] ?? null) === 'global_middleware_entry'
        ) {
            return self::SECTION_DESCRIPTIONS['lifecycle'].' Timing starts at the debug middleware, so early Laravel bootstrap is not measured.';
        }

        return self::SECTION_DESCRIPTIONS[$key]
            ?? 'Review the collected '.strtolower($label).' details for this request.';
    }

    private function refreshHistoryData(
        ProfileStore $store,
        ProfilePresenter $presenter,
        ProfileSummaryPresenter $summaries,
    ): void {
        $currentPath = $this->summary['path'] ?? null;
        $current = [];
        $retained = [];

        foreach ($store->recent() as $profile) {
            try {
                $summary = $summaries->present($presenter->present($profile));
            } catch (\Throwable) {
                continue;
            }

            $summary['is_current'] = ($summary['id'] ?? null) === $this->currentProfileId;
            $summary['is_selected'] = ($summary['id'] ?? null) === $this->profileId;
            $summary['comparable'] = ! $summary['is_selected'] && ($summary['path'] ?? null) === $currentPath;

            if ($summary['is_current']) {
                $current[] = $summary;
            } else {
                $retained[] = $summary;
            }
        }

        $this->history = [...$current, ...$retained];
    }

    private function validProfileId(string $profileId): bool
    {
        return ProfileStore::validId($profileId);
    }
}
