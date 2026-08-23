<?php

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\Create;
use Illuminate\Cache\Events\CacheEvent;
use Illuminate\Cache\Events\CacheFlushed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Redis\Events\CommandFailed;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use NewDebugBar\Http\Middleware\ProfileRequest;
use NewDebugBar\Presentation\ProfilePresenter;
use NewDebugBar\ProfileManager;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Tests\Fixtures\Models\ProfiledModel;

it('captures a local web request and its Laravel activity', function () {
    $route = app('router')->getRoutes()->match(request()->create('/profiled'));

    expect(app()->environment())->toBe('testing')
        ->and(app()->bound('middleware.disable'))->toBeFalse()
        ->and(config('newdebugbar.environments'))->toBe(['testing'])
        ->and(app('router')->gatherRouteMiddleware($route))->toContain(ProfileRequest::class);

    $response = $this->get('/profiled?token=visible', [
        'Accept' => 'text/html',
        'Authorization' => 'Bearer visible',
    ]);

    $response
        ->assertOk()
        ->assertHeader('X-NewDebugBar-Profile')
        ->assertSee('data-testid="host-page"', false)
        ->assertSee('id="newdebugbar"', false)
        ->assertSee('/__newdebugbar/assets/newdebugbar.css', false)
        ->assertSee('/__newdebugbar/assets/newdebugbar.js', false)
        ->assertSee('id="newdebugbar-critical-css"', false)
        ->assertDontSee('data-navigate-track', false)
        ->assertSee('wire:key="newdebugbar-toolbar"', false)
        ->assertSee('data-update-uri', false);

    expect(substr_count((string) $response->getContent(), '<!-- Livewire Styles -->'))->toBe(1)
        ->and(substr_count((string) $response->getContent(), 'data-update-uri='))->toBe(1);

    $files = File::files(config('newdebugbar.storage.path'));

    expect($files)->toHaveCount(1);

    $profile = json_decode(File::get($files[0]->getPathname()), true, flags: JSON_THROW_ON_ERROR);

    expect($profile)
        ->schema_version->toBe(1)
        ->environment->toBe('testing')
        ->sections->request->summary->method->toBe('GET')
        ->sections->request->summary->status->toBe(200)
        ->sections->request->payload->path->toBe('/profiled')
        ->sections->request->payload->url->not->toContain('visible')
        ->sections->request->payload->url->toContain('token=%5Bredacted%5D')
        ->sections->request->payload->query->token->toBe('[redacted]')
        ->sections->request->payload->headers->authorization->toBe('[redacted]')
        ->sections->request->payload->content_type->toContain('text/html')
        ->sections->request->payload->request_size_bytes->toBe(0)
        ->sections->request->payload->response_size_bytes->toBeGreaterThan(0)
        ->sections->request->payload->session_present->toBeFalse()
        ->sections->request->payload->authenticated->toBeFalse()
        ->sections->overview->payload->runtime->environment->toBe('testing')
        ->sections->overview->payload->runtime->laravel->toBe(app()->version())
        ->sections->overview->payload->drivers->database->toBe(config('database.default'))
        ->sections->queries->summary->count->toBeGreaterThanOrEqual(1)
        ->sections->models->summary->count->toBeGreaterThanOrEqual(1)
        ->sections->cache->summary->hits->toBe(1)
        ->sections->cache->summary->misses->toBe(1)
        ->sections->logs->summary->count->toBe(1)
        ->sections->events->summary->count->toBeGreaterThanOrEqual(1);

    expect(array_column($profile['sections']['models']['payload']['items'], 'event'))
        ->toContain('retrieved');

    expect($profile['metrics'])->not->toHaveKey('memory_mb')
        ->and($profile['sections']['request']['payload'])->not->toHaveKey('early_bootstrap_measured');

    expect($profile['sections']['logs']['payload']['items'][0]['callsite'])
        ->toMatchArray(['file' => 'tests/Support/DefinesTestApplication.php'])
        ->and($profile['sections']['logs']['payload']['items'][0]['stack'])->not->toBeEmpty();

    foreach ($profile['sections'] as $section) {
        expect($section['payload'])->not->toHaveKeys([
            'dropped',
            'retained',
            'total',
            'truncated',
            'transaction_retained',
            'transaction_dropped',
            'transaction_total',
        ]);

        foreach ($section['payload']['items'] ?? [] as $item) {
            expect($item['at_ms'])->toBeNumeric()->toBeGreaterThanOrEqual(0);
        }
    }
});

it('presents model activity as useful record loads', function () {
    $response = $this->get('/profiled-models', ['Accept' => 'text/html'])->assertOk();
    $stored = app(ProfileStore::class)->get($response->headers->get('X-NewDebugBar-Profile'));
    $models = app(ProfilePresenter::class)->present($stored)['sections']['models'];

    expect($models['summary'])
        ->retrieval_count->toBe(44)
        ->distinct_record_count->toBe(24)
        ->repeated_load_count->toBe(20)
        ->model_change_count->toBe(0)
        ->and(array_map(
            fn (array $group): array => [class_basename($group['model']), $group['load_count'], $group['record_count'], $group['repeated_load_count']],
            $models['payload']['model_groups'],
        ))->toBe([
            ['StudioJob', 14, 6, 8],
            ['Client', 10, 4, 6],
            ['ProofVersion', 8, 5, 3],
            ['User', 5, 2, 3],
            ['JobActivity', 7, 7, 0],
        ]);
});

it('captures bounded redacted outbound HTTP request and response evidence', function () {
    $failedConnection = method_exists(Factory::class, 'failedConnection')
        ? Http::failedConnection('private connection details')
        : fn ($request) => Create::rejectionFor(new ConnectException(
            'private connection details',
            $request->toPsrRequest(),
        ));

    Http::fake([
        'api.example.test/*' => Http::response(
            ['private' => 'response-body'],
            202,
            ['Set-Cookie' => 'session=private-response-cookie'],
        ),
        'down.example.test/*' => $failedConnection,
    ]);

    $response = $this->get('/profiled-http-client', ['Accept' => 'text/html'])->assertOk();
    $profile = app(ProfileStore::class)->get($response->headers->get('X-NewDebugBar-Profile'));
    $section = $profile['sections']['http_client'];

    expect($section['summary'])
        ->count->toBe(2)
        ->failed_count->toBe(1)
        ->duration_ms->toBeFloat()
        ->and($section['payload']['items'][0])
        ->method->toBe('GET')
        ->url->toBe('https://api.example.test/v1/patients?token=%5Bredacted%5D&limit=5')
        ->status->toBe(202)
        ->failed->toBeFalse()
        ->request->headers->Authorization->toBe('[redacted]')
        ->request->headers->{'X-Trace'}->toBe(['trace-1'])
        ->request->body->toBeNull()
        ->response->headers->{'Set-Cookie'}->toBe('[redacted]')
        ->response->body->toBe(['private' => 'response-body'])
        ->stack->not->toBeEmpty()
        ->and($section['payload']['items'][1])
        ->method->toBe('POST')
        ->url->toBe('https://down.example.test/v1/sync?api_key=%5Bredacted%5D')
        ->status->toBeNull()
        ->failed->toBeTrue()
        ->exception_class->toBe(ConnectionException::class)
        ->request->headers->Cookie->toBe('[redacted]')
        ->request->body->toBe([
            'token' => '[redacted]',
            'patient' => 'visible-patient',
        ])
        ->response->toBeNull()
        ->and(json_encode($section))->not->toContain(
            'private-token',
            'private-key',
            'private-bearer',
            'private-cookie',
            'private-response-cookie',
            'private-body-token',
            'connection details',
        );
});

it('captures queued dispatches and synchronous execution without job data', function () {
    $response = $this->get('/profiled-queue', ['Accept' => 'text/html'])->assertOk();
    $profile = app(ProfileStore::class)->get($response->headers->get('X-NewDebugBar-Profile'));
    $section = $profile['sections']['queue'];

    expect($section['summary'])
        ->count->toBe(3)
        ->queued_count->toBe(1)
        ->executed_count->toBe(2)
        ->failed_count->toBe(1)
        ->duration_ms->toBeFloat()
        ->and($section['payload']['items'][0])
        ->kind->toBe('queued')
        ->connection->toBe('redis')
        ->queue->toBe('emails')
        ->delay_seconds->toBe(5)
        ->and($section['payload']['items'][1])
        ->kind->toBe('executed')
        ->connection->toBe('sync')
        ->queue->toBe('sync')
        ->duration_ms->toBeGreaterThanOrEqual(0)
        ->and($section['payload']['items'][2])
        ->kind->toBe('failed')
        ->exception_class->toBe(RuntimeException::class)
        ->and(json_encode($section))->not->toContain('private queued value', 'queued payload', 'private sync value', 'private failed value', 'private failure message');
});

it('captures mail previews and notification shape by default', function () {
    $response = $this->get('/profiled-messages', ['Accept' => 'text/html'])->assertOk();
    $profile = app(ProfileStore::class)->get($response->headers->get('X-NewDebugBar-Profile'));
    $mail = $profile['sections']['mail'];
    $notifications = $profile['sections']['notifications'];

    expect($mail['summary'])
        ->count->toBe(1)
        ->recipient_count->toBe(2)
        ->attachment_count->toBe(1)
        ->duration_ms->toBeFloat()
        ->and($mail['payload']['items'][0])
        ->recipient_count->toBe(2)
        ->attachment_count->toBe(1)
        ->has_text->toBeTrue()
        ->status->toBe('sent')
        ->mailer->toBe('array')
        ->transport->toBe('array')
        ->transport_message_id->toBeString()
        ->callsite->file->toBe('tests/Support/DefinesTestApplication.php')
        ->stack->not->toBeEmpty()
        ->preview->subject->toBe('private subject')
        ->preview->from->toBe(['private-sender@example.test'])
        ->preview->to->toBe(['private-recipient@example.test'])
        ->preview->cc->toBe(['private-copy@example.test'])
        ->preview->text->toBe('private body')
        ->preview->attachments_omitted->toBe(1)
        ->preview->attachments->toHaveCount(1)
        ->and($notifications['summary'])
        ->count->toBe(2)
        ->sent_count->toBe(1)
        ->failed_count->toBe(1)
        ->and($notifications['payload']['items'][0])
        ->status->toBe('sent')
        ->channel->toBe('mail')
        ->and($notifications['payload']['items'][1])
        ->status->toBe('failed')
        ->channel->toBe('slack')
        ->and(json_encode([$mail, $notifications]))->not->toContain(
            'private attachment',
            'private notification data',
            'failure data',
        );
});

it('captures direct Redis commands and removes cache command duplicates', function () {
    $response = $this->get('/profiled-redis', ['Accept' => 'text/html'])->assertOk();
    $profile = app(ProfileStore::class)->get($response->headers->get('X-NewDebugBar-Profile'));
    $redis = $profile['sections']['redis'];
    $cache = $profile['sections']['cache'];
    $storeAware = property_exists(CacheEvent::class, 'storeName');
    $flushAware = class_exists(CacheFlushed::class);
    $failureAware = class_exists(CommandFailed::class);
    $expectedCommands = ['GET'];
    $expectedDuration = 1.25;

    if (! $storeAware) {
        $expectedCommands[] = 'SETEX';
        $expectedDuration += 0.4;
    }

    if (! $flushAware) {
        $expectedCommands[] = 'FLUSHDB';
        $expectedDuration += 0.5;
    }

    if ($failureAware) {
        $expectedCommands[] = 'HGET';
    }

    expect($redis['summary'])
        ->count->toBe(count($expectedCommands))
        ->duration_ms->toBe(round($expectedDuration, 2))
        ->failed_count->toBe($failureAware ? 1 : 0)
        ->and(array_column($redis['payload']['items'], 'command'))->toBe($expectedCommands)
        ->and(array_column($cache['payload']['items'], 'operation'))->toBe(
            $flushAware ? ['write', 'flush'] : ['write'],
        )
        ->and($redis['payload']['items'][0])
        ->command->toBe('GET')
        ->connection->toBe('default')
        ->key_count->toBe(1)
        ->key_hashes->toBe([substr(hash('sha256', 'private-direct-key'), 0, 16)])
        ->keys->toBe(['private-direct-key'])
        ->key_policy->toBe('full')
        ->and($cache['payload']['items'][0])
        ->tag_count->toBe(2)
        ->tag_hashes->toBe([
            substr(hash('sha256', 'tenant:private-clinic'), 0, 16),
            substr(hash('sha256', 'patient:private-patient'), 0, 16),
        ])
        ->key->toBe('private-cache-key')
        ->tags->toBe(['tenant:private-clinic', 'patient:private-patient'])
        ->and(json_encode([$redis, $cache]))->not->toContain(
            'private-cache-value',
            'private-field',
            'private Redis failure',
        );

    $failedRedis = collect($redis['payload']['items'])->firstWhere('command', 'HGET');

    if ($failureAware) {
        expect($failedRedis)
            ->failed->toBeTrue()
            ->exception_class->toBe(RuntimeException::class);
    } else {
        expect($failedRedis)->toBeNull();
    }
});

it('keeps direct Redis commands when a non Redis cache store emits a similar operation', function () {
    $response = $this->get('/profiled-redis-independent-cache', ['Accept' => 'text/html'])->assertOk();
    $profile = app(ProfileStore::class)->get($response->headers->get('X-NewDebugBar-Profile'));
    $redis = $profile['sections']['redis'];
    $cache = $profile['sections']['cache'];

    expect($redis['summary']['count'])->toBe(1)
        ->and($redis['payload']['items'][0])
        ->command->toBe('GET')
        ->key_hashes->toBe([substr(hash('sha256', 'private-direct-key'), 0, 16)])
        ->keys->toBe(['private-direct-key'])
        ->and($cache['summary']['misses'])->toBe(1);
});

it('hashes bounded cache and Redis keys under the explicit hash policy', function () {
    config()->set('newdebugbar.collection.key_policy', 'hash');

    $response = $this->get('/profiled-redis', ['Accept' => 'text/html'])->assertOk();
    $profile = app(ProfileStore::class)->get($response->headers->get('X-NewDebugBar-Profile'));
    $cacheWrite = collect($profile['sections']['cache']['payload']['items'])->firstWhere('operation', 'write');
    $cacheFlush = collect($profile['sections']['cache']['payload']['items'])->firstWhere('operation', 'flush');
    $redisGet = collect($profile['sections']['redis']['payload']['items'])->firstWhere('command', 'GET');

    expect($cacheWrite)
        ->key_policy->toBe('hash')
        ->key->toBeNull()
        ->tags->toBe([])
        ->and($redisGet)
        ->key_policy->toBe('hash')
        ->keys->toBe([])
        ->and(json_encode([$cacheWrite, $redisGet]))->not->toContain(
            'private-cache-key',
            'private-direct-key',
            'private-clinic',
            'private-patient',
        );

    if (class_exists(CacheFlushed::class)) {
        expect($cacheFlush)
            ->key_policy->toBe('hash')
            ->tags->toBe([]);
    } else {
        expect($cacheFlush)->toBeNull();
    }
});

it('isolates mutable collector state between application lifecycles', function () {
    $first = app(ProfileManager::class);

    $this->get('/profiled', ['Accept' => 'text/html'])
        ->assertOk()
        ->assertHeader('X-NewDebugBar-Profile');

    app()->forgetScopedInstances();
    $second = app(ProfileManager::class);

    expect($second)->not->toBe($first)
        ->and($second->isCollecting())->toBeFalse();

    $this->get('/profiled-next', ['Accept' => 'text/html'])
        ->assertOk()
        ->assertHeader('X-NewDebugBar-Profile');

    $profiles = collect(File::files(config('newdebugbar.storage.path')))
        ->map(fn ($file) => json_decode(File::get($file->getPathname()), true, flags: JSON_THROW_ON_ERROR));

    expect($profiles)->toHaveCount(2)
        ->and($profiles->pluck('sections.request.payload.path')->sort()->values()->all())
        ->toBe(['/profiled', '/profiled-next']);
});

it('captures nested input without retaining uploaded files', function () {
    $response = $this->post('/profiled-input', [
        'clinic' => [
            'name' => 'Example Clinic',
            'document' => UploadedFile::fake()->create('private.pdf'),
        ],
    ], ['Accept' => 'text/html'])->assertOk();

    $profile = app(ProfileStore::class)->get($response->headers->get('X-NewDebugBar-Profile'));

    expect($profile['sections']['request']['payload']['input'])->toBe([
        'clinic' => ['name' => 'Example Clinic'],
    ]);
});

it('profiles partial models without requiring their primary key', function () {
    Model::preventAccessingMissingAttributes();

    try {
        $this->get('/profiled-partial-model')
            ->assertOk()
            ->assertSee('Partial model');
    } finally {
        Model::preventAccessingMissingAttributes(false);
    }

    $files = File::files(config('newdebugbar.storage.path'));
    $profile = json_decode(File::get($files[0]->getPathname()), true, flags: JSON_THROW_ON_ERROR);
    $models = $profile['sections']['models']['payload']['items'];
    $partialModel = collect($models)->first(
        fn (array $model): bool => $model['model'] === ProfiledModel::class
            && $model['event'] === 'retrieved',
    );

    expect($partialModel)->not->toBeNull()
        ->and($partialModel['event'])->toBe('retrieved')
        ->and($partialModel['key'])->toBeNull();
});
