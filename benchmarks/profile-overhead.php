<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use NewDebugBar\Collectors\CacheCollector;
use NewDebugBar\Collectors\ItemCollector;
use NewDebugBar\Collectors\LivewireCollector;
use NewDebugBar\Collectors\LogCollector;
use NewDebugBar\Collectors\MailCollector;
use NewDebugBar\Collectors\NotificationCollector;
use NewDebugBar\Collectors\OutboundHttpCollector;
use NewDebugBar\Collectors\QueryCollector;
use NewDebugBar\Collectors\QueueCollector;
use NewDebugBar\Collectors\RedisCollector;
use NewDebugBar\ProfileManager;
use NewDebugBar\Support\Redactor;
use Symfony\Component\HttpFoundation\Response;

require dirname(__DIR__).'/vendor/autoload.php';

$application = new Application(dirname(__DIR__));
$application->detectEnvironment(fn (): string => 'local');
$iterations = max(100, (int) ($argv[1] ?? 2_000));
$request = Request::create('/benchmark?token=private', 'POST', ['name' => 'Example']);
$response = new Response('<!doctype html><html><body>Benchmark</body></html>', 200, ['Content-Type' => 'text/html']);

$measure = static function (callable $operation) use ($iterations): array {
    gc_collect_cycles();
    memory_reset_peak_usage();
    $beforeMemory = memory_get_usage(true);
    $startedAt = hrtime(true);

    for ($index = 0; $index < $iterations; $index++) {
        $operation();
    }

    return [
        'milliseconds_per_iteration' => round(((hrtime(true) - $startedAt) / 1_000_000) / $iterations, 4),
        'peak_memory_mb' => round(max(0, memory_get_peak_usage(true) - $beforeMemory) / 1_048_576, 4),
    ];
};

$disabledOperation = static function () use ($request, $response): void {
    $request->getMethod();
    $response->getStatusCode();
};

$enabledOperation = static function () use ($request, $response): void {
    $redactor = new Redactor;
    $maxItems = 100;
    $manager = new ProfileManager([
        new QueryCollector($redactor, $maxItems),
        new LivewireCollector($redactor, $maxItems),
        new OutboundHttpCollector($redactor, $maxItems),
        new QueueCollector($redactor, $maxItems),
        new MailCollector($redactor, $maxItems),
        new NotificationCollector($redactor, $maxItems),
        new RedisCollector($redactor, $maxItems),
        new ItemCollector($redactor, $maxItems, 'models', 'Models'),
        new CacheCollector($redactor, $maxItems),
        new ItemCollector($redactor, $maxItems, 'views', 'Views'),
        new ItemCollector($redactor, $maxItems, 'events', 'Events'),
        new LogCollector($redactor, $maxItems),
        new ItemCollector($redactor, $maxItems, 'exceptions', 'Exceptions'),
    ], $redactor);
    $manager->begin($request);
    $manager->record('queries', [
        'sql' => 'select * from users where id = ?',
        'bindings' => [42],
        'duration_ms' => 1.25,
        'connection' => 'benchmark',
        'type' => 'read',
    ]);
    $manager->record('cache', [
        'operation' => 'hit',
        'key_hash' => '0000000000000000',
        'store' => 'benchmark',
    ]);
    $manager->record('logs', ['level' => 'info', 'message' => 'Benchmark event', 'context' => []]);
    $manager->finish($request, $response);
};

for ($warmup = 0; $warmup < 20; $warmup++) {
    $disabledOperation();
    $enabledOperation();
}

$disabled = $measure($disabledOperation);
$enabled = $measure($enabledOperation);

echo json_encode([
    'iterations' => $iterations,
    'disabled' => $disabled,
    'enabled' => $enabled,
    'profiler_overhead' => [
        'milliseconds_per_request' => round($enabled['milliseconds_per_iteration'] - $disabled['milliseconds_per_iteration'], 4),
        'peak_memory_mb' => round($enabled['peak_memory_mb'] - $disabled['peak_memory_mb'], 4),
    ],
    'note' => 'Synthetic collector-core benchmark. Run in the target Laravel application for host-specific results.',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
