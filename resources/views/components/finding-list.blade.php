@props([
    'findings',
    'title' => 'Findings',
])

@if ($findings !== [])
    <div data-ndb-findings class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/55 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/35">
        <div class="ndb:flex ndb:items-center ndb:justify-between ndb:border-b ndb:border-zinc-200/80 ndb:px-4 ndb:py-3 ndb:dark:border-zinc-800"><h3 class="ndb:text-xs ndb:font-bold">{{ $title }}</h3><span class="ndb:text-[10px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-400">{{ count($findings) }}</span></div>
        <div class="ndb:divide-y ndb:divide-zinc-200/80 ndb:dark:divide-zinc-800">
            @foreach ($findings as $index => $finding)
                <article data-ndb-finding="{{ $finding['rule_id'] }}" class="ndb:px-4 ndb:py-3.5">
                    <div class="ndb:flex ndb:items-start ndb:gap-3">
                        <span class="ndb:mt-1.5 ndb:size-2 ndb:shrink-0 ndb:rounded-full {{ $finding['severity'] === 'error' ? 'ndb:bg-red-500' : ($finding['severity'] === 'warning' ? 'ndb:bg-amber-500' : 'ndb:bg-indigo-500') }}"></span>
                        <div class="ndb:min-w-0 ndb:flex-1">
                            <p class="ndb:text-xs ndb:font-bold">{{ $finding['summary'] }}</p>
                            @if (is_string($finding['why'] ?? null))
                                <p class="ndb:mt-1 ndb:text-[11px] ndb:leading-relaxed ndb:text-zinc-600 ndb:dark:text-zinc-300">{{ $finding['why'] }}</p>
                            @endif
                            @if (is_array($finding['location'] ?? null) && is_string($finding['location']['file'] ?? null))
                                <div class="ndb:mt-2 ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:items-center ndb:gap-2 ndb:text-[10px]"><span class="ndb:font-bold ndb:text-zinc-400">Where</span><code class="ndb:min-w-0 ndb:max-w-full ndb:truncate">{{ $finding['location']['file'] }}:{{ $finding['location']['line'] ?? 1 }}</code>@if (is_string($finding['location']['editor_url'] ?? null))<a href="{{ $finding['location']['editor_url'] }}" class="ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300">Open</a>@endif</div>
                            @endif
                            @if (is_string($finding['next'] ?? null))
                                <p class="ndb:mt-2 ndb:text-[10px] ndb:leading-relaxed ndb:text-zinc-500 ndb:dark:text-zinc-400"><span class="ndb:font-bold ndb:text-zinc-600 ndb:dark:text-zinc-300">Next</span> {{ $finding['next'] }}</p>
                            @endif
                            <div class="ndb:mt-3 ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-3">
                                @if (is_array($finding['action'] ?? null) && is_string($finding['action']['section'] ?? null))
                                    <button type="button" data-ndb-finding-action="{{ $finding['rule_id'] }}" @click="selectSection(@js($finding['action']['section'])); @if (is_string($finding['action']['filter'] ?? null)) $nextTick(() => reviewQueryEvidence(@js($finding['action']['filter']))) @endif" class="ndb:rounded-lg ndb:bg-zinc-900 ndb:px-3 ndb:py-2 ndb:text-[10px] ndb:font-bold ndb:text-white ndb:transition ndb:hover:bg-indigo-700 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:bg-zinc-100 ndb:dark:text-zinc-900 ndb:dark:hover:bg-indigo-300">{{ $finding['action']['label'] }}</button>
                                @endif
                                <details class="ndb:group">
                                    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-1.5 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"><span>Technical evidence</span><x-new-debug-bar::icon name="chevron-down" class="ndb-details-chevron ndb:size-3 ndb:transition" /></summary>
                                    <div class="ndb:mt-2"><p class="ndb:mb-1 ndb:font-mono ndb:text-[9px] ndb:text-zinc-400">{{ $finding['rule_id'] }}</p><pre class="ndb-code ndb-scrollbar"><code data-ndb-language="json">{{ json_encode($finding['evidence'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre></div>
                                </details>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
@endif
