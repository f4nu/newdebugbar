<?php

namespace NewDebugBar\Analysis;

/** Adds deterministic summaries and groups to existing Laravel sections. */
final class SectionAnalyzer
{
    /** @param array<string, mixed> $profile @return array<string, mixed> */
    public function analyze(array $profile): array
    {
        $profile = $this->models($profile);
        $profile = $this->cache($profile);
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
    private function cache(array $profile): array
    {
        $items = $this->items($profile, 'cache');
        $operations = [];
        $misses = [];

        foreach ($items as $item) {
            $operation = (string) ($item['operation'] ?? 'unknown');
            $operations[$operation] = ($operations[$operation] ?? 0) + 1;

            if ($operation === 'miss' && isset($item['key_hash'])) {
                $misses[$item['key_hash']] = ($misses[$item['key_hash']] ?? 0) + 1;
            }
        }

        $reads = (int) ($operations['hit'] ?? 0) + (int) ($operations['miss'] ?? 0);
        $repeatedMisses = [];

        foreach ($misses as $keyHash => $count) {
            if ($count > 1) {
                $repeatedMisses[] = ['key_hash' => $keyHash, 'count' => $count];
            }
        }

        usort($repeatedMisses, fn (array $left, array $right): int => $right['count'] <=> $left['count']);

        if (isset($profile['sections']['cache'])) {
            $profile['sections']['cache']['summary']['reads'] = $reads;
            $profile['sections']['cache']['summary']['hit_rate'] = $reads > 0
                ? round(((int) ($operations['hit'] ?? 0) / $reads) * 100, 1)
                : 0.0;
            $profile['sections']['cache']['summary']['operations'] = $operations;
            $profile['sections']['cache']['summary']['repeated_miss_count'] = count($repeatedMisses);
            $profile['sections']['cache']['payload']['repeated_misses'] = $repeatedMisses;
        }

        return $profile;
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

        foreach ($profile['sections']['events']['payload']['items'] as $index => $item) {
            $name = (string) ($item['name'] ?? '');
            $profile['sections']['events']['payload']['items'][$index]['source'] = preg_match(
                '/^(Illuminate|Laravel|Livewire|Symfony)\\\\/',
                $name,
            ) === 1 ? 'framework' : 'application';
        }

        return $profile;
    }

    /** @param array<string, mixed> $profile @return list<array<string, mixed>> */
    private function items(array $profile, string $section): array
    {
        $items = $profile['sections'][$section]['payload']['items'] ?? [];

        return is_array($items) ? array_values(array_filter($items, 'is_array')) : [];
    }
}
