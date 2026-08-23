<?php

namespace NewDebugBar\Support;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

/** Builds a bounded, attachment-free local mail preview. */
final class MailPreview
{
    public function __construct(
        private readonly int $maxBodyBytes,
        private readonly int $maxRecipients,
    ) {}

    /** @return array<string, mixed>|null */
    public function capture(mixed $message): ?array
    {
        if (! $message instanceof Email) {
            return null;
        }

        [$subject, $subjectTruncated] = $this->bounded($message->getSubject(), min(2_000, $this->maxBodyBytes));
        [$html, $htmlTruncated] = $this->bounded($message->getHtmlBody());
        [$text, $textTruncated] = $this->bounded($message->getTextBody());
        [$headers, $headersTruncated] = $this->bounded($message->getHeaders()->toString());
        $addressesOmitted = $this->addressesOmitted($message);
        $attachments = array_slice($message->getAttachments(), 0, $this->maxRecipients);
        $copy = $this->attachmentFreeCopy($message, $subject, $html, $text);

        return [
            'subject' => $subject,
            'from' => $this->addresses($message->getFrom()),
            'to' => $this->addresses($message->getTo()),
            'cc' => $this->addresses($message->getCc()),
            'bcc' => $this->addresses($message->getBcc()),
            'reply_to' => $this->addresses($message->getReplyTo()),
            'sender' => $message->getSender()?->toString(),
            'return_path' => $message->getReturnPath()?->toString(),
            'date' => $message->getDate()?->format(DATE_ATOM),
            'priority' => $message->getPriority(),
            'headers' => $headers,
            'attachments' => array_map($this->attachment(...), $attachments),
            'html' => $html,
            'text' => $text,
            // The inputs are bounded before serialization so the MIME document
            // stays valid instead of being cut through a header or body part.
            'eml' => $copy->toString(),
            'truncated' => $subjectTruncated || $htmlTruncated || $textTruncated || $headersTruncated || $addressesOmitted > 0,
            'attachments_omitted' => count($message->getAttachments()),
            'attachment_metadata_omitted' => max(0, count($message->getAttachments()) - count($attachments)),
            'addresses_omitted' => $addressesOmitted,
        ];
    }

    /** @return array{name: string, content_type: string, disposition: string, content_id: ?string} */
    private function attachment(DataPart $attachment): array
    {
        return [
            'name' => $attachment->getFilename() ?? $attachment->getName() ?? 'Attachment',
            'content_type' => $attachment->getContentType(),
            'disposition' => $attachment->getDisposition() ?? 'attachment',
            'content_id' => $attachment->hasContentId() ? $attachment->getContentId() : null,
        ];
    }

    private function attachmentFreeCopy(Email $message, ?string $subject, ?string $html, ?string $text): Email
    {
        $copy = (new Email)->subject($subject ?? '');

        foreach (array_slice($message->getFrom(), 0, $this->maxRecipients) as $address) {
            $copy->addFrom($address);
        }

        foreach (array_slice($message->getTo(), 0, $this->maxRecipients) as $address) {
            $copy->addTo($address);
        }

        foreach (array_slice($message->getCc(), 0, $this->maxRecipients) as $address) {
            $copy->addCc($address);
        }

        foreach (array_slice($message->getBcc(), 0, $this->maxRecipients) as $address) {
            $copy->addBcc($address);
        }

        foreach (array_slice($message->getReplyTo(), 0, $this->maxRecipients) as $address) {
            $copy->addReplyTo($address);
        }

        if ($text !== null) {
            $copy->text($text);
        }

        if ($html !== null) {
            $copy->html($html);
        }

        if ($message->getAttachments() !== []) {
            $copy->getHeaders()->addTextHeader(
                'X-NewDebugBar-Attachments-Omitted',
                (string) count($message->getAttachments()),
            );
        }

        return $copy;
    }

    /** @param list<Address> $addresses @return list<string> */
    private function addresses(array $addresses): array
    {
        return array_map(
            static fn (Address $address): string => $address->toString(),
            array_slice($addresses, 0, $this->maxRecipients),
        );
    }

    /** @return array{0: ?string, 1: bool} */
    private function bounded(?string $value, ?int $limit = null): array
    {
        if ($value === null) {
            return [null, false];
        }

        $limit ??= $this->maxBodyBytes;

        if (strlen($value) <= $limit) {
            return [$value, false];
        }

        return [mb_strcut($value, 0, $limit, 'UTF-8')."\n[preview truncated]", true];
    }

    private function addressesOmitted(Email $message): int
    {
        return array_sum(array_map(
            fn (array $addresses): int => max(0, count($addresses) - $this->maxRecipients),
            [
                $message->getFrom(),
                $message->getTo(),
                $message->getCc(),
                $message->getBcc(),
                $message->getReplyTo(),
            ],
        ));
    }
}
