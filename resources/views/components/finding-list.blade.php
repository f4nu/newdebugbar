@props([
    'findings',
    'title' => 'Findings',
])

@if ($findings !== [])
    <div data-ndb-findings class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/55 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/35">
        <div class="ndb:flex ndb:items-center ndb:justify-between ndb:border-b ndb:border-zinc-200/80 ndb:px-4 ndb:py-3 ndb:dark:border-zinc-800"><h3 class="ndb:text-xs ndb:font-bold">{{ $title }}</h3><span class="ndb:text-[10px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-400">{{ count($findings) }}</span></div>
        <div class="ndb:divide-y ndb:divide-zinc-200/80 ndb:dark:divide-zinc-800">
            @foreach ($findings as $index => $finding)
                <details data-ndb-finding="{{ $finding['rule_id'] }}" class="ndb:group ndb:px-4 ndb:py-3">
                    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-start ndb:gap-3">
                        <span class="ndb:mt-1 ndb:size-1.5 ndb:shrink-0 ndb:rounded-full {{ $finding['severity'] === 'error' ? 'ndb:bg-red-500' : ($finding['severity'] === 'warning' ? 'ndb:bg-amber-500' : 'ndb:bg-indigo-500') }}"></span>
                        <span class="ndb:min-w-0 ndb:flex-1"><span class="ndb:block ndb:text-xs ndb:font-semibold">{{ $finding['summary'] }}</span><span class="ndb:mt-0.5 ndb:block ndb:font-mono ndb:text-[9px] ndb:text-zinc-400">{{ $finding['rule_id'] }}</span></span>
                        <x-new-debug-bar::icon name="chevron-down" class="ndb-details-chevron ndb:mt-0.5 ndb:size-3.5 ndb:text-zinc-400 ndb:transition" />
                    </summary>
                    <pre class="ndb-code ndb-scrollbar ndb:mt-3"><code data-ndb-language="json">{{ json_encode($finding['evidence'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                </details>
            @endforeach
        </div>
    </div>
@endif
