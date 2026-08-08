<?php

namespace NewDebugBar\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use NewDebugBar\Presentation\McpProfilePresenter;
use NewDebugBar\Storage\ProfileStore;

#[IsReadOnly]
#[IsOpenWorld(false)]
final class InspectDebugQueries extends DebugTool
{
    protected const DESCRIPTION = 'Inspect bounded query evidence using the same grouping and filters as the browser inspector.';

    public function __construct(private readonly McpProfilePresenter $profiles) {}

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'profile_id' => $schema->string()->format('uuid')->required(),
            'filter' => $schema->string()->enum(['all', 'repeated', 'slow', 'read', 'write'])->default('all'),
            'search' => $schema->string()->max(200)->default(''),
            'sort' => $schema->string()->enum(['execution', 'duration'])->default('execution'),
            'cursor' => $schema->integer()->min(0)->default(0),
            'limit' => $schema->integer()->min(1)->max($this->profiles->maxItems())->default($this->profiles->maxItems()),
        ];
    }

    public function handle(Request $request): ResponseFactory
    {
        $input = $request->validate([
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
