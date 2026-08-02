<?php

namespace NewDebugBar\Collectors;

/** Captures direct Redis commands without arguments and removes cache duplicates. */
final class RedisCollector extends AbstractCollector
{
    /** @var array{command: string, duration_ms: float, failed: bool}|null */
    private ?array $lastDropped = null;

    /** @var array<string, list<string>> */
    private const CACHE_COMMANDS = [
        'hit' => ['GET', 'MGET', 'GETDEL', 'HGET', 'HMGET', 'HEXISTS'],
        'miss' => ['GET', 'MGET', 'GETDEL', 'HGET', 'HMGET', 'HEXISTS'],
        'write' => ['SET', 'SETEX', 'PSETEX', 'SETNX', 'MSET', 'HSET', 'HSETNX', 'INCR', 'INCRBY', 'DECR', 'DECRBY', 'EVAL', 'EVALSHA'],
        'forget' => ['DEL', 'UNLINK', 'HDEL'],
        'flush' => ['FLUSHDB', 'FLUSHALL'],
    ];

    public function key(): string
    {
        return 'redis';
    }

    public function label(): string
    {
        return 'Redis';
    }

    public function summary(): array
    {
        return [
            ...parent::summary(),
            'duration_ms' => round($this->totals['duration_ms'] ?? 0, 2),
            'failed_count' => (int) ($this->totals['failed_count'] ?? 0),
        ];
    }

    public function reset(): void
    {
        parent::reset();
        $this->lastDropped = null;
    }

    public function record(array $item): void
    {
        $before = $this->dropped;
        parent::record($item);

        if ($this->dropped > $before) {
            $this->lastDropped = [
                'command' => strtoupper((string) ($item['command'] ?? '')),
                'duration_ms' => (float) ($item['duration_ms'] ?? 0),
                'failed' => (bool) ($item['failed'] ?? false),
            ];
        } else {
            $this->lastDropped = null;
        }
    }

    public function excludeCacheOperation(string $operation): void
    {
        $commands = self::CACHE_COMMANDS[$operation] ?? [];

        for ($index = count($this->items) - 1; $index >= 0; $index--) {
            if (in_array($this->items[$index]['command'] ?? null, $commands, true)) {
                $item = $this->items[$index];
                array_splice($this->items, $index, 1);
                $this->totals['duration_ms'] = max(0, ($this->totals['duration_ms'] ?? 0) - (float) ($item['duration_ms'] ?? 0));
                $this->totals['failed_count'] = max(0, ($this->totals['failed_count'] ?? 0) - (($item['failed'] ?? false) ? 1 : 0));

                return;
            }
        }

        if ($this->lastDropped !== null && in_array($this->lastDropped['command'], $commands, true)) {
            $this->dropped = max(0, $this->dropped - 1);
            $this->totals['duration_ms'] = max(0, ($this->totals['duration_ms'] ?? 0) - $this->lastDropped['duration_ms']);
            $this->totals['failed_count'] = max(0, ($this->totals['failed_count'] ?? 0) - ($this->lastDropped['failed'] ? 1 : 0));
            $this->lastDropped = null;
        }
    }

    protected function track(array $item): void
    {
        $this->totals['duration_ms'] = ($this->totals['duration_ms'] ?? 0) + (float) ($item['duration_ms'] ?? 0);
        $this->totals['failed_count'] = ($this->totals['failed_count'] ?? 0) + (($item['failed'] ?? false) ? 1 : 0);
    }
}
