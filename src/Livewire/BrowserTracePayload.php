<?php

namespace NewDebugBar\Livewire;

use InvalidArgumentException;
use NewDebugBar\Storage\ProfileStore;

/** Validates the small value-free schema accepted from the browser. */
final class BrowserTracePayload
{
    public const MAX_BYTES = 32_000;

    private const MAX_ITEMS = 100;

    /** @return array<string, mixed> */
    public function normalize(array $payload, string $nonce): array
    {
        $this->keys($payload, ['schema_version', 'idempotency_key', 'request', 'messages', 'actions', 'failures']);

        if (($payload['schema_version'] ?? null) !== 1
            || ! is_string($payload['idempotency_key'] ?? null)
            || ! ProfileStore::validId($payload['idempotency_key'])
            || ! hash_equals($nonce, $payload['idempotency_key'])) {
            throw new InvalidArgumentException('The browser trace identity is invalid.');
        }

        return [
            'schema_version' => 1,
            'idempotency_key' => $payload['idempotency_key'],
            'request' => $this->request($payload['request'] ?? null),
            'messages' => $this->messages($payload['messages'] ?? null),
            'actions' => $this->actions($payload['actions'] ?? null),
            'failures' => $this->failures($payload['failures'] ?? null),
        ];
    }

    /** @return array<string, mixed> */
    private function request(mixed $request): array
    {
        if (! is_array($request)) {
            throw new InvalidArgumentException('The browser request trace is invalid.');
        }

        $this->keys($request, ['outcome', 'status', 'wait_ms', 'parse_ms', 'total_ms']);
        $outcome = $this->enum($request['outcome'] ?? null, [
            'success', 'error', 'failure', 'cancelled', 'redirected',
        ]);
        $status = $request['status'] ?? null;

        if ($status !== null && (! is_int($status) || $status < 100 || $status > 599)) {
            throw new InvalidArgumentException('The browser response status is invalid.');
        }

        return [
            'outcome' => $outcome,
            'status' => $status,
            'wait_ms' => $this->milliseconds($request['wait_ms'] ?? null),
            'parse_ms' => $this->milliseconds($request['parse_ms'] ?? null),
            'total_ms' => $this->milliseconds($request['total_ms'] ?? null),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function messages(mixed $messages): array
    {
        $messages = $this->list($messages, 'browser messages');

        return array_map(function (array $message): array {
            $this->keys($message, ['component_id', 'outcome', 'phases', 'state']);
            $componentId = $this->identifier($message['component_id'] ?? null, 'component');
            $outcome = $this->enum($message['outcome'] ?? null, [
                'success', 'skipped', 'error', 'failure', 'cancelled', 'unknown',
            ]);
            $phases = $this->list($message['phases'] ?? null, 'message phases', 20);
            $state = $this->list($message['state'] ?? null, 'browser state');

            return [
                'component_id' => $componentId,
                'outcome' => $outcome,
                'phases' => array_map(function (array $phase): array {
                    $this->keys($phase, ['name', 'at_ms']);

                    return [
                        'name' => $this->enum($phase['name'] ?? null, [
                            'send', 'success', 'sync', 'effect', 'morph', 'render', 'finish', 'skipped',
                        ]),
                        'at_ms' => $this->milliseconds($phase['at_ms'] ?? null, false),
                    ];
                }, $phases),
                'state' => array_map(function (array $layer): array {
                    $this->keys($layer, ['path', 'matches_server', 'browser_type']);
                    $matches = $layer['matches_server'] ?? null;

                    if ($matches !== null && ! is_bool($matches)) {
                        throw new InvalidArgumentException('A browser state comparison is invalid.');
                    }

                    return [
                        'path' => $this->path($layer['path'] ?? null),
                        'matches_server' => $matches,
                        'browser_type' => $this->enum($layer['browser_type'] ?? null, [
                            'null', 'boolean', 'number', 'string', 'array', 'object', 'unknown', 'missing',
                        ]),
                    ];
                }, $state),
            ];
        }, $messages);
    }

    /** @return list<array<string, mixed>> */
    private function actions(mixed $actions): array
    {
        $actions = $this->list($actions, 'browser actions');

        return array_map(function (array $action): array {
            $this->keys($action, ['component_id', 'name', 'source']);
            $source = $action['source'] ?? null;

            if (! is_array($source)) {
                throw new InvalidArgumentException('A browser action source is invalid.');
            }

            $this->keys($source, ['status', 'directive', 'element', 'contract']);
            $status = $this->enum($source['status'] ?? null, ['observed', 'unknown']);
            $directive = $source['directive'] ?? null;
            $element = $source['element'] ?? null;

            if ($directive !== null && (! is_string($directive)
                || strlen($directive) > 100
                || preg_match('/\Awire:[a-z0-9_.:-]+\z/i', $directive) !== 1)) {
                throw new InvalidArgumentException('A browser action directive is invalid.');
            }

            if ($element !== null && (! is_string($element)
                || preg_match('/\A[a-z][a-z0-9-]{0,29}\z/', $element) !== 1)) {
                throw new InvalidArgumentException('A browser action element is invalid.');
            }

            if (($source['contract'] ?? null) !== 'livewire_action_origin_v1') {
                throw new InvalidArgumentException('The browser action source contract is invalid.');
            }

            return [
                'component_id' => $this->identifier($action['component_id'] ?? null, 'component'),
                'name' => $this->plainString($action['name'] ?? null, 200, 'action name'),
                'source' => [
                    'status' => $status,
                    'directive' => $directive,
                    'element' => $element,
                    'contract' => 'livewire_action_origin_v1',
                ],
            ];
        }, $actions);
    }

    /** @return list<array{phase: string, kind: string}> */
    private function failures(mixed $failures): array
    {
        $failures = $this->list($failures, 'browser failures', 20);

        return array_map(function (array $failure): array {
            $this->keys($failure, ['phase', 'kind']);

            return [
                'phase' => $this->enum($failure['phase'] ?? null, [
                    'request', 'message', 'action', 'append', 'navigation',
                ]),
                'kind' => $this->enum($failure['kind'] ?? null, [
                    'cancelled', 'error', 'failure', 'expired', 'unavailable', 'unsupported',
                ]),
            ];
        }, $failures);
    }

    /**
     * @param  list<string>  $allowed
     */
    private function keys(array $value, array $allowed): void
    {
        $keys = array_keys($value);
        sort($keys);
        sort($allowed);

        if ($keys !== $allowed) {
            throw new InvalidArgumentException('The browser trace has missing or unknown fields.');
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function list(mixed $value, string $label, int $limit = self::MAX_ITEMS): array
    {
        if (! is_array($value) || ! array_is_list($value) || count($value) > $limit) {
            throw new InvalidArgumentException("The {$label} list is invalid.");
        }

        foreach ($value as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException("The {$label} list is invalid.");
            }
        }

        return $value;
    }

    /** @param list<string> $allowed */
    private function enum(mixed $value, array $allowed): string
    {
        if (! is_string($value) || ! in_array($value, $allowed, true)) {
            throw new InvalidArgumentException('The browser trace contains an invalid value.');
        }

        return $value;
    }

    private function milliseconds(mixed $value, bool $nullable = true): ?float
    {
        if ($nullable && $value === null) {
            return null;
        }

        if (! is_int($value) && ! is_float($value)) {
            throw new InvalidArgumentException('A browser trace time is invalid.');
        }

        $value = (float) $value;

        if (! is_finite($value) || $value < 0 || $value > 120_000) {
            throw new InvalidArgumentException('A browser trace time is invalid.');
        }

        return round($value, 3);
    }

    private function identifier(mixed $value, string $label): string
    {
        if (! is_string($value)
            || strlen($value) > 100
            || preg_match('/\A[a-z0-9._:-]+\z/i', $value) !== 1) {
            throw new InvalidArgumentException("The browser {$label} identifier is invalid.");
        }

        return $value;
    }

    private function path(mixed $value): string
    {
        return $this->plainString($value, 200, 'state path');
    }

    private function plainString(mixed $value, int $limit, string $label): string
    {
        if (! is_string($value)
            || $value === ''
            || strlen($value) > $limit
            || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw new InvalidArgumentException("The browser {$label} is invalid.");
        }

        return $value;
    }
}
