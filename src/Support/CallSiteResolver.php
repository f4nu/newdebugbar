<?php

namespace NewDebugBar\Support;

use Throwable;

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
        $compiledViewFrame = null;

        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $this->scanLimit) as $frame) {
            $file = isset($frame['file']) ? $this->normalizePath((string) $frame['file']) : null;

            if ($file === null) {
                continue;
            }

            $compiledViewFrame ??= $this->compiledAuthorizationLocation(
                $file,
                (int) ($frame['line'] ?? 0),
            );

            if (! $this->isApplicationFile($file)) {
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

        if ($compiledViewFrame !== null) {
            array_unshift($frames, [
                ...$compiledViewFrame,
                'function' => 'Blade authorization directive',
            ]);
            $frames = array_slice($frames, 0, $this->maxFrames);
        }

        return [
            'callsite' => $frames === [] ? null : [
                'file' => $frames[0]['file'],
                'line' => $frames[0]['line'],
            ],
            'stack' => $frames,
        ];
    }

    /** Returns the first application frame from a thrown error. */
    public function fromThrowable(Throwable $exception): ?array
    {
        foreach ([
            ['file' => $exception->getFile(), 'line' => $exception->getLine()],
            ...$exception->getTrace(),
        ] as $frame) {
            if (! isset($frame['file'])) {
                continue;
            }

            $location = $this->location((string) $frame['file'], (int) ($frame['line'] ?? 1));

            if ($location !== null) {
                return $location;
            }
        }

        return null;
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

    /** @return array{file: string, line: int}|null */
    public function templateLocation(string $path, int $line = 1): ?array
    {
        $file = $this->normalizePath($path);

        if ($file === null) {
            return null;
        }

        $project = rtrim(str_replace('\\', '/', $this->projectPath), '/').'/';

        return [
            'file' => str_starts_with($file, $project) ? substr($file, strlen($project)) : $file,
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

    /** @return array{file: string, line: int}|null */
    private function compiledAuthorizationLocation(string $file, int $line): ?array
    {
        if (! str_contains($file, '/storage/framework/views/') || ! str_ends_with($file, '.php')) {
            return null;
        }

        if (! is_readable($file)) {
            return null;
        }

        $compiled = file_get_contents($file);

        if (! is_string($compiled) || ! preg_match('/<\?php \/\*\*PATH (.+?) ENDPATH\*\*\/ \?>\s*$/s', $compiled, $pathMatch)) {
            return null;
        }

        $source = $this->normalizePath($pathMatch[1]);

        if ($source === null || ! $this->isApplicationFile($source)) {
            return null;
        }

        if (! is_readable($source) || ! is_string($sourceContents = file_get_contents($source))) {
            return null;
        }

        $compiledLines = preg_split('/\R/', $compiled) ?: [];
        $sourceLines = preg_split('/\R/', $sourceContents) ?: [];
        $compiledDirectives = [];
        $sourceDirectives = [];

        foreach ($compiledLines as $index => $compiledLine) {
            if (
                str_contains($compiledLine, '\\Illuminate\\Contracts\\Auth\\Access\\Gate::class')
                && (str_contains($compiledLine, '->check(') || str_contains($compiledLine, '->any('))
            ) {
                $compiledDirectives[] = $index + 1;
            }
        }

        foreach ($sourceLines as $index => $sourceLine) {
            if (preg_match('/(?<!@)@(?:can|cannot|canany|elsecan|elsecannot)\s*\(/', $sourceLine) === 1) {
                $sourceDirectives[] = $index + 1;
            }
        }

        $directiveIndex = array_search($line, $compiledDirectives, true);

        if ($directiveIndex === false) {
            foreach ($compiledDirectives as $index => $compiledLine) {
                if (abs($compiledLine - $line) <= 2) {
                    $directiveIndex = $index;
                    break;
                }
            }
        }

        if ($directiveIndex !== false && isset($sourceDirectives[$directiveIndex])) {
            return [
                'file' => $this->relativePath($source),
                'line' => $sourceDirectives[$directiveIndex],
            ];
        }

        return null;
    }

    /** @param array<string, mixed> $frame */
    private function functionName(array $frame): string
    {
        return (string) (($frame['class'] ?? '').($frame['type'] ?? '').($frame['function'] ?? ''));
    }
}
