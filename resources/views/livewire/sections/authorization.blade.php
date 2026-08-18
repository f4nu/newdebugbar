{{-- Renders authorization decisions and their source context. --}}
@php($authorizationItems = $section['payload']['items'])
@if ($authorizationItems !== [])
    @php($authorizationCounts = array_count_values(array_column($authorizationItems, 'result')))
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
    <p data-ndb-authorization-result-count class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
        <span x-text="visibleAuthorizationCount"></span> results
    </p>
    <div x-ref="authorizationItems" class="ndb:space-y-2">
        @foreach ($authorizationItems as $index => $item)
            <article
                data-ndb-authorization-item
                data-result="{{ $item['result'] }}"
                wire:key="authorization-{{ $index }}"
                class="ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:px-3.5 ndb:py-3 {{ $item['result'] === 'allowed' ? 'ndb:border-emerald-200 ndb:bg-emerald-50/35 ndb:dark:border-emerald-950 ndb:dark:bg-emerald-950/15' : 'ndb:border-red-200 ndb:bg-red-50/35 ndb:dark:border-red-950 ndb:dark:bg-red-950/15' }}"
            >
                <span class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider {{ $item['result'] === 'allowed' ? 'ndb:text-emerald-700 ndb:dark:text-emerald-300' : 'ndb:text-red-700 ndb:dark:text-red-300' }}">{{ $item['result'] }}</span>
                <code class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-bold">{{ $item['ability'] }}</code>
                <span
                    title="{{ $item['handler'] }}"
                    class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400"
                >{{ str($item['handler'])->afterLast('\\') }}</span>
                <p class="ndb:w-full ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                    {{ implode(', ', array_filter([$item['user_type'] ?? null, ...($item['argument_types'] ?? [])])) ?: 'No typed arguments' }}
                </p>
                @if (is_array($item['callsite'] ?? null))
                    <p class="ndb:w-full ndb:min-w-0 ndb:truncate ndb:text-[11px] ndb:text-zinc-400">
                        <span class="ndb:min-w-0 ndb:flex-1 ndb:truncate">{{ $item['callsite']['copy'] ?? (($item['callsite']['file'] ?? 'Unknown source').':'.($item['callsite']['line'] ?? '?')) }}</span>
                    </p>
                @endif
            </article>
        @endforeach
    </div>
    <div x-show.important="visibleAuthorizationCount === 0">
        <x-newdebugbar::empty-state label="No authorization decisions match this filter." />
    </div>
@else
    <x-newdebugbar::empty-state label="No authorization decisions were captured." />
@endif
