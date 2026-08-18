{{-- Renders cache operations and grouped keys. --}}
<dl class="ndb:grid ndb:grid-cols-2 ndb:divide-x ndb:divide-y ndb:divide-zinc-200 ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:sm:grid-cols-4 ndb:sm:divide-y-0 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
    @foreach ([['Hit rate', $section['summary']['hit_rate'].'%'], ['Hits', $section['summary']['hits']], ['Misses', $section['summary']['misses']], ['Writes', $section['summary']['writes']]] as [$label, $value])
        <div class="ndb:px-3 ndb:py-2.5">
            <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                {{ $label }}
            </dt>
            <dd class="ndb:mt-0.5 ndb:text-sm ndb:font-bold ndb:tabular-nums">{{ $value }}</dd>
        </div>
    @endforeach
</dl>
@if ($section['payload']['repeated_misses'] !== [])
    <div class="ndb:rounded-xl ndb:border ndb:border-amber-200 ndb:bg-amber-50/40 ndb:p-4 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/20">
        <h3 class="ndb:text-xs ndb:font-bold">Repeated misses</h3>
        <div class="ndb:mt-3 ndb:space-y-2">
            @foreach ($section['payload']['repeated_misses'] as $miss)
                <div class="ndb:flex ndb:items-center ndb:gap-3">
                    <span class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-[11px]"
                        >Protected key #{{ substr($miss['key_hash'], 0, 8) }}</span
                    ><span class="ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $miss['count'] }} misses</span>
                </div>
            @endforeach
        </div>
    </div>
@endif
<div class="ndb:space-y-2">
    @foreach ($section['payload']['items'] as $index => $item)
        <details
            wire:key="cache-{{ $index }}"
            class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"
        >
            <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3 ndb:text-xs ndb:font-semibold">
                <span
                    class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:text-zinc-400"
                    >{{ $item['operation'] }}</span
                ><span
                    class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-[11px]"
                    >{{ ($item['key_policy'] ?? 'hash') === 'full' ? ($item['key'] ?? 'No key') : (isset($item['key_hash']) ? 'Protected key #'.substr($item['key_hash'], 0, 8) : 'No key') }}</span
                ><span class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">Details</span
                ><x-newdebugbar::icon
                    name="chevron-down"
                    class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
                />
            </summary>
            <pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="json">{{ json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
        </details>
    @endforeach
</div>
