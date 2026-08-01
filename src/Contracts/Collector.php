<?php

namespace NewDebugBar\Contracts;

/**
 * Internal contract. It is intentionally not a package extension point yet.
 *
 * @internal
 */
interface Collector
{
    public function key(): string;

    public function label(): string;

    public function reset(): void;

    /** @param array<string, mixed> $item */
    public function record(array $item): void;

    /** @return array<string, int|float|string> */
    public function summary(): array;

    /** @return array{items: array<int, array<string, mixed>>, dropped: int} */
    public function payload(): array;
}
