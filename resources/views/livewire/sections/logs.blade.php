{{-- Renders captured log records. --}}
@php($logLevels = array_values(array_unique(array_column($section['payload']['items'], 'level'))))
<div class="ndb:flex ndb:flex-col ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:pb-3 ndb:sm:flex-row ndb:sm:items-end ndb:dark:border-zinc-800">
    <div class="ndb:min-w-0 ndb:flex-1">
        <p class="ndb:mb-1.5 ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
            Level
        </p>
        <div class="ndb:flex ndb:gap-1 ndb:overflow-x-auto" role="group" aria-label="Filter logs by level">
            <x-newdebugbar::filter-tab
                data-ndb-log-level="all"
                @click="setLogLevel('all')"
                ::aria-pressed="logLevel === 'all'"
            >
                All
            </x-newdebugbar::filter-tab>
            @foreach ($logLevels as $level)
                <x-newdebugbar::filter-tab
                    data-ndb-log-level="{{ $level }}"
                    @click="setLogLevel({{ \Illuminate\Support\Js::from($level) }})"
                    ::aria-pressed="logLevel === {{ \Illuminate\Support\Js::from($level) }}"
                >
                    {{ strtoupper($level) }}
                </x-newdebugbar::filter-tab>
            @endforeach
        </div>
    </div>
    <label class="ndb:sm:w-72"
        ><span
            class="ndb:mb-1.5 ndb:block ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
            >Search</span
        ><input
            data-ndb-log-search
            x-model="logSearch"
            @input.debounce.100ms="applyLogFilters()"
            type="search"
            placeholder="Log message"
            class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:px-3 ndb:text-xs ndb:outline-none ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
    /></label>
</div>
<p class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400"><span x-text="visibleLogCount"></span> logs</p>
<div x-ref="logList" class="ndb:space-y-2">
    @foreach ($section['payload']['items'] as $index => $item)
        @php($logCallsite = $item['callsite'] ?? null)
        <details
            data-ndb-log-item
            data-level="{{ $item['level'] }}"
            data-search="{{ mb_strtolower($item['message']) }}"
            wire:key="log-{{ $index }}"
            class="ndb:group ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:border-zinc-800"
        >
            <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-3 ndb:px-3.5 ndb:py-3">
                <span class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:text-zinc-400">{{ $item['level'] }}</span
                ><span class="ndb:min-w-0 ndb:flex-1"
                    ><span class="ndb:block ndb:truncate ndb:text-xs ndb:font-semibold">{{ $item['message'] }}</span>
                    @if ($logCallsite)
                        <span class="ndb:mt-0.5 ndb:block ndb:truncate ndb:text-[11px] ndb:text-zinc-400">{{ $logCallsite['file'] }}:{{ $logCallsite['line'] }}</span>
                    @endif</span
                ><span class="ndb:text-[11px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-400"
                    >{{ $item['at_ms'] }} ms</span
                ><x-newdebugbar::icon
                    name="chevron-down"
                    class="ndb:size-3.5 ndb:text-zinc-400 ndb:transition ndb:group-open:rotate-180"
                />
            </summary>
            @if ($logCallsite)
                <div class="ndb:flex ndb:justify-end ndb:gap-3 ndb:border-t ndb:border-zinc-200 ndb:px-3 ndb:py-2 ndb:dark:border-zinc-800">
                    <button
                        type="button"
                        data-ndb-copy-log-callsite="{{ $index }}"
                        @click="copyText(@js($logCallsite['file'].':'.$logCallsite['line']))"
                        class="ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                    >
                        Copy file and line
                    </button>
                </div>
            @endif
            <pre class="ndb-code ndb-scrollbar ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"><code data-ndb-language="json">{{ json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
        </details>
    @endforeach
</div>
<div x-show.important="visibleLogCount === 0">
    <x-newdebugbar::empty-state label="No logs match these filters." />
</div>
