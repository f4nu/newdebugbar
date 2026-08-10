@php($activity = $livewire['activity'] ?? [])
@php($outcome = $livewire['outcome'] ?? [])

<div data-ndb-livewire-overview class="ndb:space-y-6">
    @if (($livewire['findings'] ?? []) === [])
        <section
            data-ndb-livewire-healthy
            role="status"
            class="ndb:border-y ndb:border-emerald-200 ndb:bg-emerald-50/45 ndb:px-3.5 ndb:py-3.5 ndb:dark:border-emerald-950 ndb:dark:bg-emerald-950/15"
        >
            <h3 class="ndb:text-sm ndb:font-bold ndb:text-emerald-800 ndb:dark:text-emerald-300">
                No clear problem found
            </h3>
            <p class="ndb:mt-1 ndb:text-xs ndb:leading-5 ndb:text-emerald-700/80 ndb:dark:text-emerald-300/75">
                The captured Livewire work did not match a clear problem rule.
            </p>
        </section>
    @else
        <section data-ndb-livewire-findings>
            <div class="ndb:mb-3">
                <h3 class="ndb:text-sm ndb:font-bold">Problems to check</h3>
                <p class="ndb:mt-0.5 ndb:text-[10px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                    Only clear findings are shown here.
                </p>
            </div>
            <div class="ndb:divide-y ndb:divide-amber-200 ndb:border-y ndb:border-amber-200 ndb:bg-amber-50/45 ndb:dark:divide-amber-950 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/15">
                @foreach ($livewire['findings'] as $finding)
                    <article data-ndb-livewire-finding="{{ $finding['rule_id'] }}" class="ndb:px-3.5 ndb:py-4">
                        <h4 class="ndb:text-sm ndb:font-bold ndb:text-amber-950 ndb:dark:text-amber-100">
                            {{ $finding['summary'] }}
                        </h4>
                        <dl class="ndb:mt-3 ndb:grid ndb:gap-3 ndb:text-xs ndb:sm:grid-cols-3">
                            <div>
                                <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-amber-700/70 ndb:dark:text-amber-300/60">
                                    Impact
                                </dt>
                                <dd class="ndb:mt-1 ndb:leading-5 ndb:text-zinc-700 ndb:dark:text-zinc-300">
                                    {{ $finding['impact'] }}
                                </dd>
                            </div>
                            <div>
                                <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-amber-700/70 ndb:dark:text-amber-300/60">
                                    Origin
                                </dt>
                                <dd class="ndb:mt-1 ndb:leading-5 ndb:text-zinc-700 ndb:dark:text-zinc-300">
                                    {{ $finding['origin'] }}
                                </dd>
                            </div>
                            <div>
                                <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-amber-700/70 ndb:dark:text-amber-300/60">
                                    Next check
                                </dt>
                                <dd class="ndb:mt-1 ndb:leading-5 ndb:text-zinc-700 ndb:dark:text-zinc-300">
                                    {{ $finding['next'] }}
                                </dd>
                            </div>
                        </dl>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="ndb:grid ndb:gap-5 ndb:border-b ndb:border-zinc-200 ndb:pb-5 ndb:sm:grid-cols-[minmax(0,1.35fr)_minmax(12rem,0.65fr)] ndb:dark:border-zinc-800">
        <div class="ndb:min-w-0">
            <p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                What happened
            </p>
            <h3 class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tracking-tight ndb:text-zinc-950 ndb:dark:text-white">
                {{ $activity['title'] ?? 'Livewire request' }}
            </h3>
            <p class="ndb:mt-1.5 ndb:text-xs ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300">
                {{ $activity['detail'] ?? 'The exact trigger was not observed.' }}
            </p>
        </div>
        <div class="ndb:border-t ndb:border-zinc-200 ndb:pt-4 ndb:sm:border-t-0 ndb:sm:border-l ndb:sm:pt-0 ndb:sm:pl-5 ndb:dark:border-zinc-800">
            <p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Result</p>
            <p class="ndb:mt-1 ndb:text-sm ndb:font-bold">{{ $outcome['title'] ?? 'Result not observed' }}</p>
            <p class="ndb:mt-1 ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                {{ $outcome['detail'] ?? 'The final result was not observed.' }}
            </p>
        </div>
    </section>

    @if (($livewire['state_changes'] ?? []) !== [])
        <section data-ndb-livewire-overview-state>
            <div>
                <h3 class="ndb:text-sm ndb:font-bold">Changes</h3>
                <p class="ndb:mt-0.5 ndb:text-[10px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                    Safe server values before and after the request
                </p>
            </div>
            <div class="ndb:mt-3 ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800">
                @foreach ($livewire['state_changes'] as $change)
                    <div
                        data-ndb-livewire-change="{{ $change['id'] }}"
                        class="ndb:grid ndb:gap-2 ndb:border-b ndb:border-zinc-200 ndb:py-3 ndb:sm:grid-cols-[minmax(8rem,0.55fr)_minmax(0,1.45fr)] ndb:sm:items-center ndb:dark:border-zinc-800"
                    >
                        <div class="ndb:min-w-0">
                            <p class="ndb:truncate ndb:text-xs ndb:font-bold">{{ $change['path_label'] }}</p>
                            <code class="ndb:block ndb:truncate ndb:text-[10px] ndb:text-zinc-400">{{ $change['path'] }}</code>
                        </div>
                        <div class="ndb:grid ndb:min-w-0 ndb:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] ndb:items-center ndb:gap-2 ndb:text-xs">
                            <span
                                class="ndb:min-w-0 ndb:truncate ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                title="{{ $change['before_display'] }}"
                            >{{ $change['before_display'] }}</span>
                            <span aria-hidden="true" class="ndb:text-zinc-300 ndb:dark:text-zinc-700">→</span>
                            <strong
                                class="ndb:min-w-0 ndb:truncate"
                                title="{{ $change['after_display'] }}"
                            >{{ $change['after_display'] }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if (($livewire['server_work'] ?? []) !== [])
        <section data-ndb-livewire-overview-work>
            <div>
                <h3 class="ndb:text-sm ndb:font-bold">Important server work</h3>
                <p class="ndb:mt-0.5 ndb:text-[10px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                    Longest observed Livewire server steps
                </p>
            </div>
            <div class="ndb:mt-3 ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800">
                @foreach ($livewire['server_work'] as $work)
                    <div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:py-2.5 ndb:text-xs ndb:dark:border-zinc-800">
                        <div class="ndb:min-w-0 ndb:flex-1">
                            <p class="ndb:truncate ndb:font-bold">{{ $work['label'] }}</p>
                            <p class="ndb:truncate ndb:text-[10px] ndb:text-zinc-400">{{ $work['component_name'] }}</p>
                        </div>
                        <span class="ndb:shrink-0 ndb:font-semibold ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ number_format($work['duration_ms'], $work['duration_ms'] < 10 ? 2 : 1) }} ms</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if (($livewire['notices'] ?? []) !== [])
        <section data-ndb-livewire-evidence-gaps class="ndb:space-y-2">
            @foreach ($livewire['notices'] as $notice)
                <div
                    data-ndb-livewire-notice
                    role="status"
                    class="ndb:border-l-2 {{ ($notice['tone'] ?? null) === 'attention' ? 'ndb:border-amber-400' : 'ndb:border-zinc-300 ndb:dark:border-zinc-700' }} ndb:pl-3"
                >
                    <p class="ndb:text-xs ndb:font-bold">{{ $notice['title'] }}</p>
                    <p class="ndb:mt-0.5 ndb:text-[10px] ndb:leading-4 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        {{ $notice['detail'] }}
                    </p>
                </div>
            @endforeach
        </section>
    @endif
</div>
