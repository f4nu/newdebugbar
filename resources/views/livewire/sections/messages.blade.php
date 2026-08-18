{{-- Renders developer messages recorded during the request. --}}
<div class="ndb:space-y-3">
    @forelse ($section['payload']['items'] as $index => $item)
        <article
            wire:key="message-{{ $index }}"
            class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"
        >
            <div class="ndb:flex ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3">
                <span class="ndb:min-w-0 ndb:flex-1 ndb:text-xs ndb:font-bold">{{ $item['label'] }}</span
                ><span class="ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
                    >{{ $item['at_ms'] }} ms</span>
            </div>
            @if (($item['context'] ?? []) !== [])
                <pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="json">{{ json_encode($item['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
            @endif
        </article>
    @empty
        <x-newdebugbar::empty-state label="No developer messages were recorded." />
    @endforelse
</div>
