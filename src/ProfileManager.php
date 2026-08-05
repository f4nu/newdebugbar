<?php

namespace NewDebugBar;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use NewDebugBar\Collectors\RedisCollector;
use NewDebugBar\Contracts\Collector;
use NewDebugBar\Support\ExceptionNormalizer;
use NewDebugBar\Support\Redactor;
use NewDebugBar\Support\RuntimeContext;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/** Coordinates request timing, collectors, and the final debug profile. */
final class ProfileManager
{
    /** @var array<string, Collector> */
    private array $collectors = [];

    private bool $collecting = false;

    private int $startedAt = 0;

    private int $startedMemory = 0;

    private string $profileType = 'http';

    private string $primarySectionLabel = 'Request';

    /** @var array<string, mixed> */
    private array $request = [];

    /** @param iterable<Collector> $collectors */
    public function __construct(
        iterable $collectors,
        private readonly Redactor $redactor,
        private readonly ?ExceptionNormalizer $exceptionNormalizer = null,
        private readonly ?RuntimeContext $runtimeContext = null,
    ) {
        foreach ($collectors as $collector) {
            $this->collectors[$collector->key()] = $collector;
        }
    }

    public function begin(Request $request): void
    {
        $this->start('http', 'Request');
        $query = $this->redactor->clean($request->query());
        $this->request = [
            'method' => $request->getMethod(),
            'url' => $this->redactedUrl($request, is_array($query) ? $query : []),
            'path' => '/'.ltrim($request->path(), '/'),
            'query' => $query,
            'input' => $request->headers->has('X-Livewire')
                ? ['components' => count((array) $request->input('components', []))]
                : $this->redactor->clean($request->input()),
            'headers' => $this->redactor->clean($request->headers->all()),
        ];
        $this->collecting = true;
        $this->recordLivewireRequest($request);
    }

    /** @param array<string, mixed> $context */
    public function beginRuntime(string $type, string $name, array $context = []): void
    {
        $type = in_array($type, ['artisan', 'queue', 'test'], true) ? $type : 'runtime';
        $this->start($type, 'Runtime');
        $safeContext = $this->redactor->clean($context);
        $this->request = [
            'method' => 'CLI',
            'url' => null,
            'path' => $type.':'.$name,
            'query' => [],
            'input' => [],
            'headers' => [],
            'runtime_type' => $type,
            'name' => $name,
            'context' => is_array($safeContext) ? $safeContext : [],
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
            $this->collectors[$collector]->record([
                ...$item,
                'at_ms' => $this->elapsedMilliseconds(),
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function finish(Request $request, ?Response $response = null): array
    {
        try {
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
                'request_type' => $this->requestType($request, $response),
                'content_type' => $response?->headers->get('Content-Type'),
                'request_size_bytes' => $this->requestSize($request),
                'response_size_bytes' => $this->responseSize($response),
                'session_present' => $this->hasStartedSession($request),
                'authenticated' => $this->isAuthenticated($request),
                'response_headers' => $this->redactor->clean($response?->headers->all() ?? []),
            ];

            return $this->buildProfile();
        } finally {
            $this->collecting = false;
        }
    }

    /** @return array<string, mixed> */
    public function finishRuntime(int $exitCode = 0): array
    {
        try {
            $this->request = [
                ...$this->request,
                'route' => null,
                'action' => null,
                'parameters' => [],
                'middleware' => [],
                'status' => $exitCode,
                'exit_code' => $exitCode,
                'content_type' => null,
                'request_size_bytes' => 0,
                'response_size_bytes' => 0,
                'session_present' => false,
                'authenticated' => false,
                'response_headers' => [],
            ];

            return $this->buildProfile();
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
        $this->record('exceptions', $this->exceptionNormalizer?->normalize($exception) ?? [
            'class' => $exception::class,
            'message' => $exception->getMessage(),
            'file' => basename($exception->getFile()),
            'line' => $exception->getLine(),
            'frames' => ['application' => [], 'vendor' => []],
            'source' => null,
        ]);
    }

    public function excludeRedisCacheOperation(string $operation): void
    {
        $collector = $this->collectors['redis'] ?? null;

        if ($this->collecting && $collector instanceof RedisCollector) {
            $collector->excludeCacheOperation($operation);
        }
    }

    private function responseSize(?Response $response): int
    {
        $contentLength = $response?->headers->get('Content-Length');

        if (is_numeric($contentLength)) {
            return max(0, (int) $contentLength);
        }

        if ($response instanceof BinaryFileResponse) {
            $file = $response->getFile();

            return $file->isFile() ? max(0, $file->getSize()) : 0;
        }

        if ($response instanceof StreamedResponse) {
            return 0;
        }

        $content = $response?->getContent();

        return is_string($content) ? strlen($content) : 0;
    }

    private function start(string $type, string $primarySectionLabel): void
    {
        foreach ($this->collectors as $collector) {
            $collector->reset();
        }

        $this->profileType = $type;
        $this->primarySectionLabel = $primarySectionLabel;
        $this->startedAt = hrtime(true);
        $this->startedMemory = memory_get_usage(true);
        $this->request = [];
        $this->collecting = true;
    }

    /** @return array<string, mixed> */
    private function buildProfile(): array
    {
        $duration = ($this->startedAt > 0 ? hrtime(true) - $this->startedAt : 0) / 1_000_000;
        $usedMemory = max(0, memory_get_usage(true) - $this->startedMemory);
        $metrics = [
            'duration_ms' => round($duration, 2),
            'memory_mb' => round($usedMemory / 1_048_576, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1_048_576, 2),
        ];
        $livewireActivity = (int) (($this->collectors['livewire'] ?? null)?->summary()['count'] ?? 0) > 0;
        $sections = [
            'overview' => [
                'label' => 'Overview',
                'summary' => $metrics,
                'payload' => $this->runtimeContext?->build($livewireActivity) ?? [],
            ],
            'request' => [
                'label' => $this->primarySectionLabel,
                'summary' => [
                    'method' => $this->request['method'],
                    'status' => $this->request['status'],
                    'exit_code' => $this->request['exit_code'] ?? null,
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
            'schema_version' => 1,
            'id' => (string) Str::uuid(),
            'recorded_at' => now()->toIso8601String(),
            'profile_type' => $this->profileType,
            'environment' => app()->environment(),
            'metrics' => $metrics,
            'sections' => $sections,
        ];
    }

    private function requestSize(Request $request): int
    {
        $contentLength = $request->headers->get('Content-Length');

        if (is_numeric($contentLength)) {
            return max(0, (int) $contentLength);
        }

        $content = $request->getContent();

        if ($content !== '') {
            return strlen($content);
        }

        return strlen(http_build_query($request->request->all(), '', '&', PHP_QUERY_RFC3986));
    }

    private function recordLivewireRequest(Request $request): void
    {
        if (! $request->headers->has('X-Livewire')) {
            return;
        }

        foreach (array_values((array) $request->input('components', [])) as $index => $component) {
            if (! is_array($component) || ! is_string($component['snapshot'] ?? null)) {
                continue;
            }

            $snapshot = json_decode($component['snapshot'], true);
            $name = is_array($snapshot) ? ($snapshot['memo']['name'] ?? null) : null;

            if (! is_string($name) || $name === '' || $name === 'new-debug-bar.toolbar') {
                continue;
            }

            $calls = is_array($component['calls'] ?? null) ? $component['calls'] : [];
            $updates = is_array($component['updates'] ?? null) ? $component['updates'] : [];
            $this->record('livewire', [
                'phase' => 'request',
                'kind' => 'update',
                'request_index' => $index,
                'component' => $name,
                'actions' => array_values(array_unique(array_filter(array_map(
                    fn (mixed $call): ?string => is_array($call) && is_string($call['method'] ?? null)
                        ? $call['method']
                        : null,
                    $calls,
                )))),
                'updated_properties' => array_values(array_map('strval', array_keys($updates))),
                'validation_failure_count' => 0,
                'validation_fields' => [],
                'payload_size_bytes' => $this->requestSize($request),
                'response_size_bytes' => 0,
            ]);
        }
    }

    private function hasStartedSession(Request $request): bool
    {
        try {
            return $request->hasSession() && $request->session()->isStarted();
        } catch (Throwable) {
            return false;
        }
    }

    private function isAuthenticated(Request $request): bool
    {
        try {
            return $request->user() !== null;
        } catch (Throwable) {
            return false;
        }
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

    /** @param array<string, mixed> $query */
    private function redactedUrl(Request $request, array $query): string
    {
        if ($query === []) {
            return $request->url();
        }

        return $request->url().'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    private function elapsedMilliseconds(): float
    {
        return round(($this->startedAt > 0 ? hrtime(true) - $this->startedAt : 0) / 1_000_000, 3);
    }

    private function requestType(Request $request, ?Response $response): string
    {
        if ($request->headers->has('X-Livewire')) {
            return 'livewire';
        }

        $status = $response?->getStatusCode() ?? 500;

        if ($status >= 300 && $status < 400) {
            return 'redirect';
        }

        if ($response instanceof BinaryFileResponse) {
            return 'download';
        }

        if (str_contains(strtolower((string) $response?->headers->get('Content-Disposition')), 'attachment')) {
            return 'download';
        }

        if ($response instanceof StreamedResponse) {
            return 'stream';
        }

        if ($request->ajax()) {
            return 'ajax';
        }

        $contentType = strtolower((string) $response?->headers->get('Content-Type'));

        return $request->expectsJson() || str_contains($contentType, 'json')
            ? 'json'
            : 'full_page';
    }
}
