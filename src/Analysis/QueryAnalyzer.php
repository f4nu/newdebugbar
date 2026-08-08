<?php

namespace NewDebugBar\Analysis;

/** Turns captured queries into stable summaries, groups, and evidence. */
final class QueryAnalyzer
{
    public function __construct(private readonly float $slowQueryMs = 100) {}

    public function slowThreshold(): float
    {
        return $this->slowQueryMs;
    }

    /**
     * @param  list<array<string, mixed>>  $queries
     * @return array{summary: array<string, int|float>, items: list<array<string, mixed>>, repeated_groups: list<array<string, mixed>>}
     */
    public function analyze(array $queries, float $requestDurationMs = 0): array
    {
        $items = [];
        $groups = [];
        $totalTime = 0.0;

        foreach (array_values($queries) as $index => $query) {
            $normalizedSql = $this->normalizeSql((string) ($query['sql'] ?? ''));
            $connection = (string) ($query['connection'] ?? 'default');
            $duration = round((float) ($query['duration_ms'] ?? 0), 2);
            $fingerprint = substr(hash('sha256', $normalizedSql."\0".$connection), 0, 16);
            $totalTime += $duration;

            $items[] = [
                ...$query,
                'execution' => $index + 1,
                'normalized_sql' => $normalizedSql,
                'fingerprint' => $fingerprint,
                'connection' => $connection,
                'query_type' => $this->queryType($query, $normalizedSql),
                'duration_ms' => $duration,
                'slow' => $duration >= $this->slowQueryMs,
                'start_ms' => isset($query['at_ms'])
                    ? round(max(0, (float) $query['at_ms'] - $duration), 3)
                    : null,
            ];
            $groups[$fingerprint][] = $index;
        }

        $totalTime = round($totalTime, 2);

        foreach ($items as $index => $item) {
            $items[$index]['query_time_percent'] = $this->percent((float) $item['duration_ms'], $totalTime);
            $items[$index]['request_time_percent'] = $this->percent((float) $item['duration_ms'], $requestDurationMs);
            $items[$index]['repeated_count'] = count($groups[$item['fingerprint']]);
            $items[$index]['repeated'] = $items[$index]['repeated_count'] > 1;
        }

        $repeatedGroups = [];

        foreach ($groups as $fingerprint => $indexes) {
            if (count($indexes) < 2) {
                continue;
            }

            $executions = array_map(fn (int $index): array => $items[$index], $indexes);
            $bindingSets = array_map(
                fn (array $item): array => is_array($item['bindings'] ?? null) ? $item['bindings'] : [],
                $executions,
            );
            $callSiteKeys = array_map(
                fn (array $item): ?string => $this->callSiteKey($item['callsite'] ?? null),
                $executions,
            );
            $callSites = array_values(array_unique(array_filter($callSiteKeys)));
            $hasSharedCallSite = count(array_filter($callSiteKeys)) === count($executions)
                && count($callSites) === 1;
            $bindingsVary = count(array_unique(array_map($this->bindingSignature(...), $bindingSets))) > 1;
            $groupDuration = round(array_sum(array_column($executions, 'duration_ms')), 2);

            $repeatedGroups[] = [
                'fingerprint' => $fingerprint,
                'sql' => $executions[0]['normalized_sql'],
                'connection' => $executions[0]['connection'],
                'query_type' => $executions[0]['query_type'],
                'count' => count($executions),
                'extra_executions' => count($executions) - 1,
                'duration_ms' => $groupDuration,
                'query_time_percent' => $this->percent($groupDuration, $totalTime),
                'request_time_percent' => $this->percent($groupDuration, $requestDurationMs),
                'bindings_vary' => $bindingsVary,
                'shared_callsite' => $hasSharedCallSite ? $executions[0]['callsite'] : null,
                'likely_n_plus_one' => count($executions) >= 3
                    && $bindingsVary
                    && $hasSharedCallSite,
                'executions' => $executions,
            ];
        }

        usort($repeatedGroups, fn (array $left, array $right): int => $right['count'] <=> $left['count']
            ?: $right['duration_ms'] <=> $left['duration_ms']);

        return [
            'summary' => [
                'total_count' => count($items),
                'total_time_ms' => $totalTime,
                'request_time_percent' => $this->percent($totalTime, $requestDurationMs),
                'slow_count' => count(array_filter($items, fn (array $item): bool => $item['slow'])),
                'repeated_pattern_count' => count($repeatedGroups),
                'extra_execution_count' => array_sum(array_column($repeatedGroups, 'extra_executions')),
                'read_count' => count(array_filter($items, fn (array $item): bool => $item['query_type'] === 'read')),
                'write_count' => count(array_filter($items, fn (array $item): bool => $item['query_type'] === 'write')),
            ],
            'items' => $items,
            'repeated_groups' => $repeatedGroups,
        ];
    }

    private function normalizeSql(string $sql): string
    {
        return preg_replace('/\s+/', ' ', trim($sql)) ?? trim($sql);
    }

    /** @param array<string, mixed> $query */
    private function queryType(array $query, string $sql): string
    {
        preg_match('/^(?:\/\*.*?\*\/\s*)*([a-z]+)/is', $sql, $matches);
        $verb = strtolower($matches[1] ?? '');

        if ($verb === 'with') {
            return preg_match('/\b(insert|update|delete|merge)\b/i', $sql) === 1 ? 'write' : 'read';
        }

        if ($verb !== '') {
            return in_array($verb, ['select', 'show', 'describe', 'desc', 'explain', 'pragma'], true)
                ? 'read'
                : 'write';
        }

        $reported = strtolower((string) ($query['type'] ?? ''));

        return in_array($reported, ['read', 'write'], true) ? $reported : 'write';
    }

    private function percent(float $part, float $whole): float
    {
        return $whole > 0 ? round(($part / $whole) * 100, 1) : 0.0;
    }

    /** @param array<string, mixed>|mixed $callsite */
    private function callSiteKey(mixed $callsite): ?string
    {
        if (! is_array($callsite) || ! isset($callsite['file'], $callsite['line'])) {
            return null;
        }

        return $callsite['file'].':'.$callsite['line'];
    }

    /** @param array<array-key, mixed> $bindings */
    private function bindingSignature(array $bindings): string
    {
        return json_encode($bindings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]';
    }
}
