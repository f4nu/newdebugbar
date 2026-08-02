<?php

namespace NewDebugBar;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;
use Livewire\Livewire;
use NewDebugBar\Analysis\ProfileAnalyzer;
use NewDebugBar\Analysis\ProfileComparator;
use NewDebugBar\Analysis\QueryAnalyzer;
use NewDebugBar\Analysis\SectionAnalyzer;
use NewDebugBar\Analysis\TimelineBuilder;
use NewDebugBar\Collectors\CacheCollector;
use NewDebugBar\Collectors\ItemCollector;
use NewDebugBar\Collectors\LivewireCollector;
use NewDebugBar\Collectors\LogCollector;
use NewDebugBar\Collectors\MailCollector;
use NewDebugBar\Collectors\NotificationCollector;
use NewDebugBar\Collectors\OutboundHttpCollector;
use NewDebugBar\Collectors\QueryCollector;
use NewDebugBar\Collectors\QueueCollector;
use NewDebugBar\Http\Controllers\AssetController;
use NewDebugBar\Http\Middleware\ProfileRequest;
use NewDebugBar\Livewire\DebugBar;
use NewDebugBar\Mcp\NewDebugBarServer;
use NewDebugBar\Presentation\McpProfilePresenter;
use NewDebugBar\Presentation\ProfilePresenter;
use NewDebugBar\Presentation\ProfileSummaryPresenter;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Support\CallSiteResolver;
use NewDebugBar\Support\EventRegistrar;
use NewDebugBar\Support\ExceptionNormalizer;
use NewDebugBar\Support\LivewireUpdateRecorder;
use NewDebugBar\Support\ProfileFinalizer;
use NewDebugBar\Support\Redactor;
use NewDebugBar\Support\SafeUrl;

/** Registers profiling services only in explicitly allowed environments. */
final class NewDebugBarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/new-debug-bar.php', 'new-debug-bar');

        $this->app->singleton(Redactor::class, fn (): Redactor => new Redactor(
            maxDepth: (int) config('new-debug-bar.collection.max_depth', 5),
            maxStringLength: (int) config('new-debug-bar.collection.max_string_length', 2_000),
            maxArrayItems: (int) config('new-debug-bar.collection.max_items_per_section', 100),
        ));

        $this->app->singleton(QueryAnalyzer::class, fn (): QueryAnalyzer => new QueryAnalyzer(
            (float) config('new-debug-bar.slow_query_ms', 100),
        ));
        $this->app->singleton(ProfileAnalyzer::class, fn ($app): ProfileAnalyzer => new ProfileAnalyzer(
            queries: $app->make(QueryAnalyzer::class),
            slowRequestMs: (float) config('new-debug-bar.slow_request_ms', 1_000),
            minimumCacheOperations: (int) config('new-debug-bar.findings.minimum_cache_operations', 5),
            highCacheMissRate: (float) config('new-debug-bar.findings.high_cache_miss_rate', 0.8),
            maxFindings: (int) config('new-debug-bar.findings.max_findings', 50),
        ));
        $this->app->singleton(ProfileSummaryPresenter::class);
        $this->app->singleton(ProfileComparator::class);
        $this->app->singleton(SectionAnalyzer::class);
        $this->app->singleton(TimelineBuilder::class);
        $this->app->singleton(CallSiteResolver::class, fn (): CallSiteResolver => new CallSiteResolver(
            projectPath: (string) (config('new-debug-bar.collection.application_path') ?: base_path()),
            packagePath: dirname(__DIR__),
            enabled: (bool) config('new-debug-bar.collection.call_sites', true),
            maxFrames: (int) config('new-debug-bar.collection.call_site_frames', 5),
            scanLimit: (int) config('new-debug-bar.collection.call_site_scan_limit', 40),
        ));
        $this->app->singleton(ExceptionNormalizer::class, fn (): ExceptionNormalizer => new ExceptionNormalizer(
            projectPath: (string) (config('new-debug-bar.collection.application_path') ?: base_path()),
            packagePath: dirname(__DIR__),
            maxApplicationFrames: (int) config('new-debug-bar.collection.exception_application_frames', 12),
            maxVendorFrames: (int) config('new-debug-bar.collection.exception_vendor_frames', 12),
            sourceContextLines: (int) config('new-debug-bar.collection.exception_source_context_lines', 9),
        ));
        $this->app->scoped(LivewireUpdateRecorder::class);
        $this->app->singleton(SafeUrl::class);

        $this->app->scoped(ProfileManager::class, function ($app): ProfileManager {
            $maxItems = (int) config('new-debug-bar.collection.max_items_per_section', 100);
            $redactor = $app->make(Redactor::class);

            return new ProfileManager([
                new QueryCollector(
                    $redactor,
                    $maxItems,
                    (string) config('new-debug-bar.collection.query_bindings', 'safe'),
                ),
                new LivewireCollector($redactor, $maxItems),
                new OutboundHttpCollector($redactor, $maxItems),
                new QueueCollector($redactor, $maxItems),
                new MailCollector($redactor, $maxItems),
                new NotificationCollector($redactor, $maxItems),
                new ItemCollector($redactor, $maxItems, 'models', 'Models'),
                new CacheCollector($redactor, $maxItems),
                new ItemCollector($redactor, $maxItems, 'views', 'Views'),
                new ItemCollector($redactor, $maxItems, 'events', 'Events'),
                new LogCollector($redactor, $maxItems),
                new ItemCollector($redactor, $maxItems, 'exceptions', 'Exceptions'),
            ], $redactor, $app->make(ExceptionNormalizer::class));
        });

        $this->app->singleton(ProfileStore::class, fn ($app): ProfileStore => new ProfileStore(
            files: $app->make(Filesystem::class),
            path: config('new-debug-bar.storage.path') ?: storage_path('framework/cache/new-debug-bar'),
            maxProfiles: (int) config('new-debug-bar.storage.max_profiles', 20),
            maxAgeMinutes: (int) config('new-debug-bar.storage.max_age_minutes', 60),
        ));
        $this->app->singleton(McpProfilePresenter::class, fn ($app): McpProfilePresenter => new McpProfilePresenter(
            store: $app->make(ProfileStore::class),
            profiles: $app->make(ProfilePresenter::class),
            summaries: $app->make(ProfileSummaryPresenter::class),
            redactor: $app->make(Redactor::class),
            projectPath: base_path(),
            maxItems: (int) config('new-debug-bar.mcp.max_items', 50),
            maxBytes: (int) config('new-debug-bar.mcp.max_bytes', 100_000),
        ));
    }

    public function boot(Router $router, Dispatcher $events): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'new-debug-bar');

        $this->publishes([
            __DIR__.'/../config/new-debug-bar.php' => config_path('new-debug-bar.php'),
        ], 'new-debug-bar-config');

        if (! $this->isEnabledEnvironment()) {
            return;
        }

        (new EventRegistrar(
            $events,
            $this->app,
            $this->app->make(CallSiteResolver::class),
            $this->app->make(SafeUrl::class),
        ))->register();
        $events->listen(
            RequestHandled::class,
            fn (RequestHandled $event) => $this->app->make(ProfileFinalizer::class)->handle($event),
        );
        Livewire::component('new-debug-bar.toolbar', DebugBar::class);
        Mcp::local('new-debug-bar', NewDebugBarServer::class);
        $router->get('/__new-debug-bar/assets/{path}', AssetController::class)
            ->where('path', '.*')
            ->name('new-debug-bar.asset');
        $router->pushMiddlewareToGroup('web', ProfileRequest::class);
    }

    private function isEnabledEnvironment(): bool
    {
        $environments = config('new-debug-bar.environments', ['local']);

        return config('new-debug-bar.enabled', true)
            && is_array($environments)
            && $this->app->environment($environments);
    }
}
