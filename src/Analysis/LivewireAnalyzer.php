<?php

namespace NewDebugBar\Analysis;

/** Produces a small set of high-confidence findings from Livewire facts. */
final class LivewireAnalyzer
{
    private const SLOW_SERVER_WORK_MS = 200.0;

    /** @param array<string, mixed> $section @return list<array<string, mixed>> */
    public function analyze(array $section): array
    {
        $payload = is_array($section['payload'] ?? null) ? $section['payload'] : [];
        $components = $this->keyedItems($payload['components'] ?? null, 'id');
        $actions = $this->keyedItems($payload['actions'] ?? null, 'id');
        $allSpans = array_values(array_filter(
            is_array($payload['server_spans'] ?? null) ? $payload['server_spans'] : [],
            'is_array',
        ));
        $spans = array_values(array_filter(
            $allSpans,
            fn (mixed $span): bool => is_array($span)
                && is_numeric($span['duration_ms'] ?? null)
                && (float) $span['duration_ms'] >= self::SLOW_SERVER_WORK_MS,
        ));

        usort($spans, fn (array $left, array $right): int => (float) $right['duration_ms'] <=> (float) $left['duration_ms']
        );

        $findings = array_map(function (array $span) use ($payload, $components, $actions): array {
            $componentId = is_string($span['component_id'] ?? null) ? $span['component_id'] : null;
            $actionId = is_string($span['action_id'] ?? null) ? $span['action_id'] : null;
            $component = $componentId === null ? [] : ($components[$componentId] ?? []);
            $action = $actionId === null ? [] : ($actions[$actionId] ?? []);
            $componentName = $this->componentName($component);
            $work = $this->workName($span, $action);
            $duration = round((float) $span['duration_ms'], 1);

            return [
                'rule_id' => 'livewire.slow_server_work',
                'severity' => 'warning',
                'section' => 'livewire',
                'summary' => sprintf('%s spent %s ms in %s.', $componentName, $this->number($duration), $work),
                'why' => 'The Livewire response waited for this server work to finish.',
                'location' => null,
                'origin' => sprintf('Observed %s work on %s.', $work, $componentName),
                'next' => 'Inspect the linked queries and Laravel work. If the work is independent, move it to a queue. For slow display-only regions, consider lazy loading or an island.',
                'action' => ['label' => 'Inspect Livewire work', 'section' => 'livewire'],
                'evidence' => [
                    'exchange_id' => is_string(data_get($payload, 'exchange.id')) ? data_get($payload, 'exchange.id') : null,
                    'span_id' => is_string($span['id'] ?? null) ? $span['id'] : null,
                    'component_id' => $componentId,
                    'action_id' => $actionId,
                    'phase' => is_string($span['phase'] ?? null) ? $span['phase'] : null,
                    'duration_ms' => $duration,
                    'threshold_ms' => self::SLOW_SERVER_WORK_MS,
                ],
            ];
        }, array_slice($spans, 0, 3));

        if ($findings !== [] || $allSpans !== []) {
            return $findings;
        }

        $duration = data_get($payload, 'exchange.duration_ms');

        if (! is_numeric($duration) || (float) $duration < self::SLOW_SERVER_WORK_MS) {
            return [];
        }

        $component = array_values($components)[0] ?? [];
        $componentName = $this->componentName($component);
        $duration = round((float) $duration, 1);

        return [[
            'rule_id' => 'livewire.slow_request',
            'severity' => 'warning',
            'section' => 'livewire',
            'summary' => sprintf('%s request took %s ms.', $componentName, $this->number($duration)),
            'why' => 'The Livewire response was not ready until this server request finished.',
            'location' => null,
            'origin' => sprintf('Observed total server time for %s. Internal phase timing was unavailable.', $componentName),
            'next' => 'Inspect the linked queries and Laravel work. Run the interaction with Laravel debug mode on locally to capture the slow internal phase.',
            'action' => ['label' => 'Inspect Livewire request', 'section' => 'livewire'],
            'evidence' => [
                'exchange_id' => is_string(data_get($payload, 'exchange.id')) ? data_get($payload, 'exchange.id') : null,
                'component_id' => is_string($component['id'] ?? null) ? $component['id'] : null,
                'duration_ms' => $duration,
                'threshold_ms' => self::SLOW_SERVER_WORK_MS,
                'server_phase_timing' => 'unavailable',
            ],
        ]];
    }

    /** @return array<string, array<string, mixed>> */
    private function keyedItems(mixed $items, string $key): array
    {
        $keyed = [];

        foreach (is_array($items) ? $items : [] as $item) {
            if (is_array($item) && is_string($item[$key] ?? null)) {
                $keyed[$item[$key]] = $item;
            }
        }

        return $keyed;
    }

    /** @param array<string, mixed> $component */
    private function componentName(array $component): string
    {
        $class = is_string($component['class'] ?? null) ? $component['class'] : null;
        $name = is_string($component['name'] ?? null) ? $component['name'] : null;
        $separator = $class === null ? false : strrpos($class, '\\');
        $source = $class === null ? $name : substr($class, ($separator === false ? -1 : $separator) + 1);

        return $this->humanize($source ?? 'Livewire component');
    }

    /** @param array<string, mixed> $span @param array<string, mixed> $action */
    private function workName(array $span, array $action): string
    {
        $phase = is_string($span['phase'] ?? null) ? $span['phase'] : 'server work';
        $name = is_string($action['name'] ?? null) ? $action['name'] : null;

        if (str_starts_with($phase, 'call') && $name !== null && ! str_starts_with($name, '$') && $name !== '__dispatch') {
            return $this->humanize($name);
        }

        return match (true) {
            str_starts_with($phase, 'call') => 'an action',
            $phase === 'render' => 'rendering',
            $phase === 'hydrate' => 'hydration',
            $phase === 'dehydrate' => 'dehydration',
            default => $this->humanize($phase),
        };
    }

    private function humanize(string $value): string
    {
        $value = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $value) ?? $value;
        $value = preg_replace('/[^A-Za-z0-9]+/', ' ', $value) ?? $value;

        return (string) str(trim($value))->headline();
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }
}
