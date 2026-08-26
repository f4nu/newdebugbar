{{-- Renders authorization decisions as a selectable list with structured diagnostic evidence. --}}
@php
    $rawAuthorizationItems = array_values(array_filter(
        $section['payload']['items'] ?? [],
        static fn (mixed $item): bool => is_array($item),
    ));
    $shortType = static fn (string $type): string => class_basename($type);
    $authorizationItems = collect($rawAuthorizationItems)
        ->map(function (array $item, int $index) use ($shortType): array {
            $ability = is_string($item['ability'] ?? null) && $item['ability'] !== ''
                ? $item['ability']
                : 'Ability unavailable';
            $result = ($item['result'] ?? null) === 'allowed' ? 'allowed' : 'denied';
            $rawActor = is_array($item['actor'] ?? null) ? $item['actor'] : [];
            $actorType = is_string($rawActor['type'] ?? null) && $rawActor['type'] !== ''
                ? $rawActor['type']
                : (is_string($item['user_type'] ?? null) && $item['user_type'] !== '' ? $item['user_type'] : null);
            $actorName = is_string($rawActor['name'] ?? null) && $rawActor['name'] !== ''
                ? $rawActor['name']
                : null;
            $actorIdentifierName = is_string($rawActor['identifier_name'] ?? null) && $rawActor['identifier_name'] !== ''
                ? $rawActor['identifier_name']
                : null;
            $actorIdentifier = is_scalar($rawActor['identifier'] ?? null) ? $rawActor['identifier'] : null;
            $actorLabel = match (true) {
                $actorType === null => 'Guest',
                $actorName !== null => $actorName,
                $actorIdentifier !== null => $shortType($actorType).' '.(string) $actorIdentifier,
                default => $shortType($actorType),
            };
            $rawArguments = array_values(array_filter(
                is_array($item['arguments'] ?? null) ? $item['arguments'] : [],
                static fn (mixed $argument): bool => is_array($argument),
            ));

            if ($rawArguments === [] && ! array_key_exists('arguments', $item)) {
                $legacyTypes = array_values(array_filter(
                    $item['argument_types'] ?? [],
                    static fn (mixed $type): bool => is_string($type) && $type !== '',
                ));
                $rawArguments = array_values(array_map(
                    static fn (string $type, int $argumentIndex): array => [
                        'position' => $argumentIndex + 1,
                        'kind' => 'object',
                        'type' => $type,
                    ],
                    $legacyTypes,
                    array_keys($legacyTypes),
                ));
            }

            $arguments = collect($rawArguments)
                ->map(function (array $argument, int $argumentIndex) use ($shortType): array {
                    $position = max(1, (int) ($argument['position'] ?? ($argumentIndex + 1)));
                    $type = is_string($argument['type'] ?? null) && $argument['type'] !== ''
                        ? $argument['type']
                        : 'unknown';
                    $kind = is_string($argument['kind'] ?? null) ? $argument['kind'] : 'value';
                    $name = is_string($argument['name'] ?? null) && $argument['name'] !== ''
                        ? $argument['name']
                        : null;
                    $identifier = is_scalar($argument['identifier'] ?? null) ? $argument['identifier'] : null;
                    $routeKeyName = is_string($argument['route_key_name'] ?? null) && $argument['route_key_name'] !== ''
                        ? $argument['route_key_name']
                        : null;
                    $routeKey = is_scalar($argument['route_key'] ?? null) ? $argument['route_key'] : null;
                    $hasValue = array_key_exists('value', $argument);
                    $value = $argument['value'] ?? null;
                    $valueLabel = match (true) {
                        is_bool($value) => $value ? 'true' : 'false',
                        $value === null => 'null',
                        is_scalar($value) => (string) $value,
                        default => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: 'Unavailable',
                    };
                    $label = match (true) {
                        $name !== null => $name,
                        $routeKey !== null => $shortType($type).' '.(string) $routeKey,
                        $identifier !== null => $shortType($type).' '.(string) $identifier,
                        $hasValue => $valueLabel,
                        default => $shortType($type),
                    };
                    $identityLabel = match (true) {
                        $routeKey !== null && $routeKeyName !== null => $routeKeyName.' '.(string) $routeKey,
                        $identifier !== null => 'Identifier '.(string) $identifier,
                        $hasValue => 'Value '.$valueLabel,
                        default => null,
                    };

                    return [
                        'position' => $position,
                        'role_label' => $position === 1 ? 'Target' : 'Argument '.$position,
                        'kind' => $kind,
                        'type' => $type,
                        'type_short' => $shortType($type),
                        'label' => $label,
                        'identity_label' => $identityLabel,
                    ];
                })
                ->values()
                ->all();
            $argumentCount = count($arguments);
            $argumentSummary = match (true) {
                $argumentCount === 0 => '—',
                $argumentCount === 1 => $arguments[0]['label'],
                default => $arguments[0]['label'].' and '.($argumentCount - 1).' more',
            };
            $legacyHandler = is_string($item['handler'] ?? null) && $item['handler'] !== ''
                ? $item['handler']
                : 'callback';
            $handlerKind = in_array($item['handler_kind'] ?? null, ['policy', 'callback'], true)
                ? $item['handler_kind']
                : ($legacyHandler === 'callback' ? 'callback' : 'policy');
            $handlerName = is_string($item['handler_name'] ?? null) && $item['handler_name'] !== ''
                ? $item['handler_name']
                : ($legacyHandler === 'callback' ? 'Gate callback' : $legacyHandler);
            $handlerShortName = str_contains($handlerName, '@')
                ? $shortType(\Illuminate\Support\Str::before($handlerName, '@')).'@'.\Illuminate\Support\Str::after($handlerName, '@')
                : $shortType($handlerName);
            $handlerSource = is_array($item['handler_source'] ?? null) ? $item['handler_source'] : null;
            $handlerSourceLabel = $handlerSource === null
                ? null
                : ($handlerSource['file'] ?? 'Unknown source').':'.($handlerSource['line'] ?? '?');
            $callsite = is_array($item['callsite'] ?? null) ? $item['callsite'] : null;
            $callsiteLabel = $callsite === null
                ? null
                : ($callsite['copy'] ?? (($callsite['file'] ?? 'Unknown source').':'.($callsite['line'] ?? '?')));
            $callsiteShortLabel = $callsite === null
                ? '—'
                : basename(str_replace('\\', '/', (string) ($callsite['file'] ?? $callsiteLabel))).':'.($callsite['line'] ?? '?');
            $resultMessage = is_string($item['result_message'] ?? null) && $item['result_message'] !== ''
                ? $item['result_message']
                : null;
            $resultCode = is_scalar($item['result_code'] ?? null) ? (string) $item['result_code'] : null;
            $resultStatus = is_numeric($item['result_status'] ?? null) ? (int) $item['result_status'] : null;
            $stack = array_values(array_filter(
                is_array($item['stack'] ?? null) ? $item['stack'] : [],
                static fn (mixed $frame): bool => is_array($frame) && is_string($frame['file'] ?? null),
            ));
            $actorGuidance = match (true) {
                $actorType === null && $result === 'denied' => 'Confirm guests should be denied this ability.',
                $actorType === null => 'Confirm guests should receive this ability.',
                $result === 'denied' => 'Confirm '.$actorLabel.' should be denied this ability.',
                default => 'Confirm '.$actorLabel.' should receive this ability.',
            };
            $handlerGuidance = $handlerKind === 'callback' && $handlerName === 'Gate callback'
                ? 'the configured Gate callback'
                : $handlerShortName;
            $unexpectedGuidance = match ($argumentCount) {
                0 => 'If this result is unexpected, review '.$handlerGuidance.'.',
                1 => 'If this result is unexpected, compare the supplied target with '.$handlerGuidance.'.',
                default => 'If this result is unexpected, compare all '.number_format($argumentCount).' supplied arguments with '.$handlerGuidance.'.',
            };
            $checkNext = $actorGuidance.' '.$unexpectedGuidance;
            $copyEvidence = json_encode([
                'result' => $result,
                'ability' => $ability,
                'actor' => $item['actor'] ?? ['type' => $actorType],
                'arguments' => $item['arguments'] ?? $item['argument_types'] ?? [],
                'configured_handler' => [
                    'kind' => $handlerKind,
                    'name' => $handlerName,
                    'source' => $handlerSource,
                ],
                'result_reason' => [
                    'message' => $resultMessage,
                    'code' => $resultCode,
                    'status' => $resultStatus,
                ],
                'evaluation_source' => $callsite,
                'stack' => $stack,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';

            return [
                'execution' => (int) ($item['execution'] ?? ($index + 1)),
                'ability' => $ability,
                'result' => $result,
                'result_label' => ucfirst($result),
                'result_message' => $resultMessage,
                'result_code' => $resultCode,
                'result_status' => $resultStatus,
                'actor_label' => $actorLabel,
                'actor_type' => $actorType,
                'actor_name' => $actorName,
                'actor_identifier_name' => $actorIdentifierName,
                'actor_identifier' => $actorIdentifier,
                'arguments' => $arguments,
                'argument_summary' => $argumentSummary,
                'handler_kind' => $handlerKind,
                'handler_name' => $handlerName,
                'handler_short_name' => $handlerShortName,
                'handler_source_label' => $handlerSourceLabel,
                'callsite_label' => $callsiteLabel,
                'callsite_short_label' => $callsiteShortLabel,
                'stack' => $stack,
                'check_next' => $checkNext,
                'copy_evidence' => $copyEvidence,
                'search' => mb_strtolower(implode(' ', array_filter([
                    $ability,
                    $result,
                    $actorLabel,
                    $actorType,
                    $argumentSummary,
                    $handlerName,
                    $handlerSourceLabel,
                    $callsiteLabel,
                    $resultMessage,
                    $resultCode,
                ], static fn (mixed $value): bool => is_scalar($value)))),
            ];
        })
        ->values()
        ->all();
    $authorizationCounts = array_count_values(array_column($authorizationItems, 'result'));
    $authorizationFilters = [
        'all' => ['All', count($authorizationItems)],
        'denied' => ['Denied', $authorizationCounts['denied'] ?? 0],
        'allowed' => ['Allowed', $authorizationCounts['allowed'] ?? 0],
    ];
@endphp

<div
    data-ndb-authorization
    x-init="
        initializeAuthorization(
            JSON.parse(atob($el.querySelector('[data-ndb-authorization-payload]').textContent.trim())),
        )
    "
    class="ndb:space-y-4 ndb:lg:flex ndb:lg:min-h-0 ndb:lg:flex-1 ndb:lg:flex-col ndb:lg:space-y-0"
>
    <script type="application/json" data-ndb-authorization-payload>
        {{ base64_encode(\Illuminate\Support\Js::encode($authorizationItems)) }}
    </script>

    @if ($authorizationItems !== [])
        <x-newdebugbar::inspector-workspace frame="top" data-ndb-authorization-workspace>
            <x-newdebugbar::inspector-list-panel detail-open="authorizationDetailOpen" list-ref="authorizationList">
                <x-slot:controls>
                    <x-newdebugbar::inspector-list-controls :show-search="count($authorizationItems) > 5">
                        <x-slot:leading>
                            <p
                                data-ndb-authorization-summary
                                class="ndb:min-w-0 ndb:text-xs ndb:font-semibold ndb:text-zinc-600 ndb:dark:text-zinc-300"
                            >
                                <span data-ndb-authorization-summary-count class="ndb:block">
                                    {{ number_format(count($authorizationItems)) }} {{ \Illuminate\Support\Str::plural('decision', count($authorizationItems)) }}
                                </span>
                                <span
                                    x-show.important="visibleAuthorizationCount !== authorizationDecisions.length"
                                    class="ndb:mt-0.5 ndb:block ndb:text-[11px] ndb:font-medium ndb:text-zinc-400"
                                >
                                    <span x-text="visibleAuthorizationCount"></span> shown
                                </span>
                            </p>
                        </x-slot:leading>

                        <x-slot:search>
                            <x-newdebugbar::search-field
                                label="Search authorization decisions"
                                placeholder="Search ability, actor, or target"
                                data-ndb-authorization-search
                                x-model="authorizationSearch"
                                @input.debounce.100ms="applyAuthorizationView()"
                            />
                        </x-slot:search>

                        <x-slot:filter>
                            <x-newdebugbar::select-field
                                label="Filter authorization decisions"
                                data-ndb-authorization-filter-control
                                x-model="authorizationFilter"
                                @change="setAuthorizationFilter($event.target.value)"
                            >
                                @foreach ($authorizationFilters as $filter => [$label, $count])
                                    <option value="{{ $filter }}" data-ndb-authorization-filter="{{ $filter }}">
                                        {{ $label }} ({{ $count }})
                                    </option>
                                @endforeach
                            </x-newdebugbar::select-field>
                        </x-slot:filter>
                    </x-newdebugbar::inspector-list-controls>
                </x-slot:controls>

                <x-slot:list data-ndb-authorization-list>
                    @foreach ($authorizationItems as $decision)
                        <button
                            type="button"
                            data-ndb-authorization-item="{{ $decision['execution'] }}"
                            data-ndb-authorization-execution="{{ $decision['execution'] }}"
                            data-ndb-authorization-result="{{ $decision['result'] }}"
                            data-ndb-authorization-search-value="{{ $decision['search'] }}"
                            wire:key="authorization-{{ $decision['execution'] }}"
                            @click="selectAuthorizationDecision({{ $decision['execution'] }})"
                            :aria-pressed="authorizationSelected === {{ $decision['execution'] }}"
                            :class="authorizationSelected === {{ $decision['execution'] }}
                                ? 'ndb:bg-indigo-50/65 ndb:dark:bg-indigo-950/20'
                                : 'ndb:hover:bg-zinc-50/80 ndb:dark:hover:bg-zinc-900/60'"
                            class="ndb:grid ndb:h-auto ndb:w-full ndb:grid-cols-[minmax(0,1fr)_4.75rem] ndb:items-start ndb:gap-x-3 ndb:gap-y-1.5 ndb:px-3 ndb:py-3 ndb:text-left ndb:transition-colors ndb:focus-visible:relative ndb:focus-visible:z-10 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
                        >
                            <span
                                data-ndb-authorization-ability
                                class="ndb:min-w-0 ndb:break-words ndb:text-xs ndb:font-bold ndb:leading-5"
                            >{{ $decision['ability'] }}</span>
                            <span
                                data-ndb-authorization-result-label
                                @class([
                                    'ndb:w-full ndb:bg-transparent ndb:pt-0.5 ndb:text-right ndb:text-[11px] ndb:font-bold',
                                    'ndb:text-emerald-700 ndb:dark:text-emerald-300' => $decision['result'] === 'allowed',
                                    'ndb:text-red-700 ndb:dark:text-red-300' => $decision['result'] === 'denied',
                                ])
                            >{{ $decision['result_label'] }}</span>
                            <span class="ndb:col-span-2 ndb:grid ndb:min-w-0 ndb:grid-cols-[4.75rem_minmax(0,1fr)] ndb:gap-x-2 ndb:gap-y-1 ndb:text-[11px] ndb:leading-4">
                                <span class="ndb:font-semibold ndb:text-zinc-400">Actor</span>
                                <span data-ndb-authorization-actor class="ndb:min-w-0">
                                    <span class="ndb:block ndb:min-w-0 ndb:truncate ndb:font-semibold ndb:text-zinc-600 ndb:dark:text-zinc-300">{{ $decision['actor_label'] }}</span>
                                </span>
                                <span class="ndb:font-semibold ndb:text-zinc-400">Arguments</span>
                                <span data-ndb-authorization-target class="ndb:min-w-0">
                                    <span class="ndb:block ndb:min-w-0 ndb:truncate ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $decision['argument_summary'] }}</span>
                                </span>
                            </span>
                        </button>
                    @endforeach
                </x-slot:list>

                <x-slot:empty x-show.important="visibleAuthorizationCount === 0">
                    <x-newdebugbar::empty-state label="No authorization decisions match these filters." />
                </x-slot:empty>
            </x-newdebugbar::inspector-list-panel>

            <x-newdebugbar::authorization-detail />
        </x-newdebugbar::inspector-workspace>
    @else
        <x-newdebugbar::empty-state label="No authorization decisions were captured." />
    @endif
</div>
