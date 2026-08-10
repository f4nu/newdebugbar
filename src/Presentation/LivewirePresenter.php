<?php

namespace NewDebugBar\Presentation;

/** Builds a truthful, compact view model for one Livewire request. */
final class LivewirePresenter
{
    /** @param array<string, mixed> $section @return array<string, mixed> */
    public function present(array $section): array
    {
        $summary = $this->array($section['summary'] ?? null);
        $payload = $this->array($section['payload'] ?? null);
        $messages = $this->items($payload['messages'] ?? null);
        $actions = $this->items($payload['actions'] ?? null);
        $components = $this->items($payload['components'] ?? null);
        $changes = $this->items($payload['state_changes'] ?? null);
        $events = $this->items($payload['events'] ?? null);
        $serverSpans = $this->items($payload['server_spans'] ?? null);
        $browserTrace = $this->array($payload['browser_trace'] ?? null);
        $findings = $this->items($payload['findings'] ?? null);
        $identities = $this->componentIdentities($components);

        $presentedActions = array_map(
            fn (array $action): array => $this->action($action, $identities, $browserTrace),
            $actions,
        );
        $presentedMessages = array_map(
            fn (array $message): array => $this->message($message, $identities, $browserTrace),
            $messages,
        );
        $presentedChanges = array_map(
            fn (array $change): array => $this->stateChange($change, $identities),
            $changes,
        );
        $presentedEvents = array_map(
            fn (array $event, int $index): array => $this->event($event, $identities, $index + 1),
            $events,
            array_keys($events),
        );
        $presentedWork = array_map(
            fn (array $span): array => $this->serverWork($span, $identities, $presentedActions),
            $serverSpans,
        );
        $presentedWork = array_values(array_filter(
            $presentedWork,
            fn (array $span): bool => $span['duration_ms'] > 0,
        ));
        $presentedComponents = array_map(
            fn (array $component): array => $this->component(
                $component,
                $identities,
                $presentedActions,
                $presentedMessages,
                $presentedChanges,
                $presentedEvents,
                $presentedWork,
            ),
            $components,
        );
        $presentedComponents = $this->disambiguateComponents($presentedComponents);
        usort($presentedComponents, fn (array $left, array $right): int => $left['depth'] <=> $right['depth']
            ?: strcasecmp($left['list_label'], $right['list_label'])
            ?: strcmp($left['id'], $right['id']));

        $exchange = $this->array($payload['exchange'] ?? null);
        $completeness = $this->array($payload['completeness'] ?? null);
        $traceStatus = $this->traceStatus($browserTrace['status'] ?? $summary['trace_status'] ?? null);
        $result = $this->string($exchange['result'] ?? $summary['result'] ?? null) ?? 'unknown';

        $section['label'] = 'Livewire';
        $section['summary'] = [
            ...$summary,
            'count' => $summary['message_count'] ?? count($messages),
        ];
        $section['payload'] = [
            ...$payload,
            'presentation' => [
                'activity' => $this->activity($exchange, $presentedActions, $presentedComponents),
                'outcome' => $this->outcome($result, $presentedMessages),
                'tabs' => [
                    ['key' => 'overview', 'label' => 'Overview'],
                    ['key' => 'components', 'label' => 'Components'],
                    ['key' => 'events', 'label' => 'Events'],
                ],
                'components' => $presentedComponents,
                'state_changes' => $presentedChanges,
                'events' => $presentedEvents,
                'server_work' => $this->importantServerWork($presentedWork),
                'validation_fields' => $this->validationFields($presentedMessages),
                'trace_status' => $traceStatus,
                'notices' => $this->notices($traceStatus, $completeness),
                'findings' => array_map(fn (array $finding): array => [
                    'rule_id' => $this->string($finding['rule_id'] ?? null) ?? 'livewire.unknown',
                    'severity' => $this->string($finding['severity'] ?? null) ?? 'warning',
                    'summary' => $this->string($finding['summary'] ?? null) ?? 'Review this Livewire request.',
                    'impact' => $this->string($finding['why'] ?? null) ?? 'The impact could not be described from the captured evidence.',
                    'origin' => $this->string($finding['origin'] ?? $finding['location'] ?? null) ?? 'Captured during this Livewire request.',
                    'next' => $this->string($finding['next'] ?? null) ?? 'Inspect the linked Livewire and Laravel work.',
                ], $findings),
                'affected_hierarchy_only' => ($completeness['components'] ?? null) !== 'complete',
                'truncated' => (bool) ($completeness['truncated'] ?? false),
            ],
        ];

        return $section;
    }

    /** @param array<string, mixed> $exchange @param list<array<string, mixed>> $actions @param list<array<string, mixed>> $components @return array<string, string> */
    private function activity(array $exchange, array $actions, array $components): array
    {
        $kind = $this->string($exchange['kind'] ?? null) ?? 'unknown';

        if ($kind === 'initial_mount') {
            $root = collect($components)->first(fn (array $component): bool => $component['depth'] === 0, $components[0] ?? []);
            $name = $this->string($root['display_name'] ?? null) ?? 'Livewire component';

            return [
                'title' => $name.' mounted',
                'detail' => count($components) > 1
                    ? 'Its affected child components were also observed during the mount.'
                    : 'The component was prepared for its first render.',
            ];
        }

        if (count($actions) === 1) {
            $action = $actions[0];
            $component = $this->string($action['component_name'] ?? null) ?? 'Livewire component';

            return [
                'title' => $action['display_name'],
                'detail' => match ($action['kind']) {
                    'property_update' => $component.' handled the property change.',
                    'poll' => $component.' handled the scheduled check.',
                    'event' => $component.' received the event.',
                    default => $component.' ran the action.',
                },
            ];
        }

        $propertyAction = collect($actions)
            ->where('kind', 'property_update')
            ->groupBy(fn (array $action): string => ($action['component_id'] ?? '').'|'.($action['display_name'] ?? ''))
            ->whenNotEmpty(fn ($groups) => $groups->count() === 1 ? $groups->first()->first() : null);

        if (is_array($propertyAction)) {
            $component = $this->string($propertyAction['component_name'] ?? null) ?? 'Livewire component';

            return [
                'title' => $propertyAction['display_name'],
                'detail' => $component.' handled the property change.',
            ];
        }

        $repeatedAction = collect($actions)
            ->groupBy(fn (array $action): string => ($action['component_id'] ?? '').'|'.($action['display_name'] ?? ''))
            ->whenNotEmpty(fn ($groups) => $groups->count() === 1 ? $actions[0] : null);

        if (is_array($repeatedAction)) {
            $component = $this->string($repeatedAction['component_name'] ?? null) ?? 'Livewire component';

            return [
                'title' => $repeatedAction['display_name'],
                'detail' => $repeatedAction['kind'] === 'property_update'
                    ? $component.' handled the property change.'
                    : $component.' ran the repeated trigger.',
            ];
        }

        if (count($actions) > 1) {
            return [
                'title' => 'Multiple components updated',
                'detail' => count($components).' affected component'.(count($components) === 1 ? '' : 's').' ran work in this request.',
            ];
        }

        return [
            'title' => 'Livewire request',
            'detail' => 'The exact trigger was not observed.',
        ];
    }

    /** @param list<array<string, mixed>> $messages @return array<string, string> */
    private function outcome(string $result, array $messages): array
    {
        $validationFields = $this->validationFields($messages);

        return [
            'title' => match ($result) {
                'rendered' => 'Rendered',
                'renderless', 'skipped' => 'Finished without rendering',
                'validation_failed' => 'Validation failed',
                'redirected' => 'Redirected',
                'downloaded' => 'Downloaded',
                'failed' => 'Failed',
                'cancelled' => 'Cancelled',
                'mixed' => 'Mixed result',
                default => 'Result not observed',
            },
            'detail' => match ($result) {
                'rendered' => 'The affected view was updated.',
                'renderless', 'skipped' => 'The action finished without a render.',
                'validation_failed' => $validationFields === []
                    ? 'Laravel validation stopped the action.'
                    : 'Check '.implode(', ', $validationFields).'.',
                'redirected' => ($messages[0]['redirect'] ?? null) === null
                    ? 'The response sent the browser to another location.'
                    : 'Redirected to '.$messages[0]['redirect'].'.',
                'downloaded' => ($messages[0]['download']['name'] ?? null) === null
                    ? 'The response returned a file without storing its body.'
                    : 'Returned '.$messages[0]['download']['name'].' without storing its body.',
                'failed' => 'The request did not finish normally.',
                'cancelled' => 'The request was cancelled before it finished.',
                'mixed' => 'Affected components returned different results.',
                default => 'The available evidence does not prove the final result.',
            },
            'result' => $result,
        ];
    }

    /** @param array<string, mixed> $action @param array<string, array<string, string>> $identities @param array<string, mixed> $browserTrace @return array<string, mixed> */
    private function action(array $action, array $identities, array $browserTrace): array
    {
        $id = (string) ($action['id'] ?? '');
        $browser = collect($this->items($browserTrace['actions'] ?? null))
            ->first(fn (array $item): bool => ($item['action_id'] ?? null) === $id, []);
        $source = $this->array($browser['source'] ?? null);
        $kind = $this->string($action['kind'] ?? null) ?? 'unknown';
        $name = $this->string($action['name'] ?? null);
        $directive = $this->string($source['directive'] ?? null);
        $paths = array_values(array_filter((array) ($action['property_paths'] ?? []), 'is_string'));

        if ($kind === 'refresh' && $directive !== null && str_starts_with($directive, 'wire:poll')) {
            $kind = 'poll';
        }

        if ($name === '__dispatch') {
            $kind = 'event';
        }

        return [
            'id' => $id,
            'message_id' => $this->string($action['message_id'] ?? null),
            'component_id' => $this->string($action['component_id'] ?? null),
            'kind' => $kind,
            'display_name' => $this->actionName($kind, $name, $paths),
            'component_name' => $this->identityName($identities, $action['component_id'] ?? null),
            'property_paths' => $paths,
            'source_label' => $this->sourceLabel($source),
            'source_status' => $source === [] ? 'missing' : ($source['status'] ?? 'unknown'),
        ];
    }

    /** @param array<string, mixed> $message @param array<string, array<string, string>> $identities @param array<string, mixed> $browserTrace @return array<string, mixed> */
    private function message(array $message, array $identities, array $browserTrace): array
    {
        $effects = $this->array($message['effects'] ?? null);
        $download = $this->array($effects['download'] ?? null);
        $errors = $this->array($message['validation_errors'] ?? null);
        $result = $this->string($message['result'] ?? null) ?? 'unknown';
        $id = (string) ($message['id'] ?? '');
        $browser = collect($this->items($browserTrace['messages'] ?? null))
            ->first(fn (array $item): bool => ($item['message_id'] ?? null) === $id, []);

        return [
            'id' => $id,
            'component_id' => $this->string($message['component_id'] ?? null),
            'component_name' => $this->identityName($identities, $message['component_id'] ?? null),
            'result' => $result,
            'result_label' => $this->resultLabel($result),
            'browser_outcome' => $this->string($browser['outcome'] ?? null) ?? 'unknown',
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

    /** @param array<string, mixed> $change @param array<string, array<string, string>> $identities @return array<string, mixed> */
    private function stateChange(array $change, array $identities): array
    {
        $redacted = (bool) ($change['redacted'] ?? false);
        $submitted = $change['submitted'] ?? null;
        $server = $change['server'] ?? null;
        $browser = $this->array($change['browser'] ?? null);

        return [
            'id' => (string) ($change['id'] ?? ''),
            'action_id' => $this->string($change['action_id'] ?? null),
            'component_id' => $this->string($change['component_id'] ?? null),
            'path' => $this->string($change['path'] ?? null) ?? 'unknown',
            'path_label' => $this->humanize($this->string($change['path'] ?? null) ?? 'property'),
            'component_name' => $this->identityName($identities, $change['component_id'] ?? null),
            'before_display' => $this->value($change['before'] ?? null, $redacted),
            'after_display' => $this->value($server, $redacted),
            'submitted_display' => $this->value($submitted, $redacted),
            'submitted_material' => $submitted !== null && $submitted !== $server,
            'browser_status' => $browser['status'] ?? 'unknown',
            'browser_matches_server' => $browser['matches_server'] ?? null,
            'redacted' => $redacted,
        ];
    }

    /** @param array<string, mixed> $component @param array<string, array<string, string>> $identities @param list<array<string, mixed>> $actions @param list<array<string, mixed>> $messages @param list<array<string, mixed>> $changes @param list<array<string, mixed>> $events @param list<array<string, mixed>> $work @return array<string, mixed> */
    private function component(
        array $component,
        array $identities,
        array $actions,
        array $messages,
        array $changes,
        array $events,
        array $work,
    ): array {
        $id = (string) ($component['id'] ?? '');
        $identity = $identities[$id] ?? ['display_name' => 'Unknown component', 'raw_name' => 'Unknown component'];
        $source = $this->array($component['source'] ?? null);
        $componentActions = $this->matching($actions, 'component_id', $id);
        $componentMessages = $this->matching($messages, 'component_id', $id);
        $componentChanges = $this->matching($changes, 'component_id', $id);
        $componentWork = $this->matching($work, 'component_id', $id);
        $emitted = array_values(array_filter($events, fn (array $event): bool => $event['source_component_id'] === $id));
        $received = array_values(array_filter($events, fn (array $event): bool => in_array($id, $event['observed_recipient_ids'], true)));
        $message = $componentMessages[0] ?? [];
        $rendered = in_array($component['rendered'] ?? null, ['yes', 'no'], true)
            ? $component['rendered']
            : 'unknown';
        $result = $this->string($message['result'] ?? null) ?? 'unknown';
        $trigger = $this->preferredTrigger($componentActions);
        $validationFields = $this->validationFields($componentMessages);
        $sourceLabel = $source === [] ? null : (($source['file'] ?? 'unknown').':'.($source['line'] ?? '?'));
        $class = $this->string($component['class'] ?? null);
        $view = $this->string($component['view'] ?? null);

        $presented = [
            'id' => $id,
            'short_id' => $id === '' ? 'unknown' : substr($id, 0, 8),
            'display_name' => $identity['display_name'],
            'list_label' => $identity['display_name'],
            'raw_name' => $identity['raw_name'],
            'class' => $class,
            'depth' => max(0, min(6, (int) ($component['depth'] ?? 0))),
            'parent_name' => $this->identityNameOrNull($identities, $component['parent_id'] ?? null),
            'source_label' => $sourceLabel,
            'view_label' => $view,
            'trigger_label' => $trigger,
            'rendered' => $rendered,
            'rendered_label' => match ($rendered) {
                'yes' => 'Rendered',
                'no' => 'Not rendered',
                default => 'Render not observed',
            },
            'result' => $result,
            'result_label' => $this->resultLabel($result),
            'actions' => $componentActions,
            'state_changes' => $componentChanges,
            'validation_fields' => $validationFields,
            'emitted_events' => $emitted,
            'received_events' => $received,
            'server_work' => $this->importantServerWork($componentWork),
            'redirect' => $this->string($message['redirect'] ?? null),
            'download' => is_array($message['download'] ?? null) ? $message['download'] : null,
        ];

        return [
            ...$presented,
            'copy_details' => $this->componentCopy($presented),
        ];
    }

    /** @param array<string, mixed> $event @param array<string, array<string, string>> $identities @return array<string, mixed> */
    private function event(array $event, array $identities, int $sequence): array
    {
        $recipients = array_values(array_filter((array) ($event['observed_recipient_ids'] ?? []), 'is_string'));
        $recipientNames = array_map(
            fn (string $id): string => $identities[$id]['display_name'] ?? 'Instance '.substr($id, 0, 8),
            $recipients,
        );
        $status = in_array($event['recipient_status'] ?? null, ['observed', 'unknown'], true)
            ? $event['recipient_status']
            : 'unknown';
        $rawName = $this->string($event['name'] ?? null) ?? 'unknown-event';
        $parameters = is_array($event['parameters'] ?? null) ? $event['parameters'] : [];
        $declaredTarget = $event['declared_target'] ?? null;
        $presented = [
            'id' => (string) ($event['id'] ?? ''),
            'sequence' => $sequence,
            'name' => $rawName,
            'display_name' => $this->humanize($rawName),
            'mode' => $this->string($event['mode'] ?? null) ?? 'unknown',
            'mode_label' => $this->eventModeLabel($this->string($event['mode'] ?? null) ?? 'unknown'),
            'source_component_id' => $this->string($event['source_component_id'] ?? null),
            'source_name' => $this->identityNameOrNull($identities, $event['source_component_id'] ?? null),
            'declared_target' => $declaredTarget,
            'declared_target_label' => $declaredTarget === null
                ? 'No target declared'
                : $this->componentReferenceName($declaredTarget),
            'observed_recipient_ids' => $recipients,
            'recipient_names' => $recipientNames,
            'recipient_status' => $status,
            'recipient_label' => $status === 'unknown'
                ? 'Not observed'
                : ($recipientNames === [] ? 'No recipients observed' : implode(', ', $recipientNames)),
            'parameters' => $parameters,
            'parameters_json' => $this->json($parameters),
        ];

        return [
            ...$presented,
            'copy_details' => $this->eventCopy($presented),
        ];
    }

    /** @param array<string, mixed> $span @param array<string, array<string, string>> $identities @param list<array<string, mixed>> $actions @return array<string, mixed> */
    private function serverWork(array $span, array $identities, array $actions): array
    {
        $actionId = $this->string($span['action_id'] ?? null);
        $action = collect($actions)->first(fn (array $item): bool => $item['id'] === $actionId, []);
        $phase = $this->string($span['phase'] ?? null) ?? 'unknown';

        return [
            'id' => (string) ($span['id'] ?? ''),
            'component_id' => $this->string($span['component_id'] ?? null),
            'component_name' => $this->identityName($identities, $span['component_id'] ?? null),
            'action_id' => $actionId,
            'phase' => $phase,
            'label' => str_starts_with($phase, 'call') && $action !== []
                ? $action['display_name']
                : match ($phase) {
                    'render' => 'Rendered',
                    'hydrate' => 'Hydrated',
                    'dehydrate' => 'Dehydrated',
                    'call' => 'Action work',
                    default => $this->humanize($phase),
                },
            'start_ms' => round(max(0, (float) ($span['start_ms'] ?? 0)), 3),
            'duration_ms' => round(max(0, (float) ($span['duration_ms'] ?? 0)), 3),
        ];
    }

    /** @param list<array<string, mixed>> $components @return array<string, array<string, string>> */
    private function componentIdentities(array $components): array
    {
        $identities = [];

        foreach ($components as $component) {
            $id = $this->string($component['id'] ?? null);

            if ($id === null) {
                continue;
            }

            $rawName = $this->string($component['name'] ?? null) ?? 'Unknown component';
            $class = $this->string($component['class'] ?? null);
            $className = $class === null ? null : substr($class, (strrpos($class, '\\') ?: -1) + 1);
            $useClass = $className !== null
                && ! str_contains(strtolower($className), 'anonymous')
                && strtolower($className) !== 'component';
            $source = $useClass ? $className : $this->meaningfulComponentSegment($rawName);

            $identities[$id] = [
                'display_name' => $this->humanize($source),
                'raw_name' => $rawName,
            ];
        }

        return $identities;
    }

    /** @param list<array<string, mixed>> $components @return list<array<string, mixed>> */
    private function disambiguateComponents(array $components): array
    {
        $counts = array_count_values(array_column($components, 'display_name'));

        foreach ($components as $index => $component) {
            if (($counts[$component['display_name']] ?? 0) < 2) {
                continue;
            }

            $collisions = array_values(array_filter(
                $components,
                fn (array $candidate): bool => $candidate['display_name'] === $component['display_name'],
            ));
            $prefixLength = 6;
            $maxLength = max(array_map(fn (array $candidate): int => strlen($candidate['id']), $collisions));

            while ($prefixLength < $maxLength) {
                $prefixes = array_map(
                    fn (array $candidate): string => substr($candidate['id'], 0, $prefixLength),
                    $collisions,
                );

                if (count(array_unique($prefixes)) === count($prefixes)) {
                    break;
                }

                $prefixLength++;
            }

            $components[$index]['list_label'] = $component['display_name'].' ('.substr($component['id'], 0, $prefixLength).')';
        }

        return $components;
    }

    /** @param array<string, mixed> $component */
    private function componentCopy(array $component): string
    {
        $lines = [
            $component['display_name'],
            'Trigger: '.$component['trigger_label'],
            'Result: '.$component['result_label'],
            'Render: '.$component['rendered_label'],
        ];

        foreach (array_slice($component['state_changes'], 0, 10) as $change) {
            $lines[] = $change['path'].': '.$change['before_display'].' -> '.$change['after_display'];
        }

        if ($component['validation_fields'] !== []) {
            $lines[] = 'Validation fields: '.implode(', ', $component['validation_fields']);
        }

        foreach (array_slice($component['server_work'], 0, 4) as $work) {
            $lines[] = sprintf('%s: %s ms', $work['label'], $this->number($work['duration_ms']));
        }

        if ($component['redirect'] !== null) {
            $lines[] = 'Redirect: '.$component['redirect'];
        }

        if (($component['download']['name'] ?? null) !== null) {
            $lines[] = 'Download: '.$component['download']['name'];
        }

        foreach ([
            'Source' => $component['source_label'],
            'Class' => $component['class'],
            'View' => $component['view_label'],
            'Livewire name' => $component['raw_name'],
            'Instance' => $component['id'],
        ] as $label => $value) {
            if (is_string($value) && $value !== '') {
                $lines[] = $label.': '.$value;
            }
        }

        return implode("\n", $lines);
    }

    /** @param array<string, mixed> $event */
    private function eventCopy(array $event): string
    {
        return implode("\n", [
            '#'.$event['sequence'].' '.$event['display_name'],
            'Event name: '.$event['name'],
            'Dispatch mode: '.$event['mode_label'],
            'Source: '.($event['source_name'] ?? 'Not observed'),
            'Declared target: '.$event['declared_target_label'],
            'Observed recipients: '.$event['recipient_label'],
            'Payload: '.$event['parameters_json'],
        ]);
    }

    /** @param list<array<string, mixed>> $spans @return list<array<string, mixed>> */
    private function importantServerWork(array $spans): array
    {
        usort($spans, fn (array $left, array $right): int => $right['duration_ms'] <=> $left['duration_ms']
            ?: $left['start_ms'] <=> $right['start_ms']);

        return array_slice($spans, 0, 4);
    }

    /** @param list<array<string, mixed>> $messages @return list<string> */
    private function validationFields(array $messages): array
    {
        $fields = [];

        foreach ($messages as $message) {
            foreach ((array) ($message['validation_fields'] ?? []) as $field) {
                if (is_string($field)) {
                    $fields[] = $field;
                }
            }
        }

        return array_values(array_unique($fields));
    }

    /** @param array<string, mixed> $completeness @return list<array{title: string, detail: string, tone: string}> */
    private function notices(string $traceStatus, array $completeness): array
    {
        $notices = [];

        if ($traceStatus !== 'complete') {
            $notices[] = [
                'title' => $traceStatus === 'missing' ? 'Browser evidence is missing' : 'Browser evidence is partial',
                'detail' => $traceStatus === 'missing'
                    ? 'Server facts are available. Repeat the interaction with this page open to collect browser evidence.'
                    : 'Some browser callbacks could not be matched. Unmatched details stay unknown.',
                'tone' => 'neutral',
            ];
        }

        if (($completeness['server_spans'] ?? null) === 'unknown') {
            $reasons = array_values(array_filter((array) ($completeness['unknown_reasons'] ?? []), 'is_string'));
            $notices[] = [
                'title' => 'Server timing is unavailable',
                'detail' => $reasons[0] ?? 'The request is still recorded without internal server timing.',
                'tone' => 'neutral',
            ];
        }

        if (($completeness['truncated'] ?? false) === true) {
            $notices[] = [
                'title' => 'Some evidence was truncated',
                'detail' => 'The visible rows are the retained bounded evidence. Omitted values were not stored.',
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
            $directive !== null && $element !== null => $directive.' on '.$element,
            $directive !== null => $directive,
            $element !== null => $element,
            default => null,
        };
    }

    /** @param list<array<string, mixed>> $items @return list<array<string, mixed>> */
    private function matching(array $items, string $key, string $value): array
    {
        return array_values(array_filter($items, fn (array $item): bool => ($item[$key] ?? null) === $value));
    }

    /** @param array<string, array<string, string>> $identities */
    private function identityName(array $identities, mixed $id): string
    {
        return is_string($id) && isset($identities[$id])
            ? $identities[$id]['display_name']
            : 'Unknown component';
    }

    /** @param array<string, array<string, string>> $identities */
    private function identityNameOrNull(array $identities, mixed $id): ?string
    {
        return is_string($id) && isset($identities[$id])
            ? $identities[$id]['display_name']
            : null;
    }

    /** @param list<string> $paths */
    private function actionName(string $kind, ?string $name, array $paths): string
    {
        if ($kind === 'property_update') {
            return count($paths) === 1 ? $this->humanize($paths[0]).' changed' : 'Properties changed';
        }

        if ($kind === 'event') {
            return 'Event received';
        }

        if ($name !== null && ! str_starts_with($name, '$')) {
            return $this->humanize($name);
        }

        return match ($kind) {
            'initial_mount' => 'Mounted',
            'poll' => 'Polled',
            'refresh' => 'Refreshed',
            'action' => 'Action ran',
            default => 'Trigger not observed',
        };
    }

    /** @param list<array<string, mixed>> $actions */
    private function preferredTrigger(array $actions): string
    {
        if ($actions === []) {
            return 'Trigger not observed';
        }

        $propertyActions = array_values(array_filter(
            $actions,
            fn (array $action): bool => ($action['kind'] ?? null) === 'property_update',
        ));
        $propertyLabels = array_values(array_unique(array_column($propertyActions, 'display_name')));

        if (count($propertyLabels) === 1) {
            return $propertyLabels[0];
        }

        $labels = array_values(array_unique(array_column($actions, 'display_name')));

        return count($labels) === 1 ? $labels[0] : 'Multiple triggers';
    }

    private function resultLabel(string $result): string
    {
        return match ($result) {
            'rendered' => 'Rendered',
            'renderless', 'skipped' => 'Finished without rendering',
            'validation_failed' => 'Validation failed',
            'redirected' => 'Redirected',
            'downloaded' => 'Downloaded',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            'mixed' => 'Mixed result',
            default => 'Result not observed',
        };
    }

    private function eventModeLabel(string $mode): string
    {
        return match ($mode) {
            'global' => 'All components',
            'self' => 'Source only',
            'targeted', 'to' => 'Named target',
            'received' => 'Received',
            default => 'Mode not observed',
        };
    }

    private function meaningfulComponentSegment(string $name): string
    {
        $segments = preg_split('/[.:\/\\\\]+/', $name) ?: [];
        $segments = array_values(array_filter($segments, fn (string $segment): bool => $segment !== ''));

        return (string) ($segments[array_key_last($segments)] ?? $name);
    }

    private function componentReferenceName(mixed $reference): string
    {
        if (! is_scalar($reference)) {
            return 'Declared target';
        }

        return $this->humanize($this->meaningfulComponentSegment((string) $reference));
    }

    private function humanize(string $value): string
    {
        $value = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $value) ?? $value;
        $value = preg_replace('/[^A-Za-z0-9]+/', ' ', $value) ?? $value;
        $value = trim($value);

        return $value === '' ? 'Unknown' : (string) str($value)->headline();
    }

    private function traceStatus(mixed $status): string
    {
        return in_array($status, ['complete', 'partial', 'missing', 'expired'], true)
            ? (string) $status
            : 'missing';
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
            return $value === '' ? 'empty' : (string) $value;
        }

        return $this->json($value);
    }

    private function json(mixed $value): string
    {
        $encoded = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($encoded) ? $encoded : '[unavailable]';
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, $value < 10 ? 2 : 1, '.', ''), '0'), '.');
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
