<?php

namespace NewDebugBar\Collectors;

use NewDebugBar\Contracts\Collector;
use NewDebugBar\Support\Redactor;

/** Keeps bounded Laravel AI runs together with their request-scoped tool calls. */
final class AiCollector implements Collector
{
    /** @var list<array<string, mixed>> */
    private array $items = [];

    /** @var array<string, array<string, mixed>> */
    private array $pending = [];

    /** @var array<string, true> */
    private array $seenInvocations = [];

    /** @var array<string, true> */
    private array $seenTools = [];

    private int $total = 0;

    private int $toolTotal = 0;

    private int $toolRetained = 0;

    public function __construct(
        private readonly Redactor $redactor,
        private readonly int $maxItems,
        private readonly bool $captureContent = false,
    ) {}

    public function key(): string
    {
        return 'ai';
    }

    public function label(): string
    {
        return 'AI activity';
    }

    public function reset(): void
    {
        $this->items = [];
        $this->pending = [];
        $this->seenInvocations = [];
        $this->seenTools = [];
        $this->total = 0;
        $this->toolTotal = 0;
        $this->toolRetained = 0;
    }

    /** @param array<string, mixed> $item */
    public function record(array $item): void
    {
        $this->total++;

        if ($this->retainedCount() >= $this->maxItems) {
            return;
        }

        $item = $this->safe([
            'status' => 'completed',
            'streamed' => false,
            'content_captured' => $this->captureContent,
            'tools' => [],
            'tool_count' => 0,
            ...$item,
        ]);
        $this->items[] = $this->withoutHiddenContent($item);
    }

    /** @param array<string, mixed> $item */
    public function startInvocation(string $invocationId, array $item): void
    {
        if (isset($this->seenInvocations[$invocationId])) {
            return;
        }

        $this->seenInvocations[$invocationId] = true;
        $this->total++;

        if ($this->retainedCount() >= $this->maxItems) {
            return;
        }

        $item = $this->safe([
            'invocation_id' => $invocationId,
            'status' => 'running',
            'streamed' => false,
            'content_captured' => $this->captureContent,
            'tools' => [],
            'tool_count' => 0,
            ...$item,
        ]);
        $this->pending[$invocationId] = $this->withoutHiddenContent($item);
    }

    /** @param array<string, mixed> $item */
    public function finishInvocation(string $invocationId, array $item): void
    {
        if (! isset($this->pending[$invocationId])) {
            $this->startInvocation($invocationId, []);
        }

        if (! isset($this->pending[$invocationId])) {
            return;
        }

        $run = $this->pending[$invocationId];
        $finished = $this->safe($item);
        $startedAt = $this->number($run['started_at_ms'] ?? $run['at_ms'] ?? null);
        $finishedAt = $this->number($finished['finished_at_ms'] ?? $finished['at_ms'] ?? null);
        $tools = [];

        foreach ((array) ($run['tools'] ?? []) as $tool) {
            if (! is_array($tool)) {
                continue;
            }

            $tools[] = [
                ...$tool,
                'status' => ($tool['status'] ?? null) === 'completed' ? 'completed' : 'incomplete',
            ];
        }

        $completed = $this->safe([
            ...$run,
            ...$finished,
            'status' => 'completed',
            'started_at_ms' => $startedAt,
            'finished_at_ms' => $finishedAt,
            'duration_ms' => $startedAt !== null && $finishedAt !== null
                ? round(max(0, $finishedAt - $startedAt), 2)
                : null,
            'tool_count' => (int) ($run['tool_count'] ?? count($tools)),
            'tools' => $tools,
            'content_captured' => $this->captureContent,
        ]);

        unset($this->pending[$invocationId]);
        $this->items[] = $this->withoutHiddenContent($completed);
    }

    /** @param array<string, mixed> $item */
    public function startTool(string $invocationId, string $toolInvocationId, array $item): void
    {
        $key = $invocationId."\0".$toolInvocationId;

        if (! isset($this->pending[$invocationId]) || isset($this->seenTools[$key])) {
            return;
        }

        $this->seenTools[$key] = true;
        $this->toolTotal++;
        $run = $this->pending[$invocationId];
        $tools = is_array($run['tools'] ?? null) ? $run['tools'] : [];
        $run['tool_count'] = (int) ($run['tool_count'] ?? 0) + 1;

        if ($this->toolRetained < $this->maxItems) {
            $tool = $this->safe([
                'tool_invocation_id' => $toolInvocationId,
                'status' => 'running',
                ...$item,
            ]);
            $tools[$toolInvocationId] = $this->withoutHiddenToolContent($tool);
            $this->toolRetained++;
        }

        $run['tools'] = $tools;
        $this->pending[$invocationId] = $run;
    }

    /** @param array<string, mixed> $item */
    public function finishTool(string $invocationId, string $toolInvocationId, array $item): void
    {
        if (! isset($this->pending[$invocationId])) {
            return;
        }

        $key = $invocationId."\0".$toolInvocationId;

        if (! isset($this->seenTools[$key])) {
            $this->startTool($invocationId, $toolInvocationId, []);
        }

        $run = $this->pending[$invocationId];
        $tools = is_array($run['tools'] ?? null) ? $run['tools'] : [];

        if (! isset($tools[$toolInvocationId])) {
            $this->pending[$invocationId] = $run;

            return;
        }

        $tool = is_array($tools[$toolInvocationId]) ? $tools[$toolInvocationId] : [];
        $finished = $this->safe($item);
        $startedAt = $this->number($tool['started_at_ms'] ?? null);
        $finishedAt = $this->number($finished['finished_at_ms'] ?? null);
        $tools[$toolInvocationId] = $this->withoutHiddenToolContent($this->safe([
            ...$tool,
            ...$finished,
            'tool_invocation_id' => $toolInvocationId,
            'status' => 'completed',
            'started_at_ms' => $startedAt,
            'finished_at_ms' => $finishedAt,
            'duration_ms' => $startedAt !== null && $finishedAt !== null
                ? round(max(0, $finishedAt - $startedAt), 2)
                : null,
        ]));
        $run['tools'] = $tools;
        $this->pending[$invocationId] = $run;
    }

    public function summary(): array
    {
        $items = $this->profileItems();
        $retained = count($items);
        $usage = [
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'cache_write_input_tokens' => 0,
            'cache_read_input_tokens' => 0,
            'reasoning_tokens' => 0,
        ];

        foreach ($items as $item) {
            foreach ($usage as $key => $total) {
                $usage[$key] = $total + (int) ($item['usage'][$key] ?? 0);
            }
        }

        return [
            'count' => $this->total,
            'retained_count' => $retained,
            'dropped_count' => max(0, $this->total - $retained),
            'truncated' => $retained < $this->total,
            'completed_count' => count($this->items),
            'incomplete_count' => count($this->pending),
            'streamed_count' => count(array_filter($items, fn (array $item): bool => (bool) ($item['streamed'] ?? false))),
            'tool_count' => $this->toolTotal,
            'tool_retained_count' => $this->toolRetained,
            'tool_dropped_count' => max(0, $this->toolTotal - $this->toolRetained),
            'token_count' => $usage['prompt_tokens'] + $usage['completion_tokens'],
            'usage' => $usage,
            'content_captured' => $this->captureContent,
        ];
    }

    public function payload(): array
    {
        return [
            'items' => $this->profileItems(),
            'content_captured' => $this->captureContent,
            'capture_scope' => 'current_profile_only',
        ];
    }

    /** @return list<array<string, mixed>> */
    private function profileItems(): array
    {
        $pending = array_map(function (array $item): array {
            $tools = [];

            foreach ((array) ($item['tools'] ?? []) as $tool) {
                if (is_array($tool)) {
                    $tools[] = [...$tool, 'status' => 'incomplete'];
                }
            }

            return $this->withoutHiddenContent($this->safe([
                ...$item,
                'status' => 'incomplete',
                'tools' => $tools,
            ]));
        }, array_values($this->pending));

        return [...$this->items, ...$pending];
    }

    /** @param array<string, mixed> $item @return array<string, mixed> */
    private function withoutHiddenContent(array $item): array
    {
        if ($this->captureContent) {
            return $item;
        }

        unset($item['prompt'], $item['response']);

        foreach ((array) ($item['tools'] ?? []) as $key => $tool) {
            if (is_array($tool)) {
                $item['tools'][$key] = $this->withoutHiddenToolContent($tool);
            }
        }

        return $item;
    }

    /** @param array<string, mixed> $tool @return array<string, mixed> */
    private function withoutHiddenToolContent(array $tool): array
    {
        if (! $this->captureContent) {
            unset($tool['arguments'], $tool['result']);
        }

        return $tool;
    }

    /** @param array<string, mixed> $item @return array<string, mixed> */
    private function safe(array $item): array
    {
        $safe = $this->redactor->clean($item);

        return is_array($safe) ? $safe : [];
    }

    private function retainedCount(): int
    {
        return count($this->items) + count($this->pending);
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
