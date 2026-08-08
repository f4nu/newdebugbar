<?php

namespace NewDebugBar\Support;

use Illuminate\Contracts\Container\Container;
use Livewire\Component;
use NewDebugBar\ProfileManager;

use function Livewire\on;

/** Records application Livewire components rendered during a full-page request. */
final class LivewireMountRecorder
{
    public function __construct(private readonly Container $container) {}

    public function register(): void
    {
        on('mount', function (Component $component, array $params, mixed $key, mixed $parent): ?callable {
            $manager = $this->container->make(ProfileManager::class);

            if (! $manager->isCollecting() || $component->getName() === 'newdebugbar.toolbar') {
                return null;
            }

            $startedAt = hrtime(true);
            $componentName = $component->getName();
            $parentName = $parent instanceof Component ? $parent->getName() : null;

            return function () use ($manager, $startedAt, $componentName, $parentName): void {
                $manager->record('livewire', [
                    'phase' => 'initial',
                    'kind' => 'initial',
                    'component' => $componentName,
                    'parent_component' => $parentName,
                    'actions' => [],
                    'updated_properties' => [],
                    'validation_failure_count' => 0,
                    'validation_fields' => [],
                    'payload_size_bytes' => 0,
                    'response_size_bytes' => 0,
                    'duration_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
                ]);
            };
        });
    }
}
