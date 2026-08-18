{{-- Renders outbound HTTP requests. --}}
<dl class="ndb:grid ndb:grid-cols-3 ndb:divide-x ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
    @foreach ([['Requests', $section['summary']['count']], ['Total time', $section['summary']['duration_ms'].' ms'], ['Failures', $section['summary']['failed_count']]] as [$label, $value])
        <div class="ndb:px-3.5 ndb:py-3">
            <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                {{ $label }}
            </dt>
            <dd class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $value }}</dd>
        </div>
    @endforeach
</dl>
<div class="ndb:space-y-2">
    @forelse ($section['payload']['items'] as $index => $item)
        <article
            wire:key="http-client-{{ $index }}"
            class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:px-3.5 ndb:py-3 {{ ($item['failed'] ?? false) ? 'ndb:border-red-200 ndb:bg-red-50/35 ndb:dark:border-red-950 ndb:dark:bg-red-950/15' : 'ndb:border-zinc-200 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30' }}"
        >
            <span class="ndb:rounded-md ndb:bg-indigo-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300">{{ $item['method'] }}</span>
            <div class="ndb:min-w-0 ndb:flex-1">
                <p class="ndb:truncate ndb:text-xs ndb:font-semibold">{{ $item['url'] }}</p>
                <p class="ndb:mt-1 ndb:text-[11px] ndb:font-semibold {{ ($item['failed'] ?? false) ? 'ndb:text-red-700 ndb:dark:text-red-300' : 'ndb:text-zinc-400' }}">
                    {{ ($item['failed'] ?? false) ? ($item['exception_message'] ?? $item['exception_class'] ?? 'Request failed') : 'HTTP '.$item['status'] }}
                </p>
                @if (is_array($item['callsite'] ?? null))
                    <p class="ndb:mt-1 ndb:min-w-0 ndb:truncate ndb:text-[11px] ndb:text-zinc-400">
                        <span class="ndb:min-w-0 ndb:truncate">{{ $item['callsite']['copy'] ?? (($item['callsite']['file'] ?? 'Unknown source').':'.($item['callsite']['line'] ?? '?')) }}</span>
                    </p>
                @endif
            </div>
            <span class="ndb:shrink-0 ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $item['duration_ms'] }} ms</span>
        </article>
    @empty
        <x-newdebugbar::empty-state label="No outbound HTTP requests were captured." />
    @endforelse
</div>
