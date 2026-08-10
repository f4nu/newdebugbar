<?php

namespace NewDebugBar\Presentation;

use Illuminate\Support\Str;

/** Builds the ranked activity and runtime detail rows used by the Overview. */
final class OverviewPresenter
{
    /** @var array<string, int> */
    private const ACTIVITY_PRIORITY = [
        'exceptions' => 0,
        'validation' => 10,
        'authorization' => 20,
        'queries' => 30,
        'timeline' => 40,
        'http_client' => 50,
        'logs' => 60,
        'events' => 70,
        'lifecycle' => 80,
        'queue' => 100,
        'cache' => 110,
        'redis' => 120,
        'models' => 130,
        'views' => 140,
        'mail' => 150,
        'notifications' => 160,
        'messages' => 170,
    ];

    /**
     * @param  array<string, mixed>  $profile
     * @param  list<array<string, mixed>>  $sectionLinks
     * @return array{activity: list<array<string, mixed>>, runtime: array<string, array<string, mixed>>}
     */
    public function present(array $profile, array $sectionLinks): array
    {
        return [
            'activity' => $this->activity($profile, $sectionLinks),
            'runtime' => $this->runtime($profile['sections']['overview']['payload'] ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  list<array<string, mixed>>  $sectionLinks
     * @return list<array<string, mixed>>
     */
    private function activity(array $profile, array $sectionLinks): array
    {
        $links = array_values(array_filter(
            $sectionLinks,
            fn (mixed $link): bool => is_array($link)
                && ! in_array($link['key'] ?? null, ['overview', 'request', 'history'], true)
                && ($link['count'] ?? null) !== null
                && ($link['active'] ?? true),
        ));

        usort($links, static fn (array $left, array $right): int => ((int) ($right['attention'] ?? false) <=> (int) ($left['attention'] ?? false))
            ?: ((self::ACTIVITY_PRIORITY[$left['key']] ?? 999) <=> (self::ACTIVITY_PRIORITY[$right['key']] ?? 999))
            ?: strcasecmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? '')));

        return array_map(function (array $link) use ($profile): array {
            return [
                'key' => (string) $link['key'],
                'label' => (string) $link['label'],
                'description' => $this->activityDescription($profile, $link),
                'attention' => (bool) ($link['attention'] ?? false),
            ];
        }, array_slice($links, 0, 5));
    }

    /** @param array<string, mixed> $profile @param array<string, mixed> $link */
    private function activityDescription(array $profile, array $link): string
    {
        $key = (string) $link['key'];
        $count = (int) ($link['count'] ?? 0);
        $details = is_array($profile['sections'][$key] ?? null) ? $profile['sections'][$key] : [];
        $summary = is_array($details['summary'] ?? null) ? $details['summary'] : [];
        $payload = is_array($details['payload'] ?? null) ? $details['payload'] : [];
        $items = array_values(array_filter((array) ($payload['items'] ?? []), 'is_array'));

        if ($key === 'queries') {
            $repeatedGroups = array_values(array_filter((array) ($payload['repeated_groups'] ?? []), 'is_array'));
            $repeatCount = (int) ($repeatedGroups[0]['count'] ?? 0);

            if ($repeatCount > 1) {
                return $this->counted($count, 'query', 'queries').', including one pattern repeated '.number_format($repeatCount).' times';
            }

            $duration = rtrim(rtrim(number_format((float) ($summary['total_time_ms'] ?? 0), 2, '.', ''), '0'), '.');

            return $this->counted($count, 'query', 'queries').' in '.$duration.' ms';
        }

        if ($key === 'logs') {
            $errorCount = count(array_filter($items, static fn (array $item): bool => in_array(
                strtolower((string) ($item['level'] ?? '')),
                ['error', 'critical', 'alert', 'emergency'],
                true,
            )));

            return $this->counted($count, 'message').($errorCount > 0
                ? ', '.$this->counted($errorCount, 'error')
                : ', no errors');
        }

        return match ($key) {
            'timeline' => $this->counted($count, 'event').' across the request',
            'http_client' => $this->counted($count, 'outbound request'),
            'events' => $this->counted($count, 'event').' dispatched',
            'lifecycle' => $this->counted($count, 'Laravel lifecycle event'),
            'exceptions' => $this->counted($count, 'exception').' captured',
            'validation' => $this->counted($count, 'validation check'),
            'authorization' => $this->counted($count, 'authorization check'),
            'queue' => $this->counted($count, 'queue event'),
            'cache' => $this->counted($count, 'cache operation'),
            'redis' => $this->counted($count, 'Redis command'),
            'models' => $this->counted($count, 'model event'),
            'views' => $this->counted($count, 'view').' rendered',
            'mail' => $this->counted($count, 'mail event'),
            'notifications' => $this->counted($count, 'notification'),
            default => $this->counted($count, strtolower((string) ($link['label'] ?? 'item'))),
        };
    }

    /** @param array<string, mixed>|mixed $payload @return array<string, array<string, mixed>> */
    private function runtime(mixed $payload): array
    {
        $payload = is_array($payload) ? $payload : [];
        $facts = is_array($payload['runtime'] ?? null)
            ? $payload['runtime']
            : array_filter([
                'environment' => $payload['environment'] ?? null,
                'php' => $payload['php'] ?? null,
                'laravel' => $payload['laravel'] ?? null,
            ]);
        $drivers = is_array($payload['drivers'] ?? null) ? $payload['drivers'] : [];
        $cache = is_array($payload['cache_state'] ?? null) ? $payload['cache_state'] : [];
        $ecosystem = [];

        foreach ((array) ($payload['ecosystem'] ?? []) as $package) {
            if (is_array($package) && is_string($package['label'] ?? null)) {
                $ecosystem[$package['label']] = $package['version'] ?? 'installed';
            }
        }

        return [
            'runtime' => $this->runtimeGroup('Runtime', $facts, static fn (mixed $value): mixed => is_bool($value) ? ($value ? 'On' : 'Off') : $value),
            'drivers' => $this->runtimeGroup('Drivers', $drivers),
            'framework-cache' => $this->runtimeGroup('Framework cache', $cache, static fn (mixed $value): string => $value ? 'Cached' : 'Open'),
            'ecosystem' => $this->runtimeGroup('Ecosystem', $ecosystem),
        ];
    }

    /**
     * @param  array<string, mixed>  $items
     * @return array{label: string, items: list<array{name: string, value: mixed}>, copy: array<string, mixed>}
     */
    private function runtimeGroup(string $label, array $items, ?callable $transform = null): array
    {
        $rows = [];
        $copy = [];

        foreach ($items as $name => $value) {
            $value = $transform === null ? $value : $transform($value);
            $displayName = match ((string) $name) {
                'php' => 'PHP',
                'php_sapi' => 'PHP SAPI',
                'runtime_type' => 'Runtime type',
                'laravel' => 'Laravel',
                default => Str::headline((string) $name),
            };
            $rows[] = ['name' => $displayName, 'value' => $value];
            $copy[(string) $name] = $value;
        }

        return ['label' => $label, 'items' => $rows, 'copy' => $copy];
    }

    private function counted(int $count, string $singular, ?string $plural = null): string
    {
        return number_format($count).' '.($count === 1 ? $singular : ($plural ?? $singular.'s'));
    }
}
