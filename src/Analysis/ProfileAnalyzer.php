<?php

namespace NewDebugBar\Analysis;

/** Produces bounded, deterministic findings from one captured profile. */
final class ProfileAnalyzer
{
    public function __construct(
        private readonly QueryAnalyzer $queries,
        private readonly float $slowRequestMs = 1_000,
        private readonly int $minimumCacheOperations = 5,
        private readonly float $highCacheMissRate = 0.8,
        private readonly int $maxFindings = 50,
    ) {}

    /**
     * @param  array<string, mixed>  $profile
     * @return list<array<string, mixed>>
     */
    public function analyze(array $profile): array
    {
        $sections = is_array($profile['sections'] ?? null) ? $profile['sections'] : [];
        $requestDuration = (float) ($profile['metrics']['duration_ms'] ?? 0);
        $queryItems = $sections['queries']['payload']['items'] ?? [];
        $queryAnalysis = $this->queries->analyze(is_array($queryItems) ? $queryItems : [], $requestDuration);
        $status = (int) ($sections['request']['summary']['status'] ?? 0);
        $exceptionCount = (int) ($sections['exceptions']['summary']['count'] ?? 0);
        $findings = [];

        if ($status >= 400 || $exceptionCount > 0) {
            $findings[] = $this->finding(
                'request.error',
                'error',
                $exceptionCount > 0 ? 'exceptions' : 'request',
                'The request ended with an error or captured exception.',
                ['status' => $status, 'exception_count' => $exceptionCount],
            );
        }

        if ($requestDuration >= $this->slowRequestMs) {
            $findings[] = $this->finding(
                'request.slow',
                'warning',
                'overview',
                'The request exceeded the configured duration threshold.',
                ['duration_ms' => $requestDuration, 'threshold_ms' => $this->slowRequestMs],
            );
        }

        if ($queryAnalysis['summary']['slow_count'] > 0) {
            $findings[] = $this->finding(
                'query.slow',
                'warning',
                'queries',
                'One or more queries exceeded the configured duration threshold.',
                [
                    'count' => $queryAnalysis['summary']['slow_count'],
                    'threshold_ms' => $this->queries->slowThreshold(),
                    'fingerprints' => array_values(array_map(
                        fn (array $item): string => $item['fingerprint'],
                        array_filter($queryAnalysis['items'], fn (array $item): bool => $item['slow']),
                    )),
                ],
            );
        }

        foreach ($queryAnalysis['repeated_groups'] as $group) {
            $findings[] = $this->finding(
                'query.repeated',
                'warning',
                'queries',
                'A query pattern ran more than once.',
                [
                    'fingerprint' => $group['fingerprint'],
                    'count' => $group['count'],
                    'extra_executions' => $group['extra_executions'],
                    'connection' => $group['connection'],
                    'duration_ms' => $group['duration_ms'],
                ],
            );

            if ($group['likely_n_plus_one']) {
                $findings[] = $this->finding(
                    'query.n_plus_one',
                    'warning',
                    'queries',
                    'A repeated query pattern has evidence of a likely N+1 query.',
                    [
                        'fingerprint' => $group['fingerprint'],
                        'count' => $group['count'],
                        'bindings_vary' => true,
                        'shared_callsite' => $group['shared_callsite'],
                    ],
                );
            }
        }

        foreach ($sections as $key => $section) {
            $dropped = (int) ($section['payload']['dropped'] ?? 0);

            if ($dropped > 0) {
                $findings[] = $this->finding(
                    'collector.truncated',
                    'info',
                    (string) $key,
                    'A collector reached its configured item limit.',
                    ['collector' => (string) $key, 'dropped' => $dropped],
                );
            }
        }

        $cache = $sections['cache']['summary'] ?? [];
        $cacheReads = (int) ($cache['hits'] ?? 0) + (int) ($cache['misses'] ?? 0);
        $missRate = $cacheReads > 0 ? (int) ($cache['misses'] ?? 0) / $cacheReads : 0;

        if ($cacheReads >= $this->minimumCacheOperations && $missRate >= $this->highCacheMissRate) {
            $findings[] = $this->finding(
                'cache.high_miss_rate',
                'warning',
                'cache',
                'Most cache reads missed.',
                [
                    'reads' => $cacheReads,
                    'misses' => (int) ($cache['misses'] ?? 0),
                    'miss_rate_percent' => round($missRate * 100, 1),
                ],
            );
        }

        return array_slice($findings, 0, $this->maxFindings);
    }

    /** @return array<string, mixed> */
    private function finding(string $ruleId, string $severity, string $section, string $summary, array $evidence): array
    {
        return [
            'rule_id' => $ruleId,
            'severity' => $severity,
            'section' => $section,
            'summary' => $summary,
            'evidence' => $evidence,
        ];
    }
}
