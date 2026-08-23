<?php

namespace NewDebugBar\Tests\Support;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\Create;
use Illuminate\Cache\Events\CacheEvent;
use Illuminate\Cache\Events\CacheFlushed;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Request;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\SendQueuedNotifications;
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
use Illuminate\Support\Str;
use Livewire\Livewire;
use NewDebugBar\Debug;
use NewDebugBar\Http\Middleware\ProfileRequest;
use NewDebugBar\ProfileManager;
use NewDebugBar\Storage\BackgroundActivityStore;
use NewDebugBar\Tests\Fixtures\Events\ProfiledApplicationEvent;
use NewDebugBar\Tests\Fixtures\Events\ProfiledApplicationListener;
use NewDebugBar\Tests\Fixtures\HostCounter;
use NewDebugBar\Tests\Fixtures\HostCounterGroup;
use NewDebugBar\Tests\Fixtures\HostValidationForm;
use NewDebugBar\Tests\Fixtures\Jobs\ProfiledAfterResponseMailJob;
use NewDebugBar\Tests\Fixtures\Jobs\ProfiledFailingJob;
use NewDebugBar\Tests\Fixtures\Jobs\ProfiledJob;
use NewDebugBar\Tests\Fixtures\Mail\ProfiledMailable;
use NewDebugBar\Tests\Fixtures\Models\Client;
use NewDebugBar\Tests\Fixtures\Models\JobActivity;
use NewDebugBar\Tests\Fixtures\Models\ProfiledModel;
use NewDebugBar\Tests\Fixtures\Models\ProofVersion;
use NewDebugBar\Tests\Fixtures\Models\StudioJob;
use NewDebugBar\Tests\Fixtures\Models\User;
use NewDebugBar\Tests\Fixtures\Notifications\ProfiledNotifiable;
use NewDebugBar\Tests\Fixtures\Notifications\ProfiledNotification;
use NewDebugBar\Tests\Fixtures\Redis\ProfiledRedisConnection;

trait DefinesTestApplication
{
    protected function defineRoutes($router): void
    {
        Livewire::component('host-counter', HostCounter::class);
        Livewire::component('host-counter-group', HostCounterGroup::class);
        Livewire::component('host-validation-form', HostValidationForm::class);
        Livewire::addLocation(viewPath: dirname(__DIR__).'/Fixtures/views/components');

        foreach ([StudioJob::class, Client::class, ProofVersion::class, JobActivity::class, User::class] as $modelClass) {
            new $modelClass;
        }

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

        $router->middleware(ProfileRequest::class)->get('/profiled-livewire', function () {
            $component = app('livewire')->mount('host-counter', key: 'host-counter-browser');

            return response(<<<HTML
                <!doctype html>
                <html>
                    <head><meta name="viewport" content="width=device-width, initial-scale=1"><title>Livewire host</title></head>
                    <body><main><h1 data-testid="host-page">Livewire host</h1>{$component}</main></body>
                </html>
                HTML);
        });

        $router->middleware(ProfileRequest::class)->get('/profiled-livewire-nested', function () {
            $component = app('livewire')->mount('host-counter-group', key: 'host-counter-group-browser');

            return response(<<<HTML
                <!doctype html>
                <html>
                    <head><meta name="viewport" content="width=device-width, initial-scale=1"><title>Nested Livewire host</title></head>
                    <body><main><h1 data-testid="host-page">Nested Livewire host</h1>{$component}</main></body>
                </html>
                HTML);
        });

        $router->middleware(ProfileRequest::class)->get('/profiled-livewire-validation', function () {
            $component = app('livewire')->mount('host-validation-form', key: 'host-validation-form-browser');

            return response(<<<HTML
                <!doctype html>
                <html>
                    <head><meta name="viewport" content="width=device-width, initial-scale=1"><title>Livewire validation</title></head>
                    <body><main><h1 data-testid="host-page">Livewire validation</h1>{$component}</main></body>
                </html>
                HTML);
        });

        $router->middleware(ProfileRequest::class)->get('/profiled-livewire-single-file', function () {
            $component = app('livewire')->mount('host-functional-status', key: 'host-functional-status-browser');

            return response('<!doctype html><html><body>'.$component.'</body></html>');
        });

        $router->middleware(ProfileRequest::class)->get('/profiled-rich', function () use ($profiledPage) {
            Http::fake(['api.example.test/*' => Http::response(['private' => 'body'], 202)]);
            Http::get('https://api.example.test/v1/status?token=private&limit=5');
            Event::dispatch($this->queuedEvent('job-visual', new ProfiledJob('private')));
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

        $router->middleware(ProfileRequest::class)->get('/profiled-models', function () {
            $retrievals = [
                StudioJob::class => [1, 5, 7, 2, 3, 4, 1, 5, 7, 2, 3, 1, 5, 7],
                Client::class => [1, 4, 2, 3, 1, 4, 2, 3, 1, 4],
                ProofVersion::class => [2, 8, 9, 1, 3, 2, 8, 9],
                JobActivity::class => [1, 2, 3, 4, 5, 6, 7],
                User::class => [1, 2, 1, 2, 1],
            ];

            foreach ($retrievals as $modelClass => $keys) {
                foreach ($keys as $key) {
                    $model = new $modelClass;
                    $model->setConnection('testing');
                    $model->setRawAttributes(['id' => $key], true);
                    Event::dispatch('eloquent.retrieved: '.$modelClass, [$model]);
                }
            }

            if (request()->boolean('changes')) {
                $model = new Client;
                $model->setConnection('testing');
                $model->setRawAttributes(['id' => 4], true);
                Event::dispatch('eloquent.updated: '.Client::class, [$model]);
            }

            return response(<<<'HTML'
                <!doctype html>
                <html>
                    <head><meta name="viewport" content="width=device-width, initial-scale=1"><title>Model activity</title></head>
                    <body><main><h1 data-testid="host-page">Model activity</h1></main></body>
                </html>
                HTML);
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
            $view = view('context', [
                'label' => 'Context view',
                'private_value' => 'view-data-value',
                'rows' => collect([
                    [
                        'reference' => 'NL-1042',
                        'ready' => true,
                        'version_count' => 2,
                    ],
                ]),
            ])->render();

            return response('<!doctype html><html><body>'.$view.'</body></html>');
        });

        $router->middleware(ProfileRequest::class)->get('/profiled-views', function () {
            $context = view('context', [
                'label' => 'Context view',
                'private_value' => 'view-data-value',
                'rows' => collect(),
            ])->render();
            $firstResponse = view('original-response', ['label' => 'First response'])->render();
            $secondResponse = view('original-response', ['label' => 'Second response'])->render();

            return response('<!doctype html><html><body>'.$context.$firstResponse.$secondResponse.'</body></html>');
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

        $router->middleware(['web', ProfileRequest::class])->get(
            '/profiled-session-validation',
            fn () => response('<!doctype html><html><body>Validation redirect target</body></html>'),
        );

        $router->middleware(ProfileRequest::class)->get('/hostile-styles', function () {
            foreach (['alpha', 'beta', 'gamma'] as $value) {
                DB::select('select ? as hostile_value', [$value]);
            }
            Mail::raw('Hostile style mail body', fn ($message) => $message
                ->from('sender@example.test')
                ->to('recipient@example.test')
                ->subject('Hostile style mail'));
            $queuedMailable = (new ProfiledMailable(
                subjectLine: 'Hostile queued mail',
                heading: 'Hostile queued heading',
                messageCopy: 'Hostile queued body',
            ))->to('queued@example.test');
            $queuedNotification = new ProfiledNotification('hostile queued notification');
            Event::dispatch($this->queuedEvent(
                'hostile-mail-job',
                new SendQueuedMailable($queuedMailable),
                queue: 'hostile-mail',
                delay: 0,
            ));
            Event::dispatch($this->queuedEvent(
                'hostile-notification-job',
                new SendQueuedNotifications(
                    collect([new ProfiledNotifiable('queued@example.test')]),
                    $queuedNotification,
                    ['mail'],
                ),
                queue: 'hostile-notifications',
                delay: 0,
            ));
            Event::dispatch($this->queuedEvent(
                'hostile-pending-job',
                new ProfiledJob('hostile pending payload'),
                queue: 'hostile-pending',
                delay: 60,
            ));
            $workerId = (string) Str::uuid();
            $background = app(BackgroundActivityStore::class);
            $background->recordOutcome($background->key('redis', 'hostile-mail', 'hostile-mail-job'), 'sent', $workerId, 1);
            $background->recordOutcome($background->key('redis', 'hostile-notifications', 'hostile-notification-job'), 'sent', $workerId, 1);

            return response(<<<'HTML'
                <!doctype html>
                <html data-theme="dark">
                    <head>
                        <style>
                            @layer base {
                                :root, [data-theme] {
                                    background-color: var(--root-bg);
                                    color: var(--color-base-content);
                                }

                                :where(:root, [data-theme]) {
                                    --root-bg: rgb(255, 255, 255);
                                    --color-base-content: rgb(0, 0, 0);
                                }
                            }

                            body { font-family: serif; }
                            button { background: rgb(255, 0, 0); border-radius: 0; color: rgb(0, 128, 0); height: 91px; }
                            button svg { width: 64px; height: 64px; }
                            a { background: rgb(255, 0, 255); color: rgb(0, 128, 0); height: 91px; text-decoration: underline 8px; }
                            details { background: rgb(255, 0, 0); border-left: 13px solid rgb(255, 0, 0); padding: 24px; }
                            dl, dt, dd { background: rgb(255, 0, 0); color: rgb(0, 128, 0); font-size: 42px; }
                            pre, code { background: rgb(243, 243, 243); color: rgb(0, 0, 0); }
                            iframe { width: 17px; height: 19px; border: 9px solid rgb(255, 0, 0); }
                            summary { color: rgb(255, 0, 0); font-size: 42px; }
                            [data-mail] { border-left: 20px solid rgb(255, 0, 0); }
                            [data-ndb-queue-item], [data-ndb-notification-item] { border-left: 20px solid rgb(255, 0, 0); }
                            [data-ndb-queue-status], [data-ndb-notification-status], [data-ndb-mail-status] { background: rgb(255, 0, 0); color: rgb(0, 128, 0); font-size: 42px; }
                            [data-ndb-background-refresh], [data-ndb-queue-profile-link], [data-ndb-notification-profile-link], [data-ndb-mail-related-profile], [data-ndb-mail-open-related] { background: rgb(255, 0, 255); border-radius: 0; color: rgb(0, 128, 0); height: 91px; }
                        </style>
                    </head>
                    <body>
                        <button data-testid="host-button">Host button</button>
                        <button data-testid="host-icon-button"><svg aria-hidden="true"></svg></button>
                        <code data-testid="host-code">Host code</code>
                    </body>
                </html>
                HTML);
        });

        $router->middleware(ProfileRequest::class)->get('/plain-json', fn () => response()->json(['ready' => true]));

        $router->match(['get', 'post', 'patch'], '/api/plain-json', fn () => response()->json(['source' => 'api']));

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
            dirname(__DIR__).'/Fixtures/views/original-response.blade.php',
            'original-response.txt',
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
            Http::fake(['api.example.test/*' => Http::response(['ready' => true])]);
            Http::get('https://api.example.test/v1/status');
            $component = app('livewire')->mount('host-functional-status', key: 'host-functional-exception');
            app(ProfileManager::class)->recordException(new \RuntimeException('Reported failure.'));

            return response('<!doctype html><html><body>Reported failure'.$component.'</body></html>');
        });

        $router->middleware(ProfileRequest::class)->get('/profiled-http-client', function () {
            Http::withToken('private-bearer')
                ->withHeaders(['X-Trace' => 'trace-1'])
                ->get('https://api.example.test/v1/patients?token=private-token&limit=5');

            try {
                Http::withHeaders(['Cookie' => 'session=private-cookie'])
                    ->post('https://down.example.test/v1/sync?api_key=private-key', [
                        'token' => 'private-body-token',
                        'patient' => 'visible-patient',
                    ]);
            } catch (ConnectionException) {
                // The application handled the failed dependency.
            }

            return response('<!doctype html><html><body>HTTP client</body></html>');
        });

        $router->middleware(ProfileRequest::class)->get('/profiled-http-client-rich', function () {
            $failedConnection = method_exists(Factory::class, 'failedConnection')
                ? Http::failedConnection('Connection refused')
                : fn ($request) => Create::rejectionFor(new ConnectException(
                    'Connection refused',
                    $request->toPsrRequest(),
                ));

            Http::fake([
                'api.recommendations.test/*' => function () {
                    usleep(275_000);

                    return Http::response(['recommendations' => ['debugging', 'profiling']], 200, [
                        'X-Upstream-Cache' => 'miss',
                    ]);
                },
                'api.healthy.test/*' => Http::response(null, 204),
                'api.validation.test/*' => Http::response([
                    'message' => 'The submitted data was invalid.',
                    'errors' => ['email' => ['The email must be valid.']],
                ], 422),
                'api.rate-limit.test/*' => Http::response(['message' => 'Too many requests.'], 429, [
                    'Retry-After' => '30',
                ]),
                'api.error.test/*' => Http::response(['message' => 'Service unavailable.'], 503),
                'api.down.test/*' => $failedConnection,
            ]);

            Http::withHeaders(['X-Debug-Request' => 'recommendations'])
                ->get('https://api.recommendations.test/v2/personalized/homepage?locale=en');
            Http::get('https://api.healthy.test/v1/status');
            Http::patch('https://api.validation.test/v1/team-members/42', [
                'email' => 'not-an-email',
            ]);
            Http::get('https://api.rate-limit.test/v1/downloads/today');
            Http::delete('https://api.error.test/v1/stale-cache/very-long-resource-identifier');

            try {
                Http::post('https://api.down.test/v1/webhooks/deliver', [
                    'event' => 'profile.ready',
                ]);
            } catch (ConnectionException) {
                // The application handled the failed dependency.
            }

            return response(<<<'HTML'
                <!doctype html>
                <html>
                    <head>
                        <meta name="viewport" content="width=device-width, initial-scale=1">
                        <title>HTTP client diagnostics</title>
                    </head>
                    <body>
                        <main><h1 data-testid="host-page">HTTP client diagnostics</h1></main>
                    </body>
                </html>
                HTML);
        });

        $router->middleware(ProfileRequest::class)->get('/profiled-queue', function () {
            Event::dispatch($this->queuedEvent(
                'job-1',
                new ProfiledJob('private queued value'),
                providerId: 9001,
            ));
            Bus::dispatchSync(new ProfiledJob('private sync value'));

            try {
                Bus::dispatchSync(new ProfiledFailingJob('private failed value'));
            } catch (\RuntimeException) {
                // The application handled the failed synchronous job.
            }

            return response('<!doctype html><html><body>Queue</body></html>');
        });

        $router->middleware(ProfileRequest::class)->get('/profiled-queued-communications', function () {
            $mailable = (new ProfiledMailable(
                subjectLine: 'Private queued subject',
                heading: 'Private queued heading',
                messageCopy: 'Private queued body',
            ))->to('private-recipient@example.test');
            $notification = new ProfiledNotification('private queued notification');
            $notifiables = collect([new ProfiledNotifiable('private-notifiable@example.test')]);

            Event::dispatch($this->queuedEvent(
                'mail-job-1',
                new SendQueuedMailable($mailable),
                queue: 'mail-delayed',
                delay: 30,
            ));
            Event::dispatch($this->queuedEvent(
                'notification-job-1',
                new SendQueuedNotifications($notifiables, $notification, ['mail']),
                queue: 'notifications',
                delay: 0,
            ));

            return response('<!doctype html><html><body>Queued communications</body></html>');
        });

        $router->middleware(ProfileRequest::class)->get('/profiled-after-response', function () {
            $deferred = static function (): void {
                usleep(80_000);
                DB::select('select 24 as deferred_mail');
                Mail::raw('Deferred body', fn ($message) => $message
                    ->to('deferred@example.test')
                    ->subject('Deferred mail'));
            };

            if (function_exists('defer')) {
                defer($deferred);
            } else {
                app()->terminating($deferred);
            }

            Bus::dispatchAfterResponse(new ProfiledAfterResponseMailJob);

            return response('<!doctype html><html><head><title>After response</title></head><body><main>Original response</main></body></html>');
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

        $router->middleware(ProfileRequest::class)->get('/profiled-mail-rich', function () {
            Mail::to('taylor@example.test')->send(new ProfiledMailable(
                subjectLine: 'Payment receipt #NS-1042',
                heading: 'Payment received',
                messageCopy: 'Thanks, Taylor. Your workspace subscription is paid and ready for the next billing period.',
                detailLabel: 'Total paid',
                detailValue: '$49.00',
                actionLabel: 'View receipt',
                attachment: [
                    'name' => 'receipt-NS-1042.pdf',
                    'body' => '%PDF-1.4 profiled receipt',
                    'mime' => 'application/pdf',
                ],
            ));
            Mail::to('alex@example.test')->send(new ProfiledMailable(
                subjectLine: 'Welcome to Northstar',
                heading: 'Your workspace is ready',
                messageCopy: 'Invite your team, connect your first project, and start tracking the work that matters.',
                detailLabel: 'Workspace',
                detailValue: 'Acme Studio',
                actionLabel: 'Open workspace',
            ));
            Mail::to('morgan@example.test')->send(new ProfiledMailable(
                subjectLine: 'Weekly account digest',
                heading: 'Your week at a glance',
                messageCopy: 'Three projects shipped, two reviews are waiting, and there were no failed deployments.',
                detailLabel: 'Reporting period',
                detailValue: 'August 17–23',
                includeHtml: false,
            ));

            return response(<<<'HTML'
                <!doctype html>
                <html>
                    <head>
                        <meta name="viewport" content="width=device-width, initial-scale=1">
                        <title>Mail diagnostics</title>
                    </head>
                    <body><main><h1 data-testid="host-page">Mail diagnostics</h1></main></body>
                </html>
                HTML);
        });

        $router->middleware(ProfileRequest::class)->get('/profiled-redis', function () {
            $connection = new ProfiledRedisConnection;
            Event::dispatch(new CommandExecuted('get', ['private-direct-key'], 1.25, $connection));
            Event::dispatch(new CommandExecuted('setex', ['private-cache-key', 60, 'private-cache-value'], 0.4, $connection));
            Event::dispatch($this->keyWrittenEvent());
            Event::dispatch(new CommandExecuted('flushdb', [], 0.5, $connection));

            if (class_exists(CacheFlushed::class)) {
                Event::dispatch(new CacheFlushed('redis', ['tenant:private-clinic']));
            }

            if (class_exists(CommandFailed::class)) {
                Event::dispatch(new CommandFailed('hget', ['private-hash', 'private-field'], new \RuntimeException('private Redis failure'), $connection));
            }

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

    private function queuedEvent(
        string $id,
        object $job,
        string $queue = 'emails',
        int $delay = 5,
        string|int|null $providerId = null,
    ): JobQueued
    {
        if (method_exists($job, 'onQueue')) {
            $job->onQueue($queue);
        }

        if (method_exists($job, 'delay')) {
            $job->delay($delay);
        }

        $payload = json_encode(['uuid' => $id, 'private' => 'queued payload'], JSON_THROW_ON_ERROR);
        $providerId ??= $id;

        if (property_exists(JobQueued::class, 'queue')) {
            return new JobQueued('redis', $queue, $providerId, $job, $payload, $delay);
        }

        return new JobQueued('redis', $providerId, $job, $payload);
    }

    private function keyWrittenEvent(): KeyWritten
    {
        $arguments = [
            'private-cache-key',
            'private-cache-value',
            60,
            ['tenant:private-clinic', 'patient:private-patient'],
        ];

        if (property_exists(CacheEvent::class, 'storeName')) {
            array_unshift($arguments, 'redis');
        }

        return new KeyWritten(...$arguments);
    }
}
