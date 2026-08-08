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
final class ListDebugProfiles extends DebugTool
{
    protected const DESCRIPTION = 'List recent redacted debug profile summaries with optional request filters.';

    public function __construct(
        private readonly McpProfilePresenter $profiles,
        private readonly ProfileStore $store,
    ) {}

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->raw('method', ['type' => 'string', 'maxLength' => 20, 'description' => 'Exact HTTP method, such as GET.'])
            ->raw('path', ['type' => 'string', 'maxLength' => 200, 'description' => 'Case-sensitive path fragment.'])
            ->raw('status', ['type' => 'integer', 'minimum' => 100, 'maximum' => 599, 'description' => 'Exact HTTP status.'])
            ->boolean('warning')->description('Only profiles with or without findings.')
            ->raw('limit', [
                'type' => 'integer',
                'minimum' => 1,
                'maximum' => $this->store->maxProfiles(),
                'default' => $this->store->maxProfiles(),
            ]);
    }

    /** @param array<string, mixed> $arguments */
    public function handle(array $arguments): ToolResult
    {
        $input = Validator::validate($arguments, [
            'method' => 'nullable|string|max:20',
            'path' => 'nullable|string|max:200',
            'status' => 'nullable|integer|between:100,599',
            'warning' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:'.$this->store->maxProfiles(),
        ]);

        return $this->response($this->profiles->list([
            'method' => isset($input['method']) ? strtoupper($input['method']) : null,
            'path' => $input['path'] ?? null,
            'status' => $input['status'] ?? null,
            'warning' => $input['warning'] ?? null,
        ], (int) ($input['limit'] ?? $this->store->maxProfiles())));
    }
}
