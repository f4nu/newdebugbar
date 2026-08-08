<?php

namespace NewDebugBar\Mcp;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;
use NewDebugBar\Mcp\Tools\GetDebugFindings;
use NewDebugBar\Mcp\Tools\GetDebugProfileSection;
use NewDebugBar\Mcp\Tools\InspectDebugQueries;
use NewDebugBar\Mcp\Tools\ListDebugProfiles;

#[Name('New Debug Bar')]
#[Version('1.0.0')]
#[Instructions('Read bounded, redacted Laravel debug profiles. Use the exact profile ID from the X-NewDebugBar-Profile response header when correlating a request.')]
final class NewDebugBarServer extends Server
{
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
