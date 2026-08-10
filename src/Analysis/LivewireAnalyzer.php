<?php

namespace NewDebugBar\Analysis;

/** Produces a small set of high-confidence findings from Livewire facts. */
final class LivewireAnalyzer
{
    private const LARGE_BATCH_MESSAGES = 10;

    /** @param array<string, mixed> $section @return list<array<string, mixed>> */
    public function analyze(array $section): array
    {
        $messages = array_values(array_filter(
            is_array(data_get($section, 'payload.messages')) ? $section['payload']['messages'] : [],
            'is_array',
        ));

        if (count($messages) < self::LARGE_BATCH_MESSAGES) {
            return [];
        }

        $actions = array_values(array_filter(
            is_array(data_get($section, 'payload.actions')) ? $section['payload']['actions'] : [],
            'is_array',
        ));
        $exchangeId = data_get($section, 'payload.exchange.id');

        return [[
            'rule_id' => 'livewire.large_batch',
            'severity' => 'info',
            'section' => 'livewire',
            'summary' => count($messages).' Livewire messages ran in one exchange.',
            'why' => 'The exchange carried at least '.self::LARGE_BATCH_MESSAGES.' component messages. This may be intentional, but it is enough work to review as one group.',
            'location' => null,
            'next' => 'Inspect the message and action links, then confirm that this amount of bundled work is expected.',
            'action' => ['label' => 'Inspect Livewire exchange', 'section' => 'livewire'],
            'evidence' => [
                'exchange_id' => is_string($exchangeId) ? $exchangeId : null,
                'message_count' => count($messages),
                'action_count' => count($actions),
                'threshold' => self::LARGE_BATCH_MESSAGES,
                'message_ids' => array_values(array_filter(array_column($messages, 'id'), 'is_string')),
                'action_ids' => array_values(array_filter(array_column($actions, 'id'), 'is_string')),
            ],
        ]];
    }
}
