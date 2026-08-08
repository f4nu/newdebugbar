<?php

namespace NewDebugBar\Presentation;

use Composer\InstalledVersions;
use NewDebugBar\Analysis\ProfileAnalyzer;
use NewDebugBar\Analysis\QueryAnalyzer;
use NewDebugBar\Analysis\SectionAnalyzer;
use NewDebugBar\Analysis\TimelineBuilder;
use NewDebugBar\Support\EditorLink;

/** Enriches a stored profile for human-facing and machine-facing views. */
final class ProfilePresenter
{
    public function __construct(
        private readonly QueryAnalyzer $queries,
        private readonly ProfileAnalyzer $profiles,
        private readonly SectionAnalyzer $sections,
        private readonly TimelineBuilder $timeline,
        private readonly EditorLink $editor,
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

        if (isset($profile['sections']['livewire']) && (int) ($profile['sections']['livewire']['summary']['count'] ?? 0) > 0) {
            $profile['sections']['livewire']['summary']['version'] = InstalledVersions::getPrettyVersion('livewire/livewire') ?? 'installed';
        }

        $profile = $this->addEditorLinks($profile);

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

        return $profile;
    }

    /** @param array<string, mixed> $profile @return array<string, mixed> */
    private function addEditorLinks(array $profile): array
    {
        if (is_array($profile['sections']['request']['payload'] ?? null)) {
            $action = $profile['sections']['request']['payload']['action'] ?? null;
            $profile['sections']['request']['payload']['action_location'] = $this->editor->action(
                is_string($action) ? $action : null,
            );
        }

        foreach (['queries', 'logs', 'authorization', 'http_client'] as $section) {
            foreach ($profile['sections'][$section]['payload']['items'] ?? [] as $index => $item) {
                $profile['sections'][$section]['payload']['items'][$index]['callsite'] = $this->editor->enrich($item['callsite'] ?? null);

                foreach ($item['stack'] ?? [] as $frameIndex => $frame) {
                    $profile['sections'][$section]['payload']['items'][$index]['stack'][$frameIndex] = $this->editor->enrich($frame) ?? $frame;
                }
            }
        }

        foreach ($profile['sections']['views']['payload']['items'] ?? [] as $index => $item) {
            $profile['sections']['views']['payload']['items'][$index]['render_order'] = $index + 1;
            $profile['sections']['views']['payload']['items'][$index]['source'] = $this->editor->enrich($item['source'] ?? null);
            $profile['sections']['views']['payload']['items'][$index]['composers'] = $this->enrichListeners($item['composers'] ?? []);
        }

        foreach ($profile['sections']['events']['payload']['items'] ?? [] as $index => $item) {
            $profile['sections']['events']['payload']['items'][$index]['listeners'] = $this->enrichListeners($item['listeners'] ?? []);
        }

        foreach ($profile['sections']['exceptions']['payload']['items'] ?? [] as $index => $item) {
            $profile['sections']['exceptions']['payload']['items'][$index]['location'] = $this->editor->enrich([
                'file' => $item['file'] ?? '',
                'line' => $item['line'] ?? 1,
            ]);

            foreach (['application', 'vendor'] as $group) {
                foreach ($item['frames'][$group] ?? [] as $frameIndex => $frame) {
                    $profile['sections']['exceptions']['payload']['items'][$index]['frames'][$group][$frameIndex] = $this->editor->enrich($frame) ?? $frame;
                }
            }

            if (is_array($item['source'] ?? null)) {
                $sourceLocation = $this->editor->enrich([
                    'file' => $item['source']['file'] ?? '',
                    'line' => $item['source']['focus_line'] ?? 1,
                ]);
                $profile['sections']['exceptions']['payload']['items'][$index]['source'] = [
                    ...$item['source'],
                    'editor_url' => $sourceLocation['editor_url'] ?? null,
                    'copy' => $sourceLocation['copy'] ?? null,
                ];
            }
        }

        return $profile;
    }

    /** @param mixed $listeners @return list<array<string, mixed>> */
    private function enrichListeners(mixed $listeners): array
    {
        if (! is_array($listeners)) {
            return [];
        }

        return array_values(array_map(function (mixed $listener): array {
            if (! is_array($listener)) {
                return ['name' => (string) $listener, 'source' => null];
            }

            return [
                ...$listener,
                'source' => $this->editor->enrich($listener['source'] ?? null),
            ];
        }, $listeners));
    }
}
