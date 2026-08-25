{{-- Renders collector data that has no specialized section view. --}}
@forelse ($section['payload']['items'] as $index => $item)
    <details
        wire:key="{{ $sectionKey }}-{{ $index }}"
        class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"
    >
        <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3 ndb:text-xs ndb:font-semibold ndb:transition ndb:hover:bg-zinc-50 ndb:dark:hover:bg-zinc-900">
            <span class="ndb:text-[11px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-400">#{{ $index + 1 }}</span
            ><span
                class="ndb:min-w-0 ndb:flex-1 ndb:truncate"
                >{{ $item['model'] ?? $item['name'] ?? $item['event'] ?? $item['level'] ?? $item['operation'] ?? $section['label'] }}</span
            ><x-newdebugbar::icon
                name="chevron-down"
                class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
            />
        </summary>
        <x-newdebugbar::code-block
            language="json"
            class="ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"
        >{{ json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</x-newdebugbar::code-block>
    </details>
@empty
    <x-newdebugbar::empty-state :label="'No '.strtolower($section['label']).' were captured.'" />
@endforelse
