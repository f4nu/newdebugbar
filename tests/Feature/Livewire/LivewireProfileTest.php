<?php

use Illuminate\Support\Facades\File;
use Livewire\Drawer\Utils;
use Livewire\Livewire;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Tests\Fixtures\Livewire\DiagnosticsChildFixture;
use NewDebugBar\Tests\Fixtures\Livewire\DiagnosticsFixture;
use NewDebugBar\Tests\Fixtures\Livewire\DiagnosticsParentFixture;

beforeEach(function () {
    config(['app.debug' => true]);
    Livewire::component('diagnostics-fixture', DiagnosticsFixture::class);
    Livewire::component('diagnostics-parent', DiagnosticsParentFixture::class);
    Livewire::component('diagnostics-child', DiagnosticsChildFixture::class);
    app('router')->get('/livewire-diagnostics-fixture', function () {
        $component = app('livewire')->mount('diagnostics-parent', key: 'diagnostics-page');

        return response('<!doctype html><html><head><title>Livewire diagnostics</title></head><body>'.$component.'</body></html>');
    });
});

/** @return array<string, mixed> */
function profiledDiagnosticsSnapshot(): array
{
    $html = (string) app('livewire')->mount('diagnostics-fixture');

    return Utils::extractAttributeDataFromHtml($html, 'wire:snapshot');
}

/**
 * @param  array<string, mixed>  $snapshot
 * @param  array<string, mixed>  $updates
 * @param  list<array{method: string, params: array<array-key, mixed>}>  $calls
 * @return array{snapshot: string, updates: array<string, mixed>, calls: list<array{method: string, params: array<array-key, mixed>}>}
 */
function profiledDiagnosticsMessage(array $snapshot, array $updates = [], array $calls = []): array
{
    return [
        'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
        'updates' => $updates,
        'calls' => $calls,
    ];
}

/** @param array<string, mixed> $message
 * @return array<string, mixed>
 */
function postProfiledDiagnosticsMessage(array $message): array
{
    $response = test()->postJson(app('livewire')->getUpdateUri(), [
        'components' => [$message],
    ], ['X-Livewire' => '1']);

    $response->assertOk()->assertHeader('X-NewDebugBar-Profile');

    return app(ProfileStore::class)->get($response->headers->get('X-NewDebugBar-Profile'));
}

it('profiles one property update without storing a Livewire snapshot', function () {
    $snapshot = profiledDiagnosticsSnapshot();
    $response = $this->postJson(app('livewire')->getUpdateUri(), [
        'components' => [profiledDiagnosticsMessage($snapshot, ['search' => 'northline'])],
    ], ['X-Livewire' => '1']);

    $response->assertOk()->assertHeader('X-NewDebugBar-Profile');
    $profile = app(ProfileStore::class)->get($response->headers->get('X-NewDebugBar-Profile'));
    $livewire = $profile['sections']['livewire'];

    expect($profile)
        ->schema_version->toBe(1)
        ->id->toBe($response->headers->get('X-NewDebugBar-Profile'))
        ->sections->request->payload->input->snapshot_data_stored->toBeFalse()
        ->sections->request->payload->input->messages->toHaveCount(1)
        ->and(json_encode($profile['sections']['request']['payload']['input']))
        ->not->toContain('initial-secret', 'wire:snapshot', 'checksum')
        ->and($livewire)
        ->schema_version->toBe(1)
        ->profile_revision->toBe(1)
        ->summary->title->toBe('Updated search')
        ->summary->message_count->toBe(1)
        ->summary->action_count->toBe(1)
        ->summary->state_change_count->toBe(1)
        ->summary->result->toBe('rendered')
        ->payload->exchange->request_id->toBe($profile['id'])
        ->payload->exchange->title_confidence->toBe('inferred')
        ->payload->exchange->browser_clock->status->toBe('missing')
        ->payload->messages->toHaveCount(1)
        ->payload->messages->{0}->result->toBe('rendered')
        ->payload->actions->{0}->kind->toBe('property_update')
        ->payload->actions->{0}->property_paths->toBe(['search'])
        ->payload->actions->{0}->execution_status->toBe('observed')
        ->payload->components->{0}->id->toBe($snapshot['memo']['id'])
        ->payload->components->{0}->class->toBe(DiagnosticsFixture::class)
        ->payload->components->{0}->source->file->toBe('tests/Fixtures/Livewire/DiagnosticsFixture.php')
        ->payload->components->{0}->rendered->toBe('yes')
        ->payload->state_changes->{0}->path->toBe('search')
        ->payload->state_changes->{0}->before->toBe('')
        ->payload->state_changes->{0}->submitted->toBe('northline')
        ->payload->state_changes->{0}->server->toBe('northline')
        ->payload->state_changes->{0}->browser->status->toBe('unknown')
        ->payload->browser_trace->status->toBe('missing')
        ->payload->completeness->components->toBe('affected_only');

    expect($livewire['payload']['server_spans'])->not->toBeEmpty()
        ->and(json_encode($livewire))->not->toContain('initial-secret', 'wire:snapshot', 'checksum');
});

it('profiles one named action and links its server state change', function () {
    $snapshot = profiledDiagnosticsSnapshot();
    $response = $this->postJson(app('livewire')->getUpdateUri(), [
        'components' => [profiledDiagnosticsMessage(
            $snapshot,
            calls: [['method' => 'saveReview', 'params' => [5]]],
        )],
    ], ['X-Livewire' => '1']);

    $response->assertOk()->assertHeader('X-NewDebugBar-Profile');
    $profile = app(ProfileStore::class)->get($response->headers->get('X-NewDebugBar-Profile'));
    $livewire = $profile['sections']['livewire'];
    $action = $livewire['payload']['actions'][0];
    $change = $livewire['payload']['state_changes'][0];

    expect($livewire)
        ->summary->title->toBe('Ran saveReview')
        ->summary->message_count->toBe(1)
        ->summary->action_count->toBe(1)
        ->summary->state_change_count->toBe(1)
        ->payload->actions->{0}->kind->toBe('action')
        ->payload->actions->{0}->name->toBe('saveReview')
        ->payload->actions->{0}->parameters->toBe([5])
        ->payload->actions->{0}->execution_status->toBe('observed')
        ->payload->components->{0}->render_reason->kind->toBe('action')
        ->payload->components->{0}->render_reason->confidence->toBe('inferred')
        ->payload->state_changes->{0}->path->toBe('reviewScore')
        ->payload->state_changes->{0}->before->toBe(0)
        ->payload->state_changes->{0}->submitted->toBeNull()
        ->payload->state_changes->{0}->server->toBe(5)
        ->and($change['action_id'])->toBe($action['id'])
        ->and($change['caused_by'])->toBe([['type' => 'action', 'id' => $action['id']]])
        ->and($livewire['payload']['messages'][0]['action_ids'])->toBe([$action['id']])
        ->and($livewire['payload']['messages'][0]['state_change_ids'])->toBe([$change['id']]);
});

it('shows a secret property changed without retaining either value', function () {
    $profile = postProfiledDiagnosticsMessage(profiledDiagnosticsMessage(
        profiledDiagnosticsSnapshot(),
        ['password' => 'replacement-secret'],
    ));
    $change = $profile['sections']['livewire']['payload']['state_changes'][0];
    $encoded = json_encode($profile);

    expect($change)
        ->path->toBe('password')
        ->before->toBe('[redacted]')
        ->submitted->toBe('[redacted]')
        ->server->toBe('[redacted]')
        ->redacted->toBeTrue()
        ->and($encoded)->not->toContain('initial-secret', 'replacement-secret');
});

it('profiles an initial nested mount as an affected hierarchy', function () {
    $response = $this->get('/livewire-diagnostics-fixture')->assertOk()->assertHeader('X-NewDebugBar-Profile');
    $profile = app(ProfileStore::class)->get($response->headers->get('X-NewDebugBar-Profile'));
    $livewire = $profile['sections']['livewire'];
    $parent = collect($livewire['payload']['components'])->firstWhere('name', 'diagnostics-parent');
    $child = collect($livewire['payload']['components'])->firstWhere('name', 'diagnostics-child');

    expect($livewire)
        ->summary->title->toBe('Mounted diagnostics-parent')
        ->summary->message_count->toBe(2)
        ->summary->action_count->toBe(2)
        ->summary->component_count->toBe(2)
        ->summary->result->toBe('rendered')
        ->payload->exchange->kind->toBe('initial_mount')
        ->payload->messages->toHaveCount(2)
        ->payload->actions->toHaveCount(2)
        ->and(array_unique(array_column($livewire['payload']['actions'], 'kind')))->toBe(['initial_mount'])
        ->and($parent['parent_id'])->toBeNull()
        ->and($parent['key'])->toBe('diagnostics-page')
        ->and($parent['depth'])->toBe(0)
        ->and($child['parent_id'])->toBe($parent['id'])
        ->and($child['depth'])->toBe(1)
        ->and($livewire['payload']['completeness']['components'])->toBe('affected_only');
});

it('keeps a seventeen message batch distinct and bounded', function () {
    $messages = [];

    foreach (range(1, 17) as $index) {
        $messages[] = profiledDiagnosticsMessage(
            profiledDiagnosticsSnapshot(),
            calls: [['method' => 'saveReview', 'params' => [$index]]],
        );
    }

    $response = $this->postJson(app('livewire')->getUpdateUri(), ['components' => $messages], ['X-Livewire' => '1']);
    $response->assertOk()->assertHeader('X-NewDebugBar-Profile')->assertJsonCount(17, 'components');
    $profile = app(ProfileStore::class)->get($response->headers->get('X-NewDebugBar-Profile'));
    $livewire = $profile['sections']['livewire'];

    expect($livewire)
        ->summary->title->toBe('Livewire exchange')
        ->summary->message_count->toBe(17)
        ->summary->action_count->toBe(17)
        ->summary->component_count->toBe(17)
        ->summary->state_change_count->toBe(17)
        ->summary->truncated->toBeFalse()
        ->payload->messages->toHaveCount(17)
        ->payload->actions->toHaveCount(17)
        ->payload->components->toHaveCount(17)
        ->payload->state_changes->toHaveCount(17)
        ->and(collect($livewire['payload']['messages'])->pluck('component_id')->unique())->toHaveCount(17)
        ->and($profile['sections']['request']['payload']['input']['component_message_count'])->toBe(17);
});

it('keeps emitted and received event evidence separate', function () {
    $emitted = postProfiledDiagnosticsMessage(profiledDiagnosticsMessage(
        profiledDiagnosticsSnapshot(),
        calls: [['method' => 'announceCheckIn', 'params' => []]],
    ));
    $emittedEvent = $emitted['sections']['livewire']['payload']['events'][0];

    expect($emittedEvent)
        ->name->toBe('vendor-checked-in')
        ->source_component_id->toBe($emitted['sections']['livewire']['payload']['components'][0]['id'])
        ->mode->toBe('global')
        ->declared_target->toBeNull()
        ->observed_recipient_ids->toBe([])
        ->recipient_status->toBe('unknown');

    $childHtml = (string) app('livewire')->mount('diagnostics-child');
    $childSnapshot = Utils::extractAttributeDataFromHtml($childHtml, 'wire:snapshot');
    $received = postProfiledDiagnosticsMessage(profiledDiagnosticsMessage(
        $childSnapshot,
        calls: [[
            'method' => '__dispatch',
            'params' => ['vendor-checked-in', ['vendor' => 'Northline Ceramics']],
        ]],
    ));
    $receivedEvent = $received['sections']['livewire']['payload']['events'][0];

    expect($receivedEvent)
        ->name->toBe('vendor-checked-in')
        ->source_component_id->toBeNull()
        ->mode->toBe('received')
        ->declared_target->toBeNull()
        ->observed_recipient_ids->toBe([$childSnapshot['memo']['id']])
        ->recipient_status->toBe('observed');
});

it('records validation redirect download and renderless outcomes safely', function () {
    $validation = postProfiledDiagnosticsMessage(profiledDiagnosticsMessage(
        profiledDiagnosticsSnapshot(),
        calls: [['method' => 'validateReview', 'params' => []]],
    ));

    expect($validation['sections']['livewire']['payload']['messages'][0])
        ->result->toBe('validation_failed')
        ->validation_errors->toHaveKey('search');

    $redirect = postProfiledDiagnosticsMessage(profiledDiagnosticsMessage(
        profiledDiagnosticsSnapshot(),
        calls: [['method' => 'goToVendor', 'params' => []]],
    ));

    expect($redirect['sections']['livewire']['payload']['messages'][0])
        ->result->toBe('redirected')
        ->effects->redirect->toBe('/vendors?token=%5Bredacted%5D');

    $download = postProfiledDiagnosticsMessage(profiledDiagnosticsMessage(
        profiledDiagnosticsSnapshot(),
        calls: [['method' => 'downloadReport', 'params' => []]],
    ));
    $downloadJson = json_encode($download['sections']['livewire']);

    expect($download['sections']['livewire']['payload']['messages'][0])
        ->result->toBe('downloaded')
        ->effects->download->name->toBe('review-report.txt')
        ->effects->download->content_type->toContain('text/plain')
        ->effects->download->size_bytes->toBe(strlen('private report body'))
        ->effects->download->content_stored->toBeFalse()
        ->and($downloadJson)->not->toContain('private report body', base64_encode('private report body'));

    $renderless = postProfiledDiagnosticsMessage(profiledDiagnosticsMessage(
        profiledDiagnosticsSnapshot(),
        calls: [['method' => 'recordHeartbeat', 'params' => []]],
    ));

    expect($renderless['sections']['livewire']['payload']['messages'][0])
        ->result->toBe('renderless')
        ->and($renderless['sections']['livewire']['payload']['components'][0]['rendered'])->toBe('no');
});

it('adds compact Livewire attribution to work proven inside an action', function () {
    $profile = postProfiledDiagnosticsMessage(profiledDiagnosticsMessage(
        profiledDiagnosticsSnapshot(),
        calls: [['method' => 'saveReviewWithWork', 'params' => []]],
    ));
    $livewire = $profile['sections']['livewire']['payload'];
    $actionId = $livewire['actions'][0]['id'];
    $messageId = $livewire['messages'][0]['id'];
    $query = collect($profile['sections']['queries']['payload']['items'])->first(
        fn (array $item): bool => str_contains($item['sql'], 'review_score'),
    );
    $log = collect($profile['sections']['logs']['payload']['items'])->firstWhere('message', 'Review saved by Livewire.');

    expect($query['livewire'])
        ->exchange_id->toBe($livewire['exchange']['id'])
        ->message_id->toBe($messageId)
        ->action_id->toBe($actionId)
        ->component_id->toBe($livewire['components'][0]['id'])
        ->phase->toBe('call')
        ->and($log['livewire'])->toMatchArray($query['livewire']);
});

it('falls back visibly without debug timings and clears attribution afterward', function () {
    config(['app.debug' => false]);
    $profile = postProfiledDiagnosticsMessage(profiledDiagnosticsMessage(
        profiledDiagnosticsSnapshot(),
        calls: [['method' => 'saveReview', 'params' => [1]]],
    ));

    expect($profile['sections']['livewire']['payload'])
        ->server_spans->toBe([])
        ->completeness->server_spans->toBe('unknown')
        ->completeness->unknown_reasons->toContain('Livewire server timing evidence requires app.debug=true.');

    $ordinary = $this->get('/profiled')->assertOk()->assertHeader('X-NewDebugBar-Profile');
    $ordinaryProfile = app(ProfileStore::class)->get($ordinary->headers->get('X-NewDebugBar-Profile'));

    expect($ordinaryProfile['sections'])->not->toHaveKey('livewire')
        ->and($ordinaryProfile['sections']['queries']['payload']['items'][0] ?? [])->not->toHaveKey('livewire');
});

it('records a failed action and keeps its error attribution', function () {
    $snapshot = profiledDiagnosticsSnapshot();
    $response = $this->postJson(app('livewire')->getUpdateUri(), [
        'components' => [profiledDiagnosticsMessage(
            $snapshot,
            calls: [['method' => 'failReview', 'params' => []]],
        )],
    ], ['X-Livewire' => '1']);

    $response->assertStatus(500)->assertHeader('X-NewDebugBar-Profile');
    $profile = app(ProfileStore::class)->get($response->headers->get('X-NewDebugBar-Profile'));
    $livewire = $profile['sections']['livewire']['payload'];
    $exception = $profile['sections']['exceptions']['payload']['items'][0];

    expect($profile['sections']['livewire'])
        ->summary->result->toBe('failed')
        ->payload->messages->{0}->result->toBe('failed')
        ->and($exception['class'])->toBe(RuntimeException::class)
        ->and($exception['livewire'])
        ->exchange_id->toBe($livewire['exchange']['id'])
        ->message_id->toBe($livewire['messages'][0]['id'])
        ->action_id->toBe($livewire['actions'][0]['id'])
        ->component_id->toBe($snapshot['memo']['id'])
        ->phase->toBe('call');
});

it('preserves Livewire response bytes and application headers', function () {
    $snapshot = profiledDiagnosticsSnapshot();
    $payload = ['components' => [profiledDiagnosticsMessage(
        $snapshot,
        calls: [['method' => 'saveReview', 'params' => [5]]],
    )]];
    $profiled = $this->postJson(app('livewire')->getUpdateUri(), $payload, ['X-Livewire' => '1']);

    config(['newdebugbar.enabled' => false]);
    $plain = $this->postJson(app('livewire')->getUpdateUri(), $payload, ['X-Livewire' => '1']);

    $profiled->assertOk()->assertHeader('X-NewDebugBar-Profile');
    $plain->assertOk()->assertHeaderMissing('X-NewDebugBar-Profile');
    expect($profiled->getContent())->toBe($plain->getContent())
        ->and($profiled->json())->toBe($plain->json())
        ->and($profiled->headers->get('Content-Type'))->toBe($plain->headers->get('Content-Type'))
        ->and($profiled->getStatusCode())->toBe($plain->getStatusCode());
});

it('excludes toolbar updates from profiling and storage', function () {
    $host = $this->get('/profiled')->assertOk()->assertHeader('X-NewDebugBar-Profile');
    $profileId = $host->headers->get('X-NewDebugBar-Profile');
    $toolbarHtml = (string) app('livewire')->mount('newdebugbar.toolbar', ['profileId' => $profileId]);
    $snapshot = Utils::extractAttributeDataFromHtml($toolbarHtml, 'wire:snapshot');
    $storedBefore = count(File::files(config('newdebugbar.storage.path')));
    $response = $this->postJson(app('livewire')->getUpdateUri(), [
        'components' => [profiledDiagnosticsMessage(
            $snapshot,
            calls: [['method' => 'loadDetails', 'params' => []]],
        )],
    ], ['X-Livewire' => '1']);

    $response->assertOk()->assertHeaderMissing('X-NewDebugBar-Profile');

    expect(count(File::files(config('newdebugbar.storage.path'))))->toBe($storedBefore);
});
