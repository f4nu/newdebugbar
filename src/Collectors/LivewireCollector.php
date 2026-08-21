<?php

namespace NewDebugBar\Collectors;

use NewDebugBar\Contracts\Collector;
use NewDebugBar\Support\Redactor;

/** Stores bounded Livewire component descriptors and server activity. */
final class LivewireCollector implements Collector
{
    private const MAX_COMPONENTS = 200;

    private const MAX_ACTIVITY = 500;

    /** @var array<string, array<string, mixed>> */
    private array $components = [];

    /** @var list<array<string, mixed>> */
    private array $activity = [];

    private int $droppedComponents = 0;

    private int $droppedActivity = 0;

    public function __construct(
        private readonly Redactor $redactor,
        private readonly int $maxComponents = self::MAX_COMPONENTS,
        private readonly int $maxActivity = self::MAX_ACTIVITY,
    ) {}

    public function key(): string
    {
        return 'livewire';
    }

    public function label(): string
    {
        return 'Livewire';
    }

    public function reset(): void
    {
        $this->components = [];
        $this->activity = [];
        $this->droppedComponents = 0;
        $this->droppedActivity = 0;
    }

    public function record(array $item): void
    {
        $safe = $this->redactor->clean($item);

        if (! is_array($safe)) {
            return;
        }

        if (($safe['kind'] ?? null) === 'component') {
            $this->recordComponent($safe['component'] ?? null);

            return;
        }

        if (($safe['kind'] ?? null) === 'activity') {
            $activity = $safe['activity'] ?? null;

            if (is_array($activity) && isset($safe['at_ms'])) {
                $activity['at_ms'] = $safe['at_ms'];
            }

            $this->recordActivity($activity);
        }
    }

    public function summary(): array
    {
        $componentCount = count($this->components);
        $activityCount = count($this->activity);
        $dropped = $this->droppedComponents + $this->droppedActivity;

        return [
            'count' => $componentCount,
            'component_count' => $componentCount,
            'activity_count' => $activityCount,
            'retained_count' => $componentCount + $activityCount,
            'dropped_count' => $dropped,
            'dropped_component_count' => $this->droppedComponents,
            'dropped_activity_count' => $this->droppedActivity,
            'truncated' => $dropped > 0,
        ];
    }

    public function payload(): array
    {
        return [
            'items' => $this->activity,
            'activity' => $this->activity,
            'components' => array_values($this->components),
            'dropped_counts' => [
                'components' => $this->droppedComponents,
                'activity' => $this->droppedActivity,
            ],
        ];
    }

    private function recordComponent(mixed $component): void
    {
        if (! is_array($component) || ! is_string($component['id'] ?? null) || $component['id'] === '') {
            return;
        }

        $id = $component['id'];

        if (isset($this->components[$id])) {
            $this->components[$id] = array_replace($this->components[$id], $component);

            return;
        }

        if (count($this->components) >= max(0, $this->maxComponents)) {
            $this->droppedComponents++;

            return;
        }

        $this->components[$id] = $component;
    }

    private function recordActivity(mixed $activity): void
    {
        if (! is_array($activity)) {
            return;
        }

        if (count($this->activity) >= max(0, $this->maxActivity)) {
            $this->droppedActivity++;

            return;
        }

        $this->activity[] = $activity;
    }
}
