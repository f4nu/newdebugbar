{{-- Renders queued and executed jobs. --}}
<dl class="ndb:grid ndb:grid-cols-2 ndb:divide-x ndb:divide-y ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:sm:grid-cols-4 ndb:sm:divide-y-0 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
    @foreach ([['Queued', $section['summary']['queued_count']], ['Executed', $section['summary']['executed_count']], ['Failures', $section['summary']['failed_count']], ['Run time', $section['summary']['duration_ms'].' ms']] as [$label, $value])
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
            wire:key="queue-{{ $index }}"
            class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:px-3.5 ndb:py-3 {{ ($item['kind'] ?? null) === 'failed' ? 'ndb:border-red-200 ndb:bg-red-50/35 ndb:dark:border-red-950 ndb:dark:bg-red-950/15' : 'ndb:border-zinc-200 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30' }}"
        >
            <span class="ndb:w-16 ndb:shrink-0 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider {{ ($item['kind'] ?? null) === 'failed' ? 'ndb:text-red-600 ndb:dark:text-red-300' : 'ndb:text-zinc-400' }}">{{ $item['kind'] }}</span>
            <div class="ndb:min-w-0 ndb:flex-1">
                <code
                    title="{{ $item['job'] }}"
                    class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold"
                >{{ class_basename($item['job']) }}</code>
                <p class="ndb:mt-1 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
                    <span>{{ $item['connection'] }}</span><span>{{ $item['queue'] ?: 'default queue' }}</span>
                    @if (($item['attempt'] ?? null) !== null)
                        <span>Attempt {{ $item['attempt'] }}</span>
                    @endif
                </p>
            </div>
            @if (($item['kind'] ?? null) !== 'queued')
                <span class="ndb:shrink-0 ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $item['duration_ms'] }} ms</span>
            @elseif (($item['delay_seconds'] ?? null) !== null)
                <span class="ndb:shrink-0 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">{{ $item['delay_seconds'] }} s delay</span>
            @endif
        </article>
    @empty
        <x-newdebugbar::empty-state label="No queue activity was captured." />
    @endforelse
</div>
