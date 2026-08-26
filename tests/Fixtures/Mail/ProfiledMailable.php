<?php

namespace NewDebugBar\Tests\Fixtures\Mail;

use Illuminate\Mail\Mailable;

/** A configurable mailable used to exercise the mail inspector. */
final class ProfiledMailable extends Mailable
{
    /**
     * @param  array{name: string, body: string, mime: string}|null  $attachment
     */
    public function __construct(
        private readonly string $subjectLine,
        private readonly string $heading,
        private readonly string $messageCopy,
        private readonly ?string $detailLabel = null,
        private readonly ?string $detailValue = null,
        private readonly ?string $actionLabel = null,
        private readonly bool $includeHtml = true,
        private readonly ?array $attachment = null,
    ) {}

    public function build(): self
    {
        $mail = $this
            ->from('hello@northstar.test', 'Northstar')
            ->replyTo('support@northstar.test', 'Northstar Support')
            ->subject($this->subjectLine)
            ->with([
                'heading' => $this->heading,
                'messageCopy' => $this->messageCopy,
                'detailLabel' => $this->detailLabel,
                'detailValue' => $this->detailValue,
                'actionLabel' => $this->actionLabel,
            ])
            ->text('mail.profiled-text');

        if ($this->includeHtml) {
            $mail->view('mail.profiled-html');
        }

        if ($this->attachment !== null) {
            $mail->attachData(
                $this->attachment['body'],
                $this->attachment['name'],
                ['mime' => $this->attachment['mime']],
            );
        }

        return $mail;
    }
}
