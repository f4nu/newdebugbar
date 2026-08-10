<?php

namespace NewDebugBar\Livewire;

use NewDebugBar\Support\Redactor;

/** Produces bounded public-property changes without retaining state snapshots. */
final class StateDiff
{
    public function __construct(
        private readonly Redactor $redactor,
        private readonly int $maxChanges = 500,
        private readonly int $maxDepth = 5,
    ) {}

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<string, mixed>  $submitted
     * @return array{changes: list<array<string, mixed>>, dropped: int}
     */
    public function between(array $before, array $after, array $submitted = []): array
    {
        $beforePaths = $this->flatten($before);
        $afterPaths = $this->flatten($after);
        $paths = array_values(array_unique([...array_keys($beforePaths), ...array_keys($afterPaths)]));
        sort($paths);
        $changes = [];
        $dropped = 0;

        foreach ($paths as $path) {
            $hadBefore = array_key_exists($path, $beforePaths);
            $hasAfter = array_key_exists($path, $afterPaths);
            $beforeValue = $beforePaths[$path] ?? null;
            $afterValue = $afterPaths[$path] ?? null;

            if ($hadBefore === $hasAfter && $beforeValue === $afterValue) {
                continue;
            }

            if (count($changes) >= $this->maxChanges) {
                $dropped++;

                continue;
            }

            $key = $this->lastSegment($path);
            $cleanBefore = $hadBefore ? $this->redactor->clean($beforeValue, key: $key) : null;
            $cleanAfter = $hasAfter ? $this->redactor->clean($afterValue, key: $key) : null;
            $redacted = $cleanBefore === '[redacted]' || $cleanAfter === '[redacted]';

            $changes[] = [
                'path' => $path,
                'type' => $hasAfter ? get_debug_type($afterValue) : 'removed',
                'before' => $cleanBefore,
                'submitted' => array_key_exists($path, $submitted)
                    ? $this->redactor->clean($submitted[$path], key: $key)
                    : null,
                'server' => $cleanAfter,
                'browser' => ['status' => 'unknown'],
                'redacted' => $redacted,
                'confidence' => 'observed',
            ];
        }

        return ['changes' => $changes, 'dropped' => $dropped];
    }

    /** @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function flatten(array $values, string $prefix = '', int $depth = 0): array
    {
        $flat = [];

        foreach ($values as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value) && $value !== [] && $depth < $this->maxDepth) {
                $flat += $this->flatten($value, $path, $depth + 1);

                continue;
            }

            $flat[$path] = $value;
        }

        return $flat;
    }

    private function lastSegment(string $path): string
    {
        $segments = explode('.', $path);

        return (string) end($segments);
    }
}
