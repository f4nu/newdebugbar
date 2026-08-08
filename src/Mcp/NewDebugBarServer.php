<?php

namespace NewDebugBar\Mcp;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Tool;
use NewDebugBar\Mcp\Tools\GetDebugFindings;
use NewDebugBar\Mcp\Tools\GetDebugProfileSection;
use NewDebugBar\Mcp\Tools\InspectDebugQueries;
use NewDebugBar\Mcp\Tools\ListDebugProfiles;

final class NewDebugBarServer extends Server
{
    protected string $name = 'New Debug Bar';

    protected string $version = '1.0.0';

    protected string $instructions = 'Read bounded, redacted Laravel debug profiles. Use the exact profile ID from the X-NewDebugBar-Profile response header when correlating a request.';

    /** @var array<int, class-string<Tool>> */
    protected array $tools = [
        ListDebugProfiles::class,
        GetDebugProfileSection::class,
        InspectDebugQueries::class,
        GetDebugFindings::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
