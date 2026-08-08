<?php

namespace NewDebugBar\Tests\Fixtures\LaravelAi;

final class Manager
{
    // Registration guard only.
}

final class Agent
{
    // Class name is the useful fixture value.
}

final class Tool
{
    // Class name is the useful fixture value.
}

final class Prompt
{
    /** @param list<mixed> $attachments */
    public function __construct(
        public Agent $agent,
        public string $prompt,
        public array $attachments,
        public string $model,
    ) {}
}

final class Meta
{
    public function __construct(
        public string $provider,
        public string $model,
    ) {}
}

final class Usage
{
    public function __construct(
        public int $promptTokens,
        public int $completionTokens,
        public int $reasoningTokens = 0,
    ) {}

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'reasoning_tokens' => $this->reasoningTokens,
        ];
    }
}

final class Response
{
    public function __construct(
        public string $text,
        public Usage $usage,
        public Meta $meta,
    ) {}
}

final class PromptingAgent
{
    public function __construct(
        public string $invocationId,
        public Prompt $prompt,
    ) {}
}

final class AgentPrompted
{
    public function __construct(
        public string $invocationId,
        public Prompt $prompt,
        public Response $response,
    ) {}
}

final class StreamingAgent
{
    public function __construct(
        public string $invocationId,
        public Prompt $prompt,
    ) {}
}

final class AgentStreamed
{
    public function __construct(
        public string $invocationId,
        public Prompt $prompt,
        public Response $response,
    ) {}
}

final class InvokingTool
{
    /** @param array<string, mixed> $arguments */
    public function __construct(
        public string $invocationId,
        public string $toolInvocationId,
        public Agent $agent,
        public Tool $tool,
        public array $arguments,
    ) {}
}

final class ToolInvoked
{
    /** @param array<string, mixed> $arguments */
    public function __construct(
        public string $invocationId,
        public string $toolInvocationId,
        public Agent $agent,
        public Tool $tool,
        public array $arguments,
        public mixed $result,
    ) {}
}

final class QueueJob
{
    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [];
    }

    public function resolveName(): string
    {
        return self::class;
    }

    public function getQueue(): string
    {
        return 'default';
    }

    public function attempts(): int
    {
        return 1;
    }

    public function getJobId(): string
    {
        return 'fake-job';
    }
}
