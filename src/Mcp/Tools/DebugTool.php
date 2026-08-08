<?php

namespace NewDebugBar\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

abstract class DebugTool extends Tool
{
    protected const DESCRIPTION = '';

    final public function description(): string
    {
        return static::DESCRIPTION;
    }

    /** @return array<string, mixed> */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'version' => $schema->integer()->required(),
            'status' => $schema->string()->enum(['ok', 'not_found'])->required(),
            'data' => $schema->object()->required(),
        ];
    }

    /** @param array<string, mixed> $content */
    protected function response(array $content): ResponseFactory
    {
        return Response::structured($content);
    }
}
