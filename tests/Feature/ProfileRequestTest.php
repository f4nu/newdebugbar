<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use NewDebugBar\Contracts\Collector;
use NewDebugBar\Http\Controllers\AssetController;
use NewDebugBar\Http\Middleware\ProfileRequest;
use NewDebugBar\ProfileManager;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Support\AssetUrl;
use NewDebugBar\Support\BarInjector;
use NewDebugBar\Support\LivewireUpdateRecorder;
use NewDebugBar\Support\ProfileFinalizer;
use NewDebugBar\Support\Redactor;
use NewDebugBar\Support\RequestEligibility;
use NewDebugBar\Tests\ProfiledModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

it('captures a local web request and its Laravel activity', function () {
    $route = app('router')->getRoutes()->match(request()->create('/profiled'));

    expect(app()->environment())->toBe('testing')
        ->and(app()->bound('middleware.disable'))->toBeFalse()
        ->and(config('new-debug-bar.environments'))->toBe(['testing'])
        ->and(app('router')->gatherRouteMiddleware($route))->toContain(ProfileRequest::class);

    $response = $this->get('/profiled?token=visible', [
        'Accept' => 'text/html',
        'Authorization' => 'Bearer visible',
    ]);

    $response
        ->assertOk()
        ->assertHeader('X-New-Debug-Bar-Profile')
        ->assertSee('data-testid="host-page"', false)
        ->assertSee('id="new-debug-bar"', false)
        ->assertSee('/__new-debug-bar/assets/new-debug-bar.css', false)
        ->assertSee('/__new-debug-bar/assets/new-debug-bar.js', false)
        ->assertSee('id="new-debug-bar-critical-css"', false)
        ->assertDontSee('data-navigate-track', false)
        ->assertSee('wire:key="new-debug-bar-toolbar"', false)
        ->assertSee('data-update-uri', false);

    expect(substr_count((string) $response->getContent(), '<!-- Livewire Styles -->'))->toBe(1)
        ->and(substr_count((string) $response->getContent(), 'data-update-uri='))->toBe(1);

    $files = File::files(config('new-debug-bar.storage.path'));

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

    expect(array_column($profile['sections']['overview']['payload']['ecosystem'], 'key'))
        ->not->toContain('livewire');

    expect($profile['sections']['logs']['payload']['items'][0]['callsite'])
        ->toMatchArray(['file' => 'tests/TestCase.php'])
        ->and($profile['sections']['logs']['payload']['items'][0]['stack'])->not->toBeEmpty();

    foreach ($profile['sections'] as $section) {
        foreach ($section['payload']['items'] ?? [] as $item) {
            expect($item['at_ms'])->toBeFloat()->toBeGreaterThanOrEqual(0);
        }
    }
});

it('records initial application Livewire renders and then reports Livewire in the ecosystem', function () {
    $response = $this->get('/profiled-livewire', ['Accept' => 'text/html'])->assertOk();
    $profile = app(ProfileStore::class)->get($response->headers->get('X-New-Debug-Bar-Profile'));
    $section = $profile['sections']['livewire'];

    expect(substr_count((string) $response->getContent(), '<!-- Livewire Styles -->'))->toBe(1)
        ->and(substr_count((string) $response->getContent(), 'data-update-uri='))->toBe(1);

    expect($section['summary'])
        ->count->toBe(1)
        ->initial_render_count->toBe(1)
        ->update_count->toBe(0)
        ->component_count->toBe(1)
        ->and($section['payload']['items'][0])
        ->kind->toBe('initial')
        ->component->toBe('profiled-counter')
        ->duration_ms->toBeFloat()
        ->and(array_column($profile['sections']['overview']['payload']['ecosystem'], 'key'))
        ->toContain('livewire');
});

it('captures outbound HTTP results without private URLs or bodies', function () {
    Http::fake([
        'api.example.test/*' => Http::response(['private' => 'response-body'], 202),
        'down.example.test/*' => Http::failedConnection('private connection details'),
    ]);

    $response = $this->get('/profiled-http-client', ['Accept' => 'text/html'])->assertOk();
    $profile = app(ProfileStore::class)->get($response->headers->get('X-New-Debug-Bar-Profile'));
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
        ->and($section['payload']['items'][1])
        ->method->toBe('POST')
        ->url->toBe('https://down.example.test/v1/sync?api_key=%5Bredacted%5D')
        ->status->toBeNull()
        ->failed->toBeTrue()
        ->exception_class->toBe(ConnectionException::class)
        ->and(json_encode($section))->not->toContain('private-token', 'private-key', 'response-body', 'connection details');
});

it('captures queued dispatches and synchronous execution without job data', function () {
    $response = $this->get('/profiled-queue', ['Accept' => 'text/html'])->assertOk();
    $profile = app(ProfileStore::class)->get($response->headers->get('X-New-Debug-Bar-Profile'));
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

it('captures mail and notification shape without private content or identities', function () {
    $response = $this->get('/profiled-messages', ['Accept' => 'text/html'])->assertOk();
    $profile = app(ProfileStore::class)->get($response->headers->get('X-New-Debug-Bar-Profile'));
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
            'private body',
            'private subject',
            'private attachment',
            'private-sender',
            'private-recipient',
            'private-copy',
            'private notification data',
            'failure data',
        );
});

it('captures direct Redis commands and removes cache command duplicates', function () {
    $response = $this->get('/profiled-redis', ['Accept' => 'text/html'])->assertOk();
    $profile = app(ProfileStore::class)->get($response->headers->get('X-New-Debug-Bar-Profile'));
    $redis = $profile['sections']['redis'];
    $cache = $profile['sections']['cache'];

    expect($redis['summary'])
        ->count->toBe(2)
        ->duration_ms->toBe(1.25)
        ->failed_count->toBe(1)
        ->and($redis['payload']['items'][0])
        ->command->toBe('GET')
        ->connection->toBe('default')
        ->key_count->toBe(1)
        ->key_hashes->toBe([substr(hash('sha256', 'private-direct-key'), 0, 16)])
        ->and($redis['payload']['items'][1])
        ->command->toBe('HGET')
        ->failed->toBeTrue()
        ->exception_class->toBe(RuntimeException::class)
        ->and(array_column($cache['payload']['items'], 'operation'))->toContain('write', 'flush')
        ->and($cache['payload']['items'][0])
        ->tag_count->toBe(2)
        ->tag_hashes->toBe([
            substr(hash('sha256', 'tenant:private-clinic'), 0, 16),
            substr(hash('sha256', 'patient:private-patient'), 0, 16),
        ])
        ->tags->toBe([])
        ->and(json_encode([$redis, $cache]))->not->toContain(
            'private-direct-key',
            'private-cache-key',
            'private-cache-value',
            'private-clinic',
            'private-patient',
            'private-hash',
            'private-field',
            'private Redis failure',
        );
});

it('keeps direct Redis commands when a non Redis cache store emits a similar operation', function () {
    $response = $this->get('/profiled-redis-independent-cache', ['Accept' => 'text/html'])->assertOk();
    $profile = app(ProfileStore::class)->get($response->headers->get('X-New-Debug-Bar-Profile'));
    $redis = $profile['sections']['redis'];
    $cache = $profile['sections']['cache'];

    expect($redis['summary']['count'])->toBe(1)
        ->and($redis['payload']['items'][0])
        ->command->toBe('GET')
        ->key_hashes->toBe([substr(hash('sha256', 'private-direct-key'), 0, 16)])
        ->and($cache['summary']['misses'])->toBe(1);
});

it('reveals bounded cache and Redis keys only under the explicit full key policy', function () {
    config()->set('new-debug-bar.collection.key_policy', 'full');

    $response = $this->get('/profiled-redis', ['Accept' => 'text/html'])->assertOk();
    $profile = app(ProfileStore::class)->get($response->headers->get('X-New-Debug-Bar-Profile'));
    $cacheWrite = collect($profile['sections']['cache']['payload']['items'])->firstWhere('operation', 'write');
    $cacheFlush = collect($profile['sections']['cache']['payload']['items'])->firstWhere('operation', 'flush');
    $redisGet = collect($profile['sections']['redis']['payload']['items'])->firstWhere('command', 'GET');

    expect($cacheWrite)
        ->key_policy->toBe('full')
        ->key->toBe('private-cache-key')
        ->tags->toBe(['tenant:private-clinic', 'patient:private-patient'])
        ->and($cacheFlush)
        ->key_policy->toBe('full')
        ->tags->toBe(['tenant:private-clinic'])
        ->and($redisGet)
        ->key_policy->toBe('full')
        ->keys->toBe(['private-direct-key']);
});

it('isolates mutable collector state between application lifecycles', function () {
    $first = app(ProfileManager::class);

    $this->get('/profiled', ['Accept' => 'text/html'])
        ->assertOk()
        ->assertHeader('X-New-Debug-Bar-Profile');

    app()->forgetScopedInstances();
    $second = app(ProfileManager::class);

    expect($second)->not->toBe($first)
        ->and($second->isCollecting())->toBeFalse();

    $this->get('/profiled-next', ['Accept' => 'text/html'])
        ->assertOk()
        ->assertHeader('X-New-Debug-Bar-Profile');

    $profiles = collect(File::files(config('new-debug-bar.storage.path')))
        ->map(fn ($file) => json_decode(File::get($file->getPathname()), true, flags: JSON_THROW_ON_ERROR));

    expect($profiles)->toHaveCount(2)
        ->and($profiles->pluck('sections.request.payload.path')->sort()->values()->all())
        ->toBe(['/profiled', '/profiled-next']);
});

it('serves its compiled assets through local package routes', function () {
    $response = $this->get('/__new-debug-bar/assets/new-debug-bar.css')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/css; charset=UTF-8')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $stylesheet = File::get($response->baseResponse->getFile()->getPathname());

    expect($stylesheet)
        ->not->toContain('@layer theme')
        ->not->toContain('@layer utilities', '@keyframes pulse{')
        ->toContain('@keyframes ndb-debug-bar-pulse{');
});

it('injects assets into an html document that has no head', function () {
    $this->get('/html-without-head', ['Accept' => 'text/html'])
        ->assertOk()
        ->assertHeader('X-New-Debug-Bar-Profile')
        ->assertSee('<html><head><style id="new-debug-bar-critical-css"', false)
        ->assertSee('id="new-debug-bar"', false);
});

it('leaves response types that cannot host the bar untouched', function (string $path, int $status) {
    $response = $this->get($path, ['Accept' => 'text/html'])
        ->assertStatus($status)
        ->assertHeader('X-New-Debug-Bar-Profile')
        ->assertDontSee('id="new-debug-bar"', false);

    $profile = app(ProfileStore::class)->get($response->headers->get('X-New-Debug-Bar-Profile'));

    expect($profile)->not->toBeNull()
        ->and($profile['sections']['request']['payload']['request_type'])->toBe(match ($path) {
            '/download' => 'download',
            default => 'full_page',
        });
})->with([
    'html without a body' => ['/html-without-body', 200],
    'plain text' => ['/plain-text', 200],
    'download' => ['/download', 200],
]);

it('profiles an html error response', function () {
    $this->get('/failed-html', ['Accept' => 'text/html'])
        ->assertUnprocessable()
        ->assertHeader('X-New-Debug-Bar-Profile')
        ->assertSee('id="new-debug-bar"', false);
});

it('preserves a profile when the application throws', function () {
    $response = $this->get('/profiled-exception', ['Accept' => 'text/html'])
        ->assertInternalServerError()
        ->assertHeader('X-New-Debug-Bar-Profile')
        ->assertSee('id="new-debug-bar"', false);

    $profile = app(ProfileStore::class)->get($response->headers->get('X-New-Debug-Bar-Profile'));

    expect($profile['sections']['request']['summary']['status'])->toBe(500)
        ->and($profile['sections']['exceptions']['summary']['count'])->toBe(1)
        ->and($profile['sections']['exceptions']['payload']['items'][0]['class'])->toBe(RuntimeException::class)
        ->and($profile['sections']['exceptions']['payload']['items'][0]['file'])->toBe('tests/TestCase.php')
        ->and($profile['sections']['exceptions']['payload']['items'][0])->not->toHaveKey('trace')
        ->and($profile['sections']['exceptions']['payload']['items'][0]['frames']['application'])->not->toBeEmpty()
        ->and($profile['sections']['exceptions']['payload']['items'][0]['source']['lines'])->not->toBeEmpty()
        ->and(app(ProfileManager::class)->isCollecting())->toBeFalse();
});

it('rejects unknown and unsafe package assets', function () {
    $this->get('/__new-debug-bar/assets/unknown.txt')->assertNotFound();

    expect(fn () => app(AssetController::class)('../composer.json'))
        ->toThrow(NotFoundHttpException::class)
        ->and(fn () => app(AssetUrl::class)->for('../composer.json'))
        ->toThrow(RuntimeException::class);
});

it('profiles JSON without changing its body or injecting the toolbar', function () {
    $response = $this->getJson('/plain-json')
        ->assertOk()
        ->assertExactJson(['ready' => true])
        ->assertHeader('X-New-Debug-Bar-Profile')
        ->assertDontSee('id="new-debug-bar"', false);

    $profile = app(ProfileStore::class)->get($response->headers->get('X-New-Debug-Bar-Profile'));

    expect($profile['sections']['request']['payload']['request_type'])->toBe('json')
        ->and($profile['sections']['request']['payload']['response_size_bytes'])
        ->toBe(strlen(json_encode(['ready' => true])));
});

it('profiles API AJAX redirect streamed and binary responses without body injection', function () {
    $api = $this->getJson('/api/plain-json')
        ->assertOk()
        ->assertExactJson(['source' => 'api'])
        ->assertHeader('X-New-Debug-Bar-Profile');

    $ajax = $this->get('/ajax-fragment', [
        'Accept' => 'text/html',
        'X-Requested-With' => 'XMLHttpRequest',
    ])
        ->assertOk()
        ->assertContent('<div data-fragment>Search result</div>')
        ->assertHeader('X-New-Debug-Bar-Profile')
        ->assertDontSee('id="new-debug-bar"', false);

    $redirect = $this->get('/profile-redirect', ['Accept' => 'text/html'])
        ->assertRedirect('/profiled')
        ->assertHeader('X-New-Debug-Bar-Profile')
        ->assertDontSee('id="new-debug-bar"', false);

    $stream = $this->get('/streamed-response', ['Accept' => 'text/plain'])
        ->assertOk()
        ->assertHeader('X-New-Debug-Bar-Profile')
        ->assertStreamedContent('streamed-body');

    $binary = $this->get('/binary-response')
        ->assertOk()
        ->assertDownload('profiled-counter.txt')
        ->assertHeader('X-New-Debug-Bar-Profile');

    $profiles = collect([
        'json' => $api,
        'ajax' => $ajax,
        'redirect' => $redirect,
        'stream' => $stream,
        'download' => $binary,
    ])->map(fn ($response) => app(ProfileStore::class)->get(
        $response->headers->get('X-New-Debug-Bar-Profile'),
    ));

    expect($profiles->map(fn (array $profile): string => $profile['sections']['request']['payload']['request_type'])->all())
        ->toBe([
            'json' => 'json',
            'ajax' => 'ajax',
            'redirect' => 'redirect',
            'stream' => 'stream',
            'download' => 'download',
        ]);
});

it('captures nested input without retaining uploaded files', function () {
    $response = $this->post('/profiled-input', [
        'clinic' => [
            'name' => 'Example Clinic',
            'document' => UploadedFile::fake()->create('private.pdf'),
        ],
    ], ['Accept' => 'text/html'])->assertOk();

    $profile = app(ProfileStore::class)->get($response->headers->get('X-New-Debug-Bar-Profile'));

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

    $files = File::files(config('new-debug-bar.storage.path'));
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

it('returns the application response when a collector fails', function () {
    app('livewire')->flushState();

    $manager = new ProfileManager(
        [new CollectorThatFailsDuringSummary],
        $this->app->make(Redactor::class),
    );
    $this->app->instance(ProfileManager::class, $manager);

    $this->get('/profiled-collector-failure')
        ->assertOk()
        ->assertContent('<!doctype html><html><body>Application response</body></html>')
        ->assertHeaderMissing('X-New-Debug-Bar-Profile');

    expect($manager->isCollecting())->toBeFalse();
});

it('discards request state when profiling setup fails', function () {
    $manager = app(ProfileManager::class);
    $middleware = new ProfileRequest(
        $manager,
        app(RequestEligibility::class),
    );
    $request = Request::create('/setup-failure', 'POST', [
        'value' => new StringableThatFails,
    ], server: ['HTTP_ACCEPT' => 'text/html']);

    $response = $middleware->handle(
        $request,
        fn () => new Response('<html><body>Application response</body></html>'),
    );

    expect($response->getContent())->toBe('<html><body>Application response</body></html>')
        ->and($manager->isCollecting())->toBeFalse();
});

it('discards request state when Livewire response collection fails', function () {
    $manager = new ProfileManager(
        [new CollectorThatFailsDuringRecord],
        app(Redactor::class),
    );
    $request = Request::create('/livewire/update', 'POST');
    $manager->begin($request);
    $request->headers->set('X-Livewire', 'true');
    $request->request->set('components', [[
        'snapshot' => json_encode(['memo' => ['name' => 'application-counter']], JSON_THROW_ON_ERROR),
        'updates' => [],
        'calls' => [],
    ]]);
    $response = new JsonResponse(['components' => [[
        'snapshot' => json_encode(['memo' => ['errors' => []]], JSON_THROW_ON_ERROR),
        'effects' => [],
    ]]]);
    $finalizer = new ProfileFinalizer(
        $manager,
        app(ProfileStore::class),
        app(BarInjector::class),
        app(RequestEligibility::class),
        new LivewireUpdateRecorder($manager),
    );

    $finalizer->handle(new RequestHandled($request, $response));

    expect($manager->isCollecting())->toBeFalse()
        ->and($response->headers->has('X-New-Debug-Bar-Profile'))->toBeFalse();
});

final class CollectorThatFailsDuringSummary implements Collector
{
    public function key(): string
    {
        return 'failing';
    }

    public function label(): string
    {
        return 'Failing';
    }

    public function reset(): void {}

    public function record(array $item): void {}

    public function summary(): array
    {
        throw new RuntimeException('Collector failed.');
    }

    public function payload(): array
    {
        return ['items' => [], 'dropped' => 0];
    }
}

final class CollectorThatFailsDuringRecord implements Collector
{
    public function key(): string
    {
        return 'livewire';
    }

    public function label(): string
    {
        return 'Livewire';
    }

    public function reset(): void {}

    public function record(array $item): void
    {
        throw new RuntimeException('Collector failed.');
    }

    public function summary(): array
    {
        return ['count' => 0];
    }

    public function payload(): array
    {
        return ['items' => [], 'dropped' => 0];
    }
}

final class StringableThatFails
{
    public function __toString(): string
    {
        throw new RuntimeException('String conversion failed.');
    }
}
