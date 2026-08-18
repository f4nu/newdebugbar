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
    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-1.5 ndb:py-2 ndb:text-[10px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300">
        Query actions
        <x-newdebugbar::icon name="chevron-down" class="ndb-details-chevron ndb:size-3 ndb:transition" />
    </summary>
    <div class="ndb:absolute ndb:top-full ndb:right-0 ndb:z-20 ndb:min-w-40 ndb:overflow-hidden ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white ndb:p-1 ndb:shadow-lg ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900">
        <button
            type="button"
            @click="copyText(@js($sql)); $el.closest('details').open = false"
            class="ndb:block ndb:w-full ndb:rounded-md ndb:px-2.5 ndb:py-2 ndb:text-left ndb:text-[10px] ndb:font-semibold ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-zinc-800"
        >
            Copy SQL
        </button>
        @if (($query['runnable_available'] ?? false) && is_string($query['runnable_sql'] ?? null))
            <button
                type="button"
                @click="copyText(@js($query['runnable_sql'])); $el.closest('details').open = false"
                class="ndb:block ndb:w-full ndb:rounded-md ndb:px-2.5 ndb:py-2 ndb:text-left ndb:text-[10px] ndb:font-semibold ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-zinc-800"
            >
                Copy runnable SQL
            </button>
            <button
                type="button"
                wire:click="explainQuery({{ $query['execution'] }})"
                wire:loading.attr="disabled"
                wire:target="explainQuery({{ $query['execution'] }})"
                data-ndb-query-explain-action
                @click="
                    queryExplainScrollTop = $el.closest('#newdebugbar')?.querySelector('main')?.scrollTop ?? null;
                    $el.closest('details').open = false;
                "
                class="ndb:block ndb:w-full ndb:rounded-md ndb:px-2.5 ndb:py-2 ndb:text-left ndb:text-[10px] ndb:font-semibold ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:opacity-50 ndb:dark:hover:bg-zinc-800"
            >
                Explain query
            </button>
        @endif
        @if (($query['callsite'] ?? null) !== null)
            <button
                type="button"
                @click="copyText(@js($query['callsite']['file'].':'.$query['callsite']['line'])); $el.closest('details').open = false"
                class="ndb:block ndb:w-full ndb:rounded-md ndb:px-2.5 ndb:py-2 ndb:text-left ndb:text-[10px] ndb:font-semibold ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-zinc-800"
            >
                Copy call site
            </button>
        @endif
    </div>
</details>
