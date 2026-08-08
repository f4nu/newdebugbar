<?php

namespace NewDebugBar\Mcp\Legacy\Tools;

use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;
use NewDebugBar\Presentation\McpProfilePresenter;
use NewDebugBar\Storage\ProfileStore;

#[IsReadOnly]
#[IsOpenWorld(false)]
final class InspectDebugQueries extends DebugTool
{
    protected const DESCRIPTION = 'Inspect bounded query evidence using the same grouping and filters as the browser inspector.';

    public function __construct(private readonly McpProfilePresenter $profiles) {}

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->raw('profile_id', ['type' => 'string', 'format' => 'uuid'])->required()
            ->raw('filter', ['type' => 'string', 'enum' => ['all', 'repeated', 'slow', 'read', 'write'], 'default' => 'all'])
            ->raw('search', ['type' => 'string', 'maxLength' => 200, 'default' => ''])
            ->raw('sort', ['type' => 'string', 'enum' => ['execution', 'duration'], 'default' => 'execution'])
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
            'profile_id' => 'required|string|regex:'.ProfileStore::ID_REGEX,
            'filter' => 'nullable|string|in:all,repeated,slow,read,write',
            'search' => 'nullable|string|max:200',
            'sort' => 'nullable|string|in:execution,duration',
            'cursor' => 'nullable|integer|min:0',
            'limit' => 'nullable|integer|min:1|max:'.$this->profiles->maxItems(),
        ]);

        return $this->response($this->profiles->queries(
            $input['profile_id'],
            $input['filter'] ?? 'all',
            $input['search'] ?? '',
            $input['sort'] ?? 'execution',
            (int) ($input['cursor'] ?? 0),
            (int) ($input['limit'] ?? $this->profiles->maxItems()),
        ));
    }
}
