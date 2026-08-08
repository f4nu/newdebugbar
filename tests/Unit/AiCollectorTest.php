<?php

use NewDebugBar\Collectors\AiCollector;
use NewDebugBar\Support\Redactor;

it('keeps AI metadata and usage while hiding content by default', function () {
    $collector = new AiCollector(new Redactor, maxItems: 10);

    $collector->startInvocation('run-1', [
        'agent' => 'App\\Ai\\SupportAgent',
        'model' => 'model-start',
        'prompt' => 'private prompt',
        'streamed' => true,
        'started_at_ms' => 2.5,
    ]);
    $collector->startTool('run-1', 'tool-1', [
        'tool' => 'LookupPatient',
        'arguments' => ['patient' => 'private patient'],
        'started_at_ms' => 3.0,
    ]);
    $collector->finishTool('run-1', 'tool-1', [
        'arguments' => ['patient' => 'private patient'],
        'result' => 'private result',
        'finished_at_ms' => 4.5,
    ]);
    $collector->finishInvocation('run-1', [
        'provider' => 'openai',
        'model' => 'gpt-test',
        'response' => 'private response',
        'usage' => [
            'prompt_tokens' => 12,
            'completion_tokens' => 8,
            'reasoning_tokens' => 3,
        ],
        'finished_at_ms' => 7.0,
    ]);

    $item = $collector->payload()['items'][0];

    expect($collector->summary())
        ->count->toBe(1)
        ->completed_count->toBe(1)
        ->incomplete_count->toBe(0)
        ->streamed_count->toBe(1)
        ->tool_count->toBe(1)
        ->token_count->toBe(20)
        ->content_captured->toBeFalse()
        ->and($item)
        ->provider->toBe('openai')
        ->model->toBe('gpt-test')
        ->duration_ms->toBe(4.5)
        ->tool_count->toBe(1)
        ->not->toHaveKeys(['prompt', 'response'])
        ->and($item['tools'][0])
        ->tool->toBe('LookupPatient')
        ->status->toBe('completed')
        ->duration_ms->toBe(1.5)
        ->not->toHaveKeys(['arguments', 'result'])
        ->and(json_encode($collector->payload()))
        ->not->toContain('private prompt', 'private patient', 'private result', 'private response');
});

it('redacts and bounds AI content when content capture is enabled', function () {
    $collector = new AiCollector(
        new Redactor(maxStringLength: 12),
        maxItems: 10,
        captureContent: true,
    );

    $collector->startInvocation('run-1', [
        'prompt' => 'a prompt longer than twelve characters',
        'started_at_ms' => 0,
    ]);
    $collector->startTool('run-1', 'tool-1', [
        'arguments' => ['api_key' => 'private key', 'city' => 'Lausanne'],
        'started_at_ms' => 1,
    ]);
    $collector->finishTool('run-1', 'tool-1', [
        'result' => ['access_token' => 'private token', 'answer' => 'available'],
        'finished_at_ms' => 2,
    ]);
    $collector->finishInvocation('run-1', [
        'response' => 'a response longer than twelve characters',
        'finished_at_ms' => 3,
    ]);

    $item = $collector->payload()['items'][0];

    expect($collector->summary()['content_captured'])->toBeTrue()
        ->and($item['prompt'])->toBe('a prompt lon…')
        ->and($item['response'])->toBe('a response l…')
        ->and($item['tools'][0]['arguments']['api_key'])->toBe('[redacted]')
        ->and($item['tools'][0]['result']['access_token'])->toBe('[redacted]')
        ->and(json_encode($item))->not->toContain('private key', 'private token');
});

it('shares bounded retention across AI runs and tool calls', function () {
    $collector = new AiCollector(new Redactor, maxItems: 1);

    $collector->startInvocation('run-1', []);
    $collector->startTool('run-1', 'tool-1', ['tool' => 'FirstTool']);
    $collector->finishTool('run-1', 'tool-1', []);
    $collector->startTool('run-1', 'tool-2', ['tool' => 'SecondTool']);
    $collector->finishTool('run-1', 'tool-2', []);
    $collector->finishInvocation('run-1', []);
    $collector->startInvocation('run-2', []);
    $collector->finishInvocation('run-2', []);

    expect($collector->summary())
        ->count->toBe(2)
        ->retained_count->toBe(1)
        ->dropped_count->toBe(1)
        ->truncated->toBeTrue()
        ->tool_count->toBe(2)
        ->tool_retained_count->toBe(1)
        ->tool_dropped_count->toBe(1)
        ->and($collector->payload()['items'])->toHaveCount(1)
        ->and($collector->payload()['items'][0]['tools'])->toHaveCount(1);
});
