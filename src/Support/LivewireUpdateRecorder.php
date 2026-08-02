<?php

namespace NewDebugBar\Support;

use Illuminate\Http\Request;
use NewDebugBar\ProfileManager;

/** Records safe request and response metadata for application Livewire updates. */
final class LivewireUpdateRecorder
{
    public function __construct(private readonly ProfileManager $manager) {}

    /** @param array<string, mixed> $responsePayload */
    public function record(array $responsePayload, ?Request $request = null): void
    {
        if (! $this->manager->isCollecting()) {
            return;
        }

        $request ??= request();
        $requests = $request->input('components', []);
        $responses = $responsePayload['components'] ?? [];

        if (! is_array($requests) || ! is_array($responses)) {
            return;
        }

        $payloadSize = $this->payloadSize($request);

        foreach (array_values($requests) as $index => $requestComponent) {
            if (! is_array($requestComponent)) {
                continue;
            }

            $snapshot = $this->decode($requestComponent['snapshot'] ?? null);
            $name = $snapshot['memo']['name'] ?? null;

            if (! is_string($name) || $name === '' || $name === 'new-debug-bar.toolbar') {
                continue;
            }

            $responseComponent = $responses[$index] ?? [];
            $responseSnapshot = $this->decode(is_array($responseComponent) ? ($responseComponent['snapshot'] ?? null) : null);
            $fields = $this->validationFields(is_array($responseComponent) ? $responseComponent : [], $responseSnapshot);
            $calls = is_array($requestComponent['calls'] ?? null) ? $requestComponent['calls'] : [];
            $updates = is_array($requestComponent['updates'] ?? null) ? $requestComponent['updates'] : [];

            $this->manager->record('livewire', [
                'phase' => 'response',
                'request_index' => $index,
                'component' => $name,
                'actions' => array_values(array_unique(array_filter(array_map(
                    fn (mixed $call): ?string => is_array($call) && is_string($call['method'] ?? null)
                        ? $call['method']
                        : null,
                    $calls,
                )))),
                'updated_properties' => array_values(array_map('strval', array_keys($updates))),
                'validation_failure_count' => count($fields),
                'validation_fields' => $fields,
                'payload_size_bytes' => $payloadSize,
                'response_size_bytes' => is_array($responseComponent)
                    ? strlen((string) json_encode($responseComponent))
                    : 0,
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function decode(mixed $json): array
    {
        if (! is_string($json)) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string, mixed> $response @param array<string, mixed> $snapshot @return list<string> */
    private function validationFields(array $response, array $snapshot): array
    {
        $fields = array_keys(is_array($snapshot['memo']['errors'] ?? null) ? $snapshot['memo']['errors'] : []);
        $returnsMeta = $response['effects']['returnsMeta'] ?? [];

        if (is_array($returnsMeta)) {
            foreach ($returnsMeta as $meta) {
                if (is_array($meta) && is_array($meta['errors'] ?? null)) {
                    $fields = [...$fields, ...array_keys($meta['errors'])];
                }
            }
        }

        return array_values(array_unique(array_map('strval', $fields)));
    }

    private function payloadSize(Request $request): int
    {
        $contentLength = $request->headers->get('Content-Length');

        return is_numeric($contentLength)
            ? max(0, (int) $contentLength)
            : strlen($request->getContent());
    }
}
