<?php

namespace NewDebugBar\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Livewire\LivewireServiceProvider;
use NewDebugBar\Http\Middleware\ProfileRequest;
use NewDebugBar\NewDebugBarServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            NewDebugBarServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('new-debug-bar.environments', ['testing']);
        $app['config']->set('new-debug-bar.storage.path', storage_path('framework/testing-new-debug-bar'));
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineRoutes($router): void
    {
        $profiledPage = function (string $title, string $nextPath, string $nextLabel) {
            DB::select('select ? as number', [1]);
            Cache::put('dashboard', 'ready', 60);
            Cache::get('dashboard');
            Cache::get('missing');
            Event::dispatch('application.ready', [['safe' => true]]);
            Event::dispatch('eloquent.retrieved: '.ProfiledModel::class, [new ProfiledModel]);
            Log::info('Profiled request completed', ['authorization' => 'hidden']);

            return response(<<<HTML
                <!doctype html>
                <html>
                    <head><title>{$title}</title></head>
                    <body>
                        <main>
                            <h1 data-testid="host-page">{$title}</h1>
                            <a href="{$nextPath}" wire:navigate data-testid="host-navigation">{$nextLabel}</a>
                        </main>
                    </body>
                </html>
                HTML);
        };

        $router->middleware(ProfileRequest::class)->get(
            '/profiled',
            fn () => $profiledPage('First request', '/profiled-next', 'Next request'),
        );

        $router->middleware(ProfileRequest::class)->get(
            '/profiled-next',
            fn () => $profiledPage('Second request', '/profiled', 'Previous request'),
        );

        $router->middleware(ProfileRequest::class)->get('/plain-json', fn () => response()->json(['ready' => true]));

        $router->middleware(ProfileRequest::class)->get(
            '/html-without-head',
            fn () => response('<html><body>Headless page</body></html>'),
        );

        $router->middleware(ProfileRequest::class)->get(
            '/html-without-body',
            fn () => response('<html><head><title>No body</title></head><main>No body</main></html>'),
        );

        $router->middleware(ProfileRequest::class)->get(
            '/plain-text',
            fn () => response('Plain text', 200, ['Content-Type' => 'text/plain']),
        );

        $router->middleware(ProfileRequest::class)->get(
            '/download',
            fn () => response('<html><body>Download</body></html>', 200, [
                'Content-Disposition' => 'attachment; filename="debug.html"',
            ]),
        );

        $router->middleware(ProfileRequest::class)->get(
            '/failed-html',
            fn () => response('<html><body>Failed</body></html>', 422),
        );

        $router->middleware(ProfileRequest::class)->get('/profiled-partial-model', function () {
            $model = new ProfiledModel;
            $model->setRawAttributes(['name' => 'Partial model']);

            Event::dispatch('eloquent.retrieved: '.ProfiledModel::class, [$model]);

            return response('<!doctype html><html><body>Partial model</body></html>');
        });

        $router->middleware(ProfileRequest::class)->get(
            '/profiled-collector-failure',
            fn () => response('<!doctype html><html><body>Application response</body></html>'),
        );

        $router->middleware(ProfileRequest::class)->get(
            '/profiled-exception',
            fn () => throw new \RuntimeException('Application failed.'),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['files']->deleteDirectory(config('new-debug-bar.storage.path'));
    }

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory(config('new-debug-bar.storage.path'));

        parent::tearDown();
    }
}

final class ProfiledModel extends Model
{
    protected $table = 'profiled_models';

    protected $guarded = [];
}
