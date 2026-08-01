<?php

namespace NewDebugBar;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use NewDebugBar\Collectors\CacheCollector;
use NewDebugBar\Collectors\EventCollector;
use NewDebugBar\Collectors\ExceptionCollector;
use NewDebugBar\Collectors\LogCollector;
use NewDebugBar\Collectors\ModelCollector;
use NewDebugBar\Collectors\QueryCollector;
use NewDebugBar\Collectors\ViewCollector;
use NewDebugBar\Http\Controllers\AssetController;
use NewDebugBar\Http\Middleware\ProfileRequest;
use NewDebugBar\Livewire\DebugBar;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Support\EventRegistrar;
use NewDebugBar\Support\Redactor;

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

        $this->app->singleton(ProfileManager::class, function ($app): ProfileManager {
            $maxItems = (int) config('new-debug-bar.collection.max_items_per_section', 100);
            $redactor = $app->make(Redactor::class);

            return new ProfileManager([
                new QueryCollector($redactor, $maxItems),
                new ModelCollector($redactor, $maxItems),
                new CacheCollector($redactor, $maxItems),
                new ViewCollector($redactor, $maxItems),
                new EventCollector($redactor, $maxItems),
                new LogCollector($redactor, $maxItems),
                new ExceptionCollector($redactor, $maxItems),
            ], $redactor);
        });

        $this->app->singleton(ProfileStore::class, fn ($app): ProfileStore => new ProfileStore(
            files: $app->make(Filesystem::class),
            path: config('new-debug-bar.storage.path') ?: storage_path('framework/cache/new-debug-bar'),
            maxProfiles: (int) config('new-debug-bar.storage.max_profiles', 20),
            maxAgeMinutes: (int) config('new-debug-bar.storage.max_age_minutes', 60),
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

        (new EventRegistrar($events, $this->app->make(ProfileManager::class)))->register();
        Livewire::component('new-debug-bar.toolbar', DebugBar::class);
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
