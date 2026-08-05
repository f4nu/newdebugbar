<?php

namespace NewDebugBar\Presentation;

use NewDebugBar\Support\Redactor;

/** Produces one stable summary for history, comparison, UI, and MCP. */
final class ProfileSummaryPresenter
{
    public function __construct(private readonly Redactor $redactor) {}

    /** @param array<string, mixed> $profile @return array<string, mixed> */
    public function present(array $profile): array
    {
        $request = $profile['sections']['request'] ?? [];
        $queries = $profile['sections']['queries']['summary'] ?? [];
        $cache = $profile['sections']['cache']['summary'] ?? [];
        $status = (int) ($request['summary']['status'] ?? 0);
        $exceptionCount = (int) ($profile['sections']['exceptions']['summary']['count'] ?? 0);
        $exitCode = $request['summary']['exit_code'] ?? null;
        $cacheReads = (int) ($cache['hits'] ?? 0) + (int) ($cache['misses'] ?? 0);

        /** @var array<string, mixed> $summary */
        $summary = $this->redactor->clean([
            'id' => $profile['id'] ?? null,
            'recorded_at' => $profile['recorded_at'] ?? null,
            'request_type' => $this->requestType($request),
            'method' => $request['summary']['method'] ?? null,
            'path' => $request['payload']['path'] ?? null,
            'status' => $status,
            'duration_ms' => $profile['metrics']['duration_ms'] ?? 0,
            'peak_memory_mb' => $profile['metrics']['peak_memory_mb'] ?? 0,
            'query_count' => $queries['total_count'] ?? $queries['count'] ?? 0,
            'query_time_ms' => $queries['total_time_ms'] ?? $queries['duration_ms'] ?? 0,
            'repeated_pattern_count' => $queries['repeated_pattern_count'] ?? 0,
            'slow_query_count' => $queries['slow_count'] ?? 0,
            'cache_hits' => $cache['hits'] ?? 0,
            'cache_misses' => $cache['misses'] ?? 0,
            'cache_hit_rate' => $cacheReads > 0 ? round(((int) ($cache['hits'] ?? 0) / $cacheReads) * 100, 1) : 0.0,
            'exception_count' => $exceptionCount,
            'finding_count' => count($profile['findings'] ?? []),
            'warning' => $status >= 400 || (is_int($exitCode) && $exitCode !== 0) || $exceptionCount > 0 || ($profile['findings'] ?? []) !== [],
        ]);

        return $summary;
    }

    /** @param array<string, mixed> $request */
    private function requestType(array $request): string
    {
        $runtimeType = $request['payload']['runtime_type'] ?? null;

        if (is_string($runtimeType) && $runtimeType !== '') {
            return $runtimeType;
        }

        $status = (int) ($request['summary']['status'] ?? 0);

        if ($status >= 300 && $status < 400) {
            return 'redirect';
        }

        return (string) ($request['payload']['request_type']
            ?? (($request['payload']['headers']['x-livewire'] ?? null) !== null ? 'livewire' : 'full_page'));
    }
}
