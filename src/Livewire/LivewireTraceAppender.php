<?php

namespace NewDebugBar\Livewire;

use Illuminate\Support\Str;
use InvalidArgumentException;
use NewDebugBar\Storage\ProfileStore;
use Throwable;

/** Correlates one validated browser trace with its exact stored server profile. */
final class LivewireTraceAppender
{
    public function __construct(
        private readonly ProfileStore $store,
        private readonly BrowserTracePayload $payloads,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{status: string, revision?: int}
     */
    public function append(
        string $profileId,
        int $expectedRevision,
        string $nonce,
        array $payload,
    ): array {
        try {
            $trace = $this->payloads->normalize($payload, $nonce);
        } catch (InvalidArgumentException) {
            return ['status' => 'malformed'];
        }

        try {
            return $this->store->withWriteLock(function () use ($profileId, $expectedRevision, $trace): array {
                $profile = $this->store->get($profileId);

                if ($profile === null || ! is_array(data_get($profile, 'sections.livewire.payload'))) {
                    return ['status' => 'not_found'];
                }

                $section = $profile['sections']['livewire'];
                $revision = $section['profile_revision'] ?? null;
                $idempotencyHash = substr(hash('sha256', $trace['idempotency_key']), 0, 16);

                if (hash_equals(
                    (string) data_get($section, 'payload.browser_trace.idempotency_hash', ''),
                    $idempotencyHash,
                )) {
                    return ['status' => 'repeated'];
                }

                if (! is_int($revision) || $revision !== $expectedRevision) {
                    return ['status' => 'conflict'];
                }

                if (data_get($section, 'payload.browser_trace.appended_at') !== null) {
                    return ['status' => 'repeated'];
                }

                $correlated = $this->correlate($section['payload'], $trace);
                $revision++;
                $section['profile_revision'] = $revision;
                $section['summary']['trace_status'] = $correlated['status'];
                $section['payload']['browser_trace'] = $correlated;
                $section['payload']['exchange']['browser_clock'] = [
                    'type' => 'performance_monotonic_offset',
                    'status' => $correlated['status'],
                    'unit' => 'milliseconds',
                ];
                $section['payload']['completeness']['browser_trace'] = $correlated['status'];
                $profile['sections']['livewire'] = $section;
                $this->applyBrowserState($profile['sections']['livewire']['payload'], $correlated['state']);
                $this->store->put($profile);

                return ['status' => 'accepted', 'revision' => $revision];
            });
        } catch (Throwable) {
            return ['status' => 'unavailable'];
        }
    }

    /**
     * @param  array<string, mixed>  $server
     * @param  array<string, mixed>  $trace
     * @return array<string, mixed>
     */
    private function correlate(array $server, array $trace): array
    {
        $messages = (array) ($server['messages'] ?? []);
        $actions = (array) ($server['actions'] ?? []);
        $stateChanges = (array) ($server['state_changes'] ?? []);
        $spans = [];
        $browserMessages = [];
        $browserActions = [];
        $browserState = [];
        $partial = $trace['failures'] !== [];
        $request = $trace['request'];

        foreach ([
            ['phase' => 'request_wait', 'start_ms' => 0.0, 'duration_ms' => $request['wait_ms']],
            ['phase' => 'response_parse', 'start_ms' => $request['wait_ms'], 'duration_ms' => $request['parse_ms']],
            ['phase' => 'request_callbacks', 'start_ms' => 0.0, 'duration_ms' => $request['total_ms']],
        ] as $span) {
            if ($span['start_ms'] === null || $span['duration_ms'] === null) {
                continue;
            }

            $spans[] = $this->span(
                phase: $span['phase'],
                start: (float) $span['start_ms'],
                duration: (float) $span['duration_ms'],
            );
        }

        foreach ($trace['messages'] as $browserMessage) {
            $matches = array_values(array_filter(
                $messages,
                fn (array $message): bool => ($message['component_id'] ?? null) === $browserMessage['component_id'],
            ));
            $messageId = count($matches) === 1 ? ($matches[0]['id'] ?? null) : null;
            $partial = $partial || $messageId === null || $browserMessage['outcome'] === 'unknown';
            $browserMessages[] = [
                'message_id' => $messageId,
                'component_id' => $browserMessage['component_id'],
                'outcome' => $browserMessage['outcome'],
                'confidence' => $messageId === null ? 'unknown' : 'observed',
            ];

            foreach ($browserMessage['phases'] as $phase) {
                $spans[] = $this->span(
                    phase: 'message_'.$phase['name'],
                    start: (float) $phase['at_ms'],
                    duration: 0.0,
                    messageId: is_string($messageId) ? $messageId : null,
                    componentId: $browserMessage['component_id'],
                    kind: 'point',
                );
            }

            foreach ($browserMessage['state'] as $state) {
                $stateMatches = array_filter(
                    $stateChanges,
                    fn (array $change): bool => ($change['component_id'] ?? null) === $browserMessage['component_id']
                        && ($change['path'] ?? null) === $state['path'],
                );
                $partial = $partial || count($stateMatches) !== 1;
                $browserState[] = [
                    'message_id' => $messageId,
                    'component_id' => $browserMessage['component_id'],
                    ...$state,
                ];
            }
        }

        foreach ($trace['actions'] as $browserAction) {
            $matches = array_values(array_filter(
                $actions,
                fn (array $action): bool => ($action['component_id'] ?? null) === $browserAction['component_id']
                    && ($action['name'] ?? null) === $browserAction['name'],
            ));
            $actionId = count($matches) === 1 ? ($matches[0]['id'] ?? null) : null;
            $partial = $partial || $actionId === null;
            $browserActions[] = [
                'action_id' => $actionId,
                'component_id' => $browserAction['component_id'],
                'name' => $browserAction['name'],
                'source' => $browserAction['source'],
                'confidence' => $actionId === null ? 'unknown' : $browserAction['source']['status'],
            ];
        }

        $status = $partial ? 'partial' : 'complete';

        return [
            'status' => $status,
            'appended_at' => now()->toIso8601String(),
            'clock' => ['type' => 'performance_monotonic_offset', 'unit' => 'milliseconds'],
            'request' => $request,
            'messages' => $browserMessages,
            'actions' => $browserActions,
            'state' => $browserState,
            'spans' => $spans,
            'failures' => $trace['failures'],
            'idempotency_hash' => substr(hash('sha256', $trace['idempotency_key']), 0, 16),
            'raw_values_stored' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function span(
        string $phase,
        float $start,
        float $duration,
        ?string $messageId = null,
        ?string $componentId = null,
        string $kind = 'duration',
    ): array {
        return [
            'id' => (string) Str::uuid(),
            'lane' => 'browser',
            'kind' => $kind,
            'phase' => $phase,
            'message_id' => $messageId,
            'component_id' => $componentId,
            'start_ms' => round(max(0, $start), 3),
            'duration_ms' => round(max(0, $duration), 3),
            'source' => 'livewire_public',
            'confidence' => 'observed',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<array<string, mixed>>  $browserState
     */
    private function applyBrowserState(array &$payload, array $browserState): void
    {
        foreach ($browserState as $layer) {
            $matched = false;

            foreach ($payload['state_changes'] ?? [] as $index => $change) {
                if (($change['component_id'] ?? null) !== $layer['component_id']
                    || ($change['path'] ?? null) !== $layer['path']) {
                    continue;
                }

                $payload['state_changes'][$index]['browser'] = [
                    'status' => 'observed',
                    'matches_server' => $layer['matches_server'],
                    'type' => $layer['browser_type'],
                ];
                $matched = true;
            }

            if (! $matched) {
                $payload['browser_trace']['status'] = 'partial';
                $payload['completeness']['browser_trace'] = 'partial';
            }
        }
    }
}
