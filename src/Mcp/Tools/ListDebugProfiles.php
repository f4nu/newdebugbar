<?php

namespace NewDebugBar\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use NewDebugBar\Presentation\McpProfilePresenter;
use NewDebugBar\Storage\ProfileStore;

#[Description('List recent redacted debug profile summaries with optional request filters.')]
#[IsReadOnly]
#[IsOpenWorld(false)]
final class ListDebugProfiles extends DebugTool
{
    public function __construct(
        private readonly McpProfilePresenter $profiles,
        private readonly ProfileStore $store,
    ) {}

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'method' => $schema->string()->max(20)->description('Exact HTTP method, such as GET.'),
            'path' => $schema->string()->max(200)->description('Case-sensitive path fragment.'),
            'status' => $schema->integer()->min(100)->max(599)->description('Exact HTTP status.'),
            'warning' => $schema->boolean()->description('Only profiles with or without findings.'),
            'limit' => $schema->integer()->min(1)->max($this->store->maxProfiles())->default($this->store->maxProfiles()),
        ];
    }

    public function handle(Request $request): ResponseFactory
    {
        $input = $request->validate([
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
