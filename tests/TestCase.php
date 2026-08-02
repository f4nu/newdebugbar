<?php

namespace NewDebugBar\Tests;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Server\McpServiceProvider;
use Livewire\Component;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use NewDebugBar\Http\Middleware\ProfileRequest;
use NewDebugBar\NewDebugBarServiceProvider;
use NewDebugBar\ProfileManager;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            McpServiceProvider::class,
            NewDebugBarServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('new-debug-bar.environments', ['testing']);
        $app['config']->set('new-debug-bar.storage.path', storage_path('framework/testing-new-debug-bar'));
        $app['config']->set('new-debug-bar.collection.application_path', dirname(__DIR__));
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
            foreach ([1, 2, 3] as $number) {
                DB::select('select ? as number', [$number]);
            }
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

        $router->middleware(ProfileRequest::class)->get('/profiled-livewire', function () {
            $component = app('livewire')->mount('profiled-counter');

            return response('<!doctype html><html><head><title>Livewire request</title></head><body><h1 data-testid="host-page">Livewire request</h1>'.$component.'</body></html>');
        });

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

        $router->middleware(ProfileRequest::class)->get('/profiled-reported-exception', function () {
            app(ProfileManager::class)->recordException(new \RuntimeException('Reported failure.'));

            return response('<!doctype html><html><body>Reported failure</body></html>');
        });

        $router->middleware(ProfileRequest::class)->post(
            '/profiled-input',
            fn (Request $request) => response('<!doctype html><html><body>'.$request->input('clinic.name').'</body></html>'),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        view()->addLocation(__DIR__.'/views');
        Livewire::component('profiled-counter', ProfiledCounter::class);
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

final class ProfiledCounter extends Component
{
    public int $count = 0;

    public string $name = '';

    public function increment(): void
    {
        $this->count++;
    }

    public function save(): void
    {
        $this->validate(['name' => ['required']]);
    }

    public function render(): View
    {
        return view('profiled-counter');
    }
}
