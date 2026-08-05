<?php

use Livewire\Livewire;
use NewDebugBar\Livewire\DebugBar;
use NewDebugBar\Storage\ProfileStore;

it('stores and serves explicitly enabled local previews without attachments', function () {
    config()->set('new-debug-bar.mail_preview.enabled', true);
    $response = $this->get('/profiled-messages', ['Accept' => 'text/html'])->assertOk();
    $profileId = $response->headers->get('X-New-Debug-Bar-Profile');
    $profile = app(ProfileStore::class)->get($profileId);
    $preview = $profile['sections']['mail']['payload']['items'][0]['preview'];

    expect($preview)
        ->subject->toBe('private subject')
        ->from->toBe(['private-sender@example.test'])
        ->to->toBe(['private-recipient@example.test'])
        ->cc->toBe(['private-copy@example.test'])
        ->text->toBe('private body')
        ->attachments_omitted->toBe(1)
        ->and($preview['eml'])->not->toContain('private attachment', 'private.txt');

    $profile['sections']['mail']['payload']['items'][0]['preview']['html'] = '<script>window.top.location="https://example.test"</script><h1>Safe preview</h1>';
    app(ProfileStore::class)->put($profile);

    $this->get(route('new-debug-bar.mail-preview', [
        'profile' => $profileId,
        'index' => 0,
        'format' => 'html',
    ]))
        ->assertOk()
        ->assertHeader('Content-Security-Policy', "sandbox; default-src 'none'; img-src data:; style-src 'unsafe-inline'; form-action 'none'; base-uri 'none'; frame-ancestors 'none'")
        ->assertSee('Safe preview');

    $textResponse = $this->get(route('new-debug-bar.mail-preview', [
        'profile' => $profileId,
        'index' => 0,
        'format' => 'text',
    ]));
    $textResponse->assertOk()->assertSeeText('private body');
    expect($textResponse->headers->get('Cache-Control'))->toContain('no-store', 'private');

    $this->get(route('new-debug-bar.mail-preview', [
        'profile' => $profileId,
        'index' => 0,
        'format' => 'eml',
    ]))
        ->assertOk()
        ->assertHeader('Content-Type', 'message/rfc822')
        ->assertHeader('Content-Disposition', 'attachment; filename="message-1.eml"')
        ->assertDontSee('private attachment');

    Livewire::test(DebugBar::class, ['profileId' => $profileId])
        ->call('loadDetails')
        ->assertSee('Download .eml')
        ->assertSee('Open text preview');
});

it('keeps preview routes unavailable when capture is disabled', function () {
    $response = $this->get('/profiled-messages', ['Accept' => 'text/html'])->assertOk();
    $profileId = $response->headers->get('X-New-Debug-Bar-Profile');

    expect(app(ProfileStore::class)->get($profileId)['sections']['mail']['payload']['items'][0])
        ->not->toHaveKey('preview');

    $this->get('/__new-debug-bar/mail/'.$profileId.'/0/text')->assertNotFound();
});
