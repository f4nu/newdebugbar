<?php

namespace NewDebugBar\Presentation;

/** Builds a truthful, compact view model for one Livewire exchange. */
final class LivewirePresenter
{
    /** @param array<string, mixed> $section @return array<string, mixed> */
    public function present(array $section): array
    {
        $payload = $this->array($section['payload'] ?? null);
        $messages = $this->items($payload['messages'] ?? null);
        $actions = $this->items($payload['actions'] ?? null);
        $components = $this->items($payload['components'] ?? null);
        $changes = $this->items($payload['state_changes'] ?? null);
        $events = $this->items($payload['events'] ?? null);
        $serverSpans = $this->items($payload['server_spans'] ?? null);
        $browserTrace = $this->array($payload['browser_trace'] ?? null);
        $browserSpans = $this->items($browserTrace['spans'] ?? null);
        $findings = $this->items($payload['findings'] ?? null);
        $componentNames = [];

        foreach ($components as $component) {
            $id = $this->string($component['id'] ?? null);

            if ($id !== null) {
                $componentNames[$id] = $this->string($component['name'] ?? null) ?? 'Unknown component';
            }
        }

        $presentedChanges = array_map(
            fn (array $change): array => $this->stateChange($change, $componentNames),
            $changes,
        );
        $presentedActions = array_map(
            fn (array $action): array => $this->action($action, $componentNames, $browserTrace),
            $actions,
        );
        $presentedMessages = array_map(
            fn (array $message): array => $this->message($message, $componentNames),
            $messages,
        );
        $presentedComponents = array_map(
            fn (array $component): array => $this->component(
                $component,
                $presentedActions,
                $presentedChanges,
                $messages,
                $componentNames,
            ),
            $components,
        );
        usort($presentedComponents, fn (array $left, array $right): int => $left['depth'] <=> $right['depth']
            ?: strcasecmp($left['name'], $right['name'])
            ?: strcmp($left['id'], $right['id']));

        $exchange = $this->array($payload['exchange'] ?? null);
        $completeness = $this->array($payload['completeness'] ?? null);
        $traceStatus = $this->traceStatus($browserTrace['status'] ?? $section['summary']['trace_status'] ?? null);
        $serverLane = $this->lane('Server', 'server', $serverSpans);
        $browserLane = $this->lane('Browser', 'browser', $browserSpans);
        $result = $this->string($exchange['result'] ?? $section['summary']['result'] ?? null) ?? 'unknown';
        $messageCount = count($messages);

        $section['label'] = 'Livewire';
        $section['summary'] = [
            ...$this->array($section['summary'] ?? null),
            'count' => $section['summary']['message_count'] ?? $messageCount,
        ];
        $section['payload'] = [
            ...$payload,
            'presentation' => [
                'headline' => $this->headline($section, $exchange, $actions, $componentNames),
                'outcome' => $this->outcome($result, $messages, $presentedComponents, $presentedChanges),
                'facts' => [
                    ['label' => 'Messages', 'value' => $messageCount],
                    ['label' => 'Actions', 'value' => count($actions)],
                    ['label' => 'Affected components', 'value' => count($components)],
                    ['label' => 'State changes', 'value' => count($changes)],
                ],
                'tabs' => [
                    ['key' => 'overview', 'label' => 'Overview', 'count' => null],
                    ['key' => 'components', 'label' => 'Components', 'count' => count($components)],
                    ['key' => 'timeline', 'label' => 'Timeline', 'count' => count($serverSpans) + count($browserSpans)],
                    ['key' => 'events', 'label' => 'Events', 'count' => count($events)],
                ],
                'messages' => $presentedMessages,
                'actions' => $presentedActions,
                'components' => $presentedComponents,
                'state_changes' => $presentedChanges,
                'events' => array_map(
                    fn (array $event): array => $this->event($event, $componentNames),
                    $events,
                ),
                'server_work' => $this->importantServerWork($serverLane['items']),
                'lanes' => [$serverLane, $browserLane],
                'trace_status' => $traceStatus,
                'notices' => $this->notices($traceStatus, $completeness),
                'findings' => array_map(fn (array $finding): array => [
                    'rule_id' => $this->string($finding['rule_id'] ?? null) ?? 'livewire.unknown',
                    'summary' => $this->string($finding['summary'] ?? null) ?? 'Review this exchange.',
                    'why' => $this->string($finding['why'] ?? null),
                    'next' => $this->string($finding['next'] ?? null),
                ], $findings),
                'affected_hierarchy_only' => ($completeness['components'] ?? null) !== 'complete',
                'truncated' => (bool) ($completeness['truncated'] ?? false),
            ],
        ];

        return $section;
    }

    /** @param array<string, mixed> $section @param array<string, mixed> $exchange @param list<array<string, mixed>> $actions @param array<string, string> $componentNames @return array<string, mixed> */
    private function headline(array $section, array $exchange, array $actions, array $componentNames): array
    {
        $title = $this->string($exchange['title'] ?? $section['summary']['title'] ?? null) ?? 'Livewire exchange';
        $confidence = $this->confidence($exchange['title_confidence'] ?? null);
        $kind = $this->string($exchange['kind'] ?? null) ?? 'unknown';
        $detail = match (true) {
            $kind === 'initial_mount' => 'An initial mount ran for '.count($componentNames).' affected component'.(count($componentNames) === 1 ? '' : 's').'.',
            count($actions) === 1 => $this->actionDetail($actions[0], $componentNames),
            count($actions) > 1 => count($actions).' actions ran across this exchange.',
            default => 'The trigger could not be derived from the available evidence.',
        };

        return [
            'title' => $title,
            'detail' => $detail,
            'kind' => $kind,
            'kind_label' => $this->label($kind),
            'confidence' => $confidence,
        ];
    }

    /** @param array<string, mixed> $action @param array<string, string> $componentNames */
    private function actionDetail(array $action, array $componentNames): string
    {
        $component = $componentNames[(string) ($action['component_id'] ?? '')] ?? 'an affected component';
        $kind = $this->string($action['kind'] ?? null) ?? 'unknown';

        if ($kind === 'property_update') {
            $paths = array_values(array_filter((array) ($action['property_paths'] ?? []), 'is_string'));

            return $paths === []
                ? 'A property update ran on '.$component.'.'
                : 'A property update submitted '.implode(', ', $paths).' on '.$component.'.';
        }

        $name = $this->string($action['name'] ?? null) ?? 'an unknown action';

        return $this->label($kind).' '.$name.' ran on '.$component.'.';
    }

    /** @param list<array<string, mixed>> $messages @param list<array<string, mixed>> $components @param list<array<string, mixed>> $changes @return array<string, string> */
    private function outcome(string $result, array $messages, array $components, array $changes): array
    {
        $rendered = count(array_filter($components, fn (array $component): bool => $component['rendered'] === 'yes'));
        $failed = count(array_filter($messages, fn (array $message): bool => in_array(
            $message['result'] ?? 'unknown',
            ['failed', 'cancelled'],
            true,
        )));
        $title = match ($result) {
            'rendered' => 'Rendered successfully',
            'renderless' => 'Completed without a render',
            'skipped' => 'Render was skipped',
            'validation_failed' => 'Validation stopped the action',
            'redirected' => 'Returned a redirect',
            'downloaded' => 'Returned a download',
            'failed' => 'Exchange failed',
            'cancelled' => 'Exchange was cancelled',
            'mixed' => 'Messages had mixed results',
            default => 'Result is not fully known',
        };

        return [
            'title' => $title,
            'detail' => $failed > 0
                ? $failed.' message'.($failed === 1 ? '' : 's').' failed or were cancelled.'
                : $rendered.' affected component'.($rendered === 1 ? '' : 's').' rendered and '.count($changes).' state change'.(count($changes) === 1 ? ' was' : 's were').' observed.',
            'result' => $result,
        ];
    }

    /** @param array<string, mixed> $action @param array<string, string> $componentNames @param array<string, mixed> $browserTrace @return array<string, mixed> */
    private function action(array $action, array $componentNames, array $browserTrace): array
    {
        $id = (string) ($action['id'] ?? '');
        $browser = collect($this->items($browserTrace['actions'] ?? null))
            ->first(fn (array $item): bool => ($item['action_id'] ?? null) === $id, []);
        $source = $this->array($browser['source'] ?? null);
        $kind = $this->string($action['kind'] ?? null) ?? 'unknown';
        $name = $this->string($action['name'] ?? null) ?? 'Unknown action';

        return [
            ...$action,
            'id' => $id,
            'kind' => $kind,
            'kind_label' => $this->label($kind),
            'name' => $name,
            'component_name' => $componentNames[(string) ($action['component_id'] ?? '')] ?? 'Unknown component',
            'source_label' => $this->sourceLabel($source),
            'source_status' => $source === [] ? 'missing' : ($source['status'] ?? 'unknown'),
        ];
    }

    /** @param array<string, mixed> $message @param array<string, string> $componentNames @return array<string, mixed> */
    private function message(array $message, array $componentNames): array
    {
        $effects = $this->array($message['effects'] ?? null);
        $download = $this->array($effects['download'] ?? null);
        $errors = $this->array($message['validation_errors'] ?? null);
        $result = $this->string($message['result'] ?? null) ?? 'unknown';

        return [
            ...$message,
            'id' => (string) ($message['id'] ?? ''),
            'component_name' => $componentNames[(string) ($message['component_id'] ?? '')] ?? 'Unknown component',
            'result' => $result,
            'result_label' => $this->label($result),
            'validation_fields' => array_values(array_filter(array_keys($errors), 'is_string')),
            'redirect' => $this->string($effects['redirect'] ?? null),
            'download' => $download === [] ? null : [
                'name' => $this->string($download['name'] ?? null) ?? 'download',
                'content_type' => $this->string($download['content_type'] ?? null),
                'size_bytes' => is_numeric($download['size_bytes'] ?? null)
                    ? max(0, (int) $download['size_bytes'])
                    : null,
            ],
        ];
    }

    /** @param array<string, mixed> $change @param array<string, string> $componentNames @return array<string, mixed> */
    private function stateChange(array $change, array $componentNames): array
    {
        $redacted = (bool) ($change['redacted'] ?? false);
        $submitted = $change['submitted'] ?? null;
        $server = $change['server'] ?? null;
        $browser = $this->array($change['browser'] ?? null);

        return [
            ...$change,
            'id' => (string) ($change['id'] ?? ''),
            'path' => $this->string($change['path'] ?? null) ?? 'unknown',
            'component_name' => $componentNames[(string) ($change['component_id'] ?? '')] ?? 'Unknown component',
            'before_display' => $this->value($change['before'] ?? null, $redacted),
            'server_display' => $this->value($server, $redacted),
            'submitted_display' => $this->value($submitted, $redacted),
            'submitted_material' => $submitted !== null && $submitted !== $server,
            'browser_status' => $browser['status'] ?? 'unknown',
            'browser_matches_server' => $browser['matches_server'] ?? null,
            'browser_type' => $browser['type'] ?? null,
            'redacted' => $redacted,
        ];
    }

    /** @param array<string, mixed> $component @param list<array<string, mixed>> $actions @param list<array<string, mixed>> $changes @param list<array<string, mixed>> $messages @param array<string, string> $componentNames @return array<string, mixed> */
    private function component(
        array $component,
        array $actions,
        array $changes,
        array $messages,
        array $componentNames,
    ): array {
        $id = (string) ($component['id'] ?? '');
        $source = $this->array($component['source'] ?? null);
        $reason = $this->array($component['render_reason'] ?? null);
        $message = collect($messages)->first(
            fn (array $item): bool => ($item['component_id'] ?? null) === $id,
            [],
        );

        return [
            ...$component,
            'id' => $id,
            'short_id' => $id === '' ? 'unknown' : substr($id, 0, 8),
            'name' => $this->string($component['name'] ?? null) ?? 'Unknown component',
            'class' => $this->string($component['class'] ?? null),
            'depth' => max(0, min(6, (int) ($component['depth'] ?? 0))),
            'parent_name' => $componentNames[(string) ($component['parent_id'] ?? '')] ?? null,
            'source_label' => $source === [] ? null : (($source['file'] ?? 'unknown').':'.($source['line'] ?? '?')),
            'view_label' => $this->string($component['view'] ?? null),
            'rendered' => in_array($component['rendered'] ?? null, ['yes', 'no'], true)
                ? $component['rendered']
                : 'unknown',
            'render_reason_label' => $this->label((string) ($reason['kind'] ?? 'unknown')),
            'render_reason_confidence' => $this->confidence($reason['confidence'] ?? null),
            'message_result' => $this->string($message['result'] ?? null) ?? 'unknown',
            'actions' => array_values(array_filter(
                $actions,
                fn (array $action): bool => ($action['component_id'] ?? null) === $id,
            )),
            'state_changes' => array_values(array_filter(
                $changes,
                fn (array $change): bool => ($change['component_id'] ?? null) === $id,
            )),
        ];
    }

    /** @param array<string, mixed> $event @param array<string, string> $componentNames @return array<string, mixed> */
    private function event(array $event, array $componentNames): array
    {
        $recipients = array_values(array_filter((array) ($event['observed_recipient_ids'] ?? []), 'is_string'));

        return [
            ...$event,
            'id' => (string) ($event['id'] ?? ''),
            'name' => $this->string($event['name'] ?? null) ?? 'Unknown event',
            'mode_label' => $this->label((string) ($event['mode'] ?? 'unknown')),
            'source_name' => $componentNames[(string) ($event['source_component_id'] ?? '')] ?? null,
            'declared_target_label' => $this->value($event['declared_target'] ?? null, false),
            'recipient_names' => array_map(
                fn (string $id): string => $componentNames[$id] ?? substr($id, 0, 8),
                $recipients,
            ),
            'recipient_status' => $event['recipient_status'] ?? 'unknown',
        ];
    }

    /** @param list<array<string, mixed>> $spans @return array<string, mixed> */
    private function lane(string $label, string $clock, array $spans): array
    {
        $end = 0.0;

        foreach ($spans as $span) {
            $end = max($end, (float) ($span['start_ms'] ?? 0) + (float) ($span['duration_ms'] ?? 0));
        }

        $items = array_map(function (array $span) use ($end): array {
            $start = max(0, (float) ($span['start_ms'] ?? 0));
            $duration = max(0, (float) ($span['duration_ms'] ?? 0));

            return [
                ...$span,
                'phase' => $this->string($span['phase'] ?? null) ?? 'unknown',
                'label' => $this->label((string) ($span['phase'] ?? 'unknown')),
                'start_ms' => round($start, 3),
                'duration_ms' => round($duration, 3),
                'start_percent' => $end > 0 ? round($start / $end * 100, 2) : 0,
                'duration_percent' => $end > 0 ? round($duration / $end * 100, 2) : 0,
                'kind' => ($span['kind'] ?? null) === 'point' || $duration === 0.0 ? 'point' : 'duration',
            ];
        }, $spans);
        usort($items, fn (array $left, array $right): int => $left['start_ms'] <=> $right['start_ms']
            ?: $right['duration_ms'] <=> $left['duration_ms']);

        return [
            'label' => $label,
            'clock' => $clock,
            'duration_ms' => round($end, 3),
            'items' => $items,
            'status' => $items === [] ? 'missing' : 'observed',
        ];
    }

    /** @param list<array<string, mixed>> $spans @return list<array<string, mixed>> */
    private function importantServerWork(array $spans): array
    {
        $spans = array_values(array_filter(
            $spans,
            fn (array $span): bool => $span['duration_ms'] > 0,
        ));
        usort($spans, fn (array $left, array $right): int => $right['duration_ms'] <=> $left['duration_ms']
            ?: $left['start_ms'] <=> $right['start_ms']);

        return array_slice($spans, 0, 4);
    }

    /** @param array<string, mixed> $completeness @return list<array{title: string, detail: string, tone: string}> */
    private function notices(string $traceStatus, array $completeness): array
    {
        $notices = [];

        if ($traceStatus !== 'complete') {
            $notices[] = [
                'title' => $traceStatus === 'missing' ? 'Browser trace is missing' : 'Browser trace is partial',
                'detail' => $traceStatus === 'missing'
                    ? 'Server facts are available. Repeat the interaction with this page open to collect browser phases.'
                    : 'Some browser callbacks could not be matched. Unmatched details stay unknown.',
                'tone' => 'neutral',
            ];
        }

        if (($completeness['server_spans'] ?? null) === 'unknown') {
            $reasons = array_values(array_filter((array) ($completeness['unknown_reasons'] ?? []), 'is_string'));
            $notices[] = [
                'title' => 'Server phase timing is unavailable',
                'detail' => $reasons[0] ?? 'The exchange is still recorded without internal phase timing.',
                'tone' => 'neutral',
            ];
        }

        if (($completeness['truncated'] ?? false) === true) {
            $notices[] = [
                'title' => 'Some evidence was truncated',
                'detail' => 'Counts include omitted items. The visible rows are only the retained evidence.',
                'tone' => 'attention',
            ];
        }

        return $notices;
    }

    /** @param array<string, mixed> $source */
    private function sourceLabel(array $source): ?string
    {
        $directive = $this->string($source['directive'] ?? null);
        $element = $this->string($source['element'] ?? null);

        return match (true) {
            $directive !== null && $element !== null => $directive.' on <'.$element.'>',
            $directive !== null => $directive,
            $element !== null => '<'.$element.'>',
            default => null,
        };
    }

    private function traceStatus(mixed $status): string
    {
        return in_array($status, ['complete', 'partial', 'missing', 'expired'], true)
            ? (string) $status
            : 'missing';
    }

    private function confidence(mixed $confidence): string
    {
        return in_array($confidence, ['observed', 'inferred', 'unknown'], true)
            ? (string) $confidence
            : 'unknown';
    }

    private function label(string $value): string
    {
        return ucfirst(str_replace('_', ' ', $value));
    }

    private function value(mixed $value, bool $redacted): string
    {
        if ($redacted || $value === '[redacted]') {
            return 'Changed, value hidden';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            if ($value === '') {
                return '""';
            }

            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? $encoded : '[unavailable]';
    }

    /** @return array<string, mixed> */
    private function array(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /** @return list<array<string, mixed>> */
    private function items(mixed $value): array
    {
        return is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
    }

    private function string(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
