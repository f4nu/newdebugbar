<?php

namespace NewDebugBar\Support;

use Closure;
use Illuminate\Auth\Access\Events\GateEvaluated;
use Illuminate\Cache\Events\CacheEvent;
use Illuminate\Cache\Events\CacheFlushed;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\Events\JobQueueing;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Redis\Events\CommandFailed;
use Illuminate\Routing\Events\PreparingResponse;
use Illuminate\Routing\Events\ResponsePrepared;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Events\Routing;
use NewDebugBar\ProfileManager;
use ReflectionFunction;
use ReflectionMethod;
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
        RequestSending::class,
        ResponseReceived::class,
        ConnectionFailed::class,
        JobQueueing::class,
        JobQueued::class,
        JobProcessing::class,
        JobProcessed::class,
        JobExceptionOccurred::class,
        MessageSending::class,
        MessageSent::class,
        NotificationSending::class,
        NotificationSent::class,
        NotificationFailed::class,
        CommandExecuted::class,
        CommandFailed::class,
        CacheFlushed::class,
        CommandStarting::class,
        CommandFinished::class,
        GateEvaluated::class,
        TransactionBeginning::class,
        TransactionCommitted::class,
        TransactionRolledBack::class,
        Routing::class,
        RouteMatched::class,
        PreparingResponse::class,
        ResponsePrepared::class,
    ];

    public function __construct(
        private readonly Dispatcher $events,
        private readonly Container $container,
        private readonly CallSiteResolver $callSites,
        private readonly SafeUrl $safeUrl,
        private readonly RuntimeProfiler $runtime,
        private readonly Redactor $redactor,
    ) {}

    public function register(): void
    {
        $this->listen(Routing::class, fn () => $this->manager()->lifecycle('routing'));
        $this->listen(RouteMatched::class, fn () => $this->manager()->lifecycle('route_matched'));
        $this->listen(PreparingResponse::class, fn () => $this->manager()->lifecycle('preparing_response'));
        $this->listen(ResponsePrepared::class, fn () => $this->manager()->lifecycle('response_prepared'));

        $this->listen(GateEvaluated::class, function (GateEvaluated $event): void {
            $this->manager()->record('authorization', [
                'ability' => $event->ability,
                'result' => $event->result === true ? 'allowed' : 'denied',
                'handler' => $this->authorizationHandler($event),
                'user_type' => is_object($event->user) ? $event->user::class : null,
                'argument_types' => array_values(array_map(
                    fn (mixed $argument): string => is_object($argument) ? $argument::class : get_debug_type($argument),
                    $event->arguments,
                )),
            ]);
        });

        $this->listen(CommandStarting::class, function (CommandStarting $event): void {
            if ($this->isLongRunningCommand($event->command)) {
                return;
            }

            $this->runtime->start(
                $event->command === 'test' ? 'test' : 'artisan',
                $event->command,
                [
                    'argument_names' => array_keys($event->input->getArguments()),
                    'option_names' => array_keys($event->input->getOptions()),
                ],
                'command:'.spl_object_id($event->input),
            );
        });

        $this->listen(CommandFinished::class, function (CommandFinished $event): void {
            if (! $this->isLongRunningCommand($event->command)) {
                $this->runtime->finish($event->exitCode, 'command:'.spl_object_id($event->input));
            }
        });

        $this->listen(QueryExecuted::class, function (QueryExecuted $event): void {
            $location = $this->callSites->capture();
            $runnableSql = null;

            if (config('new-debug-bar.collection.query_bindings') === 'full') {
                try {
                    $runnableSql = $event->toRawSql();
                } catch (Throwable) {
                    $runnableSql = null;
                }
            }

            $this->manager()->record('queries', [
                'sql' => $event->sql,
                'bindings' => $event->bindings,
                'runnable_sql' => $runnableSql,
                'duration_ms' => round((float) $event->time, 2),
                'connection' => $event->connectionName,
                'type' => $event->readWriteType,
                ...$location,
            ]);
        });

        $this->listen(TransactionBeginning::class, fn (TransactionBeginning $event) => $this->manager()->recordTransaction([
            'kind' => 'begin',
            'connection' => $event->connectionName,
        ]));
        $this->listen(TransactionCommitted::class, fn (TransactionCommitted $event) => $this->manager()->recordTransaction([
            'kind' => 'commit',
            'connection' => $event->connectionName,
        ]));
        $this->listen(TransactionRolledBack::class, fn (TransactionRolledBack $event) => $this->manager()->recordTransaction([
            'kind' => 'rollback',
            'connection' => $event->connectionName,
        ]));

        $this->listen(RequestSending::class, function (RequestSending $event): void {
            $this->manager()->record('http_client', [
                'phase' => 'sending',
                'request_id' => spl_object_id($event->request),
            ]);
        });

        $this->listen(ResponseReceived::class, function (ResponseReceived $event): void {
            $handlerStats = $event->response->handlerStats();
            $totalTime = $handlerStats['total_time'] ?? null;

            $this->manager()->record('http_client', [
                'phase' => 'completed',
                'request_id' => spl_object_id($event->request),
                'method' => strtoupper($event->request->method()),
                'url' => $this->safeUrl->clean($event->request->url()),
                'status' => $event->response->status(),
                'duration_ms' => is_numeric($totalTime) ? round((float) $totalTime * 1_000, 2) : null,
                'failed' => false,
            ]);
        });

        $this->listen(ConnectionFailed::class, function (ConnectionFailed $event): void {
            $this->manager()->record('http_client', [
                'phase' => 'failed',
                'request_id' => spl_object_id($event->request),
                'method' => strtoupper($event->request->method()),
                'url' => $this->safeUrl->clean($event->request->url()),
                'status' => null,
                'duration_ms' => null,
                'failed' => true,
                'exception_class' => $event->exception::class,
            ]);
        });

        $this->listen(JobQueued::class, function (JobQueued $event): void {
            $this->manager()->record('queue', [
                'kind' => 'queued',
                'job' => $this->jobName($event->job),
                'connection' => $event->connectionName,
                'queue' => $event->queue,
                'job_id' => $event->id,
                'delay_seconds' => $event->delay,
                'duration_ms' => 0.0,
            ]);
        });

        $this->listen(JobProcessing::class, function (JobProcessing $event): void {
            $this->runtime->start('queue', $event->job->resolveName(), [
                'connection' => $event->connectionName,
                'queue' => $event->job->getQueue(),
                'attempt' => $event->job->attempts(),
            ], 'queue:'.spl_object_id($event->job));
            $this->manager()->record('queue', [
                'phase' => 'processing',
                'execution_id' => spl_object_id($event->job),
            ]);
        });

        $this->listen(JobProcessed::class, function (JobProcessed $event): void {
            $this->manager()->record('queue', [
                'phase' => 'processed',
                'execution_id' => spl_object_id($event->job),
                'kind' => 'executed',
                'job' => $event->job->resolveName(),
                'connection' => $event->connectionName,
                'queue' => $event->job->getQueue(),
                'job_id' => $event->job->getJobId() ?: null,
                'attempt' => $event->job->attempts(),
            ]);
            $this->runtime->finish(0, 'queue:'.spl_object_id($event->job));
        });

        $this->listen(JobExceptionOccurred::class, function (JobExceptionOccurred $event): void {
            $this->manager()->record('queue', [
                'phase' => 'failed',
                'execution_id' => spl_object_id($event->job),
                'kind' => 'failed',
                'job' => $event->job->resolveName(),
                'connection' => $event->connectionName,
                'queue' => $event->job->getQueue(),
                'job_id' => $event->job->getJobId() ?: null,
                'attempt' => $event->job->attempts(),
                'exception_class' => $event->exception::class,
            ]);
            $this->runtime->fail($event->exception, 'queue:'.spl_object_id($event->job));
        });

        $this->listen(MessageSending::class, function (MessageSending $event): void {
            $this->manager()->record('mail', [
                'phase' => 'sending',
                'message_id' => spl_object_id($event->message),
            ]);
        });

        $this->listen(MessageSent::class, function (MessageSent $event): void {
            $message = $event->message;
            $source = $event->data['__laravel_mailable'] ?? $event->data['__laravel_notification'] ?? null;

            $this->manager()->record('mail', [
                'phase' => 'sent',
                'message_id' => spl_object_id($message),
                'source' => is_string($source) ? $source : null,
                'recipient_count' => count($message->getTo()) + count($message->getCc()) + count($message->getBcc()),
                'attachment_count' => count($message->getAttachments()),
                'has_html' => $message->getHtmlBody() !== null,
                'has_text' => $message->getTextBody() !== null,
            ]);
        });

        $this->listen(NotificationSent::class, function (NotificationSent $event): void {
            $this->manager()->record('notifications', [
                'status' => 'sent',
                'notification' => $event->notification::class,
                'channel' => (string) $event->channel,
                'notifiable_type' => is_object($event->notifiable) ? $event->notifiable::class : get_debug_type($event->notifiable),
            ]);
        });

        $this->listen(NotificationFailed::class, function (NotificationFailed $event): void {
            $this->manager()->record('notifications', [
                'status' => 'failed',
                'notification' => $event->notification::class,
                'channel' => (string) $event->channel,
                'notifiable_type' => is_object($event->notifiable) ? $event->notifiable::class : get_debug_type($event->notifiable),
            ]);
        });

        $this->listen(CommandExecuted::class, function (CommandExecuted $event): void {
            $command = strtoupper((string) $event->command);
            $keys = $this->redisKeys($command, (array) $event->parameters);

            $this->manager()->record('redis', [
                'command' => $command,
                'connection' => $event->connectionName,
                'duration_ms' => round((float) ($event->time ?? 0), 2),
                ...$keys,
                'failed' => false,
            ]);
        });

        $this->listen(CommandFailed::class, function (CommandFailed $event): void {
            $command = strtoupper((string) $event->command);
            $keys = $this->redisKeys($command, (array) $event->parameters);

            $this->manager()->record('redis', [
                'command' => $command,
                'connection' => $event->connectionName,
                'duration_ms' => 0.0,
                ...$keys,
                'failed' => true,
                'exception_class' => $event->exception::class,
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
        $this->listen(CacheFlushed::class, function (CacheFlushed $event): void {
            $this->manager()->record('cache', [
                'operation' => 'flush',
                'key_hash' => null,
                'store' => $event->storeName,
                'tags' => $event->tags,
                'seconds' => null,
            ]);
            $this->manager()->excludeRedisCacheOperation('flush');
        });

        $this->listen('composing: *', function (string $name, array $payload): void {
            $view = $payload[0] ?? null;
            $viewName = str($name)->after('composing: ')->toString();
            $path = is_object($view) && method_exists($view, 'getPath') ? $view->getPath() : null;

            $this->manager()->record('views', [
                'name' => $viewName,
                'source' => is_string($path) ? $this->callSites->location($path) : null,
                'composers' => $this->listenerDetails('composing: '.$viewName),
                'data_keys' => is_object($view) && method_exists($view, 'getData')
                    ? array_keys($view->getData())
                    : [],
                'timing' => 'composition_marker',
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
                'listeners' => $this->listenerDetails($name),
                'broadcast' => ($payload[0] ?? null) instanceof ShouldBroadcast,
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
                'key_hash' => $this->redactor->cleanKey($event->key),
                'key' => $this->keyPolicy() === 'full' ? $this->redactor->cleanKey($event->key, 'full') : null,
                'key_policy' => $this->keyPolicy(),
                'store' => $event->storeName,
                'tags' => $event->tags,
                'seconds' => $event instanceof KeyWritten ? $event->seconds : null,
            ]);
            $this->manager()->excludeRedisCacheOperation($operation);
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

    private function jobName(mixed $job): string
    {
        if (is_object($job)) {
            return $job::class;
        }

        return is_string($job) && $job !== '' ? $job : get_debug_type($job);
    }

    private function isLongRunningCommand(string $command): bool
    {
        return in_array($command, [
            'horizon',
            'mcp:inspector',
            'mcp:start',
            'octane:start',
            'queue:listen',
            'queue:work',
            'reverb:start',
            'schedule:work',
            'serve',
        ], true);
    }

    private function authorizationHandler(GateEvaluated $event): string
    {
        try {
            $gate = $this->container->make('gate');

            foreach ($event->arguments as $argument) {
                if (! is_object($argument) && ! is_string($argument)) {
                    continue;
                }

                $policy = $gate->getPolicyFor($argument);

                if (is_object($policy) || is_string($policy)) {
                    return (is_object($policy) ? $policy::class : $policy).'@'.$event->ability;
                }
            }
        } catch (Throwable) {
            // Fall through to the honest callback label.
        }

        return 'callback';
    }

    /** @return list<array<string, mixed>> */
    private function listenerDetails(string $event): array
    {
        if (! method_exists($this->events, 'getRawListeners')) {
            return [];
        }

        $raw = $this->events->getRawListeners();
        $listeners = [];

        foreach ((array) ($raw[$event] ?? []) as $listener) {
            $detail = $this->listenerDetail($listener);

            if ($detail !== null) {
                $listeners[] = $detail;
            }
        }

        return array_slice($listeners, 0, 25);
    }

    /** @return array<string, mixed>|null */
    private function listenerDetail(mixed $listener): ?array
    {
        if (is_string($listener)) {
            if (str_starts_with($listener, self::class) || str_starts_with($listener, 'Illuminate\\')) {
                return null;
            }

            [$class, $method] = str_contains($listener, '@')
                ? explode('@', $listener, 2)
                : [$listener, method_exists($listener, 'handle') ? 'handle' : '__invoke'];

            return ['name' => $class.'@'.$method, 'source' => $this->methodLocation($class, $method)];
        }

        if (is_array($listener) && count($listener) >= 2) {
            $class = is_object($listener[0]) ? $listener[0]::class : (string) $listener[0];
            $method = (string) $listener[1];

            return ['name' => $class.'@'.$method, 'source' => $this->methodLocation($class, $method)];
        }

        if ($listener instanceof Closure) {
            $reflection = new ReflectionFunction($listener);
            $file = $reflection->getFileName();
            $source = is_string($file) ? $this->callSites->location($file, $reflection->getStartLine()) : null;

            return $source === null ? null : ['name' => 'Closure', 'source' => $source];
        }

        if (is_object($listener)) {
            return ['name' => $listener::class, 'source' => $this->methodLocation($listener::class, '__invoke')];
        }

        return null;
    }

    /** @return array{file: string, line: int}|null */
    private function methodLocation(string $class, string $method): ?array
    {
        try {
            $reflection = new ReflectionMethod($class, $method);
            $file = $reflection->getFileName();

            return is_string($file) ? $this->callSites->location($file, $reflection->getStartLine()) : null;
        } catch (Throwable) {
            return null;
        }
    }

    /** @param list<mixed> $parameters @return array{key_count: int, key_hashes: list<string>, keys: list<string>, key_policy: string} */
    private function redisKeys(string $command, array $parameters): array
    {
        $multiKeyCommands = ['DEL', 'UNLINK', 'EXISTS', 'MGET', 'PFCOUNT'];
        $firstKeyCommands = [
            'APPEND', 'BITCOUNT', 'DECR', 'DECRBY', 'EXPIRE', 'EXPIREAT', 'GET', 'GETDEL', 'GETEX',
            'GETSET', 'HDEL', 'HEXISTS', 'HGET', 'HGETALL', 'HINCRBY', 'HINCRBYFLOAT', 'HLEN', 'HMGET',
            'HMSET', 'HSCAN', 'HSET', 'HSETNX', 'INCR', 'INCRBY', 'INCRBYFLOAT', 'LINDEX', 'LINSERT',
            'LLEN', 'LPOP', 'LPUSH', 'LRANGE', 'LREM', 'LSET', 'LTRIM', 'PERSIST', 'PEXPIRE', 'PSETEX',
            'PTTL', 'RPOP', 'RPUSH', 'SADD', 'SCARD', 'SET', 'SETEX', 'SETNX', 'SISMEMBER', 'SMEMBERS',
            'SPOP', 'SREM', 'SSCAN', 'STRLEN', 'TTL', 'TYPE', 'ZADD', 'ZCARD', 'ZCOUNT', 'ZINCRBY',
            'ZRANGE', 'ZRANK', 'ZREM', 'ZSCAN', 'ZSCORE',
        ];
        $keys = [];

        if (in_array($command, $multiKeyCommands, true)) {
            $keys = count($parameters) === 1 && is_array($parameters[0]) ? $parameters[0] : $parameters;
        } elseif ($command === 'MSET') {
            if (count($parameters) === 1 && is_array($parameters[0])) {
                $keys = array_keys($parameters[0]);
            } else {
                foreach ($parameters as $index => $parameter) {
                    if ($index % 2 === 0) {
                        $keys[] = $parameter;
                    }
                }
            }
        } elseif (in_array($command, $firstKeyCommands, true) && array_key_exists(0, $parameters)) {
            $keys = [$parameters[0]];
        }

        $hashes = array_values(array_unique(array_map(
            fn (mixed $key): string => $this->redactor->cleanKey($key),
            $keys,
        )));
        $policy = $this->keyPolicy();

        return [
            'key_count' => count($hashes),
            'key_hashes' => $hashes,
            'keys' => $policy === 'full' ? array_values(array_unique(array_map(
                fn (mixed $key): string => $this->redactor->cleanKey($key, 'full'),
                $keys,
            ))) : [],
            'key_policy' => $policy,
        ];
    }

    private function keyPolicy(): string
    {
        return config('new-debug-bar.collection.key_policy') === 'full' ? 'full' : 'hash';
    }
}
