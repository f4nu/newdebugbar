<?php

namespace NewDebugBar\Mcp\Legacy\Tools;

use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;
use NewDebugBar\Presentation\McpProfilePresenter;

#[IsReadOnly]
#[IsOpenWorld(false)]
final class GetDebugProfileSection extends DebugTool
{
    protected const DESCRIPTION = 'Read one bounded, redacted section from an exact debug profile.';

    public function __construct(private readonly McpProfilePresenter $profiles) {}

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->raw('profile_id', ['type' => 'string', 'format' => 'uuid'])->required()
            ->raw('section', ['type' => 'string', 'enum' => $this->profiles->sectionNames()])->required()
            ->raw('cursor', ['type' => 'integer', 'minimum' => 0, 'default' => 0])
            ->raw('limit', [
                'type' => 'integer',
                'minimum' => 1,
                'maximum' => $this->profiles->maxItems(),
                'default' => $this->profiles->maxItems(),
            ]);
    }

    /** @param array<string, mixed> $arguments */
    public function handle(array $arguments): ToolResult
    {
        $input = Validator::validate($arguments, [
            'profile_id' => 'required|uuid',
            'section' => 'required|string|in:'.implode(',', $this->profiles->sectionNames()),
            'cursor' => 'nullable|integer|min:0',
            'limit' => 'nullable|integer|min:1|max:'.$this->profiles->maxItems(),
        ]);

        return $this->response($this->profiles->section(
            $input['profile_id'],
            $input['section'],
            (int) ($input['cursor'] ?? 0),
            (int) ($input['limit'] ?? $this->profiles->maxItems()),
        ));
    }
}
