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
        'livewire',
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

        if ($section === 'livewire') {
            return $this->livewireSection($profileId, $sectionData, $cursor, $limit);
        }

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
        try {
            $profile = $this->store->get($profileId);

            if ($profile === null) {
                return null;
            }

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
            unset(
                $payload['items'],
                $payload['groups'],
                $payload['model_groups'],
                $payload['boot_items'],
                $payload['repeated_groups'],
                $payload['repeated_misses'],
            );

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

    /** @param array<string, mixed> $section @return array<string, mixed> */
    private function livewireSection(string $profileId, array $section, int $cursor, int $limit): array
    {
        $payload = is_array($section['payload'] ?? null) ? $section['payload'] : [];
        $exchange = is_array($payload['exchange'] ?? null) ? $payload['exchange'] : [];
        $browserTrace = is_array($payload['browser_trace'] ?? null) ? $payload['browser_trace'] : [];
        $records = $this->livewireRecords($payload);
        $recordCounts = [];

        foreach ($records as $record) {
            $type = (string) ($record['record_type'] ?? 'unknown');
            $recordCounts[$type] = ($recordCounts[$type] ?? 0) + 1;
        }

        return $this->paginatedResponse(
            $records,
            $cursor,
            $limit,
            fn (array $page, array $pagination): array => [
                'profile_id' => $profileId,
                'section' => 'livewire',
                'label' => 'Livewire',
                'summary' => $this->clean($section['summary'] ?? []),
                'payload' => [
                    'schema_version' => $section['schema_version'] ?? null,
                    'profile_revision' => $section['profile_revision'] ?? null,
                    'exchange' => $this->clean(array_intersect_key($exchange, array_flip([
                        'id',
                        'request_id',
                        'kind',
                        'title',
                        'title_confidence',
                        'result',
                        'status',
                        'path',
                        'request_bytes',
                        'response_bytes',
                        'duration_ms',
                        'server_clock',
                        'browser_clock',
                        'source',
                        'confidence',
                    ]))),
                    'trace' => [
                        'status' => $browserTrace['status'] ?? 'missing',
                        'appended_at' => $browserTrace['appended_at'] ?? null,
                        'raw_values_included' => false,
                    ],
                    'completeness' => $this->clean($payload['completeness'] ?? []),
                    'finding_rule_ids' => array_values(array_filter(array_column(
                        is_array($payload['findings'] ?? null) ? $payload['findings'] : [],
                        'rule_id',
                    ), 'is_string')),
                    'record_counts' => $recordCounts,
                    'items' => $this->clean($page),
                ],
                'pagination' => $pagination,
            ],
        );
    }

    /** @param array<string, mixed> $payload @return list<array<string, mixed>> */
    private function livewireRecords(array $payload): array
    {
        $records = [];
        $browserTrace = is_array($payload['browser_trace'] ?? null) ? $payload['browser_trace'] : [];
        $browserActions = collect(is_array($browserTrace['actions'] ?? null) ? $browserTrace['actions'] : [])
            ->filter(fn (mixed $item): bool => is_array($item))
            ->keyBy('action_id');

        foreach ($this->arrayItems($payload['messages'] ?? null) as $message) {
            $effects = is_array($message['effects'] ?? null) ? $message['effects'] : [];
            $download = is_array($effects['download'] ?? null) ? $effects['download'] : [];
            $records[] = [
                'record_type' => 'message',
                'id' => $message['id'] ?? null,
                'request_index' => $message['request_index'] ?? null,
                'component_id' => $message['component_id'] ?? null,
                'action_ids' => $message['action_ids'] ?? [],
                'state_change_ids' => $message['state_change_ids'] ?? [],
                'result' => $message['result'] ?? 'unknown',
                'validation_fields' => array_keys(is_array($message['validation_errors'] ?? null) ? $message['validation_errors'] : []),
                'effects' => [
                    'redirect' => $effects['redirect'] ?? null,
                    'download' => $download === [] ? null : [
                        'name' => $download['name'] ?? null,
                        'content_type' => $download['content_type'] ?? null,
                        'size_bytes' => $download['size_bytes'] ?? null,
                        'content_included' => false,
                    ],
                    'dispatch_count' => $effects['dispatch_count'] ?? null,
                    'rendered_html' => $effects['rendered_html'] ?? null,
                ],
                'caused_by' => $message['caused_by'] ?? [],
                'source' => $message['source'] ?? null,
                'confidence' => $message['confidence'] ?? null,
            ];
        }

        foreach ($this->arrayItems($payload['actions'] ?? null) as $action) {
            $browser = $browserActions->get($action['id'] ?? null);
            $records[] = [
                'record_type' => 'action',
                'id' => $action['id'] ?? null,
                'message_id' => $action['message_id'] ?? null,
                'component_id' => $action['component_id'] ?? null,
                'kind' => $action['kind'] ?? 'unknown',
                'name' => $action['name'] ?? null,
                'property_paths' => $action['property_paths'] ?? [],
                'execution_status' => $action['execution_status'] ?? null,
                'parameters_included' => false,
                'browser_source' => is_array($browser) ? ($browser['source'] ?? null) : null,
                'source' => $action['source'] ?? null,
                'confidence' => $action['confidence'] ?? null,
                'caused_by' => $action['caused_by'] ?? [],
            ];
        }

        foreach ($this->arrayItems($payload['components'] ?? null) as $component) {
            $records[] = [
                'record_type' => 'component',
                'id' => $component['id'] ?? null,
                'mount_scope' => $component['mount_scope'] ?? null,
                'name' => $component['name'] ?? null,
                'class' => $component['class'] ?? null,
                'source' => $component['source'] ?? null,
                'view' => $component['view'] ?? null,
                'parent_id' => $component['parent_id'] ?? null,
                'depth' => $component['depth'] ?? null,
                'rendered' => $component['rendered'] ?? 'unknown',
                'render_reason' => $component['render_reason'] ?? null,
                'completeness' => $component['completeness'] ?? null,
            ];
        }

        foreach ($this->arrayItems($payload['state_changes'] ?? null) as $change) {
            $browser = is_array($change['browser'] ?? null) ? $change['browser'] : [];
            $records[] = [
                'record_type' => 'state_change',
                'id' => $change['id'] ?? null,
                'action_id' => $change['action_id'] ?? null,
                'component_id' => $change['component_id'] ?? null,
                'path' => $change['path'] ?? null,
                'type' => $change['type'] ?? null,
                'changed' => true,
                'redacted' => (bool) ($change['redacted'] ?? false),
                'values_included' => false,
                'browser' => [
                    'status' => $browser['status'] ?? 'unknown',
                    'matches_server' => $browser['matches_server'] ?? null,
                    'type' => $browser['type'] ?? null,
                ],
                'confidence' => $change['confidence'] ?? null,
                'caused_by' => $change['caused_by'] ?? [],
            ];
        }

        foreach ($this->arrayItems($payload['events'] ?? null) as $event) {
            $records[] = [
                'record_type' => 'event',
                'id' => $event['id'] ?? null,
                'action_id' => $event['action_id'] ?? null,
                'source_component_id' => $event['source_component_id'] ?? null,
                'name' => $event['name'] ?? null,
                'mode' => $event['mode'] ?? null,
                'declared_target' => $event['declared_target'] ?? null,
                'observed_recipient_ids' => $event['observed_recipient_ids'] ?? [],
                'recipient_status' => $event['recipient_status'] ?? 'unknown',
                'parameters_included' => false,
                'source' => $event['source'] ?? null,
                'confidence' => $event['confidence'] ?? null,
            ];
        }

        foreach ($this->arrayItems($payload['server_spans'] ?? null) as $span) {
            $records[] = $this->livewireSpan($span, 'server_span');
        }

        foreach ($this->arrayItems($browserTrace['spans'] ?? null) as $span) {
            $records[] = $this->livewireSpan($span, 'browser_span');
        }

        return $records;
    }

    /** @param array<string, mixed> $span @return array<string, mixed> */
    private function livewireSpan(array $span, string $type): array
    {
        return [
            'record_type' => $type,
            'id' => $span['id'] ?? null,
            'message_id' => $span['message_id'] ?? null,
            'action_id' => $span['action_id'] ?? null,
            'component_id' => $span['component_id'] ?? null,
            'phase' => $span['phase'] ?? null,
            'kind' => $span['kind'] ?? null,
            'start_ms' => $span['start_ms'] ?? null,
            'duration_ms' => $span['duration_ms'] ?? null,
            'source' => $span['source'] ?? null,
            'confidence' => $span['confidence'] ?? null,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function arrayItems(mixed $value): array
    {
        return is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
    }

    private function safeItem(string $section, mixed $item): mixed
    {
        if (! is_array($item)) {
            return $this->clean($item);
        }

        if ($section === 'queries') {
            $item = $this->safeQueryItem($item);
        } elseif ($section === 'logs') {
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
        } elseif ($section === 'views' && is_array($item['data'] ?? null)) {
            $item['data'] = $this->redactor->cleanBindings($item['data'], 'safe');
        } elseif ($section === 'timeline') {
            if (($item['section'] ?? null) === 'logs') {
                $item['label'] = '[log message hidden]';
            } elseif (($item['section'] ?? null) === 'queries' && is_string($item['label'] ?? null)) {
                $item['label'] = $this->redactor->cleanSql($item['label']);
            }
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

    /** @param array<string, mixed> $item @return array<string, mixed> */
    private function safeQueryItem(array $item): array
    {
        foreach (['sql', 'normalized_sql'] as $key) {
            if (is_string($item[$key] ?? null)) {
                $item[$key] = $this->redactor->cleanSql($item[$key]);
            }
        }

        $hasBindingMetadata = array_key_exists('bindings', $item)
            || array_key_exists('binding_policy', $item)
            || array_key_exists('bindings_complete', $item)
            || array_key_exists('runnable_available', $item)
            || array_key_exists('runnable_sql', $item);

        if ($hasBindingMetadata) {
            $item['bindings'] = $this->redactor->cleanBindings(
                is_array($item['bindings'] ?? null) ? $item['bindings'] : [],
                'safe',
            );
            $item['binding_policy'] = 'safe';
            $item['bindings_complete'] = false;
            $item['runnable_available'] = false;
        }

        unset($item['runnable_sql']);

        if (is_array($item['executions'] ?? null)) {
            $item['executions'] = array_map(
                fn (mixed $execution): mixed => is_array($execution) ? $this->safeQueryItem($execution) : $execution,
                $item['executions'],
            );
        }

        return $item;
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
        $requestedCount = count($page);
        $truncated = $cursor + count($page) < count($all);
        $response = $this->response($build($page, $this->pagination($cursor, count($page), count($all), $truncated)));

        while ($this->byteLength($response) > $this->maxBytes && $page !== []) {
            array_pop($page);
            $truncated = true;
            $response = $this->response($build($page, $this->pagination($cursor, count($page), count($all), true)));
        }

        if ($requestedCount > 0 && $page === []) {
            $response = $this->response($build([], $this->pagination(
                $cursor,
                0,
                count($all),
                true,
                omittedDueToBytes: 1,
            )));
        }

        if ($this->byteLength($response) > $this->maxBytes) {
            $response = $this->minimalPaginatedResponse($response);
        }

        return $response;
    }

    /** @return array<string, mixed> */
    private function pagination(
        int $cursor,
        int $count,
        int $total,
        bool $truncated,
        int $omittedDueToBytes = 0,
    ): array {
        $nextOffset = $cursor + ($omittedDueToBytes > 0 ? $omittedDueToBytes : $count);
        $pagination = [
            'cursor' => $cursor,
            'returned' => $count,
            'total' => $total,
            'truncated' => $truncated,
            'next_cursor' => $nextOffset < $total ? $nextOffset : null,
        ];

        if ($omittedDueToBytes > 0) {
            $pagination['omitted_due_to_bytes'] = $omittedDueToBytes;
        }

        return $pagination;
    }

    /** @param array<string, mixed> $response @return array<string, mixed> */
    private function minimalPaginatedResponse(array $response): array
    {
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $minimal = [];

        foreach (['profile_id', 'section', 'filter', 'search', 'sort'] as $key) {
            if (array_key_exists($key, $data)) {
                $minimal[$key] = $data[$key];
            }
        }

        $pagination = is_array($data['pagination'] ?? null) ? $data['pagination'] : [];
        $pagination['truncated'] = true;

        return $this->response([
            ...$minimal,
            'content_omitted' => true,
            'pagination' => $pagination,
        ]);
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
