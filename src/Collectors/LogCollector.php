<?php

namespace NewDebugBar\Collectors;

/** Collects log records and counts severe entries. */
final class LogCollector extends AbstractCollector
{
    public function record(array $item): void
    {
        $message = (string) ($item['message'] ?? '');

        if ($this->looksLikeMimeMessage($message)) {
            $item['message'] = 'Mail content captured. Open the Mail section for its preview.';
            $item['context'] = ['linked_section' => 'mail', 'collapsed' => 'mime_message'];
        }

        parent::record($item);
    }

    public function key(): string
    {
        return 'logs';
    }

    public function label(): string
    {
        return 'Logs';
    }

    public function summary(): array
    {
        return [
            ...parent::summary(),
            'errors' => $this->totals['errors'] ?? 0,
        ];
    }

    protected function track(array $item): void
    {
        if (in_array($item['level'] ?? null, ['error', 'critical', 'alert', 'emergency'], true)) {
            $this->totals['errors'] = ($this->totals['errors'] ?? 0) + 1;
        }
    }

    private function looksLikeMimeMessage(string $message): bool
    {
        return str_contains($message, 'MIME-Version:')
            && (str_contains($message, 'Message-ID:') || str_contains($message, 'Content-Type: multipart/'));
    }
}
