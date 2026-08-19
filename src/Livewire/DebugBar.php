<?php

namespace NewDebugBar\Livewire;

use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;
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
        'logs' => 'Review log messages, their context, and the application code that wrote them.',
        'mail' => 'Inspect mail created during the request, including recipients, metadata, and previews.',
        'messages' => 'Review developer messages, their context, and when they were recorded.',
        'models' => 'See which Eloquent models this request loaded or changed. Find repeated record loads, unexpected writes, and when the work happened. Repeated means extra retrievals after a record’s first load.',
        'notifications' => 'Inspect notifications sent during the request and the channels they used.',
        'queries' => 'Find repeated work, slow SQL, and the application code that triggered it.',
        'queue' => 'Review queued work, its connection and queue, and what happened during dispatch.',
        'redis' => 'Inspect direct Redis commands, their keys, connections, and timing.',
        'request' => 'Inspect the selected request and switch between recently captured requests.',
        'timeline' => 'Follow important work in the order it happened across the request.',
        'validation' => 'Review validation failures, affected fields, rules, and messages.',
        'views' => 'See which Blade templates rendered and the data each received. Use this to spot missing variables, unexpected partials, and repeated renders.',
    ];

    #[Locked]
    public string $profileId;

    /** @var array<string, mixed> */
    #[Locked]
    public array $summary = [];

    #[Locked]
    public bool $detailsLoaded = false;

    #[Locked]
    public int $profileLimit = 20;

    /** @var array<int, array<string, mixed>> */
    #[Locked]
    public array $queryExplains = [];

    /** @var array<int, string> */
    #[Locked]
    public array $queryExplainErrors = [];

    public function mount(
        string $profileId,
        ProfileStore $store,
        ProfilePresenter $presenter,
        ProfileSummaryPresenter $summaries,
    ): void {
        $this->profileId = $profileId;
        $this->profileLimit = $store->maxProfiles();
        $profile = $presenter->present($store->get($profileId) ?? []);
        $this->summary = $this->makeSummary($profile, $summaries);
        $this->detailsLoaded = (int) ($this->summary['status'] ?? 0) >= 400
            || (int) ($this->summary['exception_count'] ?? 0) > 0;
    }

    public function loadDetails(
        ProfileStore $store,
    ): void {
        abort_if($store->get($this->profileId) === null, 404);

        $this->detailsLoaded = true;
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
        $this->activateProfile($profileId, $store, $presenter, $summaries);
    }

    #[Renderless]
    public function noticeProfile(
        string $profileId,
        ProfileStore $store,
        ProfilePresenter $presenter,
        ProfileSummaryPresenter $summaries,
    ): void {
        abort_unless($this->validProfileId($profileId), 422);
        $profile = $store->get($profileId);
        abort_if($profile === null, 404);

        $this->dispatch(
            'newdebugbar-profile-noticed',
            summary: $summaries->present($presenter->present($profile)),
        );
    }

    #[Renderless]
    public function refreshRecentProfiles(
        ProfileStore $store,
        ProfilePresenter $presenter,
        ProfileSummaryPresenter $summaries,
    ): void {
        $this->dispatch(
            'newdebugbar-profiles-refreshed',
            profiles: $this->recentProfileSummaries($store, $presenter, $summaries),
        );
    }

    private function activateProfile(
        string $profileId,
        ProfileStore $store,
        ProfilePresenter $presenter,
        ProfileSummaryPresenter $summaries,
    ): void {
        abort_unless($this->validProfileId($profileId), 422);
        $profile = $store->get($profileId);
        abort_if($profile === null, 404);

        $this->profileId = $profileId;
        $this->summary = $this->makeSummary($presenter->present($profile), $summaries);
        $this->detailsLoaded = false;
        $this->queryExplains = [];
        $this->queryExplainErrors = [];
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

    /** @return list<array<string, mixed>> */
    #[Computed]
    public function recentProfiles(): array
    {
        return $this->recentProfileSummaries(
            app(ProfileStore::class),
            app(ProfilePresenter::class),
            app(ProfileSummaryPresenter::class),
        );
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
            $label = $key === 'request'
                ? 'Requests'
                : (string) ($section['label'] ?? ucfirst($key));
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
                'label' => $label,
                'description' => $this->sectionDescription((string) $key, $label),
                'count' => $count,
                'active' => $count === null || (int) $count > 0 || $attention,
                'attention' => $attention,
                'finding_count' => $findingCount,
                'truncated' => $truncated,
                'incomplete' => $incomplete,
            ];
            $sectionCounts[$key] = $count;
        }

        return [
            ...$summary,
            'id' => $summary['id'] ?? $this->profileId,
            'theme' => config('newdebugbar.theme', 'system'),
            'environment' => (string) ($summary['environment'] ?? app()->environment()),
            'method' => $summary['method'] ?? 'GET',
            'path' => $summary['path'] ?? '/',
            'sections' => $sectionLinks,
            'section_counts' => $sectionCounts,
        ];
    }

    private function sectionDescription(string $key, string $label): string
    {
        return self::SECTION_DESCRIPTIONS[$key]
            ?? 'Review the collected '.strtolower($label).' details for this request.';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentProfileSummaries(
        ProfileStore $store,
        ProfilePresenter $presenter,
        ProfileSummaryPresenter $summaries,
    ): array {
        return array_values(array_map(
            fn (array $profile): array => $summaries->present($presenter->present($profile)),
            $store->recent(),
        ));
    }

    private function validProfileId(string $profileId): bool
    {
        return ProfileStore::validId($profileId);
    }
}
