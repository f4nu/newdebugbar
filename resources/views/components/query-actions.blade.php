@props([
    'query',
    'identity',
    'sql',
])

<details
    data-ndb-query-actions="{{ $identity }}"
    {{ $attributes->class('ndb:relative') }}
    @click.outside="$el.open = false"
    @keydown.escape.stop="
        $el.open = false;
        $el.querySelector('summary')?.focus();
    "
>
    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-1.5 ndb:py-2 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300">
        Query actions
        <x-newdebugbar::icon name="chevron-down" class="ndb-details-chevron ndb:size-3 ndb:transition" />
    </summary>
    <x-newdebugbar::popover-surface
        direction="below"
        width-class="ndb:w-56"
        arrow-class="ndb:right-[41px]"
        data-ndb-query-actions-popover
        role="menu"
        aria-label="Query actions"
    >
        <button
            type="button"
            role="menuitem"
            @click="copyText(@js($sql)); $el.closest('details').open = false"
            class="ndb:flex ndb:min-h-11 ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-left ndb:transition-colors ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-white/10"
        >
            <x-newdebugbar::icon name="copy" class="ndb:size-4 ndb:text-zinc-500 ndb:dark:text-zinc-400" />
            <span class="ndb:text-sm ndb:font-medium">Copy SQL</span>
        </button>
        @if (($query['runnable_available'] ?? false) && is_string($query['runnable_sql'] ?? null))
            <button
                type="button"
                role="menuitem"
                @click="copyText(@js($query['runnable_sql'])); $el.closest('details').open = false"
                class="ndb:flex ndb:min-h-11 ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-left ndb:transition-colors ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-white/10"
            >
                <x-newdebugbar::icon name="code" class="ndb:size-4 ndb:text-zinc-500 ndb:dark:text-zinc-400" />
                <span class="ndb:text-sm ndb:font-medium">Copy runnable SQL</span>
            </button>
            <button
                type="button"
                role="menuitem"
                wire:click="explainQuery({{ $query['execution'] }})"
                wire:loading.attr="disabled"
                wire:target="explainQuery({{ $query['execution'] }})"
                data-ndb-query-explain-action
                @click="
                    queryExplainScrollTop = $el.closest('#newdebugbar')?.querySelector('main')?.scrollTop ?? null;
                    $el.closest('details').open = false;
                "
                class="ndb:flex ndb:min-h-11 ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-left ndb:transition-colors ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:opacity-50 ndb:dark:hover:bg-white/10"
            >
                <x-newdebugbar::icon name="search" class="ndb:size-4 ndb:text-zinc-500 ndb:dark:text-zinc-400" />
                <span class="ndb:text-sm ndb:font-medium">Explain query</span>
            </button>
        @endif
        @if (($query['callsite'] ?? null) !== null)
            <button
                type="button"
                role="menuitem"
                @click="copyText(@js($query['callsite']['file'].':'.$query['callsite']['line'])); $el.closest('details').open = false"
                class="ndb:flex ndb:min-h-11 ndb:w-full ndb:items-center ndb:gap-3 ndb:rounded-lg ndb:px-3 ndb:py-2 ndb:text-left ndb:transition-colors ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-white/10"
            >
                <x-newdebugbar::icon name="copy" class="ndb:size-4 ndb:text-zinc-500 ndb:dark:text-zinc-400" />
                <span class="ndb:text-sm ndb:font-medium">Copy call site</span>
            </button>
        @endif
    </x-newdebugbar::popover-surface>
</details>
