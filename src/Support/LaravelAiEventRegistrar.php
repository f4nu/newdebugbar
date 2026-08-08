<?php

namespace NewDebugBar\Support;

use Closure;
use Countable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use NewDebugBar\ProfileManager;
use Throwable;

/** Adapts optional laravel/ai events without making the SDK a package dependency. */
final class LaravelAiEventRegistrar
{
    /** @var array<string, class-string> */
    private const EVENT_CLASSES = [
        'prompting' => 'Laravel\\Ai\\Events\\PromptingAgent',
        'prompted' => 'Laravel\\Ai\\Events\\AgentPrompted',
        'streaming' => 'Laravel\\Ai\\Events\\StreamingAgent',
        'streamed' => 'Laravel\\Ai\\Events\\AgentStreamed',
        'invoking_tool' => 'Laravel\\Ai\\Events\\InvokingTool',
        'tool_invoked' => 'Laravel\\Ai\\Events\\ToolInvoked',
    ];

    private const MANAGER_CLASS = 'Laravel\\Ai\\AiManager';

    /** @var array<string, class-string> */
    private array $eventClasses;

    /** @var class-string */
    private string $managerClass;

    private int $queuedWorkDepth = 0;

    /**
     * @param  array<string, class-string>|null  $eventClasses
     * @param  class-string|null  $managerClass
     */
    public function __construct(
        private readonly Dispatcher $events,
        private readonly Container $container,
        ?array $eventClasses = null,
        ?string $managerClass = null,
    ) {
        $this->eventClasses = $eventClasses ?? self::EVENT_CLASSES;
        $this->managerClass = $managerClass ?? self::MANAGER_CLASS;
    }

    public static function packageAvailable(): bool
    {
        return self::classesExist(self::MANAGER_CLASS, self::EVENT_CLASSES);
    }

    public function register(): bool
    {
        if (! config('newdebugbar.ai.enabled', true)
            || ! self::classesExist($this->managerClass, $this->eventClasses)) {
            return false;
        }

        $this->listen(JobProcessing::class, function (): void {
            $this->queuedWorkDepth++;
        });
        $this->listen(JobProcessed::class, function (): void {
            $this->queuedWorkDepth = max(0, $this->queuedWorkDepth - 1);
        });
        $this->listen(JobExceptionOccurred::class, function (): void {
            $this->queuedWorkDepth = max(0, $this->queuedWorkDepth - 1);
        });

        $this->listen($this->eventClasses['prompting'], fn (object $event) => $this->startInvocation($event, false));
        $this->listen($this->eventClasses['streaming'], fn (object $event) => $this->startInvocation($event, true));
        $this->listen($this->eventClasses['prompted'], fn (object $event) => $this->finishInvocation($event, false));
        $this->listen($this->eventClasses['streamed'], fn (object $event) => $this->finishInvocation($event, true));
        $this->listen($this->eventClasses['invoking_tool'], fn (object $event) => $this->startTool($event));
        $this->listen($this->eventClasses['tool_invoked'], fn (object $event) => $this->finishTool($event));

        return true;
    }

    private function startInvocation(object $event, bool $streamed): void
    {
        $manager = $this->managerForCurrentContext();
        $invocationId = $this->string($event, 'invocationId');
        $prompt = $this->value($event, 'prompt');

        if ($manager === null || $invocationId === null || ! is_object($prompt)) {
            return;
        }

        $agent = $this->value($prompt, 'agent');
        $item = [
            'agent' => is_object($agent) ? $agent::class : null,
            'model' => $this->string($prompt, 'model'),
            'attachment_count' => $this->count($this->value($prompt, 'attachments')),
            'streamed' => $streamed,
        ];

        if (config('newdebugbar.ai.capture_content', false)) {
            $item['prompt'] = $this->string($prompt, 'prompt');
        }

        $manager->startAiInvocation($invocationId, $item);
    }

    private function finishInvocation(object $event, bool $streamed): void
    {
        $manager = $this->managerForCurrentContext();
        $invocationId = $this->string($event, 'invocationId');
        $prompt = $this->value($event, 'prompt');
        $response = $this->value($event, 'response');

        if ($manager === null || $invocationId === null || ! is_object($response)) {
            return;
        }

        $meta = $this->value($response, 'meta');
        $usage = $this->usage($this->value($response, 'usage'));
        $item = [
            'provider' => is_object($meta) ? $this->string($meta, 'provider') : null,
            'model' => is_object($meta)
                ? ($this->string($meta, 'model') ?? (is_object($prompt) ? $this->string($prompt, 'model') : null))
                : (is_object($prompt) ? $this->string($prompt, 'model') : null),
            'streamed' => $streamed,
            'usage' => $usage,
            'token_count' => (int) ($usage['prompt_tokens'] ?? 0) + (int) ($usage['completion_tokens'] ?? 0),
        ];

        if (config('newdebugbar.ai.capture_content', false)) {
            $item['response'] = $this->string($response, 'text');
        }

        $manager->finishAiInvocation($invocationId, $item);
    }

    private function startTool(object $event): void
    {
        $manager = $this->managerForCurrentContext();
        $invocationId = $this->string($event, 'invocationId');
        $toolInvocationId = $this->string($event, 'toolInvocationId');
        $tool = $this->value($event, 'tool');

        if ($manager === null || $invocationId === null || $toolInvocationId === null || ! is_object($tool)) {
            return;
        }

        $item = [
            'tool' => class_basename($tool),
            'tool_class' => $tool::class,
        ];

        if (config('newdebugbar.ai.capture_content', false)) {
            $item['arguments'] = $this->value($event, 'arguments');
        }

        $manager->startAiTool($invocationId, $toolInvocationId, $item);
    }

    private function finishTool(object $event): void
    {
        $manager = $this->managerForCurrentContext();
        $invocationId = $this->string($event, 'invocationId');
        $toolInvocationId = $this->string($event, 'toolInvocationId');
        $tool = $this->value($event, 'tool');

        if ($manager === null || $invocationId === null || $toolInvocationId === null || ! is_object($tool)) {
            return;
        }

        $item = [
            'tool' => class_basename($tool),
            'tool_class' => $tool::class,
        ];

        if (config('newdebugbar.ai.capture_content', false)) {
            $item['arguments'] = $this->value($event, 'arguments');
            $item['result'] = $this->value($event, 'result');
        }

        $manager->finishAiTool($invocationId, $toolInvocationId, $item);
    }

    private function managerForCurrentContext(): ?ProfileManager
    {
        $manager = $this->container->make(ProfileManager::class);

        if (! $manager->isCollecting()) {
            return null;
        }

        if ($manager->profileType() === 'http' && $this->queuedWorkDepth > 0) {
            return null;
        }

        return $manager;
    }

    /** @param class-string $event */
    private function listen(string $event, Closure $listener): void
    {
        $this->events->listen($event, function (...$arguments) use ($listener): void {
            try {
                $event = $arguments[0] ?? null;

                if (is_object($event)) {
                    $listener($event);
                }
            } catch (Throwable) {
                // Optional AI collection must never change application behavior.
            }
        });
    }

    /** @return array<string, int> */
    private function usage(mixed $usage): array
    {
        $values = [];

        if (is_object($usage) && method_exists($usage, 'toArray')) {
            try {
                $values = $usage->toArray();
            } catch (Throwable) {
                $values = [];
            }
        } elseif (is_array($usage)) {
            $values = $usage;
        }

        $normalized = [];

        foreach ([
            'prompt_tokens' => 'promptTokens',
            'completion_tokens' => 'completionTokens',
            'cache_write_input_tokens' => 'cacheWriteInputTokens',
            'cache_read_input_tokens' => 'cacheReadInputTokens',
            'reasoning_tokens' => 'reasoningTokens',
        ] as $snake => $camel) {
            $value = $values[$snake] ?? $values[$camel] ?? (is_object($usage) ? $this->value($usage, $camel) : null);

            if (is_numeric($value)) {
                $normalized[$snake] = max(0, (int) $value);
            }
        }

        return $normalized;
    }

    private function value(object $object, string $property): mixed
    {
        try {
            return $object->{$property} ?? null;
        } catch (Throwable) {
            return null;
        }
    }

    private function string(object $object, string $property): ?string
    {
        $value = $this->value($object, $property);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function count(mixed $value): int
    {
        return is_array($value) || $value instanceof Countable ? count($value) : 0;
    }

    /** @param class-string $managerClass @param array<string, class-string> $eventClasses */
    private static function classesExist(string $managerClass, array $eventClasses): bool
    {
        if (! class_exists($managerClass)) {
            return false;
        }

        foreach (self::EVENT_CLASSES as $key => $unused) {
            if (! isset($eventClasses[$key]) || ! class_exists($eventClasses[$key])) {
                return false;
            }
        }

        return true;
    }
}
