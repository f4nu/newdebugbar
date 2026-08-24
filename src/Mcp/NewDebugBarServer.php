<?php

namespace NewDebugBar\Mcp;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Tool;
use NewDebugBar\Mcp\Tools\GetDebugFindings;
use NewDebugBar\Mcp\Tools\GetDebugProfileData;
use NewDebugBar\Mcp\Tools\GetDebugProfileSection;
use NewDebugBar\Mcp\Tools\InspectDebugQueries;
use NewDebugBar\Mcp\Tools\ListDebugProfiles;

final class NewDebugBarServer extends Server
{
    protected string $name = 'New Debug Bar';

    protected string $version = '1.1.0';

    protected string $instructions = 'Read bounded Laravel debug profiles. Use the exact X-NewDebugBar-Profile ID, inspect findings and a small section first, then use get-debug-profile-data with /sections and returned JSON Pointer paths when deeper evidence is needed. For Models, follow /sections/models/payload/model_groups to reach folded writes, identifiers, changed attributes, sources, timings, related queries, and guidance.';

    /** @var array<int, class-string<Tool>> */
    protected array $tools = [
        ListDebugProfiles::class,
        GetDebugProfileSection::class,
        GetDebugProfileData::class,
        InspectDebugQueries::class,
        GetDebugFindings::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
