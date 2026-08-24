<?php

namespace NewDebugBar\Analysis;

/** Adds deterministic summaries and groups to existing Laravel sections. */
final class SectionAnalyzer
{
    /** @param array<string, mixed> $profile @return array<string, mixed> */
    public function analyze(array $profile): array
    {
        $profile = $this->models($profile);
        $profile = $this->views($profile);

        return $this->events($profile);
    }

    /** @param array<string, mixed> $profile @return array<string, mixed> */
    private function models(array $profile): array
    {
        $items = $this->items($profile, 'models');
        $groups = [];
        $events = [];
        $modelGroups = [];
        $changeEvents = ['created', 'updated', 'deleted', 'restored', 'forceDeleted', 'trashed'];

        foreach ($items as $item) {
            $key = ($item['model'] ?? 'Unknown').'::'.($item['event'] ?? 'unknown');
            $groups[$key] ??= [
                'model' => $item['model'] ?? 'Unknown',
                'event' => $item['event'] ?? 'unknown',
                'count' => 0,
                'items' => [],
            ];
            $groups[$key]['count']++;
            $groups[$key]['items'][] = $item;
            $event = (string) ($item['event'] ?? 'unknown');
            $events[$event] = ($events[$event] ?? 0) + 1;

            $model = (string) ($item['model'] ?? 'Unknown');
            $modelGroups[$model] ??= [
                'model' => $model,
                'connection' => $item['connection'] ?? null,
                'table' => $item['table'] ?? null,
                'load_count' => 0,
                'record_count' => 0,
                'unidentified_load_count' => 0,
                'repeated_load_count' => 0,
                'change_count' => 0,
                'change_events' => [],
                'total_count' => 0,
                'first_seen_ms' => null,
                'last_seen_ms' => null,
                'records' => [],
                'items' => [],
            ];
            $modelGroups[$model]['items'][] = $item;
            $modelGroups[$model]['total_count']++;
            $this->addModelTiming($modelGroups[$model], $item);

            if ($event === 'retrieved') {
                $modelGroups[$model]['load_count']++;

                if (($item['key'] ?? null) === null) {
                    $modelGroups[$model]['unidentified_load_count']++;

                    continue;
                }

                $recordKey = get_debug_type($item['key']).':'.(string) $item['key'];
                $modelGroups[$model]['records'][$recordKey] ??= [
                    'key' => $item['key'],
                    'loads' => 0,
                    'first_seen_ms' => null,
                    'last_seen_ms' => null,
                    'items' => [],
                ];
                $record = &$modelGroups[$model]['records'][$recordKey];
                $record['loads']++;
                $record['items'][] = $item;
                $this->addModelTiming($record, $item);
                unset($record);
            }

            if (in_array($event, $changeEvents, true)) {
                $modelGroups[$model]['change_count']++;
                $modelGroups[$model]['change_events'][$event] = ($modelGroups[$model]['change_events'][$event] ?? 0) + 1;
            }
        }

        $groups = array_values($groups);
        usort($groups, fn (array $left, array $right): int => $right['count'] <=> $left['count']
            ?: strcasecmp((string) $left['model'], (string) $right['model'])
            ?: strcasecmp((string) $left['event'], (string) $right['event']));

        foreach ($modelGroups as &$modelGroup) {
            $modelGroup['records'] = array_values($modelGroup['records']);
            $modelGroup['record_count'] = count($modelGroup['records']);
            $modelGroup['repeated_load_count'] = array_sum(array_map(
                fn (array $record): int => max(0, $record['loads'] - 1),
                $modelGroup['records'],
            ));
            usort($modelGroup['records'], fn (array $left, array $right): int => $right['loads'] <=> $left['loads']
                ?: ($left['first_seen_ms'] ?? PHP_FLOAT_MAX) <=> ($right['first_seen_ms'] ?? PHP_FLOAT_MAX)
                ?: strnatcasecmp((string) $left['key'], (string) $right['key']));
        }
        unset($modelGroup);

        $modelGroups = array_values($modelGroups);
        usort($modelGroups, fn (array $left, array $right): int => ($right['change_count'] > 0) <=> ($left['change_count'] > 0)
            ?: $right['change_count'] <=> $left['change_count']
            ?: $right['repeated_load_count'] <=> $left['repeated_load_count']
            ?: $right['load_count'] <=> $left['load_count']
            ?: $right['total_count'] <=> $left['total_count']
            ?: strcasecmp((string) $left['model'], (string) $right['model']));

        if (isset($profile['sections']['models'])) {
            $profile['sections']['models']['summary']['model_classes'] = count(array_unique(array_column($items, 'model')));
            $profile['sections']['models']['summary']['lifecycle_events'] = $events;
            $profile['sections']['models']['summary']['retrieval_count'] = array_sum(array_column($modelGroups, 'load_count'));
            $profile['sections']['models']['summary']['distinct_record_count'] = array_sum(array_column($modelGroups, 'record_count'));
            $profile['sections']['models']['summary']['unidentified_load_count'] = array_sum(array_column($modelGroups, 'unidentified_load_count'));
            $profile['sections']['models']['summary']['repeated_load_count'] = array_sum(array_column($modelGroups, 'repeated_load_count'));
            $profile['sections']['models']['summary']['model_change_count'] = array_sum(array_column($modelGroups, 'change_count'));
            $profile['sections']['models']['summary']['model_change_events'] = array_reduce(
                $modelGroups,
                function (array $events, array $group): array {
                    foreach ($group['change_events'] as $event => $count) {
                        $events[$event] = ($events[$event] ?? 0) + $count;
                    }

                    return $events;
                },
                [],
            );
            $profile['sections']['models']['payload']['groups'] = $groups;
            $profile['sections']['models']['payload']['model_groups'] = $modelGroups;
        }

        return $profile;
    }

    /** @param array<string, mixed> $target @param array<string, mixed> $item */
    private function addModelTiming(array &$target, array $item): void
    {
        if (! isset($item['at_ms']) || ! is_numeric($item['at_ms'])) {
            return;
        }

        $at = round((float) $item['at_ms'], 3);
        $target['first_seen_ms'] = $target['first_seen_ms'] === null ? $at : min($target['first_seen_ms'], $at);
        $target['last_seen_ms'] = $target['last_seen_ms'] === null ? $at : max($target['last_seen_ms'], $at);
    }

    /** @param array<string, mixed> $profile @return array<string, mixed> */
    private function views(array $profile): array
    {
        $items = $this->items($profile, 'views');
        $groups = [];

        foreach ($items as $index => $item) {
            $item['render_order'] = $index + 1;

            if (isset($profile['sections']['views']['payload']['items'][$index])) {
                $profile['sections']['views']['payload']['items'][$index]['render_order'] = $index + 1;
            }

            $name = (string) ($item['name'] ?? 'unknown');
            $groups[$name] ??= ['name' => $name, 'count' => 0, 'items' => []];
            $groups[$name]['count']++;
            $groups[$name]['items'][] = $item;
        }

        if (isset($profile['sections']['views'])) {
            $profile['sections']['views']['summary']['unique_views'] = count($groups);
            $profile['sections']['views']['payload']['groups'] = array_values($groups);
        }

        return $profile;
    }

    /** @param array<string, mixed> $profile @return array<string, mixed> */
    private function events(array $profile): array
    {
        if (! isset($profile['sections']['events']['payload']['items'])) {
            return $profile;
        }

        $items = $this->items($profile, 'events');
        $groups = [];
        $sourceCounts = ['application' => 0, 'framework' => 0];

        foreach ($items as $index => $item) {
            $item = $this->normalizeEvent($item, $index + 1);
            $profile['sections']['events']['payload']['items'][$index] = $item;
            $sourceCounts[$item['source']]++;
            $signature = $this->eventSignature($item);

            if (! isset($groups[$signature])) {
                $groups[$signature] = [
                    ...$item,
                    'id' => $item['sequence'],
                    'occurrence_count' => 0,
                    'first_sequence' => $item['sequence'],
                    'last_sequence' => $item['sequence'],
                    'first_at_ms' => null,
                    'last_at_ms' => null,
                    'span_ms' => 0.0,
                    'occurrences' => [],
                    'dispatch_sources' => [],
                ];
            }

            $group = &$groups[$signature];
            $group['occurrence_count']++;
            $group['last_sequence'] = $item['sequence'];
            $group['occurrences'][] = [
                'sequence' => $item['sequence'],
                'at_ms' => $item['at_ms'],
                'lifecycle' => $item['lifecycle'] ?? null,
                'after_response_ms' => isset($item['after_response_ms']) && is_numeric($item['after_response_ms'])
                    ? round((float) $item['after_response_ms'], 3)
                    : null,
                'callsite' => $item['callsite'],
            ];
            $this->addEventTiming($group, $item);
            $this->addEventDispatchSource($group, $item);
            unset($group);
        }

        foreach ($groups as &$group) {
            $group['dispatch_sources'] = array_values($group['dispatch_sources']);
            $group['span_ms'] = $group['first_at_ms'] !== null && $group['last_at_ms'] !== null
                ? round($group['last_at_ms'] - $group['first_at_ms'], 3)
                : 0.0;
            $group['search'] = $this->eventSearchText($group);
            $group['next_step'] = $this->eventNextStep($group);
            $group['related_section'] = $this->relatedEventSection($group['name']);
        }
        unset($group);

        $groups = array_values($groups);

        $profile['sections']['events']['summary'] = [
            ...($profile['sections']['events']['summary'] ?? []),
            'application_count' => $sourceCounts['application'],
            'framework_count' => $sourceCounts['framework'],
            'group_count' => count($groups),
            'application_group_count' => count(array_filter(
                $groups,
                fn (array $group): bool => $group['source'] === 'application',
            )),
            'framework_group_count' => count(array_filter(
                $groups,
                fn (array $group): bool => $group['source'] === 'framework',
            )),
        ];
        $profile['sections']['events']['payload']['groups'] = $groups;

        return $profile;
    }

    /** @param array<string, mixed> $item @return array<string, mixed> */
    private function normalizeEvent(array $item, int $sequence): array
    {
        $name = trim((string) ($item['name'] ?? ''));
        $name = $name === '' ? 'Unknown event' : $name;
        $separator = strrpos($name, '\\');
        $listeners = array_values(array_filter(
            is_array($item['listeners'] ?? null) ? $item['listeners'] : [],
            'is_array',
        ));

        foreach ($listeners as &$listener) {
            $listener['name'] = trim((string) ($listener['name'] ?? 'Listener')) ?: 'Listener';
            $listener['registrations'] = max(1, (int) ($listener['registrations'] ?? 1));
            $listener['queued'] = (bool) ($listener['queued'] ?? false);
            $listener['outcome'] = $listener['queued'] ? 'queued' : 'completed';
            $listener['source'] = is_array($listener['source'] ?? null) ? $listener['source'] : null;
        }
        unset($listener);

        $listenerCount = array_sum(array_column($listeners, 'registrations'));
        $queuedCount = array_sum(array_map(
            fn (array $listener): int => $listener['queued'] ? $listener['registrations'] : 0,
            $listeners,
        ));
        $completedCount = $listenerCount - $queuedCount;
        $payloadShape = $this->eventPayloadShape($item);
        $callsite = is_array($item['callsite'] ?? null) ? $item['callsite'] : null;

        return [
            ...$item,
            'name' => $name,
            'display_name' => $separator === false ? $name : substr($name, $separator + 1),
            'namespace' => $separator === false ? null : substr($name, 0, $separator),
            'sequence' => $sequence,
            'source' => preg_match('/^(Illuminate|Laravel|Livewire|Symfony)\\\\/', $name) === 1
                ? 'framework'
                : 'application',
            'listeners' => $listeners,
            'listener_count' => $listenerCount,
            'listener_group_count' => count($listeners),
            'queued_listener_count' => $queuedCount,
            'completed_listener_count' => $completedCount,
            'duplicate_registration_count' => array_sum(array_map(
                fn (array $listener): int => max(0, $listener['registrations'] - 1),
                $listeners,
            )),
            'listener_outcome' => match (true) {
                $listenerCount === 0 => 'observed',
                $queuedCount === $listenerCount => 'queued',
                $queuedCount > 0 => 'mixed',
                default => 'completed',
            },
            'listener_outcome_label' => match (true) {
                $listenerCount === 0 => 'Observed',
                $queuedCount === $listenerCount => 'Queued',
                $queuedCount > 0 => 'Completed and queued',
                default => 'Completed',
            },
            'listener_summary' => $this->eventListenerSummary($completedCount, $queuedCount),
            'payload_shape' => $payloadShape,
            'payload_field_count' => array_sum(array_column($payloadShape, 'field_count')),
            'callsite' => $callsite,
            'stack' => array_values(array_filter(
                is_array($item['stack'] ?? null) ? $item['stack'] : [],
                'is_array',
            )),
            'at_ms' => isset($item['at_ms']) && is_numeric($item['at_ms'])
                ? round((float) $item['at_ms'], 3)
                : null,
        ];
    }

    /** @param array<string, mixed> $item @return list<array<string, mixed>> */
    private function eventPayloadShape(array $item): array
    {
        $shape = array_values(array_filter(
            is_array($item['payload_shape'] ?? null) ? $item['payload_shape'] : [],
            'is_array',
        ));

        if ($shape === []) {
            $types = array_values(array_filter(
                is_array($item['payload_types'] ?? null) ? $item['payload_types'] : [],
                'is_string',
            ));
            $shape = array_map(
                fn (string $type, int $index): array => [
                    'position' => $index + 1,
                    'type' => $type,
                    'fields' => [],
                    'field_count' => 0,
                    'truncated' => false,
                ],
                $types,
                array_keys($types),
            );
        }

        return array_map(function (array $entry, int $index): array {
            $fields = array_values(array_map(
                'strval',
                array_slice(is_array($entry['fields'] ?? null) ? $entry['fields'] : [], 0, 25),
            ));

            return [
                'position' => max(1, (int) ($entry['position'] ?? $index + 1)),
                'type' => trim((string) ($entry['type'] ?? 'mixed')) ?: 'mixed',
                'fields' => $fields,
                'field_count' => max(count($fields), (int) ($entry['field_count'] ?? count($fields))),
                'truncated' => (bool) ($entry['truncated'] ?? false),
            ];
        }, array_slice($shape, 0, 10), array_keys(array_slice($shape, 0, 10)));
    }

    private function eventListenerSummary(int $completed, int $queued): string
    {
        if ($completed === 0 && $queued === 0) {
            return 'No application listener was registered.';
        }

        if ($queued === 0) {
            return $completed.' listener '.($completed === 1 ? 'registration completed.' : 'registrations completed.');
        }

        if ($completed === 0) {
            return $queued.' queued listener '.($queued === 1 ? 'registration was handed off.' : 'registrations were handed off.');
        }

        return $completed.' completed, '.$queued.' queued.';
    }

    /** @param array<string, mixed> $item */
    private function eventSignature(array $item): string
    {
        $signature = json_encode([
            $item['name'],
            $item['source'],
            $item['broadcast'] ?? false,
            array_map(fn (array $listener): array => [
                $listener['name'],
                $listener['registrations'],
                $listener['queued'],
            ], $item['listeners']),
            array_map(fn (array $entry): array => [
                $entry['type'],
                $entry['fields'],
                $entry['field_count'],
            ], $item['payload_shape']),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash('sha256', is_string($signature) ? $signature : $item['name']);
    }

    /** @param array<string, mixed> $group @param array<string, mixed> $item */
    private function addEventTiming(array &$group, array $item): void
    {
        if ($item['at_ms'] === null) {
            return;
        }

        $group['first_at_ms'] = $group['first_at_ms'] === null
            ? $item['at_ms']
            : min($group['first_at_ms'], $item['at_ms']);
        $group['last_at_ms'] = $group['last_at_ms'] === null
            ? $item['at_ms']
            : max($group['last_at_ms'], $item['at_ms']);
    }

    /** @param array<string, mixed> $group @param array<string, mixed> $item */
    private function addEventDispatchSource(array &$group, array $item): void
    {
        if ($item['callsite'] === null) {
            return;
        }

        $file = (string) ($item['callsite']['file'] ?? '');
        $line = (int) ($item['callsite']['line'] ?? 0);

        if ($file === '' || $line < 1) {
            return;
        }

        $key = $file.':'.$line;
        $group['dispatch_sources'][$key] ??= [
            'file' => $file,
            'line' => $line,
            'count' => 0,
            'sequences' => [],
        ];
        $group['dispatch_sources'][$key]['count']++;
        $group['dispatch_sources'][$key]['sequences'][] = $item['sequence'];
    }

    /** @param array<string, mixed> $group */
    private function eventSearchText(array $group): string
    {
        $parts = [
            $group['name'],
            $group['display_name'],
            $group['namespace'],
            $group['source'],
            $group['listener_outcome_label'],
            ...array_column($group['listeners'], 'name'),
            ...array_column($group['dispatch_sources'], 'file'),
        ];

        foreach ($group['payload_shape'] as $entry) {
            $parts[] = $entry['type'];
            array_push($parts, ...$entry['fields']);
        }

        return mb_strtolower(implode(' ', array_filter($parts, fn (mixed $part): bool => is_scalar($part))));
    }

    /** @param array<string, mixed> $group */
    private function eventNextStep(array $group): string
    {
        if ($group['duplicate_registration_count'] > 0) {
            return 'The same listener is registered more than once. Check explicit registration and event discovery.';
        }

        if ($group['queued_listener_count'] > 0) {
            return 'Open Queue to confirm the worker ran each queued listener.';
        }

        if (($group['broadcast'] ?? false) === true) {
            return 'Check the broadcast channel and frontend subscription if connected clients did not update.';
        }

        if (count($group['dispatch_sources']) > 1) {
            return 'Compare the dispatch sources, then inspect the registered listeners.';
        }

        if ($group['source'] === 'application' && $group['dispatch_sources'] !== []) {
            return 'Start at the dispatch source, then inspect each registered listener.';
        }

        if ($group['listener_count'] === 0) {
            return $group['source'] === 'framework'
                ? 'Use the related collector for deeper evidence when this framework event looks unexpected.'
                : 'Confirm whether this event is observation-only or missing an application listener.';
        }

        return 'Inspect the listener source when the observed result does not match the event.';
    }

    /** @return array{key: string, label: string}|null */
    private function relatedEventSection(string $name): ?array
    {
        foreach ([
            'Illuminate\\Database\\' => ['queries', 'Queries'],
            'Illuminate\\Cache\\' => ['cache', 'Cache'],
            'Illuminate\\Queue\\' => ['queue', 'Queue'],
            'Illuminate\\Mail\\' => ['mail', 'Mail'],
            'Illuminate\\Notifications\\' => ['notifications', 'Notifications'],
            'Illuminate\\Http\\Client\\' => ['http_client', 'HTTP Client'],
            'Illuminate\\Auth\\Access\\' => ['authorization', 'Authorization'],
        ] as $prefix => [$key, $label]) {
            if (str_starts_with($name, $prefix)) {
                return ['key' => $key, 'label' => $label];
            }
        }

        return null;
    }

    /** @param array<string, mixed> $profile @return list<array<string, mixed>> */
    private function items(array $profile, string $section): array
    {
        $items = $profile['sections'][$section]['payload']['items'] ?? [];

        return is_array($items) ? array_values(array_filter($items, 'is_array')) : [];
    }
}
