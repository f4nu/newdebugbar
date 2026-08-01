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
        $router->middleware(ProfileRequest::class)->get('/profiled', function () {
            DB::select('select 1');
            Cache::put('dashboard', 'ready', 60);
            Cache::get('dashboard');
            Cache::get('missing');
            Event::dispatch('application.ready', [['safe' => true]]);
            Event::dispatch('eloquent.retrieved: '.ProfiledModel::class, [new ProfiledModel]);
            Log::info('Profiled request completed', ['authorization' => 'hidden']);

            return response('<!doctype html><html><body>Ready</body></html>');
        });

        $router->middleware(ProfileRequest::class)->get('/plain-json', fn () => response()->json(['ready' => true]));

        $router->middleware(ProfileRequest::class)->get('/profiled-partial-model', function () {
            $model = new ProfiledModel;
            $model->setRawAttributes(['name' => 'Partial model']);

            Event::dispatch('eloquent.retrieved: '.ProfiledModel::class, [$model]);

            return response('<!doctype html><html><body>Partial model</body></html>');
        });
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
