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
        }

        if (isset($profile['sections']['models'])) {
            $profile['sections']['models']['summary']['model_classes'] = count(array_unique(array_column($items, 'model')));
            $profile['sections']['models']['summary']['lifecycle_events'] = $events;
            $profile['sections']['models']['payload']['groups'] = array_values($groups);
        }

        return $profile;
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

        foreach ($items as $item) {
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
