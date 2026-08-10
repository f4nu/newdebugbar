<?php

use Livewire\Drawer\Utils;
use Livewire\Livewire;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Tests\Fixtures\Livewire\DiagnosticsFixture;

beforeEach(function () {
    config(['app.debug' => true]);
    Livewire::component('diagnostics-fixture', DiagnosticsFixture::class);
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
