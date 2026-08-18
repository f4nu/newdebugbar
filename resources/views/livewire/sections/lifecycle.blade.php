{{-- Renders Laravel request lifecycle events. --}}
<div class="ndb:space-y-2">
    @forelse ($section['payload']['items'] as $index => $item)
        <article
            wire:key="lifecycle-{{ $index }}"
            class="ndb:flex ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:px-3.5 ndb:py-3 ndb:dark:border-zinc-800"
        >
            <span class="ndb:text-[11px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-400">#{{ $index + 1 }}</span
            ><span class="ndb:min-w-0 ndb:flex-1 ndb:text-xs ndb:font-semibold">{{ $item['name'] }}</span
            ><span class="ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $item['duration_ms'] }} ms</span>
        </article>
    @empty
        <x-newdebugbar::empty-state label="Laravel did not expose lifecycle spans for this profile." />
    @endforelse
</div>
