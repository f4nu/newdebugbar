<?php

use Livewire\Livewire;
use NewDebugBar\Livewire\DebugBar;
use NewDebugBar\Storage\ProfileStore;

it('stores and serves local previews without attachments by default', function () {
    $response = $this->get('/profiled-messages', ['Accept' => 'text/html'])->assertOk();
    $profileId = $response->headers->get('X-NewDebugBar-Profile');
    $profile = app(ProfileStore::class)->get($profileId);
    $preview = $profile['sections']['mail']['payload']['items'][0]['preview'];

    expect($preview)
        ->subject->toBe('private subject')
        ->from->toBe(['private-sender@example.test'])
        ->to->toBe(['private-recipient@example.test'])
        ->cc->toBe(['private-copy@example.test'])
        ->text->toBe('private body')
        ->attachments_omitted->toBe(1)
        ->attachments->toBe([[
            'name' => 'private.txt',
            'content_type' => 'application/octet-stream',
            'disposition' => 'attachment',
            'content_id' => null,
        ]])
        ->and($preview['eml'])->not->toContain('private attachment', 'private.txt');

    $profile['sections']['mail']['payload']['items'][0]['preview']['html'] = '<script>window.top.location="https://example.test"</script><h1>Safe preview</h1>';
    app(ProfileStore::class)->put($profile);

    $htmlResponse = $this->get(route('newdebugbar.mail-preview', [
        'profile' => $profileId,
        'index' => 0,
        'format' => 'html',
    ]));
    $htmlResponse
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertSee('Safe preview')
        ->assertSee('newdebugbar:mail-preview-height', false);
    expect($htmlResponse->headers->get('Content-Security-Policy'))
        ->toStartWith("sandbox allow-scripts; default-src 'none'; img-src data:; style-src 'unsafe-inline'; script-src 'nonce-")
        ->toContain("'; script-src-attr 'none'; form-action 'none'; base-uri 'none'; frame-ancestors 'self'");

    $textResponse = $this->get(route('newdebugbar.mail-preview', [
        'profile' => $profileId,
        'index' => 0,
        'format' => 'text',
    ]));
    $textResponse
        ->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
        ->assertSeeText('private body')
        ->assertSee('newdebugbar:mail-preview-scroll', false);
    expect($textResponse->headers->get('Cache-Control'))->toContain('no-store', 'private');

    $this->get(route('newdebugbar.mail-preview', [
        'profile' => $profileId,
        'index' => 0,
        'format' => 'eml',
    ]))
        ->assertOk()
        ->assertHeader('Content-Type', 'message/rfc822')
        ->assertHeader('Content-Disposition', 'attachment; filename="message-1.eml"')
        ->assertDontSee('private attachment');

    Livewire::test(DebugBar::class, ['profileId' => $profileId])
        ->call('loadSection', 'mail')
        ->assertSee('Download .EML')
        ->assertSee('Open preview');
});

it('rejects profile identifiers that storage cannot read', function () {
    $this->get('/__newdebugbar/mail/550e8400-e29b-11d4-a716-446655440000/0/text')
        ->assertNotFound();
});
