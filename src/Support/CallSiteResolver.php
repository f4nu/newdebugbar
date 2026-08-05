<?php

namespace NewDebugBar\Support;

/** Captures a short project-relative stack without arguments or vendor frames. */
final class CallSiteResolver
{
    public function __construct(
        private readonly string $projectPath,
        private readonly string $packagePath,
        private readonly bool $enabled = true,
        private readonly int $maxFrames = 5,
        private readonly int $scanLimit = 40,
    ) {}

    /** @return array{callsite: array{file: string, line: int}|null, stack: list<array{file: string, line: int, function: string}>} */
    public function capture(): array
    {
        if (! $this->enabled) {
            return ['callsite' => null, 'stack' => []];
        }

        $frames = [];

        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $this->scanLimit) as $frame) {
            $file = isset($frame['file']) ? $this->normalizePath((string) $frame['file']) : null;

            if ($file === null || ! $this->isApplicationFile($file)) {
                continue;
            }

            $frames[] = [
                'file' => $this->relativePath($file),
                'line' => (int) ($frame['line'] ?? 0),
                'function' => $this->functionName($frame),
            ];

            if (count($frames) >= $this->maxFrames) {
                break;
            }
        }

        return [
            'callsite' => $frames === [] ? null : [
                'file' => $frames[0]['file'],
                'line' => $frames[0]['line'],
            ],
            'stack' => $frames,
        ];
    }

    /** @return array{file: string, line: int}|null */
    public function location(string $path, int $line = 1): ?array
    {
        if (! $this->enabled) {
            return null;
        }

        $file = $this->normalizePath($path);

        if ($file === null || ! $this->isApplicationFile($file)) {
            return null;
        }

        return [
            'file' => $this->relativePath($file),
            'line' => max(1, $line),
        ];
    }

    private function normalizePath(string $path): ?string
    {
        $realPath = realpath($path);

        return $realPath === false ? null : str_replace('\\', '/', $realPath);
    }

    private function isApplicationFile(string $file): bool
    {
        $project = rtrim(str_replace('\\', '/', $this->projectPath), '/').'/';
        $package = rtrim(str_replace('\\', '/', $this->packagePath), '/').'/';

        return str_starts_with($file, $project)
            && ! str_starts_with($file, $project.'vendor/')
            && ! str_starts_with($file, $project.'storage/')
            && ! str_starts_with($file, $package.'src/');
    }

    private function relativePath(string $file): string
    {
        $project = rtrim(str_replace('\\', '/', $this->projectPath), '/').'/';

        return str_starts_with($file, $project) ? substr($file, strlen($project)) : basename($file);
    }

    /** @param array<string, mixed> $frame */
    private function functionName(array $frame): string
    {
        return (string) (($frame['class'] ?? '').($frame['type'] ?? '').($frame['function'] ?? ''));
    }
}
