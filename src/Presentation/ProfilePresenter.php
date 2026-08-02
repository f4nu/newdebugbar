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
            $profile['sections']['queries']['summary'] = [
                ...($profile['sections']['queries']['summary'] ?? []),
                ...$queryAnalysis['summary'],
            ];
            $profile['sections']['queries']['payload']['items'] = $queryAnalysis['items'];
            $profile['sections']['queries']['payload']['repeated_groups'] = $queryAnalysis['repeated_groups'];
        }

        $profile = $this->sections->analyze($profile);

        if (isset($profile['sections']['request'])) {
            $timeline = $this->timeline->build($profile);
            $ordered = [];

            foreach ($profile['sections'] as $key => $section) {
                $ordered[$key] = $section;

                if ($key === 'request') {
                    $ordered['timeline'] = [
                        'label' => 'Timeline',
                        'summary' => ['count' => count($timeline)],
                        'payload' => ['items' => $timeline, 'dropped' => 0],
                    ];
                }
            }

            $profile['sections'] = $ordered;
        }

        $profile['findings'] = $this->profiles->analyze($profile);

        return $profile;
    }
}
