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
final class GetDebugFindings extends DebugTool
{
    private const DEFAULT_LIMIT = 10;

    protected const DESCRIPTION = 'Return deterministic finding rule IDs and supporting evidence for one debug profile.';

    public function __construct(private readonly McpProfilePresenter $profiles) {}

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'profile_id' => $schema->string()->format('uuid')->required(),
            'cursor' => $schema->integer()->min(0)->default(0),
            'limit' => $schema->integer()->min(1)->max($this->profiles->maxItems())->default($this->defaultLimit()),
        ];
    }

    public function handle(Request $request): ResponseFactory
    {
        $input = $request->validate([
            'profile_id' => 'required|string|regex:'.ProfileStore::ID_REGEX,
            'cursor' => 'nullable|integer|min:0',
            'limit' => 'nullable|integer|min:1|max:'.$this->profiles->maxItems(),
        ]);

        return $this->response($this->profiles->findings(
            $input['profile_id'],
            (int) ($input['cursor'] ?? 0),
            (int) ($input['limit'] ?? $this->defaultLimit()),
        ));
    }

    private function defaultLimit(): int
    {
        return min(self::DEFAULT_LIMIT, $this->profiles->maxItems());
    }
}
