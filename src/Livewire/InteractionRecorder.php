<?php

namespace NewDebugBar\Livewire;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Livewire\Component;
use NewDebugBar\Support\Redactor;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Records one bounded Livewire HTTP exchange from observed lifecycle facts. */
final class InteractionRecorder
{
    private bool $active = false;

    private string $profileId = '';

    private string $exchangeId = '';

    private int $startedAt = 0;

    private float $startedWallAt = 0;

    /** @var list<array<string, mixed>> */
    private array $messages = [];

    /** @var array<string, int> */
    private array $messageIndexesByComponent = [];

    /** @var list<array<string, mixed>> */
    private array $actions = [];

    /** @var array<string, list<int>> */
    private array $actionIndexesByComponent = [];

    /** @var array<string, array<string, mixed>> */
    private array $components = [];

    /** @var array<string, array<string, mixed>> */
    private array $beforeState = [];

    /** @var array<string, array<string, mixed>> */
    private array $submittedState = [];

    /** @var list<array<string, mixed>> */
    private array $stateChanges = [];

    /** @var list<array<string, mixed>> */
    private array $serverSpans = [];

    /** @var array<string, list<string>> */
    private array $componentContextTokens = [];

    /** @var array<string, int> */
    private array $dropped = [
        'messages' => 0,
        'actions' => 0,
        'state_changes' => 0,
        'server_spans' => 0,
    ];

    public function __construct(
        private readonly Redactor $redactor,
        private readonly StateDiff $stateDiff,
        private readonly ExecutionContext $context,
        private readonly string $projectPath,
        private readonly int $maxItems = 500,
    ) {}

    public function begin(Request $request, string $profileId): void
    {
        $this->reset();

        if (! $request->headers->has('X-Livewire') || ! is_array($request->input('components'))) {
            return;
        }

        $this->active = true;
        $this->profileId = $profileId;
        $this->exchangeId = (string) Str::uuid();
        $this->startedAt = hrtime(true);
        $this->startedWallAt = microtime(true);

        foreach (array_values($request->input('components')) as $requestIndex => $rawMessage) {
            if (! is_array($rawMessage)) {
                $this->dropped['messages']++;

                continue;
            }

            $snapshot = $this->decodeSnapshot($rawMessage['snapshot'] ?? null);
            $componentName = data_get($snapshot, 'memo.name');

            if ($componentName === 'newdebugbar.toolbar') {
                continue;
            }

            $componentId = data_get($snapshot, 'memo.id');

            if (! is_string($componentId) || $componentId === '' || ! is_string($componentName) || $componentName === '') {
                $this->dropped['messages']++;

                continue;
            }

            if (count($this->messages) >= $this->maxItems) {
                $this->dropped['messages']++;

                continue;
            }

            $messageIndex = count($this->messages);
            $messageId = (string) Str::uuid();
            $this->messages[] = [
                'id' => $messageId,
                'request_index' => $requestIndex,
                'component_id' => $componentId,
                'action_ids' => [],
                'state_change_ids' => [],
                'result' => 'unknown',
                'caused_by' => [],
                'source' => 'livewire_public',
                'confidence' => 'observed',
            ];
            $this->messageIndexesByComponent[$componentId] = $messageIndex;
            $this->components[$componentId] = [
                'id' => $componentId,
                'mount_scope' => $componentId,
                'name' => $componentName,
                'class' => null,
                'source' => null,
                'view' => null,
                'parent_id' => null,
                'key' => data_get($snapshot, 'memo.key'),
                'depth' => null,
                'rendered' => 'unknown',
                'render_reason' => ['kind' => 'unknown', 'action_id' => null, 'confidence' => 'unknown'],
                'completeness' => 'affected_only',
            ];

            foreach ((array) ($rawMessage['updates'] ?? []) as $path => $value) {
                $this->addAction($componentId, $messageIndex, [
                    'kind' => 'property_update',
                    'name' => '$set',
                    'parameters' => [],
                    'property_paths' => [(string) $path],
                    'execution_status' => 'submitted',
                    'source' => 'livewire_public',
                    'confidence' => 'observed',
                ]);
                $this->submittedState[$componentId][(string) $path] = $value;
            }

            foreach ((array) ($rawMessage['calls'] ?? []) as $call) {
                if (! is_array($call) || ! is_string($call['method'] ?? null)) {
                    $this->dropped['actions']++;

                    continue;
                }

                $params = is_array($call['params'] ?? null) ? $call['params'] : [];
                $method = $call['method'];
                $kind = match ($method) {
                    '__dispatch' => 'event_received',
                    '$refresh' => 'refresh',
                    default => 'action',
                };
                $name = $method === '__dispatch' && is_string($params[0] ?? null)
                    ? $params[0]
                    : $method;

                $this->addAction($componentId, $messageIndex, [
                    'kind' => $kind,
                    'name' => $name,
                    'parameters' => $this->redactor->clean($params),
                    'property_paths' => [],
                    'execution_status' => 'submitted',
                    'source' => 'livewire_public',
                    'confidence' => 'observed',
                ]);
            }
        }

        if ($this->messages === []) {
            $this->active = false;
        }
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    /** @return array<string, mixed> */
    public function safeRequestInput(): array
    {
        return [
            'component_message_count' => count($this->messages) + $this->dropped['messages'],
            'messages' => array_map(function (array $message): array {
                $component = $this->components[$message['component_id']] ?? [];
                $actions = array_values(array_filter(
                    $this->actions,
                    fn (array $action): bool => $action['message_id'] === $message['id'],
                ));

                return [
                    'request_index' => $message['request_index'],
                    'component_id' => $message['component_id'],
                    'component_name' => $component['name'] ?? 'unknown',
                    'property_paths' => array_values(array_unique(array_merge(...array_map(
                        fn (array $action): array => $action['property_paths'],
                        $actions,
                    )))),
                    'action_names' => array_values(array_map(
                        fn (array $action): string => (string) $action['name'],
                        array_filter($actions, fn (array $action): bool => $action['kind'] !== 'property_update'),
                    )),
                ];
            }, $this->messages),
            'snapshot_data_stored' => false,
        ];
    }

    public function observeHydrate(Component $component): void
    {
        if (! $this->tracks($component)) {
            return;
        }

        $componentId = $component->getId();
        $this->beforeState[$componentId] = $component->all();
        $this->observeComponent($component);
        $token = $this->context->push($this->componentContext($componentId, 'hydrate'));
        $this->componentContextTokens[$componentId][] = $token;
    }

    public function observeUpdate(Component $component, string $path): ?string
    {
        if (! $this->tracks($component)) {
            return null;
        }

        $actionIndex = $this->findSubmittedAction($component->getId(), 'property_update', $path);

        if ($actionIndex !== null) {
            $this->actions[$actionIndex]['execution_status'] = 'observed';
        }

        return $this->context->push($this->actionContext($component->getId(), $actionIndex, 'update'));
    }

    /** @param array<array-key, mixed> $params */
    public function observeCall(Component $component, string $method, array $params): ?string
    {
        if (! $this->tracks($component)) {
            return null;
        }

        $kind = $method === '__dispatch' ? 'event_received' : ($method === '$refresh' ? 'refresh' : 'action');
        $name = $method === '__dispatch' && is_string($params[0] ?? null) ? $params[0] : $method;
        $actionIndex = $this->findSubmittedAction($component->getId(), $kind, $name);

        if ($actionIndex !== null) {
            $this->actions[$actionIndex]['execution_status'] = 'observed';
        }

        return $this->context->push($this->actionContext($component->getId(), $actionIndex, 'call'));
    }

    public function observeRender(Component $component, mixed $view): ?string
    {
        if (! $this->tracks($component)) {
            return null;
        }

        $componentId = $component->getId();
        $this->observeComponent($component);
        $this->components[$componentId]['rendered'] = 'yes';
        $this->components[$componentId]['view'] = is_object($view) && method_exists($view, 'getPath')
            ? $this->relativePath($view->getPath())
            : null;

        return $this->context->push($this->componentContext($componentId, 'render'));
    }

    public function observeDehydrate(Component $component): void
    {
        if (! $this->tracks($component)) {
            return;
        }

        $componentId = $component->getId();
        $this->observeComponent($component);
        $diff = $this->stateDiff->between(
            $this->beforeState[$componentId] ?? [],
            $component->all(),
            $this->submittedState[$componentId] ?? [],
        );
        $this->dropped['state_changes'] += $diff['dropped'];
        $actionIndexes = $this->actionIndexesByComponent[$componentId] ?? [];
        $singleActionIndex = count($actionIndexes) === 1 ? $actionIndexes[0] : null;

        foreach ($diff['changes'] as $change) {
            if (count($this->stateChanges) >= $this->maxItems) {
                $this->dropped['state_changes']++;

                continue;
            }

            $actionIndex = $this->actionForChange($componentId, $change['path']) ?? $singleActionIndex;
            $actionId = $actionIndex !== null ? $this->actions[$actionIndex]['id'] : null;
            $messageIndex = $this->messageIndexesByComponent[$componentId] ?? null;
            $changeId = (string) Str::uuid();
            $this->stateChanges[] = [
                'id' => $changeId,
                'action_id' => $actionId,
                'component_id' => $componentId,
                ...$change,
                'caused_by' => $actionId === null ? [] : [['type' => 'action', 'id' => $actionId]],
            ];

            if ($messageIndex !== null) {
                $this->messages[$messageIndex]['state_change_ids'][] = $changeId;
            }
        }

        $this->setRenderReason($componentId);
        $this->leaveComponentContext($componentId);
        unset($this->beforeState[$componentId], $this->submittedState[$componentId]);
    }

    /** @param array{0: float|int, 1: float|int} $range */
    public function observeServerSpan(string $phase, string $componentId, array $range): void
    {
        if (! $this->active || ! isset($this->messageIndexesByComponent[$componentId])) {
            return;
        }

        if (count($this->serverSpans) >= $this->maxItems) {
            $this->dropped['server_spans']++;

            return;
        }

        $start = (float) ($range[0] ?? 0);
        $end = (float) ($range[1] ?? $start);
        $this->serverSpans[] = [
            'id' => (string) Str::uuid(),
            'component_id' => $componentId,
            'action_id' => $this->actionIdForProfilePhase($componentId, $phase),
            'phase' => $phase,
            'start_ms' => round(max(0, $start - $this->startedWallAt) * 1_000, 3),
            'duration_ms' => round(max(0, $end - $start) * 1_000, 3),
            'clock' => 'livewire_server_wall_normalized_to_exchange',
            'source' => 'livewire_internal',
            'confidence' => 'observed',
        ];
    }

    /** @param array<string, mixed> $payload */
    public function observeResponse(array $payload): void
    {
        if (! $this->active) {
            return;
        }

        foreach (array_values((array) ($payload['components'] ?? [])) as $responseIndex => $componentResponse) {
            if (! is_array($componentResponse)) {
                continue;
            }

            $snapshot = $this->decodeSnapshot($componentResponse['snapshot'] ?? null);
            $componentId = data_get($snapshot, 'memo.id');
            $messageIndex = is_string($componentId)
                ? ($this->messageIndexesByComponent[$componentId] ?? null)
                : null;

            if ($messageIndex === null) {
                $messageIndex = collect($this->messages)->search(
                    fn (array $message): bool => $message['request_index'] === $responseIndex,
                );
                $messageIndex = $messageIndex === false ? null : $messageIndex;
            }

            if (! is_int($messageIndex)) {
                continue;
            }

            $effects = is_array($componentResponse['effects'] ?? null) ? $componentResponse['effects'] : [];
            $result = match (true) {
                array_key_exists('redirect', $effects) => 'redirected',
                array_key_exists('download', $effects) => 'downloaded',
                array_key_exists('html', $effects) => 'rendered',
                $this->hasValidationErrors($snapshot) => 'validation_failed',
                default => 'renderless',
            };
            $this->messages[$messageIndex]['result'] = $result;

            if (is_string($componentId) && isset($this->components[$componentId])) {
                $this->components[$componentId]['rendered'] = $result === 'rendered' ? 'yes' : 'no';
            }
        }
    }

    public function popContext(?string $token): void
    {
        if ($token !== null) {
            $this->context->pop($token);
        }
    }

    /** @return array<string, mixed>|null */
    public function finish(Request $request, ?Response $response): ?array
    {
        if (! $this->active) {
            $this->clear();

            return null;
        }

        $results = array_values(array_unique(array_column($this->messages, 'result')));
        $result = count($results) === 1 ? $results[0] : (count($results) > 1 ? 'mixed' : 'unknown');
        $title = $this->title();
        $duration = ($this->startedAt > 0 ? hrtime(true) - $this->startedAt : 0) / 1_000_000;
        $truncated = array_sum($this->dropped) > 0;
        $unknownReasons = [];

        if ($this->serverSpans === []) {
            $unknownReasons[] = config('app.debug', false)
                ? 'Livewire server timing evidence was unavailable.'
                : 'Livewire server timing evidence requires app.debug=true.';
        }

        $section = [
            'schema_version' => 1,
            'profile_revision' => 1,
            'label' => 'Livewire',
            'summary' => [
                'title' => $title['text'],
                'message_count' => count($this->messages) + $this->dropped['messages'],
                'action_count' => count($this->actions) + $this->dropped['actions'],
                'component_count' => count($this->components),
                'state_change_count' => count($this->stateChanges) + $this->dropped['state_changes'],
                'result' => $result,
                'trace_status' => 'missing',
                'truncated' => $truncated,
            ],
            'payload' => [
                'exchange' => [
                    'id' => $this->exchangeId,
                    'request_id' => $this->profileId,
                    'kind' => 'update',
                    'title' => $title['text'],
                    'title_confidence' => $title['confidence'],
                    'result' => $result,
                    'status' => $response?->getStatusCode() ?? 500,
                    'path' => '/'.ltrim($request->path(), '/'),
                    'request_bytes' => $this->requestSize($request),
                    'response_bytes' => $this->responseSize($response),
                    'duration_ms' => round($duration, 3),
                    'server_clock' => ['type' => 'wall_normalized_to_exchange', 'unit' => 'milliseconds'],
                    'browser_clock' => ['type' => 'separate_monotonic', 'status' => 'missing'],
                    'source' => 'package',
                    'confidence' => 'observed',
                ],
                'messages' => array_values($this->messages),
                'actions' => array_values($this->actions),
                'components' => array_values($this->components),
                'state_changes' => array_values($this->stateChanges),
                'events' => [],
                'server_spans' => array_values($this->serverSpans),
                'browser_trace' => [
                    'status' => 'missing',
                    'appended_at' => null,
                    'spans' => [],
                    'failures' => [],
                ],
                'findings' => [],
                'completeness' => [
                    'messages' => $this->dropped['messages'] === 0 ? 'complete' : 'partial',
                    'components' => 'affected_only',
                    'state' => $this->dropped['state_changes'] === 0 ? 'complete' : 'partial',
                    'events' => 'not_collected',
                    'server_spans' => $this->serverSpans === [] ? 'unknown' : 'observed',
                    'browser_trace' => 'missing',
                    'truncated' => $truncated,
                    'dropped_counts' => $this->dropped,
                    'unknown_reasons' => $unknownReasons,
                ],
            ],
        ];

        $this->clear();

        return $section;
    }

    public function discard(): void
    {
        $this->clear();
    }

    /** @param array<string, mixed> $action */
    private function addAction(string $componentId, int $messageIndex, array $action): void
    {
        if (count($this->actions) >= $this->maxItems) {
            $this->dropped['actions']++;

            return;
        }

        $actionIndex = count($this->actions);
        $actionId = (string) Str::uuid();
        $this->actions[] = [
            'id' => $actionId,
            'message_id' => $this->messages[$messageIndex]['id'],
            'component_id' => $componentId,
            ...$action,
            'caused_by' => [['type' => 'message', 'id' => $this->messages[$messageIndex]['id']]],
        ];
        $this->messages[$messageIndex]['action_ids'][] = $actionId;
        $this->actionIndexesByComponent[$componentId][] = $actionIndex;
    }

    private function observeComponent(Component $component): void
    {
        $componentId = $component->getId();

        if (! isset($this->components[$componentId])) {
            return;
        }

        $source = null;

        try {
            $reflection = new ReflectionClass($component);
            $filename = $reflection->getFileName();
            $source = is_string($filename)
                ? ['file' => $this->relativePath($filename), 'line' => $reflection->getStartLine()]
                : null;
        } catch (Throwable) {
            // Missing source is an explicit unknown in the section.
        }

        $this->components[$componentId]['class'] = $component::class;
        $this->components[$componentId]['source'] = $source;
    }

    private function tracks(Component $component): bool
    {
        return $this->active && isset($this->messageIndexesByComponent[$component->getId()]);
    }

    /** @return array<string, mixed> */
    private function componentContext(string $componentId, string $phase): array
    {
        $messageIndex = $this->messageIndexesByComponent[$componentId] ?? null;

        return [
            'exchange_id' => $this->exchangeId,
            'message_id' => is_int($messageIndex) ? $this->messages[$messageIndex]['id'] : null,
            'action_id' => null,
            'component_id' => $componentId,
            'phase' => $phase,
        ];
    }

    /** @return array<string, mixed> */
    private function actionContext(string $componentId, ?int $actionIndex, string $phase): array
    {
        return [
            ...$this->componentContext($componentId, $phase),
            'action_id' => $actionIndex !== null ? $this->actions[$actionIndex]['id'] : null,
        ];
    }

    private function leaveComponentContext(string $componentId): void
    {
        $tokens = $this->componentContextTokens[$componentId] ?? [];
        $token = array_pop($tokens);

        if (is_string($token)) {
            $this->context->pop($token);
        }

        if ($tokens === []) {
            unset($this->componentContextTokens[$componentId]);
        } else {
            $this->componentContextTokens[$componentId] = $tokens;
        }
    }

    private function findSubmittedAction(string $componentId, string $kind, string $name): ?int
    {
        foreach ($this->actionIndexesByComponent[$componentId] ?? [] as $index) {
            $action = $this->actions[$index];

            if ($action['execution_status'] !== 'submitted' || $action['kind'] !== $kind) {
                continue;
            }

            if ($kind === 'property_update' && in_array($name, $action['property_paths'], true)) {
                return $index;
            }

            if ($action['name'] === $name) {
                return $index;
            }
        }

        return null;
    }

    private function actionForChange(string $componentId, string $path): ?int
    {
        foreach ($this->actionIndexesByComponent[$componentId] ?? [] as $index) {
            if (in_array($path, $this->actions[$index]['property_paths'], true)) {
                return $index;
            }
        }

        return null;
    }

    private function setRenderReason(string $componentId): void
    {
        $indexes = $this->actionIndexesByComponent[$componentId] ?? [];

        if (count($indexes) !== 1) {
            return;
        }

        $action = $this->actions[$indexes[0]];
        $this->components[$componentId]['render_reason'] = [
            'kind' => $action['kind'],
            'action_id' => $action['id'],
            'confidence' => 'inferred',
        ];
    }

    private function actionIdForProfilePhase(string $componentId, string $phase): ?string
    {
        if (! preg_match('/\Acall(\d+)\z/', $phase, $matches)) {
            return null;
        }

        $indexes = $this->actionIndexesByComponent[$componentId] ?? [];
        $index = $indexes[(int) $matches[1]] ?? null;

        return is_int($index) ? $this->actions[$index]['id'] : null;
    }

    /** @return array{text: string, confidence: string} */
    private function title(): array
    {
        if (count($this->actions) !== 1 || $this->dropped['actions'] > 0) {
            return ['text' => 'Livewire exchange', 'confidence' => 'unknown'];
        }

        $action = $this->actions[0];

        return match ($action['kind']) {
            'property_update' => [
                'text' => 'Updated '.($action['property_paths'][0] ?? 'property'),
                'confidence' => 'inferred',
            ],
            'event_received' => ['text' => 'Received '.$action['name'], 'confidence' => 'inferred'],
            'refresh' => ['text' => 'Refreshed component', 'confidence' => 'inferred'],
            default => ['text' => 'Ran '.$action['name'], 'confidence' => 'inferred'],
        };
    }

    /** @return array<string, mixed> */
    private function decodeSnapshot(mixed $snapshot): array
    {
        if (! is_string($snapshot) || $snapshot === '') {
            return [];
        }

        try {
            $decoded = json_decode($snapshot, true, flags: JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : [];
        } catch (Throwable) {
            return [];
        }
    }

    /** @param array<string, mixed> $snapshot */
    private function hasValidationErrors(array $snapshot): bool
    {
        $errors = data_get($snapshot, 'memo.errors');

        return is_array($errors) && $errors !== [];
    }

    private function relativePath(string $path): string
    {
        $project = rtrim(str_replace('\\', '/', $this->projectPath), '/').'/';
        $normalized = str_replace('\\', '/', $path);

        return str_starts_with($normalized, $project)
            ? substr($normalized, strlen($project))
            : basename($normalized);
    }

    private function requestSize(Request $request): int
    {
        $contentLength = $request->headers->get('Content-Length');

        if (is_numeric($contentLength)) {
            return max(0, (int) $contentLength);
        }

        return strlen($request->getContent());
    }

    private function responseSize(?Response $response): int
    {
        $contentLength = $response?->headers->get('Content-Length');

        if (is_numeric($contentLength)) {
            return max(0, (int) $contentLength);
        }

        $content = $response?->getContent();

        return is_string($content) ? strlen($content) : 0;
    }

    private function reset(): void
    {
        $this->active = false;
        $this->profileId = '';
        $this->exchangeId = '';
        $this->startedAt = 0;
        $this->startedWallAt = 0;
        $this->messages = [];
        $this->messageIndexesByComponent = [];
        $this->actions = [];
        $this->actionIndexesByComponent = [];
        $this->components = [];
        $this->beforeState = [];
        $this->submittedState = [];
        $this->stateChanges = [];
        $this->serverSpans = [];
        $this->componentContextTokens = [];
        $this->dropped = [
            'messages' => 0,
            'actions' => 0,
            'state_changes' => 0,
            'server_spans' => 0,
        ];
        $this->context->clear();
    }

    private function clear(): void
    {
        $this->reset();
    }
}
