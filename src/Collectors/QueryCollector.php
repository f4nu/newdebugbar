<?php

namespace NewDebugBar\Collectors;

use NewDebugBar\Support\Redactor;

/** Collects database queries and totals their duration. */
final class QueryCollector extends AbstractCollector
{
    /** @var list<array<string, mixed>> */
    private array $transactions = [];

    private int $droppedTransactions = 0;

    private int $transactionCount = 0;

    private int $rollbackCount = 0;

    public function __construct(
        Redactor $redactor,
        int $maxItems,
        private readonly string $bindingPolicy = 'safe',
    ) {
        parent::__construct($redactor, $maxItems);
    }

    public function key(): string
    {
        return 'queries';
    }

    public function label(): string
    {
        return 'Queries';
    }

    public function summary(): array
    {
        $summary = parent::summary();

        return [
            ...$summary,
            'truncated' => $summary['truncated'] || $this->droppedTransactions > 0,
            'duration_ms' => round($this->totals['duration_ms'] ?? 0, 2),
            'transaction_count' => $this->transactionCount,
            'transaction_retained_count' => count($this->transactions),
            'transaction_dropped_count' => $this->droppedTransactions,
            'rollback_count' => $this->rollbackCount,
        ];
    }

    public function reset(): void
    {
        parent::reset();
        $this->transactions = [];
        $this->droppedTransactions = 0;
        $this->transactionCount = 0;
        $this->rollbackCount = 0;
    }

    public function record(array $item): void
    {
        if (isset($item['sql']) && is_string($item['sql'])) {
            $sourceSql = $item['sql'];
            $item['sql'] = $this->redactor->cleanSql($sourceSql);
            $item['source_preserved'] = $item['sql'] === $sourceSql;
        }

        if (array_key_exists('bindings', $item)) {
            $bindings = $item['bindings'];
            $item['bindings'] = $this->redactor->cleanBindings(
                is_array($bindings) ? $bindings : [],
                $this->bindingPolicy,
            );
            $item['binding_policy'] = $this->bindingPolicy;
            $item['bindings_complete'] = $this->bindingPolicy === 'full'
                && ! array_key_exists('__truncated__', $item['bindings'])
                && ! $this->containsRedactedValue($item['bindings']);
        }

        $runnable = ($item['source_preserved'] ?? false)
            && ($item['bindings_complete'] ?? false)
            && is_string($item['runnable_sql'] ?? null);
        $item['runnable_available'] = $runnable;

        if (! $runnable) {
            unset($item['runnable_sql']);
        }

        parent::record($item);
    }

    /** @param array<string, mixed> $item */
    public function recordTransaction(array $item): void
    {
        $this->transactionCount++;

        if (($item['kind'] ?? null) === 'rollback') {
            $this->rollbackCount++;
        }

        if ($this->retainedCount() >= $this->maxItems) {
            $this->droppedTransactions++;

            return;
        }

        /** @var array<string, mixed> $safe */
        $safe = $this->redactor->clean($item);
        $this->transactions[] = $safe;
    }

    public function payload(): array
    {
        return [
            ...parent::payload(),
            'transactions' => $this->transactions,
            'transaction_retained' => count($this->transactions),
            'transaction_dropped' => $this->droppedTransactions,
            'transaction_total' => $this->transactionCount,
        ];
    }

    protected function track(array $item): void
    {
        $this->totals['duration_ms'] = ($this->totals['duration_ms'] ?? 0) + (float) ($item['duration_ms'] ?? 0);
    }

    protected function retainedCount(): int
    {
        return count($this->items) + count($this->transactions);
    }

    /** @param array<array-key, mixed> $values */
    private function containsRedactedValue(array $values): bool
    {
        foreach ($values as $value) {
            if (is_array($value) && $this->containsRedactedValue($value)) {
                return true;
            }

            if (is_string($value) && in_array($value, ['[redacted]', '[maximum depth reached]'], true)) {
                return true;
            }
        }

        return false;
    }
}
