<?php

namespace NewDebugBar\Analysis;

/** Turns captured cache events into a compact diagnostic model. */
final class CacheAnalyzer
{
    public function __construct(
        private readonly int $minimumReads = 5,
        private readonly float $highMissRate = 0.8,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{summary: array<string, mixed>, items: list<array<string, mixed>>, repeated_misses: list<array<string, mixed>>}
     */
    public function analyze(array $items): array
    {
        $items = array_values(array_filter($items, 'is_array'));
        $operations = [];
        $keyExecutions = [];
        $misses = [];
        $stores = [];
        $uniqueKeys = [];
        $timings = [];

        foreach ($items as $index => $item) {
            $operation = $this->operation($item);
            $operations[$operation] = ($operations[$operation] ?? 0) + 1;

            $keyIdentity = $this->keyIdentity($item);

            if ($keyIdentity !== null) {
                $keyExecutions[$keyIdentity][] = $index + 1;
                $uniqueKeys[$keyIdentity] = true;

                if ($operation === 'miss') {
                    $misses[$keyIdentity] ??= [
                        'key_hash' => $item['key_hash'] ?? null,
                        'key' => $item['key'] ?? null,
                        'store' => $item['store'] ?? null,
                        'count' => 0,
                    ];
                    $misses[$keyIdentity]['count']++;
                }
            }

            $store = $this->stringOrNull($item['store'] ?? null);

            if ($store !== null) {
                $stores[$store] = true;
            }

            if (isset($item['duration_ms']) && is_numeric($item['duration_ms'])) {
                $timingIdentity = $this->stringOrNull($item['duration_id'] ?? null) ?? 'operation:'.($index + 1);
                $timings[$timingIdentity] = max(0.0, (float) $item['duration_ms']);
            }
        }

        $repeatedMisses = array_values(array_filter(
            $misses,
            fn (array $miss): bool => $miss['count'] > 1,
        ));
        usort($repeatedMisses, fn (array $left, array $right): int => $right['count'] <=> $left['count']
            ?: strnatcasecmp((string) ($left['key'] ?? $left['key_hash'] ?? ''), (string) ($right['key'] ?? $right['key_hash'] ?? '')));

        $reads = (int) ($operations['hit'] ?? 0) + (int) ($operations['miss'] ?? 0);
        $missesCount = (int) ($operations['miss'] ?? 0);
        $hitRate = $reads > 0 ? round((((int) ($operations['hit'] ?? 0)) / $reads) * 100, 1) : 0.0;
        $failures = array_sum(array_intersect_key($operations, array_flip([
            'write_failed',
            'forget_failed',
            'flush_failed',
            'failover',
        ])));
        $highMissRate = $reads >= $this->minimumReads && ($missesCount / $reads) >= $this->highMissRate;

        $presentedItems = [];

        foreach ($items as $index => $item) {
            $operation = $this->operation($item);
            $keyIdentity = $this->keyIdentity($item);
            $relatedExecutions = $keyIdentity === null ? [] : ($keyExecutions[$keyIdentity] ?? []);
            $repeatMissCount = $keyIdentity === null ? 0 : (int) ($misses[$keyIdentity]['count'] ?? 0);
            $definition = $this->operationDefinition($operation);
            $failed = (bool) ($item['failed'] ?? false) || $definition['failed'];
            $attention = $failed
                || $operation === 'flush'
                || ($operation === 'miss' && $repeatMissCount > 1);
            $keyLabel = $this->keyLabel($item);
            $sourceLabel = $this->sourceLabel($item['callsite'] ?? null);
            $storeLabel = $this->stringOrNull($item['store'] ?? null) ?? 'Default store';
            $driverLabel = $this->stringOrNull($item['driver'] ?? null);

            $presentedItems[] = [
                ...$item,
                'id' => 'cache-operation-'.($index + 1),
                'execution' => $index + 1,
                'operation' => $operation,
                'operation_label' => $definition['operation_label'],
                'result' => $definition['result'],
                'result_label' => $definition['result_label'],
                'category' => $definition['category'],
                'failed' => $failed,
                'attention' => $attention,
                'key_label' => $keyLabel,
                'copy_key' => $this->stringOrNull($item['key'] ?? null) ?? $this->stringOrNull($item['key_hash'] ?? null),
                'store_label' => $storeLabel,
                'driver_label' => $driverLabel,
                'duration_label' => $this->durationLabel($item['duration_ms'] ?? null),
                'source_label' => $sourceLabel,
                'source_short_label' => $this->shortSourceLabel($item['callsite'] ?? null),
                'value_label' => $this->valueLabel($item),
                'lifetime_label' => $this->lifetimeLabel($item['seconds'] ?? null, $operation),
                'related_count' => count($relatedExecutions),
                'related_executions' => $relatedExecutions,
                'repeat_miss_count' => $repeatMissCount,
                'what_happened' => $this->whatHappened($definition, $item),
                'why_it_matters' => $this->whyItMatters($operation, $repeatMissCount),
                'check_next' => $this->checkNext($operation, $repeatMissCount, $item),
                'search' => mb_strtolower(implode(' ', array_filter([
                    $definition['operation_label'],
                    $definition['result_label'],
                    $keyLabel,
                    $storeLabel,
                    $driverLabel,
                    $sourceLabel,
                    $item['exception_class'] ?? null,
                    $item['exception_message'] ?? null,
                    ...is_array($item['tags'] ?? null) ? $item['tags'] : [],
                ], fn (mixed $value): bool => is_scalar($value) && (string) $value !== ''))),
                'raw' => $item,
            ];
        }

        $durationMs = round(array_sum($timings), 3);

        return [
            'summary' => [
                'operations' => $operations,
                'reads' => $reads,
                'hits' => (int) ($operations['hit'] ?? 0),
                'misses' => $missesCount,
                'hit_rate' => $hitRate,
                'writes' => (int) ($operations['write'] ?? 0),
                'forgets' => (int) ($operations['forget'] ?? 0),
                'flushes' => (int) ($operations['flush'] ?? 0),
                'failures' => $failures,
                'store_count' => count($stores),
                'unique_key_count' => count($uniqueKeys),
                'timed_count' => count($timings),
                'duration_ms' => $durationMs,
                'repeated_miss_count' => count($repeatedMisses),
                'high_miss_rate' => $highMissRate,
                'attention_count' => count(array_filter($presentedItems, fn (array $item): bool => $item['attention'])),
                'filter_counts' => [
                    'all' => count($presentedItems),
                    'reads' => $reads,
                    'writes' => (int) ($operations['write'] ?? 0) + (int) ($operations['write_failed'] ?? 0),
                    'deletes' => (int) ($operations['forget'] ?? 0) + (int) ($operations['flush'] ?? 0)
                        + (int) ($operations['forget_failed'] ?? 0) + (int) ($operations['flush_failed'] ?? 0),
                    'failed' => $failures,
                ],
            ],
            'items' => $presentedItems,
            'repeated_misses' => $repeatedMisses,
        ];
    }

    /** @param array<string, mixed> $item */
    private function operation(array $item): string
    {
        $operation = strtolower((string) ($item['operation'] ?? 'unknown'));

        return in_array($operation, [
            'hit', 'miss', 'write', 'forget', 'flush',
            'write_failed', 'forget_failed', 'flush_failed', 'failover',
        ], true) ? $operation : 'unknown';
    }

    /** @return array{operation_label: string, result: string, result_label: string, category: string, failed: bool} */
    private function operationDefinition(string $operation): array
    {
        return match ($operation) {
            'hit' => ['operation_label' => 'Get', 'result' => 'hit', 'result_label' => 'Hit', 'category' => 'read', 'failed' => false],
            'miss' => ['operation_label' => 'Get', 'result' => 'miss', 'result_label' => 'Miss', 'category' => 'read', 'failed' => false],
            'write' => ['operation_label' => 'Put', 'result' => 'stored', 'result_label' => 'Stored', 'category' => 'write', 'failed' => false],
            'forget' => ['operation_label' => 'Forget', 'result' => 'forgotten', 'result_label' => 'Forgotten', 'category' => 'delete', 'failed' => false],
            'flush' => ['operation_label' => 'Flush', 'result' => 'flushed', 'result_label' => 'Flushed', 'category' => 'delete', 'failed' => false],
            'write_failed' => ['operation_label' => 'Put', 'result' => 'failed', 'result_label' => 'Failed', 'category' => 'write', 'failed' => true],
            'forget_failed' => ['operation_label' => 'Forget', 'result' => 'failed', 'result_label' => 'Failed', 'category' => 'delete', 'failed' => true],
            'flush_failed' => ['operation_label' => 'Flush', 'result' => 'failed', 'result_label' => 'Failed', 'category' => 'delete', 'failed' => true],
            'failover' => ['operation_label' => 'Failover', 'result' => 'failed_over', 'result_label' => 'Failed over', 'category' => 'failure', 'failed' => true],
            default => ['operation_label' => 'Cache', 'result' => 'unknown', 'result_label' => 'Unknown', 'category' => 'other', 'failed' => false],
        };
    }

    /** @param array<string, mixed> $item */
    private function keyIdentity(array $item): ?string
    {
        $key = $this->stringOrNull($item['key_hash'] ?? null) ?? $this->stringOrNull($item['key'] ?? null);

        if ($key === null) {
            return null;
        }

        return ($this->stringOrNull($item['store'] ?? null) ?? 'default').'|'.$key;
    }

    /** @param array<string, mixed> $item */
    private function keyLabel(array $item): string
    {
        $key = $this->stringOrNull($item['key'] ?? null);

        if ($key !== null) {
            return $key;
        }

        $hash = $this->stringOrNull($item['key_hash'] ?? null);

        return $hash === null ? 'No key' : 'Protected key '.$hash;
    }

    private function durationLabel(mixed $duration): string
    {
        if (! is_numeric($duration)) {
            return 'Timing unavailable';
        }

        $duration = max(0.0, (float) $duration);

        return $duration < 1
            ? number_format($duration, 3, '.', '').' ms'
            : number_format($duration, 2, '.', '').' ms';
    }

    private function sourceLabel(mixed $callsite): string
    {
        if (! is_array($callsite) || ! is_string($callsite['file'] ?? null)) {
            return 'Source unavailable';
        }

        return $callsite['file'].':'.max(1, (int) ($callsite['line'] ?? 1));
    }

    private function shortSourceLabel(mixed $callsite): string
    {
        if (! is_array($callsite) || ! is_string($callsite['file'] ?? null)) {
            return 'Source unavailable';
        }

        return basename(str_replace('\\', '/', $callsite['file'])).':'.max(1, (int) ($callsite['line'] ?? 1));
    }

    /** @param array<string, mixed> $item */
    private function valueLabel(array $item): string
    {
        $type = $this->stringOrNull($item['value_type'] ?? null);

        if ($type === null) {
            return 'Value metadata unavailable';
        }

        if (isset($item['value_size_bytes']) && is_numeric($item['value_size_bytes'])) {
            return $type.', '.number_format((int) $item['value_size_bytes']).' bytes';
        }

        if (isset($item['value_item_count']) && is_numeric($item['value_item_count'])) {
            return $type.', '.number_format((int) $item['value_item_count']).' items';
        }

        $class = $this->stringOrNull($item['value_class'] ?? null);

        return $class ?? $type;
    }

    private function lifetimeLabel(mixed $seconds, string $operation): string
    {
        if (! in_array($operation, ['write', 'write_failed'], true)) {
            return 'Not applicable';
        }

        if ($seconds === null) {
            return 'Forever';
        }

        if (! is_numeric($seconds)) {
            return 'Unavailable';
        }

        $seconds = (int) $seconds;

        return $seconds <= 0 ? 'Expired immediately' : number_format($seconds).' seconds';
    }

    /** @param array<string, mixed> $definition @param array<string, mixed> $item */
    private function whatHappened(array $definition, array $item): string
    {
        $store = $this->stringOrNull($item['store'] ?? null) ?? 'the default store';

        return match ($definition['result']) {
            'hit' => 'The cache returned a stored value from '.$store.'.',
            'miss' => 'No stored value was found in '.$store.'.',
            'stored' => 'A value was stored in '.$store.'.',
            'forgotten' => 'The key was removed from '.$store.'.',
            'flushed' => 'All entries were removed from '.$store.'.',
            'failed' => 'Laravel reported that '.$definition['operation_label'].' did not complete.',
            'failed_over' => 'Laravel moved past a failed cache store.',
            default => 'A cache operation was recorded.',
        };
    }

    private function whyItMatters(string $operation, int $repeatMissCount): string
    {
        if (str_ends_with($operation, '_failed') || $operation === 'failover') {
            return 'The app may be doing extra work or returning stale data because the cache operation failed.';
        }

        if ($operation === 'flush') {
            return 'A flush clears the whole selected store, so later requests may need to rebuild many values.';
        }

        if ($operation === 'miss' && $repeatMissCount > 1) {
            return 'This key missed more than once in the same request, so the same fallback work may be repeated.';
        }

        return match ($operation) {
            'miss' => 'The app had to use its fallback path for this read.',
            'hit' => 'A hit avoided the fallback work behind this key.',
            'write' => 'This changes what later reads can return.',
            'forget' => 'The next read for this key will need to rebuild or fetch the value.',
            default => 'This operation changed or checked request-local cache behavior.',
        };
    }

    /** @param array<string, mixed> $item */
    private function checkNext(string $operation, int $repeatMissCount, array $item): string
    {
        if (str_ends_with($operation, '_failed') || $operation === 'failover') {
            return 'Check the store connection and the source location, then inspect the application log for the underlying error.';
        }

        if ($operation === 'flush') {
            return 'Confirm that clearing the whole store is intended at this source location.';
        }

        if ($operation === 'miss' && $repeatMissCount > 1) {
            return 'Reuse the first fallback result or populate this key before the next read.';
        }

        if ($operation === 'miss') {
            return 'Check whether this miss is expected and whether its fallback work is costly.';
        }

        if (($item['duration_ms'] ?? null) === null) {
            return 'Use the source location to inspect the operation; this Laravel version did not provide a measurable start event.';
        }

        return 'Compare this operation with related uses of the same key and store.';
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
