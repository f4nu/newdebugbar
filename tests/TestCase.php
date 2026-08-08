<?php

namespace NewDebugBar\Tests;

use Illuminate\Bus\Queueable;
use Illuminate\Cache\Events\CacheFlushed;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Redis\Events\CommandFailed;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\McpServiceProvider;
use Livewire\Component;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use NewDebugBar\Debug;
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
        $app['config']->set('newdebugbar.environments', ['testing']);
        $app['config']->set('newdebugbar.storage.path', storage_path('framework/testing-newdebugbar'));
        $app['config']->set('newdebugbar.collection.application_path', dirname(__DIR__));
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('mail.default', 'array');
        $app['config']->set('mail.mailers.array', ['transport' => 'array']);
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
                    <head><meta name="viewport" content="width=device-width, initial-scale=1"><title>{$title}</title></head>
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

        $router->middleware(ProfileRequest::class)->get('/profiled-rich', function () use ($profiledPage) {
            Http::fake(['api.example.test/*' => Http::response(['private' => 'body'], 202)]);
            Http::get('https://api.example.test/v1/status?token=private&limit=5');
            Event::dispatch(new JobQueued('redis', 'emails', 'job-visual', new ProfiledJob('private'), '{}', 5));
            Bus::dispatchSync(new ProfiledJob('private'));
            Mail::raw('private body', fn ($message) => $message
                ->from('sender@example.test')
                ->to('recipient@example.test')
                ->subject('private subject'));
            Event::dispatch(new NotificationSent(
                new ProfiledNotifiable('private@example.test'),
                new ProfiledNotification('private'),
                'mail',
            ));
            Event::dispatch(new CommandExecuted('get', ['private-direct-key'], 1.25, new ProfiledRedisConnection));

            return $profiledPage('Rich request', '/profiled', 'First request');
        });

        $router->middleware(ProfileRequest::class)->get('/profiled-livewire', function () {
            $component = app('livewire')->mount('profiled-counter');

            return response('<!doctype html><html><head><title>Livewire request</title></head><body><h1 data-testid="host-page">Livewire request</h1>'.$component.'</body></html>');
        });

        $router->middleware(ProfileRequest::class)->get('/profiled-context', function () {
            Gate::define('inspect-profile', fn (mixed $user, ProfiledModel $model): bool => $user === null && $model instanceof ProfiledModel);
            Gate::define('delete-profile', fn (): bool => false);
            Gate::allows('inspect-profile', [new ProfiledModel]);
            Gate::allows('delete-profile', [new ProfiledModel]);
            Debug::message('Checkout checkpoint', [
                'step' => 2,
                'token' => 'private-developer-token',
            ]);
            Event::listen(ProfiledApplicationEvent::class, ProfiledApplicationListener::class);
            Event::dispatch(new ProfiledApplicationEvent);
            DB::beginTransaction();
            DB::rollBack();
            $view = view()->file(__DIR__.'/views/context.blade.php', [
                'label' => 'Context view',
                'private_value' => 'not-collected',
            ])->render();

            return response('<!doctype html><html><body>'.$view.'</body></html>');
        });

        $router->middleware(ProfileRequest::class)->get('/profiled-private-query', function () {
            foreach (['private-alpha', 'private-beta', 'private-gamma'] as $value) {
                DB::select('select ? as private_value', [$value]);
            }

            Log::info('private timeline log message');

            return response('<!doctype html><html><body>Private query fixture</body></html>');
        });

        $router->middleware(['web', ProfileRequest::class])->post('/profiled-validation', function () {
            Validator::make(['email' => 'invalid'], [
                'email' => ['required', 'email'],
                'name' => ['required'],
            ])->validateWithBag('signup');

            return response('unreachable');
        });

        $router->middleware(ProfileRequest::class)->get('/hostile-styles', fn () => response(<<<'HTML'
            <!doctype html>
            <html>
                <head>
                    <style>
                        body { font-family: serif; }
                        button { background: rgb(255, 0, 0); border-radius: 0; color: rgb(0, 128, 0); height: 91px; }
                    </style>
                </head>
                <body>
                    <button data-testid="host-button">Host button</button>
                </body>
            </html>
            HTML));

        $router->middleware(ProfileRequest::class)->get('/plain-json', fn () => response()->json(['ready' => true]));

        $router->get('/api/plain-json', fn () => response()->json(['source' => 'api']));

        $router->get('/ajax-fragment', fn () => response('<div data-fragment>Search result</div>', 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]));

        $router->get('/profile-redirect', fn () => redirect('/profiled'));

        $router->get('/streamed-response', fn () => response()->stream(
            static fn () => print 'streamed-body',
            200,
            ['Content-Type' => 'text/plain'],
        ));

        $router->get('/binary-response', fn () => response()->download(
            __DIR__.'/views/profiled-counter.blade.php',
            'profiled-counter.txt',
        ));

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
                'Content-Disposition' => 'Attachment; filename="debug.html"',
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

        $router->middleware(ProfileRequest::class)->get('/profiled-http-client', function () {
            Http::get('https://api.example.test/v1/patients?token=private-token&limit=5');

            try {
                Http::post('https://down.example.test/v1/sync?api_key=private-key');
            } catch (ConnectionException) {
                // The application handled the failed dependency.
            }

            return response('<!doctype html><html><body>HTTP client</body></html>');
        });

        $router->middleware(ProfileRequest::class)->get('/profiled-queue', function () {
            Event::dispatch(new JobQueued(
                'redis',
                'emails',
                'job-1',
                new ProfiledJob('private queued value'),
                json_encode(['private' => 'queued payload'], JSON_THROW_ON_ERROR),
                5,
            ));
            Bus::dispatchSync(new ProfiledJob('private sync value'));

            try {
                Bus::dispatchSync(new ProfiledFailingJob('private failed value'));
            } catch (\RuntimeException) {
                // The application handled the failed synchronous job.
            }

            return response('<!doctype html><html><body>Queue</body></html>');
        });

        $router->middleware(ProfileRequest::class)->get('/profiled-messages', function () {
            Mail::raw('private body', function ($message): void {
                $message
                    ->from('private-sender@example.test')
                    ->to('private-recipient@example.test')
                    ->cc('private-copy@example.test')
                    ->subject('private subject')
                    ->attachData('private attachment', 'private.txt');
            });

            $notifiable = new ProfiledNotifiable('private-recipient@example.test');
            $notification = new ProfiledNotification('private notification data');
            Event::dispatch(new NotificationSent($notifiable, $notification, 'mail', ['private response']));
            Event::dispatch(new NotificationFailed($notifiable, $notification, 'slack', ['private' => 'failure data']));

            return response('<!doctype html><html><body>Messages</body></html>');
        });

        $router->middleware(ProfileRequest::class)->get('/profiled-redis', function () {
            $connection = new ProfiledRedisConnection;
            Event::dispatch(new CommandExecuted('get', ['private-direct-key'], 1.25, $connection));
            Event::dispatch(new CommandExecuted('setex', ['private-cache-key', 60, 'private-cache-value'], 0.4, $connection));
            Event::dispatch(new KeyWritten(
                'redis',
                'private-cache-key',
                'private-cache-value',
                60,
                ['tenant:private-clinic', 'patient:private-patient'],
            ));
            Event::dispatch(new CommandExecuted('flushdb', [], 0.5, $connection));
            Event::dispatch(new CacheFlushed('redis', ['tenant:private-clinic']));
            Event::dispatch(new CommandFailed('hget', ['private-hash', 'private-field'], new \RuntimeException('private Redis failure'), $connection));

            return response('<!doctype html><html><body>Redis</body></html>');
        });

        $router->middleware(ProfileRequest::class)->get('/profiled-redis-independent-cache', function () {
            Event::dispatch(new CommandExecuted('get', ['private-direct-key'], 1.25, new ProfiledRedisConnection));
            Cache::get('independent-array-cache-key');

            return response('<!doctype html><html><body>Independent cache</body></html>');
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
        $this->app['files']->deleteDirectory(config('newdebugbar.storage.path'));
    }

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory(config('newdebugbar.storage.path'));

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

final class ProfiledJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $privateValue) {}

    public function handle(): void {}
}

final class ProfiledFailingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $privateValue) {}

    public function handle(): void
    {
        throw new \RuntimeException('private failure message');
    }
}

final class ProfiledNotification extends Notification
{
    public function __construct(public string $privateValue) {}
}

final class ProfiledNotifiable
{
    public function __construct(public string $privateAddress) {}
}

final class ProfiledRedisConnection
{
    public function getName(): string
    {
        return 'default';
    }
}

final class ProfiledApplicationEvent {}

final class ProfiledApplicationListener
{
    public function handle(ProfiledApplicationEvent $event): void {}
}
