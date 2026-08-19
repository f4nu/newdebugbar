{{-- Renders authorization decisions as an adaptive actor-to-target ledger. --}}
@php
    $authorizationItems = $section['payload']['items'];
    $authorizationCounts = array_count_values(array_column($authorizationItems, 'result'));
    $shortType = static fn (string $type): string => class_basename($type);
@endphp
@if ($authorizationItems !== [])
    <div>
        <p class="ndb:mb-1.5 ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
            Filter
        </p>
        <x-newdebugbar::filter-tabs label="Filter authorization decisions">
            @foreach (['all' => count($authorizationItems), 'allowed' => $authorizationCounts['allowed'] ?? 0, 'denied' => $authorizationCounts['denied'] ?? 0] as $filter => $count)
                <x-newdebugbar::filter-tab
                    data-ndb-authorization-filter="{{ $filter }}"
                    @click="setAuthorizationFilter({{ \Illuminate\Support\Js::from($filter) }})"
                    ::aria-pressed="authorizationFilter === {{ \Illuminate\Support\Js::from($filter) }}"
                >
                    <span class="ndb:capitalize">{{ $filter }}</span>
                    <span class="ndb:text-[11px] ndb:font-bold ndb:tabular-nums ndb:opacity-65">{{ $count }}</span>
                </x-newdebugbar::filter-tab>
            @endforeach
        </x-newdebugbar::filter-tabs>
    </div>
    <ol
        x-ref="authorizationItems"
        class="ndb:m-0 ndb:list-none ndb:divide-y ndb:divide-zinc-200/90 ndb:p-0 ndb:dark:divide-zinc-800"
    >
        @foreach ($authorizationItems as $index => $item)
            @php
                $userType = is_string($item['user_type'] ?? null) ? $item['user_type'] : null;
                $actor = $userType === null ? 'Guest' : $shortType($userType);
                $targetTypes = array_values(array_filter(
                    $item['argument_types'] ?? [],
                    static fn (mixed $type): bool => is_string($type) && $type !== '',
                ));
                $targets = array_map($shortType, $targetTypes);
                $handler = is_string($item['handler'] ?? null) ? $item['handler'] : null;
                $showHandler = $handler !== null && $handler !== '' && $handler !== 'callback';
                $callsite = is_array($item['callsite'] ?? null) ? $item['callsite'] : null;
                $callsiteLabel = $callsite === null
                    ? null
                    : ($callsite['copy'] ?? (($callsite['file'] ?? 'Unknown source').':'.($callsite['line'] ?? '?')));
            @endphp
            <li
                data-ndb-authorization-item
                data-result="{{ $item['result'] }}"
                wire:key="authorization-{{ $index }}"
                class="ndb:min-w-0 ndb:py-3"
            >
                <div class="ndb:flex ndb:w-full ndb:min-w-0 ndb:flex-wrap ndb:items-baseline ndb:gap-x-3 ndb:gap-y-1 ndb:font-mono ndb:text-xs ndb:leading-5 ndb:sm:grid ndb:sm:gap-x-3 {{ $targets !== [] ? 'ndb:sm:grid-cols-[minmax(0,1fr)_minmax(1.5rem,0.5fr)_minmax(0,1fr)_minmax(1.5rem,0.5fr)_minmax(0,1fr)_minmax(1.5rem,0.5fr)_minmax(0,1fr)]' : 'ndb:sm:grid-cols-[minmax(0,1fr)_minmax(1.5rem,0.5fr)_minmax(0,1fr)_minmax(1.5rem,0.5fr)_minmax(0,1fr)]' }}">
                    <code
                        data-ndb-authorization-source
                        title="{{ $userType ?? 'Unauthenticated actor' }}"
                        class="ndb:font-bold"
                    >{{ $actor }}</code>
                    <span class="ndb:inline-flex ndb:min-w-0 ndb:items-baseline ndb:gap-3 ndb:sm:contents">
                        <span
                            data-ndb-authorization-connector
                            aria-hidden="true"
                            class="ndb:h-px ndb:w-5 ndb:shrink-0 ndb:self-center ndb:bg-zinc-300 ndb:dark:bg-zinc-700 ndb:sm:w-full"
                        ></span>
                        <span
                            data-ndb-authorization-result
                            class="ndb:font-semibold ndb:sm:text-center {{ $item['result'] === 'allowed' ? 'ndb:text-emerald-600 ndb:dark:text-emerald-400' : 'ndb:text-red-600 ndb:dark:text-red-400' }}"
                        >{{ $item['result'] }}</span>
                    </span>
                    <span class="ndb:inline-flex ndb:min-w-0 ndb:items-baseline ndb:gap-3 ndb:sm:contents">
                        <span
                            data-ndb-authorization-connector
                            aria-hidden="true"
                            class="ndb:h-px ndb:w-5 ndb:shrink-0 ndb:self-center ndb:bg-zinc-300 ndb:dark:bg-zinc-700 ndb:sm:w-full"
                        ></span>
                        <code
                            data-ndb-authorization-ability
                            class="ndb:min-w-0 ndb:break-words ndb:font-bold ndb:sm:text-center"
                        >{{ $item['ability'] }}</code>
                    </span>
                    @if ($targets !== [])
                        <span class="ndb:inline-flex ndb:min-w-0 ndb:items-baseline ndb:gap-3 ndb:sm:contents">
                            <span
                                data-ndb-authorization-connector
                                aria-hidden="true"
                                class="ndb:h-px ndb:w-5 ndb:shrink-0 ndb:self-center ndb:bg-zinc-300 ndb:dark:bg-zinc-700 ndb:sm:w-full"
                            ></span>
                            <code
                                data-ndb-authorization-target
                                title="{{ implode(', ', $targetTypes) }}"
                                class="ndb:min-w-0 ndb:break-words ndb:font-bold ndb:sm:text-right"
                            >{{ implode(', ', $targets) }}</code>
                        </span>
                    @endif
                </div>
                @if ($callsiteLabel !== null || $showHandler)
                    <div class="ndb:mt-1 ndb:flex ndb:w-full ndb:min-w-0 ndb:flex-wrap ndb:items-baseline ndb:gap-x-3 ndb:gap-y-1 ndb:text-[11px] ndb:leading-5 ndb:text-zinc-400 ndb:sm:justify-between">
                        @if ($callsiteLabel !== null)
                            <span class="ndb:inline-flex ndb:min-w-0 ndb:items-baseline ndb:gap-2">
                                <span aria-hidden="true">↳</span>
                                <code
                                    data-ndb-authorization-callsite
                                    title="{{ $callsiteLabel }}"
                                    class="ndb:min-w-0 ndb:break-words ndb:font-mono"
                                >{{ $callsiteLabel }}</code>
                            </span>
                        @endif
                        @if ($showHandler)
                            <span data-ndb-authorization-handler title="{{ $handler }}" class="ndb:font-mono"
                                >via {{ $shortType($handler) }}</span>
                        @endif
                    </div>
                @endif
            </li>
        @endforeach
    </ol>
    <div x-show.important="visibleAuthorizationCount === 0">
        <x-newdebugbar::empty-state label="No authorization decisions match this filter." />
    </div>
@else
    <x-newdebugbar::empty-state label="No authorization decisions were captured." />
@endif
