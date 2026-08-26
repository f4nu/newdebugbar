<?php

use NewDebugBar\Support\MailPreview;
use Symfony\Component\Mime\Email;

it('builds bounded html text eml and attachment previews', function () {
    $message = (new Email)
        ->from('sender@example.test')
        ->to('recipient@example.test')
        ->subject('Preview subject')
        ->text('Plain preview')
        ->html('<h1>HTML preview</h1>')
        ->attach('private attachment', 'private.txt', 'text/plain');

    $preview = (new MailPreview(maxBodyBytes: 1_000, maxRecipients: 10))->capture($message);

    expect($preview)
        ->subject->toBe('Preview subject')
        ->to->toBe(['recipient@example.test'])
        ->text->toBe('Plain preview')
        ->html->toBe('<h1>HTML preview</h1>')
        ->attachments_omitted->toBe(0)
        ->attachment_metadata_omitted->toBe(0)
        ->attachments->toBe([[
            'name' => 'private.txt',
            'content_type' => 'text/plain',
            'disposition' => 'attachment',
            'content_id' => null,
            'size_bytes' => 18,
            'body_base64' => base64_encode('private attachment'),
        ]])
        ->and($preview['eml'])
        ->toContain('Plain preview', 'HTML preview', 'private.txt', base64_encode('private attachment'))
        ->not->toContain('X-NewDebugBar-Attachments-Omitted');

    expect($preview['eml'])->toEndWith("\r\n");
});

it('keeps attachment metadata when the message exceeds its attachment budget', function () {
    $message = (new Email)
        ->from('sender@example.test')
        ->to('recipient@example.test')
        ->subject('Budgeted attachments')
        ->attach('first body', 'first.txt', 'text/plain')
        ->attach('second body', 'second.txt', 'text/plain');

    $preview = (new MailPreview(
        maxBodyBytes: 1_000,
        maxRecipients: 10,
        maxAttachmentBytes: strlen('first body'),
    ))->capture($message);

    expect($preview)
        ->attachments_omitted->toBe(1)
        ->attachment_metadata_omitted->toBe(0)
        ->attachments->toHaveCount(2)
        ->and($preview['attachments'][0])
        ->size_bytes->toBe(strlen('first body'))
        ->body_base64->toBe(base64_encode('first body'))
        ->and($preview['attachments'][1])
        ->size_bytes->toBe(strlen('second body'))
        ->body_base64->toBeNull()
        ->and($preview['eml'])
        ->toContain('first.txt', base64_encode('first body'), 'X-NewDebugBar-Attachments-Omitted: 1')
        ->not->toContain('second.txt', base64_encode('second body'));
});

it('bounds the inputs without cutting the serialized mime message', function () {
    $message = (new Email)
        ->from('sender@example.test')
        ->to('first@example.test', 'second@example.test')
        ->subject(str_repeat('s', 200))
        ->text(str_repeat('t', 200))
        ->html(str_repeat('h', 200));

    $preview = (new MailPreview(maxBodyBytes: 64, maxRecipients: 1))->capture($message);

    expect($preview)
        ->truncated->toBeTrue()
        ->to->toBe(['first@example.test'])
        ->addresses_omitted->toBe(1)
        ->attachment_metadata_omitted->toBe(0)
        ->and(strlen($preview['subject']))->toBeLessThanOrEqual(85)
        ->and($preview['text'])->toContain("\n[preview truncated]")
        ->not->toContain('\\n[preview truncated]')
        ->and($preview['eml'])->toEndWith("\r\n")
        ->not->toEndWith('[preview truncated]');
});

it('ignores unsupported mail message types', function () {
    expect((new MailPreview(maxBodyBytes: 1_000, maxRecipients: 10))->capture(new stdClass))->toBeNull();
});
