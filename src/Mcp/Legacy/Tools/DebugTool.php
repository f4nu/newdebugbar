<?php

namespace NewDebugBar\Mcp\Legacy\Tools;

use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolResult;

/** Keeps the New Debug Bar tools usable with Laravel MCP 0.1. */
abstract class DebugTool extends Tool
{
    protected const DESCRIPTION = '';

    final public function description(): string
    {
        return static::DESCRIPTION;
    }

    /** @param array<string, mixed> $content */
    protected function response(array $content): ToolResult
    {
        return ToolResult::json($content);
    }
}
