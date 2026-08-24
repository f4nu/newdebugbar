<?php

namespace NewDebugBar\Support;

use DateTimeInterface;

/** Correlates Laravel log events with the configured channel that emitted them. */
final class LogChannelTracker
{
    private const MAX_PENDING = 100;

    /** @var list<array{record: object, record_key: string, channel: string, level: string, message: string, context: array<string, mixed>}> */
    private array $pending = [];

    public function remember(string $channel, mixed $record): void
    {
        if (! is_object($record)) {
            return;
        }

        $level = $record->level ?? '';
        $level = is_object($level) && method_exists($level, 'getName')
            ? $level->getName()
            : (is_object($level) && isset($level->name) ? $level->name : (string) $level);
        $context = is_array($record->context ?? null) ? $record->context : [];
        $message = (string) ($record->message ?? '');
        $datetime = $record->datetime ?? null;
        $recordKey = $datetime instanceof DateTimeInterface
            ? strtolower($level).'|'.$message.'|'.$datetime->format('U.u')
            : 'object:'.spl_object_id($record);

        foreach (array_reverse($this->pending) as $pending) {
            if ($pending['record_key'] === $recordKey) {
                return;
            }
        }

        $this->pending[] = [
            'record' => $record,
            'record_key' => $recordKey,
            'channel' => $channel,
            'level' => strtolower($level),
            'message' => $message,
            'context' => $context,
        ];

        if (count($this->pending) > self::MAX_PENDING) {
            array_shift($this->pending);
        }
    }

    /** @param array<string, mixed> $context */
    public function take(string $level, string $message, array $context): ?string
    {
        $fallback = null;

        foreach ($this->pending as $index => $pending) {
            if ($pending['level'] !== strtolower($level) || $pending['message'] !== $message) {
                continue;
            }

            $fallback ??= $index;

            if ($pending['context'] === $context) {
                return $this->remove($index);
            }
        }

        return $fallback === null ? null : $this->remove($fallback);
    }

    private function remove(int $index): string
    {
        $channel = $this->pending[$index]['channel'];
        array_splice($this->pending, $index, 1);

        return $channel;
    }
}
