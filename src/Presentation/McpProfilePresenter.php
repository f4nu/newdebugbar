<?php

namespace NewDebugBar\Presentation;

use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Support\Redactor;
use Throwable;

/** Builds the bounded, versioned contract exposed through local MCP tools. */
final class McpProfilePresenter
{
    public const RESPONSE_VERSION = 1;

    /** @var list<string> */
    private const SECTION_NAMES = [
        'overview',
        'request',
        'timeline',
        'queries',
        'livewire',
        'http_client',
        'queue',
        'mail',
        'notifications',
        'redis',
        'models',
        'cache',
        'views',
        'events',
        'authorization',
        'validation',
        'lifecycle',
        'messages',
        'logs',
        'exceptions',
    ];

    public function __construct(
        private readonly ProfileStore $store,
        private readonly ProfilePresenter $profiles,
        private readonly ProfileSummaryPresenter $summaries,
        private readonly Redactor $redactor,
        private readonly string $projectPath,
        private readonly int $maxItems = 50,
        private readonly int $maxBytes = 100_000,
    ) {}

    /**
     * @param  array{method?: string|null, path?: string|null, status?: int|null, warning?: bool|null}  $filters
     * @return array<string, mixed>
     */
    public function list(array $filters, int $limit): array
    {
        $matching = [];

        foreach ($this->store->recent($this->store->maxProfiles()) as $profile) {
            try {
                $summary = $this->summaries->present($this->profiles->present($profile));
            } catch (Throwable) {
                continue;
            }

            if (! $this->matchesFilters($summary, $filters)) {
                continue;
            }

            $matching[] = $summary;
        }

        $total = count($matching);
        $summaries = array_slice($matching, 0, max(1, min($limit, $this->store->maxProfiles())));
        $response = $this->profileListResponse($summaries, $total, count($summaries) < $total);

        while ($this->byteLength($response) > $this->maxBytes && $summaries !== []) {
            array_pop($summaries);
            $response = $this->profileListResponse($summaries, $total, true);
        }

        return $response;
    }

    /** @return array<string, mixed> */
    public function section(string $profileId, string $section, int $cursor, int $limit): array
    {
        $profile = $this->find($profileId);

        if ($profile === null) {
            return $this->notFound($profileId);
        }

        if (! in_array($section, self::SECTION_NAMES, true) || ! isset($profile['sections'][$section])) {
            return $this->response([
                'profile_id' => $profileId,
                'section' => $section,
                'available_sections' => array_values(array_keys($profile['sections'] ?? [])),
            ], 'not_found');
        }

        $sectionData = $profile['sections'][$section];
        $items = $sectionData['payload']['items'] ?? [];
        $payload = $this->safeSectionPayload($section, $sectionData['payload'] ?? []);
        unset($payload['items']);

        return $this->paginatedResponse(
            is_array($items) ? $items : [],
            $cursor,
            $limit,
            fn (array $page, array $pagination): array => [
                'profile_id' => $profileId,
                'section' => $section,
                'label' => $sectionData['label'] ?? ucfirst($section),
                'summary' => $this->clean($sectionData['summary'] ?? []),
                'payload' => [
                    ...$payload,
                    'items' => array_map(fn (mixed $item): mixed => $this->safeItem($section, $item), $page),
                ],
                'pagination' => $pagination,
            ],
        );
    }

    /** @return array<string, mixed> */
    public function queries(
        string $profileId,
        string $filter,
        string $search,
        string $sort,
        int $cursor,
        int $limit,
    ): array {
        $profile = $this->find($profileId);

        if ($profile === null) {
            return $this->notFound($profileId);
        }

        $section = $profile['sections']['queries'] ?? ['summary' => [], 'payload' => []];
        $items = $filter === 'repeated'
            ? ($section['payload']['repeated_groups'] ?? [])
            : array_values(array_filter(
                $section['payload']['items'] ?? [],
                fn (array $query): bool => $filter === 'all'
                    || ($filter === 'slow' && ($query['slow'] ?? false))
                    || in_array($filter, ['read', 'write'], true) && ($query['query_type'] ?? null) === $filter,
            ));
        $needle = mb_strtolower(trim($search));

        if ($needle !== '') {
            $items = array_values(array_filter($items, function (array $query) use ($needle): bool {
                $haystack = ($query['sql'] ?? '').' '.json_encode(
                    $query['bindings'] ?? array_column($query['executions'] ?? [], 'bindings'),
                    JSON_UNESCAPED_SLASHES,
                );

                return str_contains(mb_strtolower($haystack), $needle);
            }));
        }

        usort($items, fn (array $left, array $right): int => $sort === 'duration'
            ? ($right['duration_ms'] ?? 0) <=> ($left['duration_ms'] ?? 0)
            : $this->executionNumber($left) <=> $this->executionNumber($right));

        return $this->paginatedResponse(
            $items,
            $cursor,
            $limit,
            fn (array $page, array $pagination): array => [
                'profile_id' => $profileId,
                'filter' => $filter,
                'search' => $search,
                'sort' => $sort,
                'summary' => $this->clean($section['summary'] ?? []),
                $filter === 'repeated' ? 'repeated_groups' : 'items' => array_map(
                    fn (mixed $item): mixed => $this->safeItem('queries', $item),
                    $page,
                ),
                'pagination' => $pagination,
            ],
        );
    }

    /** @return array<string, mixed> */
    public function findings(string $profileId, int $cursor, int $limit): array
    {
        $profile = $this->find($profileId);

        if ($profile === null) {
            return $this->notFound($profileId);
        }

        return $this->paginatedResponse(
            is_array($profile['findings'] ?? null) ? $profile['findings'] : [],
            $cursor,
            $limit,
            fn (array $page, array $pagination): array => [
                'profile_id' => $profileId,
                'findings' => $this->clean($page),
                'pagination' => $pagination,
            ],
        );
    }

    /** @return list<string> */
    public function sectionNames(): array
    {
        return self::SECTION_NAMES;
    }

    public function maxItems(): int
    {
        return $this->maxItems;
    }

    /** @return array<string, mixed>|null */
    private function find(string $profileId): ?array
    {
        $profile = $this->store->get($profileId);

        if ($profile === null) {
            return null;
        }

        try {
            return $this->profiles->present($profile);
        } catch (Throwable) {
            return null;
        }
    }

    /** @param array<string, mixed> $summary @param array<string, mixed> $filters */
    private function matchesFilters(array $summary, array $filters): bool
    {
        if (($filters['method'] ?? null) !== null && $summary['method'] !== $filters['method']) {
            return false;
        }

        if (($filters['path'] ?? null) !== null && ! str_contains((string) $summary['path'], (string) $filters['path'])) {
            return false;
        }

        if (($filters['status'] ?? null) !== null && $summary['status'] !== $filters['status']) {
            return false;
        }

        return ($filters['warning'] ?? null) === null || $summary['warning'] === $filters['warning'];
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function safeSectionPayload(string $section, array $payload): array
    {
        if ($section !== 'request') {
            unset($payload['items'], $payload['groups'], $payload['repeated_groups'], $payload['repeated_misses']);

            return $this->clean($payload);
        }

        $runtimeContext = is_array($payload['context'] ?? null) ? $payload['context'] : [];

        return $this->clean([
            'method' => $payload['method'] ?? null,
            'path' => $payload['path'] ?? null,
            'route' => $payload['route'] ?? null,
            'action' => $payload['action'] ?? null,
            'parameters' => array_keys(is_array($payload['parameters'] ?? null) ? $payload['parameters'] : []),
            'middleware' => $payload['middleware'] ?? [],
            'status' => $payload['status'] ?? null,
            'runtime_type' => $payload['runtime_type'] ?? null,
            'runtime_name' => $payload['name'] ?? null,
            'exit_code' => $payload['exit_code'] ?? null,
            'runtime_context' => [
                'argument_names' => $runtimeContext['argument_names'] ?? [],
                'option_names' => $runtimeContext['option_names'] ?? [],
                'connection' => $runtimeContext['connection'] ?? null,
                'queue' => $runtimeContext['queue'] ?? null,
                'attempt' => $runtimeContext['attempt'] ?? null,
            ],
            'content_type' => $payload['content_type'] ?? null,
            'request_size_bytes' => $payload['request_size_bytes'] ?? null,
            'response_size_bytes' => $payload['response_size_bytes'] ?? null,
            'session_present' => $payload['session_present'] ?? false,
            'authenticated' => $payload['authenticated'] ?? false,
            'request_header_names' => array_keys(is_array($payload['headers'] ?? null) ? $payload['headers'] : []),
            'response_header_names' => array_keys(is_array($payload['response_headers'] ?? null) ? $payload['response_headers'] : []),
            'query_keys' => array_keys(is_array($payload['query'] ?? null) ? $payload['query'] : []),
            'input_keys' => array_keys(is_array($payload['input'] ?? null) ? $payload['input'] : []),
        ]);
    }

    private function safeItem(string $section, mixed $item): mixed
    {
        if (! is_array($item)) {
            return $this->clean($item);
        }

        if ($section === 'logs') {
            $item = [
                'at_ms' => $item['at_ms'] ?? null,
                'level' => $item['level'] ?? null,
                'message' => '[message hidden]',
                'callsite' => $item['callsite'] ?? null,
            ];
        } elseif ($section === 'exceptions') {
            $item = [
                'at_ms' => $item['at_ms'] ?? null,
                'class' => $item['class'] ?? null,
                'message' => '[message hidden]',
                'file' => $item['file'] ?? null,
                'line' => $item['line'] ?? null,
                'application_frames' => $item['frames']['application'] ?? [],
            ];
        } elseif ($section === 'models' && array_key_exists('key', $item)) {
            $item['key'] = $item['key'] === null ? null : '[identifier]';
        } elseif ($section === 'mail' && is_array($item['preview'] ?? null)) {
            $preview = $item['preview'];
            $item['preview'] = [
                'available' => true,
                'html_available' => is_string($preview['html'] ?? null),
                'text_available' => is_string($preview['text'] ?? null),
                'eml_available' => is_string($preview['eml'] ?? null),
                'truncated' => (bool) ($preview['truncated'] ?? false),
                'attachments_omitted' => (int) ($preview['attachments_omitted'] ?? 0),
                'addresses_omitted' => (int) ($preview['addresses_omitted'] ?? 0),
            ];
        }

        return $this->clean($item);
    }

    private function executionNumber(array $item): int
    {
        return (int) ($item['execution'] ?? $item['executions'][0]['execution'] ?? 0);
    }

    /** @param list<array<string, mixed>> $profiles @return array<string, mixed> */
    private function profileListResponse(array $profiles, int $total, bool $truncated): array
    {
        return $this->response([
            'profiles' => $profiles,
            'count' => count($profiles),
            'total' => $total,
            'truncated' => $truncated,
        ]);
    }

    /**
     * @param  list<mixed>  $all
     * @param  callable(list<mixed>, array<string, mixed>): array<string, mixed>  $build
     * @return array<string, mixed>
     */
    private function paginatedResponse(array $all, int $cursor, int $limit, callable $build): array
    {
        $cursor = max(0, $cursor);
        $limit = max(1, min($limit, $this->maxItems));
        $page = array_values(array_slice($all, $cursor, $limit));
        $truncated = $cursor + count($page) < count($all);
        $response = $this->response($build($page, $this->pagination($cursor, count($page), count($all), $truncated)));

        while ($this->byteLength($response) > $this->maxBytes && $page !== []) {
            array_pop($page);
            $truncated = true;
            $response = $this->response($build($page, $this->pagination($cursor, count($page), count($all), true)));
        }

        return $response;
    }

    /** @return array<string, mixed> */
    private function pagination(int $cursor, int $count, int $total, bool $truncated): array
    {
        return [
            'cursor' => $cursor,
            'returned' => $count,
            'total' => $total,
            'truncated' => $truncated,
            'next_cursor' => $cursor + $count < $total ? $cursor + $count : null,
        ];
    }

    /** @return array<string, mixed> */
    private function response(array $data, string $status = 'ok'): array
    {
        return [
            'version' => self::RESPONSE_VERSION,
            'status' => $status,
            'data' => $data,
        ];
    }

    /** @return array<string, mixed> */
    private function notFound(string $profileId): array
    {
        return $this->response(['profile_id' => $profileId], 'not_found');
    }

    private function clean(mixed $value): mixed
    {
        return $this->relativePaths($this->redactor->clean($value));
    }

    private function relativePaths(mixed $value, ?string $key = null): mixed
    {
        if (is_array($value)) {
            $clean = [];

            foreach ($value as $itemKey => $item) {
                $clean[$itemKey] = $this->relativePaths($item, (string) $itemKey);
            }

            return $clean;
        }

        if ($key === 'file' && is_string($value) && str_starts_with($value, '/')) {
            $project = rtrim(str_replace('\\', '/', $this->projectPath), '/').'/';
            $file = str_replace('\\', '/', $value);

            return str_starts_with($file, $project) ? substr($file, strlen($project)) : basename($file);
        }

        return $value;
    }

    private function byteLength(array $response): int
    {
        return strlen(json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
    }
}
