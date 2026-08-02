<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use NewDebugBar\Contracts\Collector;
use NewDebugBar\Http\Controllers\AssetController;
use NewDebugBar\Http\Middleware\ProfileRequest;
use NewDebugBar\ProfileManager;
use NewDebugBar\Support\AssetUrl;
use NewDebugBar\Support\Redactor;
use NewDebugBar\Tests\ProfiledModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

it('captures a local web request and its Laravel activity', function () {
    $route = app('router')->getRoutes()->match(request()->create('/profiled'));

    expect(app()->environment())->toBe('testing')
        ->and(app()->bound('middleware.disable'))->toBeFalse()
        ->and(config('new-debug-bar.environments'))->toBe(['testing'])
        ->and(app('router')->getMiddlewareGroups()['web'])->toContain(ProfileRequest::class)
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
        ->assertSee('data-navigate-track="reload"', false)
        ->assertSee('data-update-uri', false);

    $files = File::files(config('new-debug-bar.storage.path'));

    expect($files)->toHaveCount(1);

    $profile = json_decode(File::get($files[0]->getPathname()), true, flags: JSON_THROW_ON_ERROR);

    expect($profile)
        ->environment->toBe('testing')
        ->sections->request->summary->method->toBe('GET')
        ->sections->request->summary->status->toBe(200)
        ->sections->request->payload->query->token->toBe('[redacted]')
        ->sections->request->payload->headers->authorization->toBe('[redacted]')
        ->sections->queries->summary->count->toBeGreaterThanOrEqual(1)
        ->sections->models->summary->count->toBeGreaterThanOrEqual(1)
        ->sections->cache->summary->hits->toBe(1)
        ->sections->cache->summary->misses->toBe(1)
        ->sections->logs->summary->count->toBe(1)
        ->sections->events->summary->count->toBeGreaterThanOrEqual(1);

    expect(array_column($profile['sections']['models']['payload']['items'], 'event'))
        ->toContain('retrieved');
});

it('serves its compiled assets through local package routes', function () {
    $response = $this->get('/__new-debug-bar/assets/new-debug-bar.css')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/css; charset=UTF-8')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $stylesheet = File::get($response->baseResponse->getFile()->getPathname());

    expect($stylesheet)
        ->not->toContain('@layer theme')
        ->not->toContain('@layer utilities');
});

it('injects assets into an html document that has no head', function () {
    $this->get('/html-without-head', ['Accept' => 'text/html'])
        ->assertOk()
        ->assertHeader('X-New-Debug-Bar-Profile')
        ->assertSee('<html><head><link', false)
        ->assertSee('id="new-debug-bar"', false);
});

it('leaves response types that cannot host the bar untouched', function (string $path, int $status) {
    $this->get($path, ['Accept' => 'text/html'])
        ->assertStatus($status)
        ->assertHeaderMissing('X-New-Debug-Bar-Profile')
        ->assertDontSee('id="new-debug-bar"', false);

    expect(File::exists(config('new-debug-bar.storage.path')))->toBeFalse();
})->with([
    'html without a body' => ['/html-without-body', 200],
    'plain text' => ['/plain-text', 200],
    'download' => ['/download', 200],
    'failed response' => ['/failed-html', 422],
]);

it('discards a profile when the application throws', function () {
    $this->get('/profiled-exception', ['Accept' => 'text/html'])
        ->assertInternalServerError();

    expect(File::exists(config('new-debug-bar.storage.path')))->toBeFalse()
        ->and(app(ProfileManager::class)->isCollecting())->toBeFalse();
});

it('rejects unknown and unsafe package assets', function () {
    $this->get('/__new-debug-bar/assets/unknown.txt')->assertNotFound();

    expect(fn () => app(AssetController::class)('../composer.json'))
        ->toThrow(NotFoundHttpException::class)
        ->and(fn () => app(AssetUrl::class)->for('../composer.json'))
        ->toThrow(RuntimeException::class);
});

it('does not profile non html traffic', function () {
    $this->getJson('/plain-json')->assertOk();

    expect(File::exists(config('new-debug-bar.storage.path')))->toBeFalse();
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
    $manager = new ProfileManager(
        [new CollectorThatFailsDuringSummary],
        $this->app->make(Redactor::class),
    );
    $this->app->instance(ProfileManager::class, $manager);

    $this->get('/profiled-collector-failure')
        ->assertOk()
        ->assertSee('Application response')
        ->assertHeaderMissing('X-New-Debug-Bar-Profile');

    expect($manager->isCollecting())->toBeFalse();
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
