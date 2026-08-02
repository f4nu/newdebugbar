<?php

namespace NewDebugBar\Analysis;

/** Builds a searchable event sequence without turning point events into spans. */
final class TimelineBuilder
{
    /** @param array<string, mixed> $profile @return list<array<string, mixed>> */
    public function build(array $profile): array
    {
        $duration = (float) ($profile['metrics']['duration_ms'] ?? 0);
        $timeline = [[
            'id' => 'request-start',
            'section' => 'request',
            'kind' => 'milestone',
            'label' => 'Request started',
            'at_ms' => 0.0,
            'start_ms' => null,
            'duration_ms' => null,
        ]];

        foreach ($profile['sections'] ?? [] as $section => $data) {
            if (in_array($section, ['overview', 'request', 'timeline'], true)) {
                continue;
            }

            foreach ($data['payload']['items'] ?? [] as $index => $item) {
                if (! is_array($item) || ! isset($item['at_ms'])) {
                    continue;
                }

                $isQuery = $section === 'queries' && isset($item['duration_ms']);
                $timeline[] = [
                    'id' => $section.'-'.$index,
                    'section' => $section,
                    'kind' => $isQuery ? 'span' : 'point',
                    'label' => $this->label($section, $item),
                    'at_ms' => round((float) $item['at_ms'], 3),
                    'start_ms' => $isQuery ? round(max(0, (float) $item['at_ms'] - (float) $item['duration_ms']), 3) : null,
                    'duration_ms' => $isQuery ? round((float) $item['duration_ms'], 2) : null,
                ];
            }
        }

        $timeline[] = [
            'id' => 'request-end',
            'section' => 'request',
            'kind' => 'milestone',
            'label' => 'Request finished',
            'at_ms' => round($duration, 3),
            'start_ms' => null,
            'duration_ms' => null,
        ];

        usort($timeline, fn (array $left, array $right): int => $left['at_ms'] <=> $right['at_ms']
            ?: $this->kindOrder($left['kind']) <=> $this->kindOrder($right['kind']));

        return $timeline;
    }

    /** @param array<string, mixed> $item */
    private function label(string $section, array $item): string
    {
        $label = match ($section) {
            'queries' => $item['normalized_sql'] ?? $item['sql'] ?? 'Query',
            'livewire' => trim(($item['component'] ?? 'Livewire').' '.implode(' ', $item['actions'] ?? [])),
            'http_client' => trim(($item['method'] ?? '').' '.($item['url'] ?? 'HTTP request')),
            'queue' => trim(($item['kind'] ?? '').' '.($item['job'] ?? 'Job')),
            'mail' => 'Mail sent'.(($item['mailable'] ?? null) ? ' '.$item['mailable'] : ''),
            'notifications' => trim(($item['status'] ?? '').' '.($item['notification'] ?? 'Notification')),
            'models' => trim(($item['event'] ?? '').' '.($item['model'] ?? 'Model')),
            'cache' => trim(($item['operation'] ?? 'Cache').' '.($item['key_hash'] ?? '')),
            'views' => $item['name'] ?? 'View rendered',
            'events' => $item['name'] ?? 'Event dispatched',
            'logs' => strtoupper((string) ($item['level'] ?? 'log')).' '.($item['message'] ?? ''),
            'exceptions' => $item['class'] ?? 'Exception',
            default => $item['name'] ?? $item['event'] ?? $item['operation'] ?? ucfirst($section),
        };

        $label = (string) $label;

        return mb_strlen($label) > 140 ? mb_substr($label, 0, 139).'…' : $label;
    }

    private function kindOrder(string $kind): int
    {
        return match ($kind) {
            'milestone' => 0,
            'span' => 1,
            default => 2,
        };
    }
}
