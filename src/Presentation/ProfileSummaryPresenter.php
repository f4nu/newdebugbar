<?php

namespace NewDebugBar\Presentation;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
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
        $requestType = $this->requestType($request);

        /** @var array<string, mixed> $summary */
        $summary = $this->redactor->clean([
            'id' => $profile['id'] ?? null,
            'short_id' => is_string($profile['id'] ?? null) ? substr($profile['id'], 0, 8) : null,
            'recorded_at' => $profile['recorded_at'] ?? null,
            'recorded_time' => $this->recordedTime($profile['recorded_at'] ?? null),
            'environment' => $profile['environment'] ?? null,
            'profile_type' => $profile['profile_type'] ?? 'http',
            'request_type' => $requestType,
            'activity' => $this->activity($profile, $request, $requestType),
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
        $capturedType = $request['payload']['request_type'] ?? null;

        if (is_string($capturedType) && $capturedType !== '') {
            return $capturedType;
        }

        if ($status >= 300 && $status < 400) {
            return 'redirect';
        }

        return ($request['payload']['headers']['x-livewire'] ?? null) !== null ? 'livewire' : 'full_page';
    }

    /** @param array<string, mixed> $profile @param array<string, mixed> $request */
    private function activity(array $profile, array $request, string $requestType): ?string
    {
        if ($requestType === 'livewire') {
            return $this->livewireActivity($profile['sections']['livewire']['payload']['items'] ?? []);
        }

        if ($requestType === 'inertia_partial') {
            $props = $this->header($request, 'x-inertia-partial-data');

            return $props === null ? 'Partial reload' : 'Partial reload: '.$props;
        }

        if (in_array($requestType, ['redirect', 'inertia_redirect'], true)) {
            $location = $this->header($request, 'location', 'response_headers');

            return $location === null ? 'Redirect response' : 'Redirected to '.$location;
        }

        if ($requestType === 'download') {
            $path = $request['payload']['path'] ?? null;

            return is_string($path) && $path !== '' ? 'Downloaded '.basename($path) : 'File download';
        }

        return null;
    }

    private function livewireActivity(mixed $items): string
    {
        if (! is_array($items)) {
            return 'Livewire update';
        }

        $item = collect($items)->first(fn (mixed $item): bool => is_array($item) && ($item['phase'] ?? null) === 'response')
            ?? collect($items)->first(fn (mixed $item): bool => is_array($item));

        if (! is_array($item)) {
            return 'Livewire update';
        }

        $component = is_string($item['component'] ?? null)
            ? Str::headline($item['component'])
            : 'Livewire';
        $validationFields = array_values(array_filter(array_map('strval', (array) ($item['validation_fields'] ?? []))));
        $actions = array_values(array_filter(array_map('strval', (array) ($item['actions'] ?? []))));
        $properties = array_values(array_filter(array_map('strval', (array) ($item['updated_properties'] ?? []))));

        if ($validationFields !== []) {
            return $component.' → Validation failed: '.implode(', ', $validationFields);
        }

        if ($actions !== []) {
            return $component.' → '.$actions[0].'()'.(count($actions) > 1 ? ' and '.(count($actions) - 1).' more' : '');
        }

        if ($properties !== []) {
            return $component.' → '.(count($properties) === 1
                ? $properties[0].' changed'
                : 'Changed: '.implode(', ', $properties));
        }

        return $component.' updated';
    }

    /** @param array<string, mixed> $request */
    private function header(array $request, string $name, string $group = 'headers'): ?string
    {
        $value = $request['payload'][$group][$name] ?? null;

        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function recordedTime(mixed $recordedAt): ?string
    {
        if (! is_string($recordedAt) || $recordedAt === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($recordedAt)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
