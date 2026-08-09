<?php

namespace NewDebugBar\Collectors;

use NewDebugBar\Support\Redactor;

/** Captures mail shape and bounded, attachment-free previews. */
final class MailCollector extends AbstractCollector
{
    /** @var array<int, int> */
    private array $startedAt = [];

    public function __construct(Redactor $redactor, int $maxItems)
    {
        parent::__construct($redactor, $maxItems);
    }

    public function key(): string
    {
        return 'mail';
    }

    public function label(): string
    {
        return 'Mail';
    }

    public function reset(): void
    {
        parent::reset();
        $this->startedAt = [];
    }

    public function record(array $item): void
    {
        $messageId = (int) ($item['message_id'] ?? 0);
        $phase = $item['phase'] ?? 'sent';
        unset($item['message_id'], $item['phase']);

        if ($phase === 'sending') {
            if ($messageId > 0) {
                $this->startedAt[$messageId] = hrtime(true);
            }

            return;
        }

        $startedAt = $this->startedAt[$messageId] ?? null;
        $item['duration_ms'] = $startedAt === null ? 0.0 : round((hrtime(true) - $startedAt) / 1_000_000, 2);
        unset($this->startedAt[$messageId]);
        $preview = $item['preview'] ?? null;
        unset($item['preview']);

        /** @var array<string, mixed> $safeItem */
        $safeItem = $this->redactor->clean($item);

        if (is_array($preview)) {
            $safeItem['preview'] = $preview;
        }

        $this->track($safeItem);

        if (count($this->items) >= $this->maxItems) {
            $this->dropped++;

            return;
        }

        $this->items[] = $safeItem;
    }

    public function summary(): array
    {
        return [
            ...parent::summary(),
            'recipient_count' => (int) ($this->totals['recipient_count'] ?? 0),
            'attachment_count' => (int) ($this->totals['attachment_count'] ?? 0),
            'duration_ms' => round($this->totals['duration_ms'] ?? 0, 2),
        ];
    }

    protected function track(array $item): void
    {
        $this->totals['recipient_count'] = ($this->totals['recipient_count'] ?? 0) + (int) ($item['recipient_count'] ?? 0);
        $this->totals['attachment_count'] = ($this->totals['attachment_count'] ?? 0) + (int) ($item['attachment_count'] ?? 0);
        $this->totals['duration_ms'] = ($this->totals['duration_ms'] ?? 0) + (float) ($item['duration_ms'] ?? 0);
    }
}
