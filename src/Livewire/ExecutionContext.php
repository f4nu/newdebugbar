<?php

namespace NewDebugBar\Livewire;

use Closure;
use Illuminate\Support\Str;
use Throwable;

/** Keeps proven Livewire attribution scoped to the work that produced it. */
final class ExecutionContext
{
    /** @var list<array{token: string, context: array<string, mixed>}> */
    private array $stack = [];

    /** @param array<string, mixed> $context */
    public function push(array $context): string
    {
        $token = (string) Str::uuid();
        $this->stack[] = ['token' => $token, 'context' => $context];

        return $token;
    }

    public function pop(string $token): void
    {
        for ($index = count($this->stack) - 1; $index >= 0; $index--) {
            if ($this->stack[$index]['token'] !== $token) {
                continue;
            }

            array_splice($this->stack, $index, 1);

            return;
        }
    }

    /** @return array<string, mixed>|null */
    public function current(): ?array
    {
        $frame = end($this->stack);

        return is_array($frame) ? $frame['context'] : null;
    }

    /**
     * @template T
     *
     * @param  array<string, mixed>  $context
     * @param  Closure(): T  $callback
     * @return T
     *
     * @throws Throwable
     */
    public function run(array $context, Closure $callback): mixed
    {
        $token = $this->push($context);

        try {
            return $callback();
        } finally {
            $this->pop($token);
        }
    }

    public function clear(): void
    {
        $this->stack = [];
    }
}
