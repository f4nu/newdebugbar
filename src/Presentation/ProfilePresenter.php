<?php

namespace NewDebugBar\Presentation;

use NewDebugBar\Analysis\ProfileAnalyzer;
use NewDebugBar\Analysis\QueryAnalyzer;
use NewDebugBar\Analysis\SectionAnalyzer;
use NewDebugBar\Analysis\TimelineBuilder;

/** Enriches a stored profile for human-facing and machine-facing views. */
final class ProfilePresenter
{
    public function __construct(
        private readonly QueryAnalyzer $queries,
        private readonly ProfileAnalyzer $profiles,
        private readonly SectionAnalyzer $sections,
        private readonly TimelineBuilder $timeline,
        private readonly LivewirePresenter $livewire,
    ) {}

    /** @param array<string, mixed> $profile @return array<string, mixed> */
    public function present(array $profile): array
    {
        if ($profile === []) {
            return [];
        }

        $queryItems = $profile['sections']['queries']['payload']['items'] ?? [];
        $queryAnalysis = $this->queries->analyze(
            is_array($queryItems) ? $queryItems : [],
            (float) ($profile['metrics']['duration_ms'] ?? 0),
        );

        if (isset($profile['sections']['queries'])) {
            $collectorSummary = $profile['sections']['queries']['summary'] ?? [];
            $profile['sections']['queries']['summary'] = [
                ...$collectorSummary,
                ...$queryAnalysis['summary'],
                'count' => $collectorSummary['count'] ?? count($queryAnalysis['items']),
                'total_count' => $collectorSummary['count'] ?? count($queryAnalysis['items']),
                'retained_count' => $collectorSummary['retained_count'] ?? count($queryAnalysis['items']),
                'dropped_count' => $collectorSummary['dropped_count'] ?? 0,
                'duration_ms' => $collectorSummary['duration_ms'] ?? $queryAnalysis['summary']['total_time_ms'],
                'total_time_ms' => $collectorSummary['duration_ms'] ?? $queryAnalysis['summary']['total_time_ms'],
            ];
            $profile['sections']['queries']['payload']['items'] = $queryAnalysis['items'];
            $profile['sections']['queries']['payload']['repeated_groups'] = $queryAnalysis['repeated_groups'];
        }

        $profile = $this->sections->analyze($profile);

        if (isset($profile['sections']['request'])) {
            $timeline = $this->timeline->build($profile);
            $omittedSources = $this->timeline->omittedSources($profile);
            $ordered = [];

            foreach ($profile['sections'] as $key => $section) {
                $ordered[$key] = $section;

                if ($key === 'request') {
                    $ordered['timeline'] = [
                        'label' => 'Timeline',
                        'summary' => ['count' => count($timeline)],
                        'payload' => [
                            'items' => $timeline,
                            'incomplete' => $omittedSources !== [],
                            'omitted_count' => array_sum($omittedSources),
                            'omitted_sources' => $omittedSources,
                        ],
                    ];
                }
            }

            $profile['sections'] = $ordered;
        }

        $profile['findings'] = $this->profiles->analyze($profile);

        if (isset($profile['sections']['livewire']) && is_array($profile['sections']['livewire'])) {
            $profile['sections']['livewire']['payload']['findings'] = array_values(array_filter(
                $profile['findings'],
                fn (array $finding): bool => ($finding['section'] ?? null) === 'livewire',
            ));
            $profile['sections']['livewire'] = $this->livewire->present($profile['sections']['livewire']);
        }

        return $profile;
    }
}
