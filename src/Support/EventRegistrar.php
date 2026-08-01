<?php

namespace NewDebugBar\Support;

use Illuminate\Cache\Events\CacheEvent;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Log\Events\MessageLogged;
use NewDebugBar\ProfileManager;
use Throwable;

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
        private readonly ProfileManager $manager,
    ) {}

    public function register(): void
    {
        $this->events->listen(QueryExecuted::class, function (QueryExecuted $event): void {
            $this->manager->record('queries', [
                'sql' => $event->sql,
                'bindings' => $event->bindings,
                'duration_ms' => round((float) $event->time, 2),
                'connection' => $event->connectionName,
                'type' => $event->readWriteType,
            ]);
        });

        $this->events->listen('eloquent.*', function (string $name, array $payload): void {
            $model = $payload[0] ?? null;

            if (! $model instanceof Model) {
                return;
            }

            $this->manager->record('models', [
                'event' => str($name)->between('eloquent.', ':')->toString(),
                'model' => $model::class,
                'connection' => $model->getConnectionName(),
                'table' => $model->getTable(),
                'key' => $model->getKey(),
            ]);
        });

        $this->listenForCacheEvent(CacheHit::class, 'hit');
        $this->listenForCacheEvent(CacheMissed::class, 'miss');
        $this->listenForCacheEvent(KeyWritten::class, 'write');
        $this->listenForCacheEvent(KeyForgotten::class, 'forget');

        $this->events->listen('composing: *', function (string $name): void {
            $this->manager->record('views', [
                'name' => str($name)->after('composing: ')->toString(),
            ]);
        });

        $this->events->listen(MessageLogged::class, function (MessageLogged $event): void {
            $this->manager->record('logs', [
                'level' => $event->level,
                'message' => $event->message,
                'context' => $event->context,
            ]);

            $exception = $event->context['exception'] ?? null;

            if ($exception instanceof Throwable) {
                $this->manager->recordException($exception);
            }
        });

        $this->events->listen('*', function (string $name, array $payload): void {
            if ($this->shouldIgnoreGeneralEvent($name)) {
                return;
            }

            $this->manager->record('events', [
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
        $this->events->listen($eventClass, function (CacheEvent $event) use ($operation): void {
            $this->manager->record('cache', [
                'operation' => $operation,
                'key' => $event->key,
                'store' => $event->storeName,
                'tags' => $event->tags,
                'seconds' => $event instanceof KeyWritten ? $event->seconds : null,
            ]);
        });
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
