<?php

namespace NewDebugBar\Mcp\Legacy;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Tool;
use NewDebugBar\Mcp\Legacy\Tools\GetDebugFindings;
use NewDebugBar\Mcp\Legacy\Tools\GetDebugProfileSection;
use NewDebugBar\Mcp\Legacy\Tools\InspectDebugQueries;
use NewDebugBar\Mcp\Legacy\Tools\ListDebugProfiles;

/** Exposes the same tools through the MCP release that supports early Laravel 10. */
final class NewDebugBarServer extends Server
{
    public string $serverName = 'New Debug Bar';

    public string $serverVersion = '1.0.0';

    public string $instructions = 'Read bounded, redacted Laravel debug profiles. Use the exact profile ID from the X-NewDebugBar-Profile response header when correlating a request.';

    /** @var array<int, class-string<Tool>> */
    public array $tools = [
        ListDebugProfiles::class,
        GetDebugProfileSection::class,
        InspectDebugQueries::class,
        GetDebugFindings::class,
    ];

    public array $resources = [];

    public array $prompts = [];
}
