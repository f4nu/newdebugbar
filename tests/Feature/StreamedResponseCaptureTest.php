<?php

use Illuminate\Http\Request;
use Livewire\Livewire;
use NewDebugBar\Collectors\ItemCollector;
use NewDebugBar\Livewire\DebugBar;
use NewDebugBar\Presentation\McpProfilePresenter;
use NewDebugBar\ProfileManager;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Support\Redactor;
use NewDebugBar\Support\StreamedProfileCapture;
use Symfony\Component\HttpFoundation\StreamedResponse;

it('does not retain streamed response profiles by default', function () {
    $response = $this->get('/streamed-response', ['Accept' => 'text/plain'])
        ->assertOk()
        ->assertHeaderMissing('X-NewDebugBar-Profile');

    expect(app(ProfileStore::class)->recent())->toBe([])
        ->and(app(ProfileManager::class)->isCollecting())->toBeFalse();

    $response->assertStreamedContent('streamed-body');

    expect(app(ProfileStore::class)->recent())->toBe([])
        ->and(app(ProfileManager::class)->isCollecting())->toBeFalse();
});

it('retains a completed streamed response without buffering its body', function () {
    config()->set('newdebugbar.capture_streamed', true);
    $response = $this->get('/streamed-response', ['Accept' => 'text/plain'])
        ->assertOk()
        ->assertHeaderMissing('X-NewDebugBar-Profile');

    expect(app(ProfileStore::class)->recent())->toBe([])
        ->and(app(ProfileManager::class)->isCollecting())->toBeTrue();

    $response->assertStreamedContent('streamed-body')
        ->assertHeaderMissing('X-NewDebugBar-Profile');

    $profiles = app(ProfileStore::class)->recent();
    $profile = $profiles[0];
    $request = $profile['sections']['request']['payload'];
    $messages = $profile['sections']['messages'];

    expect($profiles)->toHaveCount(1)
        ->and(app(ProfileManager::class)->isCollecting())->toBeFalse()
        ->and($request)
        ->path->toBe('/streamed-response')
        ->request_type->toBe('stream')
        ->response_size_bytes->toBeNull()
        ->stream_completed->toBeTrue()
        ->stream_body_captured->toBeFalse()
        ->and($messages['summary']['count'])->toBe(1)
        ->and($messages['payload']['items'][0])
        ->label->toBe('Stream callback completed')
        ->context->token->toBe('[redacted]')
        ->and(json_encode($profile))->not->toContain('streamed-body', 'private-stream-token');

    $mcp = app(McpProfilePresenter::class)->section($profile['id'], 'request', 0, 50);

    expect($mcp['data']['payload'])
        ->response_size_bytes->toBeNull()
        ->stream_completed->toBeTrue()
        ->stream_body_captured->toBeFalse();

    Livewire::test(DebugBar::class, ['profileId' => $profile['id']])
        ->call('loadDetails')
        ->assertSeeHtml('data-ndb-stream-boundary')
        ->assertSee('Saved after the response stream completed.')
        ->assertSee('Not measured')
        ->assertSee('buffered or inspected live.');
});

it('retains streamed JSON after its generator completes', function () {
    if (! method_exists(response(), 'streamJson')) {
        $this->markTestSkipped('Streamed JSON is not available in this Laravel version.');
    }

    config()->set('newdebugbar.capture_streamed', true);
    $response = $this->get('/streamed-json-response')
        ->assertOk()
        ->assertHeaderMissing('X-NewDebugBar-Profile');

    expect(app(ProfileStore::class)->recent())->toBe([]);

    $response->assertStreamedJsonContent(['items' => [['id' => 1], ['id' => 2]]]);
    $profile = app(ProfileStore::class)->recent()[0];

    expect($profile['sections']['request']['payload'])
        ->request_type->toBe('stream')
        ->content_type->toContain('application/json')
        ->stream_completed->toBeTrue()
        ->and($profile['sections']['messages']['payload']['items'][0]['label'])
        ->toBe('Streamed JSON callback completed')
        ->and(json_encode($profile))->not->toContain('private-json-token');
});

it('retains an event stream after its generator completes', function () {
    if (! method_exists(response(), 'eventStream')) {
        $this->markTestSkipped('Event streams are not available in this Laravel version.');
    }

    config()->set('newdebugbar.capture_streamed', true);
    $response = $this->get('/event-stream-response')
        ->assertOk()
        ->assertHeaderMissing('X-NewDebugBar-Profile');

    expect(app(ProfileStore::class)->recent())->toBe([]);

    expect($response->streamedContent())
        ->toContain("event: update\ndata: first event\n\n")
        ->toContain("event: update\ndata: </stream>\n\n");
    $profile = app(ProfileStore::class)->recent()[0];

    expect($profile['sections']['request']['payload'])
        ->request_type->toBe('stream')
        ->content_type->toContain('text/event-stream')
        ->stream_completed->toBeTrue()
        ->and($profile['sections']['messages']['payload']['items'][0]['label'])
        ->toBe('Event stream callback completed')
        ->and(json_encode($profile))->not->toContain('private-event-token');
});

it('retains a streamed download without changing its bytes or headers', function () {
    config()->set('newdebugbar.capture_streamed', true);
    $response = $this->get('/streamed-download')
        ->assertOk()
        ->assertDownload('streamed-report.txt')
        ->assertHeaderMissing('X-NewDebugBar-Profile')
        ->assertStreamedContent('streamed-download-body');
    $profile = app(ProfileStore::class)->recent()[0];

    expect($profile['sections']['request']['payload'])
        ->request_type->toBe('download')
        ->stream_completed->toBeTrue()
        ->stream_body_captured->toBeFalse()
        ->and($profile['sections']['messages']['payload']['items'][0]['label'])
        ->toBe('Streamed download callback completed')
        ->and(json_encode($profile))->not->toContain('streamed-download-body', 'private-download-token');
});

it('waits for Laravel termination when the callback finishes first', function () {
    $manager = new ProfileManager([], new Redactor);
    $store = app(ProfileStore::class);
    $capture = new StreamedProfileCapture($manager, $store);
    $request = Request::create('/production-order');
    $response = new StreamedResponse(static fn () => print 'unchanged');
    $manager->begin($request);

    expect($capture->prepare($request, $response))->toBeTrue();
    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toBe('unchanged')
        ->and($store->recent())->toBe([])
        ->and($manager->isCollecting())->toBeTrue();

    $capture->terminate($request, $response);

    expect($store->recent())->toHaveCount(1)
        ->and($manager->isCollecting())->toBeFalse();
});

it('preserves stream exceptions while retaining their completed profile', function () {
    $redactor = new Redactor;
    $manager = new ProfileManager([
        new ItemCollector($redactor, 10, 'exceptions', 'Exceptions'),
    ], $redactor);
    $store = app(ProfileStore::class);
    $capture = new StreamedProfileCapture($manager, $store);
    $request = Request::create('/failing-stream');
    $exception = new RuntimeException('Original stream failure.');
    $response = new StreamedResponse(static function () use ($exception): void {
        throw $exception;
    });
    $manager->begin($request);
    $capture->prepare($request, $response);
    $capture->terminate($request, $response);

    expect(fn () => $response->sendContent())->toThrow($exception::class, $exception->getMessage());
    $profile = $store->recent()[0];

    expect($profile['sections']['exceptions']['summary']['count'])->toBe(1)
        ->and($profile['sections']['request']['payload']['stream_completed'])->toBeTrue()
        ->and($manager->isCollecting())->toBeFalse();
});
