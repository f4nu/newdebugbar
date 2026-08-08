<?php

namespace NewDebugBar;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
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
use NewDebugBar\Collectors\RedisCollector;
use NewDebugBar\Collectors\ValidationCollector;
use NewDebugBar\Http\Controllers\AssetController;
use NewDebugBar\Http\Controllers\MailPreviewController;
use NewDebugBar\Http\Middleware\ProfileRequest;
use NewDebugBar\Livewire\DebugBar;
use NewDebugBar\Mcp\NewDebugBarServer;
use NewDebugBar\Presentation\McpProfilePresenter;
use NewDebugBar\Presentation\ProfilePresenter;
use NewDebugBar\Presentation\ProfileSummaryPresenter;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Support\CallSiteResolver;
use NewDebugBar\Support\EditorLink;
use NewDebugBar\Support\EventRegistrar;
use NewDebugBar\Support\ExceptionNormalizer;
use NewDebugBar\Support\LivewireMountRecorder;
use NewDebugBar\Support\LivewireUpdateRecorder;
use NewDebugBar\Support\MailPreview;
use NewDebugBar\Support\ProfileFinalizer;
use NewDebugBar\Support\QueryExplainer;
use NewDebugBar\Support\Redactor;
use NewDebugBar\Support\RequestContext;
use NewDebugBar\Support\RuntimeContext;
use NewDebugBar\Support\RuntimeProfiler;
use NewDebugBar\Support\SafeUrl;

/** Registers profiling services only in explicitly allowed environments. */
final class NewDebugBarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/newdebugbar.php', 'newdebugbar');

        $this->app->singleton(Redactor::class, fn (): Redactor => new Redactor(
            maxDepth: (int) config('newdebugbar.collection.max_depth', 5),
            maxStringLength: (int) config('newdebugbar.collection.max_string_length', 2_000),
            maxArrayItems: (int) config('newdebugbar.collection.max_items_per_array', 100),
        ));

        $this->app->singleton(QueryAnalyzer::class, fn (): QueryAnalyzer => new QueryAnalyzer(
            (float) config('newdebugbar.slow_query_ms', 100),
        ));
        $this->app->singleton(ProfileAnalyzer::class, fn ($app): ProfileAnalyzer => new ProfileAnalyzer(
            queries: $app->make(QueryAnalyzer::class),
            slowRequestMs: (float) config('newdebugbar.slow_request_ms', 1_000),
            minimumCacheOperations: (int) config('newdebugbar.findings.minimum_cache_operations', 5),
            highCacheMissRate: (float) config('newdebugbar.findings.high_cache_miss_rate', 0.8),
            maxFindings: (int) config('newdebugbar.findings.max_findings', 50),
        ));
        $this->app->singleton(ProfileSummaryPresenter::class);
        $this->app->singleton(ProfileComparator::class);
        $this->app->singleton(SectionAnalyzer::class);
        $this->app->singleton(TimelineBuilder::class);
        $this->app->singleton(CallSiteResolver::class, fn (): CallSiteResolver => new CallSiteResolver(
            projectPath: (string) (config('newdebugbar.collection.application_path') ?: base_path()),
            packagePath: dirname(__DIR__),
            enabled: (bool) config('newdebugbar.collection.call_sites', true),
            maxFrames: (int) config('newdebugbar.collection.call_site_frames', 5),
            scanLimit: (int) config('newdebugbar.collection.call_site_scan_limit', 40),
        ));
        $this->app->singleton(ExceptionNormalizer::class, fn (): ExceptionNormalizer => new ExceptionNormalizer(
            projectPath: (string) (config('newdebugbar.collection.application_path') ?: base_path()),
            packagePath: dirname(__DIR__),
            maxApplicationFrames: (int) config('newdebugbar.collection.exception_application_frames', 12),
            maxVendorFrames: (int) config('newdebugbar.collection.exception_vendor_frames', 12),
            sourceContextLines: (int) config('newdebugbar.collection.exception_source_context_lines', 9),
        ));
        $this->app->singleton(EditorLink::class, fn (): EditorLink => new EditorLink(
            projectPath: (string) (config('newdebugbar.collection.application_path') ?: base_path()),
            editor: (string) config('newdebugbar.editor.name', 'vscode'),
            remotePath: config('newdebugbar.editor.remote_path'),
            localPath: config('newdebugbar.editor.local_path'),
        ));
        $this->app->singleton(RequestContext::class, fn (): RequestContext => new RequestContext(
            maxKeys: (int) config('newdebugbar.collection.max_items_per_array', 100),
        ));
        $this->app->singleton(QueryExplainer::class);
        $this->app->singleton(MailPreview::class, fn (): MailPreview => new MailPreview(
            maxBodyBytes: (int) config('newdebugbar.mail_preview.max_body_bytes', 50_000),
            maxRecipients: (int) config('newdebugbar.collection.max_items_per_array', 100),
        ));
        $this->app->scoped(LivewireUpdateRecorder::class);
        $this->app->scoped(LivewireMountRecorder::class);
        $this->app->scoped(RuntimeProfiler::class);
        $this->app->singleton(RuntimeContext::class);
        $this->app->singleton(SafeUrl::class);

        $this->app->scoped(ProfileManager::class, function ($app): ProfileManager {
            $maxItems = (int) config('newdebugbar.collection.max_items_per_collector', 500);
            $redactor = $app->make(Redactor::class);

            return new ProfileManager([
                new QueryCollector(
                    $redactor,
                    $maxItems,
                    (string) config('newdebugbar.collection.query_bindings', 'safe'),
                ),
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
                new ItemCollector($redactor, $maxItems, 'authorization', 'Authorization'),
                new ValidationCollector($redactor, $maxItems),
                new ItemCollector($redactor, $maxItems, 'lifecycle', 'Lifecycle'),
                new ItemCollector($redactor, $maxItems, 'messages', 'Messages'),
                new LogCollector($redactor, $maxItems),
                new ItemCollector($redactor, $maxItems, 'exceptions', 'Exceptions'),
            ], $redactor, $app->make(ExceptionNormalizer::class), $app->make(RuntimeContext::class), $app->make(RequestContext::class));
        });

        $this->app->singleton(ProfileStore::class, fn ($app): ProfileStore => new ProfileStore(
            files: $app->make(Filesystem::class),
            path: config('newdebugbar.storage.path') ?: storage_path('framework/newdebugbar'),
            maxProfiles: (int) config('newdebugbar.storage.max_profiles', 20),
            maxAgeMinutes: (int) config('newdebugbar.storage.max_age_minutes', 60),
        ));
        $this->app->singleton(McpProfilePresenter::class, fn ($app): McpProfilePresenter => new McpProfilePresenter(
            store: $app->make(ProfileStore::class),
            profiles: $app->make(ProfilePresenter::class),
            summaries: $app->make(ProfileSummaryPresenter::class),
            redactor: $app->make(Redactor::class),
            projectPath: base_path(),
            maxItems: (int) config('newdebugbar.mcp.max_items', 50),
            maxBytes: (int) config('newdebugbar.mcp.max_bytes', 100_000),
        ));
    }

    public function boot(Router $router, Dispatcher $events): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'newdebugbar');

        $this->publishes([
            __DIR__.'/../config/newdebugbar.php' => config_path('newdebugbar.php'),
        ], 'newdebugbar-config');

        if (! $this->isEnabledEnvironment()) {
            return;
        }

        (new EventRegistrar(
            $events,
            $this->app,
            $this->app->make(CallSiteResolver::class),
            $this->app->make(SafeUrl::class),
            $this->app->make(RuntimeProfiler::class),
            $this->app->make(Redactor::class),
            $this->app->make(MailPreview::class),
        ))->register();
        $exceptions = $this->app->make(ExceptionHandler::class);

        if (method_exists($exceptions, 'renderable')) {
            $exceptions->renderable(function (ValidationException $exception, Request $request): null {
                $this->app->make(ProfileManager::class)->recordValidationException($exception);

                return null;
            });
        }
        $events->listen(
            RequestHandled::class,
            fn (RequestHandled $event) => $this->app->make(ProfileFinalizer::class)->handle($event),
        );
        Livewire::component('newdebugbar.toolbar', DebugBar::class);
        $this->app->make(LivewireMountRecorder::class)->register();
        Mcp::local('newdebugbar', NewDebugBarServer::class);
        $router->get('/__newdebugbar/assets/{path}', AssetController::class)
            ->where('path', '.*')
            ->name('newdebugbar.asset');
        $router->get('/__newdebugbar/mail/{profile}/{index}/{format}', MailPreviewController::class)
            ->whereUuid('profile')
            ->whereNumber('index')
            ->whereIn('format', ['html', 'text', 'eml'])
            ->name('newdebugbar.mail-preview');
        $kernel = $this->app->make(HttpKernel::class);

        if (method_exists($kernel, 'pushMiddleware')) {
            $kernel->pushMiddleware(ProfileRequest::class);
        }
    }

    private function isEnabledEnvironment(): bool
    {
        $environments = config('newdebugbar.environments', ['local']);

        return config('newdebugbar.enabled', true)
            && is_array($environments)
            && $this->app->environment($environments);
    }
}
