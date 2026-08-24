<?php

namespace NewDebugBar\Collectors;

/** Summarizes cache reads and writes for the current request. */
final class CacheCollector extends AbstractCollector
{
    /** @var array<string, float> */
    private array $timings = [];

    private int $timingSequence = 0;

    public function key(): string
    {
        return 'cache';
    }

    public function label(): string
    {
        return 'Cache';
    }

    public function summary(): array
    {
        return [
            ...parent::summary(),
            'hits' => $this->totals['hit'] ?? 0,
            'misses' => $this->totals['miss'] ?? 0,
            'writes' => $this->totals['write'] ?? 0,
            'forgets' => $this->totals['forget'] ?? 0,
            'flushes' => $this->totals['flush'] ?? 0,
            'failures' => $this->totals['failures'] ?? 0,
            'timed_count' => count($this->timings),
            'duration_ms' => round(array_sum($this->timings), 3),
        ];
    }

    public function reset(): void
    {
        parent::reset();
        $this->timings = [];
        $this->timingSequence = 0;
    }

    protected function track(array $item): void
    {
        $operation = (string) ($item['operation'] ?? '');
        $this->totals[$operation] = ($this->totals[$operation] ?? 0) + 1;

        if ((bool) ($item['failed'] ?? false) || str_ends_with($operation, '_failed') || $operation === 'failover') {
            $this->totals['failures'] = ($this->totals['failures'] ?? 0) + 1;
        }

        if (! isset($item['duration_ms']) || ! is_numeric($item['duration_ms'])) {
            return;
        }

        $timingId = is_string($item['duration_id'] ?? null) && $item['duration_id'] !== ''
            ? $item['duration_id']
            : 'operation:'.(++$this->timingSequence);
        $duration = max(0.0, (float) $item['duration_ms']);

        if (isset($this->timings[$timingId])) {
            $this->timings[$timingId] = max($this->timings[$timingId], $duration);

            return;
        }

        if (count($this->timings) < $this->maxItems) {
            $this->timings[$timingId] = $duration;
        }
    }
}
