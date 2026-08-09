<?php

use NewDebugBar\Support\MailPreview;
use Symfony\Component\Mime\Email;

it('builds bounded attachment free html text and eml previews', function () {
    $message = (new Email)
        ->from('sender@example.test')
        ->to('recipient@example.test')
        ->subject('Preview subject')
        ->text('Plain preview')
        ->html('<h1>HTML preview</h1>')
        ->attach('private attachment', 'private.txt');

    $preview = (new MailPreview(maxBodyBytes: 1_000, maxRecipients: 10))->capture($message);

    expect($preview)
        ->subject->toBe('Preview subject')
        ->to->toBe(['recipient@example.test'])
        ->text->toBe('Plain preview')
        ->html->toBe('<h1>HTML preview</h1>')
        ->attachments_omitted->toBe(1)
        ->and($preview['eml'])
        ->toContain('Plain preview', 'HTML preview', 'X-NewDebugBar-Attachments-Omitted: 1')
        ->not->toContain('private attachment', 'private.txt');

    expect($preview['eml'])->toEndWith("\r\n");
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
        ->and(strlen($preview['subject']))->toBeLessThanOrEqual(85)
        ->and($preview['text'])->toContain("\n[preview truncated]")
        ->not->toContain('\\n[preview truncated]')
        ->and($preview['eml'])->toEndWith("\r\n")
        ->not->toEndWith('[preview truncated]');
});

it('ignores unsupported mail message types', function () {
    expect((new MailPreview(maxBodyBytes: 1_000, maxRecipients: 10))->capture(new stdClass))->toBeNull();
});
