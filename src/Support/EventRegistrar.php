<?php

namespace NewDebugBar\Support;

use Closure;
use Illuminate\Cache\Events\CacheEvent;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Log\Events\MessageLogged;
use NewDebugBar\ProfileManager;
use Throwable;

/** Routes Laravel runtime events into their matching request collectors. */
final class EventRegistrar
{
    /** @var list<class-string> */
    private const EXCLUDED_GENERAL_EVENTS = [
        QueryExecuted::class,
        CacheHit::class,
        CacheMissed::class,
        KeyWritten::class,
        KeyForgotten::class,
        MessageLogged::class,
    ];

    public function __construct(
        private readonly Dispatcher $events,
        private readonly Container $container,
        private readonly CallSiteResolver $callSites,
    ) {}

    public function register(): void
    {
        $this->listen(QueryExecuted::class, function (QueryExecuted $event): void {
            $location = $this->callSites->capture();

            $this->manager()->record('queries', [
                'sql' => $event->sql,
                'bindings' => $event->bindings,
                'duration_ms' => round((float) $event->time, 2),
                'connection' => $event->connectionName,
                'type' => $event->readWriteType,
                ...$location,
            ]);
        });

        $this->listen('eloquent.*', function (string $name, array $payload): void {
            $model = $payload[0] ?? null;

            if (! $model instanceof Model) {
                return;
            }

            $this->manager()->record('models', [
                'event' => str($name)->between('eloquent.', ':')->toString(),
                'model' => $model::class,
                'connection' => $model->getConnectionName(),
                'table' => $model->getTable(),
                'key' => $this->modelKey($model),
            ]);
        });

        $this->listenForCacheEvent(CacheHit::class, 'hit');
        $this->listenForCacheEvent(CacheMissed::class, 'miss');
        $this->listenForCacheEvent(KeyWritten::class, 'write');
        $this->listenForCacheEvent(KeyForgotten::class, 'forget');

        $this->listen('composing: *', function (string $name, array $payload): void {
            $view = $payload[0] ?? null;

            $this->manager()->record('views', [
                'name' => str($name)->after('composing: ')->toString(),
                'data_keys' => is_object($view) && method_exists($view, 'getData')
                    ? array_keys($view->getData())
                    : [],
            ]);
        });

        $this->listen(MessageLogged::class, function (MessageLogged $event): void {
            $location = $this->callSites->capture();

            $this->manager()->record('logs', [
                'level' => $event->level,
                'message' => $event->message,
                'context' => $event->context,
                ...$location,
            ]);

            $exception = $event->context['exception'] ?? null;

            if ($exception instanceof Throwable) {
                $this->manager()->recordException($exception);
            }
        });

        $this->listen('*', function (string $name, array $payload): void {
            if ($this->shouldIgnoreGeneralEvent($name)) {
                return;
            }

            $this->manager()->record('events', [
                'name' => $name,
                'payload_types' => array_map(
                    fn (mixed $item): string => is_object($item) ? $item::class : get_debug_type($item),
                    $payload,
                ),
            ]);
        });
    }

    /** @param class-string<CacheEvent> $eventClass */
    private function listenForCacheEvent(string $eventClass, string $operation): void
    {
        $this->listen($eventClass, function (CacheEvent $event) use ($operation): void {
            $this->manager()->record('cache', [
                'operation' => $operation,
                'key_hash' => substr(hash('sha256', $event->key), 0, 16),
                'store' => $event->storeName,
                'tags' => $event->tags,
                'seconds' => $event instanceof KeyWritten ? $event->seconds : null,
            ]);
        });
    }

    /** @param class-string|string $event */
    private function listen(string $event, Closure $listener): void
    {
        $this->events->listen($event, function (...$arguments) use ($listener): void {
            try {
                $listener(...$arguments);
            } catch (Throwable) {
                // Debug collection must never interrupt the host application.
            }
        });
    }

    private function modelKey(Model $model): mixed
    {
        $attributes = $model->getAttributes();
        $key = $model->getKeyName();

        return array_key_exists($key, $attributes) ? $attributes[$key] : null;
    }

    private function manager(): ProfileManager
    {
        return $this->container->make(ProfileManager::class);
    }

    private function shouldIgnoreGeneralEvent(string $name): bool
    {
        if (in_array($name, self::EXCLUDED_GENERAL_EVENTS, true)) {
            return true;
        }

        return str_starts_with($name, 'eloquent.')
            || str_starts_with($name, 'composing: ')
            || str_starts_with($name, 'creating: ')
            || str_starts_with($name, 'bootstrapped: ')
            || str_starts_with($name, 'bootstrapping: ');
    }
}
