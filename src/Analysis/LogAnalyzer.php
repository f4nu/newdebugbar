<?php

namespace NewDebugBar\Analysis;

use Throwable;

/** Builds scan-friendly log entries without changing the captured chronology. */
final class LogAnalyzer
{
    /** @var list<string> */
    private const ATTENTION_LEVELS = ['warning', 'error', 'critical', 'alert', 'emergency'];

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{items: list<array<string, mixed>>, groups: list<array<string, mixed>>, summary: array<string, mixed>}
     */
    public function analyze(array $items): array
    {
        $normalized = [];
        $groups = [];
        $levels = [];
        $channels = [];
        $attentionCount = 0;

        foreach (array_values(array_filter($items, 'is_array')) as $index => $item) {
            $entry = $this->normalize($item, $index + 1);
            $normalized[] = $entry;
            $levels[$entry['level']] = ($levels[$entry['level']] ?? 0) + 1;
            $channelKey = $entry['channel_filter'];
            $channels[$channelKey] = ($channels[$channelKey] ?? 0) + 1;

            if ($entry['attention']) {
                $attentionCount++;
            }

            $lastIndex = array_key_last($groups);

            if ($lastIndex !== null && $this->sameLogicalRecord($groups[$lastIndex], $entry)) {
                $groups[$lastIndex]['repeat_count']++;
                $groups[$lastIndex]['last_sequence'] = $entry['sequence'];
                $groups[$lastIndex]['last_at_ms'] = $entry['at_ms'];
                $groups[$lastIndex]['last_occurred_at'] = $entry['occurred_at'];
                $groups[$lastIndex]['occurrences'][] = $this->occurrence($entry);

                continue;
            }

            $groups[] = [
                ...$entry,
                'first_sequence' => $entry['sequence'],
                'last_sequence' => $entry['sequence'],
                'first_at_ms' => $entry['at_ms'],
                'last_at_ms' => $entry['at_ms'],
                'first_occurred_at' => $entry['occurred_at'],
                'last_occurred_at' => $entry['occurred_at'],
                'repeat_count' => 1,
                'occurrences' => [$this->occurrence($entry)],
            ];
        }

        return [
            'items' => $normalized,
            'groups' => $groups,
            'summary' => [
                'attention_count' => $attentionCount,
                'group_count' => count($groups),
                'repeated_count' => count($normalized) - count($groups),
                'levels' => $levels,
                'channels' => $channels,
            ],
        ];
    }

    /** @param array<string, mixed> $item @return array<string, mixed> */
    private function normalize(array $item, int $sequence): array
    {
        $level = strtolower(trim((string) ($item['level'] ?? 'log')));
        $level = $level === '' ? 'log' : $level;
        $rawChannel = is_string($item['channel'] ?? null) ? trim($item['channel']) : '';
        $channel = in_array(strtolower($rawChannel), ['', 'null', '__unknown__'], true)
            ? null
            : $rawChannel;
        $channelLabel = $channel ?? 'No channel';
        $channelFilter = $channel ?? '__unknown__';
        $context = is_array($item['context'] ?? null) ? $item['context'] : [];
        $callsite = is_array($item['callsite'] ?? null) ? $item['callsite'] : null;
        $callsiteLabel = isset($callsite['file'], $callsite['line'])
            ? $callsite['file'].':'.$callsite['line']
            : null;
        $message = (string) ($item['message'] ?? '');
        $relatedException = is_array($item['related_exception'] ?? null)
            ? $item['related_exception']
            : null;
        $search = $this->searchText([
            $level,
            $message,
            $channel,
            $callsiteLabel,
            $context,
            $relatedException,
        ]);

        return [
            ...$item,
            'sequence' => $sequence,
            'level' => $level,
            'level_label' => ucfirst($level),
            'attention' => in_array($level, self::ATTENTION_LEVELS, true),
            'message' => $message,
            'channel' => $channel,
            'channel_label' => $channelLabel,
            'channel_filter' => $channelFilter,
            'context' => $context,
            'context_fields' => $this->contextFields($context),
            'context_json' => $this->json($context),
            'callsite' => $callsite,
            'callsite_label' => $callsiteLabel ?? '—',
            'callsite_short_label' => $callsiteLabel === null
                ? '—'
                : basename(str_replace('\\', '/', (string) $callsite['file'])).':'.$callsite['line'],
            'related_exception' => $relatedException,
            'occurred_at' => is_string($item['occurred_at'] ?? null) ? $item['occurred_at'] : null,
            'at_ms' => is_numeric($item['at_ms'] ?? null) ? round((float) $item['at_ms'], 3) : null,
            'search' => $search,
        ];
    }

    /** @param array<string, mixed> $left @param array<string, mixed> $right */
    private function sameLogicalRecord(array $left, array $right): bool
    {
        foreach (['level', 'message', 'channel', 'context', 'callsite', 'stack', 'related_exception', 'lifecycle'] as $key) {
            if (($left[$key] ?? null) !== ($right[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed> $entry @return array{sequence: int, at_ms: float|null, occurred_at: string|null} */
    private function occurrence(array $entry): array
    {
        return [
            'sequence' => $entry['sequence'],
            'at_ms' => $entry['at_ms'],
            'occurred_at' => $entry['occurred_at'],
        ];
    }

    /** @param array<string, mixed> $context @return list<array{key: string, value: mixed, preview: string, structured: bool}> */
    private function contextFields(array $context): array
    {
        $fields = [];

        foreach ($context as $key => $value) {
            $structured = is_array($value);
            $preview = match (true) {
                $structured => count($value).' '.(count($value) === 1 ? 'value' : 'values'),
                $value === null => 'null',
                is_bool($value) => $value ? 'true' : 'false',
                default => (string) $value,
            };
            $preview = preg_replace('/\s+/u', ' ', $preview) ?? $preview;

            if (mb_strlen($preview) > 140) {
                $preview = mb_substr($preview, 0, 139).'…';
            }

            $fields[] = [
                'key' => (string) $key,
                'value' => $value,
                'preview' => $preview,
                'structured' => $structured,
            ];
        }

        return $fields;
    }

    private function searchText(array $values): string
    {
        return mb_strtolower(implode(' ', array_map(
            fn (mixed $value): string => is_scalar($value) || $value === null
                ? (string) $value
                : $this->json($value),
            $values,
        )));
    }

    private function json(mixed $value): string
    {
        try {
            return json_encode(
                $value,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
            );
        } catch (Throwable) {
            return '[unavailable]';
        }
    }
}
