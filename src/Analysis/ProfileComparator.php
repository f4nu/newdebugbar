<?php

namespace NewDebugBar\Analysis;

use NewDebugBar\Presentation\ProfileSummaryPresenter;

/** Compares two presented profiles without guessing whether a change is good. */
final class ProfileComparator
{
    public function __construct(private readonly ProfileSummaryPresenter $summaries) {}

    /** @param array<string, mixed> $baseline @param array<string, mixed> $current @return array<string, mixed> */
    public function compare(array $baseline, array $current): array
    {
        $before = $this->summaries->present($baseline);
        $after = $this->summaries->present($current);
        $fields = [
            'duration_ms' => ['Duration', 'ms'],
            'peak_memory_mb' => ['Peak memory', 'MB'],
            'query_time_ms' => ['Query time', 'ms'],
            'query_count' => ['Queries', ''],
            'repeated_pattern_count' => ['Repeated patterns', ''],
            'slow_query_count' => ['Slow queries', ''],
            'cache_hit_rate' => ['Cache hit rate', '%'],
            'exception_count' => ['Exceptions', ''],
        ];
        $metrics = [];

        foreach ($fields as $key => [$label, $unit]) {
            $baselineValue = (float) ($before[$key] ?? 0);
            $currentValue = (float) ($after[$key] ?? 0);
            $delta = round($currentValue - $baselineValue, 2);
            $metrics[] = [
                'key' => $key,
                'label' => $label,
                'unit' => $unit,
                'baseline' => $baselineValue,
                'current' => $currentValue,
                'delta' => $delta,
                'tone' => $this->tone($key, $delta),
            ];
        }

        return [
            'path' => $after['path'],
            'baseline' => $before,
            'current' => $after,
            'metrics' => $metrics,
        ];
    }

    private function tone(string $key, float $delta): string
    {
        if ($delta === 0.0 || $key === 'query_count') {
            return 'neutral';
        }

        if ($key === 'cache_hit_rate') {
            return $delta > 0 ? 'improved' : 'regressed';
        }

        return $delta < 0 ? 'improved' : 'regressed';
    }
}
