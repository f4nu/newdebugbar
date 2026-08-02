<?php

namespace NewDebugBar;

use Composer\InstalledVersions;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use NewDebugBar\Contracts\Collector;
use NewDebugBar\Support\Redactor;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Coordinates request timing, collectors, and the final debug profile. */
final class ProfileManager
{
    /** @var array<string, Collector> */
    private array $collectors = [];

    private bool $collecting = false;

    private int $startedAt = 0;

    private int $startedMemory = 0;

    /** @var array<string, mixed> */
    private array $request = [];

    /** @param iterable<Collector> $collectors */
    public function __construct(iterable $collectors, private readonly Redactor $redactor)
    {
        foreach ($collectors as $collector) {
            $this->collectors[$collector->key()] = $collector;
        }
    }

    public function begin(Request $request): void
    {
        foreach ($this->collectors as $collector) {
            $collector->reset();
        }

        $this->startedAt = hrtime(true);
        $this->startedMemory = memory_get_usage(true);
        $this->request = [
            'method' => $request->getMethod(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'query' => $this->redactor->clean($request->query()),
            'input' => $this->redactor->clean($request->except(array_keys($request->files->all()))),
            'headers' => $this->redactor->clean($request->headers->all()),
        ];
        $this->collecting = true;
    }

    public function isCollecting(): bool
    {
        return $this->collecting;
    }

    /** @param array<string, mixed> $item */
    public function record(string $collector, array $item): void
    {
        if (! $this->collecting) {
            return;
        }

        if (isset($this->collectors[$collector])) {
            $this->collectors[$collector]->record($item);
        }
    }

    /** @return array<string, mixed> */
    public function finish(Request $request, ?Response $response = null): array
    {
        try {
            $duration = ($this->startedAt > 0 ? hrtime(true) - $this->startedAt : 0) / 1_000_000;
            $usedMemory = max(0, memory_get_usage(true) - $this->startedMemory);
            $route = $request->route();

            $this->request = [
                ...$this->request,
                'route' => is_object($route) && method_exists($route, 'getName') ? $route->getName() : null,
                'action' => is_object($route) && method_exists($route, 'getActionName') ? $route->getActionName() : null,
                'parameters' => is_object($route) && method_exists($route, 'parameters')
                    ? $this->redactor->clean($this->normalizeRouteParameters($route->parameters()))
                    : [],
                'middleware' => is_object($route) ? app('router')->gatherRouteMiddleware($route) : [],
                'status' => $response?->getStatusCode() ?? 500,
                'response_headers' => $this->redactor->clean($response?->headers->all() ?? []),
            ];

            $metrics = [
                'duration_ms' => round($duration, 2),
                'memory_mb' => round($usedMemory / 1_048_576, 2),
                'peak_memory_mb' => round(memory_get_peak_usage(true) / 1_048_576, 2),
            ];

            $sections = [
                'overview' => [
                    'label' => 'Overview',
                    'summary' => $metrics,
                    'payload' => [
                        'environment' => app()->environment(),
                        'php' => PHP_VERSION,
                        'laravel' => app()->version(),
                        'livewire' => InstalledVersions::getPrettyVersion('livewire/livewire') ?? 'unknown',
                        'package' => InstalledVersions::getPrettyVersion('newdebugbar/new-debug-bar') ?? 'dev',
                    ],
                ],
                'request' => [
                    'label' => 'Request',
                    'summary' => [
                        'method' => $this->request['method'],
                        'status' => $this->request['status'],
                    ],
                    'payload' => $this->request,
                ],
            ];

            foreach ($this->collectors as $collector) {
                $sections[$collector->key()] = [
                    'label' => $collector->label(),
                    'summary' => $collector->summary(),
                    'payload' => $collector->payload(),
                ];
            }

            return [
                'id' => (string) Str::uuid(),
                'recorded_at' => now()->toIso8601String(),
                'environment' => app()->environment(),
                'metrics' => $metrics,
                'sections' => $sections,
            ];
        } finally {
            $this->collecting = false;
        }
    }

    public function discard(): void
    {
        $this->collecting = false;
    }

    public function recordException(Throwable $exception): void
    {
        $this->record('exceptions', [
            'class' => $exception::class,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /** @param array<string, mixed> $parameters */
    private function normalizeRouteParameters(array $parameters): array
    {
        return array_map(function (mixed $parameter): mixed {
            if ($parameter instanceof UrlRoutable) {
                return $parameter->getRouteKey();
            }

            return is_object($parameter) ? '['.$parameter::class.']' : $parameter;
        }, $parameters);
    }
}
