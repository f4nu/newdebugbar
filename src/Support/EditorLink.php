<?php

namespace NewDebugBar\Support;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/** Converts project-relative source locations into configured editor links. */
final class EditorLink
{
    public function __construct(
        private readonly string $projectPath,
        private readonly string $editor = 'vscode',
        private readonly ?string $remotePath = null,
        private readonly ?string $localPath = null,
    ) {}

    /** @param array<string, mixed>|null $location @return array<string, mixed>|null */
    public function enrich(?array $location): ?array
    {
        if (! is_array($location) || ! is_string($location['file'] ?? null)) {
            return $location;
        }

        $line = max(1, (int) ($location['line'] ?? 1));
        $path = $this->absolutePath($location['file']);

        return [
            ...$location,
            'copy' => $location['file'].':'.$line,
            'editor_url' => $this->url($path, $line),
        ];
    }

    /** @return array<string, mixed>|null */
    public function action(?string $action): ?array
    {
        if (! is_string($action) || $action === '' || $action === 'Closure') {
            return null;
        }

        [$class, $method] = str_contains($action, '@')
            ? explode('@', $action, 2)
            : [$action, '__invoke'];

        try {
            $reflection = method_exists($class, $method)
                ? new ReflectionMethod($class, $method)
                : new ReflectionClass($class);
            $file = $reflection->getFileName();

            if (! is_string($file)) {
                return null;
            }

            $relative = $this->relativePath($file);

            return $this->enrich([
                'file' => $relative,
                'line' => max(1, $reflection->getStartLine()),
            ]);
        } catch (ReflectionException) {
            return null;
        }
    }

    private function absolutePath(string $file): string
    {
        $path = str_starts_with($file, '/') ? $file : rtrim($this->projectPath, '/').'/'.ltrim($file, '/');
        $remote = $this->remotePath === null ? null : rtrim(str_replace('\\', '/', $this->remotePath), '/');
        $local = $this->localPath === null ? null : rtrim(str_replace('\\', '/', $this->localPath), '/');
        $path = str_replace('\\', '/', $path);

        return $remote !== null && $local !== null && str_starts_with($path, $remote.'/')
            ? $local.substr($path, strlen($remote))
            : $path;
    }

    private function relativePath(string $file): string
    {
        $project = rtrim(str_replace('\\', '/', $this->projectPath), '/').'/';
        $file = str_replace('\\', '/', $file);

        return str_starts_with($file, $project) ? substr($file, strlen($project)) : basename($file);
    }

    private function url(string $path, int $line): string
    {
        return match ($this->editor) {
            'vscode-insiders' => 'vscode-insiders://file/'.$this->encodePath($path).':'.$line,
            'phpstorm' => 'phpstorm://open?file='.rawurlencode($path).'&line='.$line,
            default => 'vscode://file/'.$this->encodePath($path).':'.$line,
        };
    }

    private function encodePath(string $path): string
    {
        return str_replace([' ', '#', '?'], ['%20', '%23', '%3F'], ltrim($path, '/'));
    }
}
