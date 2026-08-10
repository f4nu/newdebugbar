<div data-ndb-livewire-timeline class="ndb:space-y-6">
    <div>
        <h4 class="ndb:text-xs ndb:font-bold">Separate timing lanes</h4>
        <p class="ndb:mt-0.5 ndb:max-w-2xl ndb:text-[10px] ndb:leading-4 ndb:text-zinc-400">
            Browser and server clocks have different origins. Browser wait is not exact network time, and a render
            callback is not paint time.
        </p>
    </div>

    @foreach ($livewire['lanes'] ?? [] as $lane)
        <section data-ndb-livewire-lane="{{ $lane['clock'] }}">
            <div class="ndb:flex ndb:items-end ndb:gap-3">
                <div class="ndb:min-w-0 ndb:flex-1">
                    <h5 class="ndb:text-xs ndb:font-bold">{{ $lane['label'] }}</h5>
                    <p class="ndb:mt-0.5 ndb:text-[10px] ndb:text-zinc-400">
                        {{ str_replace('_', ' ', $lane['clock']) }} clock
                    </p>
                </div>
                @if ($lane['items'] !== [])
                    <p class="ndb:text-[10px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        {{ number_format($lane['duration_ms'], $lane['duration_ms'] < 10 ? 2 : 1) }} ms observed range
                    </p>
                @endif
            </div>

            @if ($lane['items'] === [])
                <div class="ndb:mt-3 ndb:border-y ndb:border-zinc-200 ndb:py-4 ndb:dark:border-zinc-800">
                    <x-newdebugbar::empty-state :label="$lane['label'].' timing is unavailable.'" />
                </div>
            @else
                <ol class="ndb:mt-3 ndb:list-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800">
                    @foreach ($lane['items'] as $span)
                        <li
                            data-ndb-livewire-span="{{ $span['id'] ?? '' }}"
                            class="ndb:border-b ndb:border-zinc-200 ndb:dark:border-zinc-800"
                        >
                            <details class="ndb:group">
                                <summary class="ndb:grid ndb:cursor-pointer ndb:list-none ndb:grid-cols-[minmax(7rem,0.7fr)_minmax(9rem,1.3fr)_auto] ndb:items-center ndb:gap-3 ndb:py-3 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500">
                                    <span class="ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-semibold">{{ $span['label'] }}</span>
                                    <span class="ndb:relative ndb:h-2 ndb:overflow-hidden ndb:rounded-full ndb:bg-zinc-100 ndb:dark:bg-zinc-800">
                                        @if ($span['kind'] === 'point')
                                            <span
                                                class="ndb:absolute ndb:top-1/2 ndb:size-2 ndb:-translate-x-1/2 ndb:-translate-y-1/2 ndb:rounded-full ndb:bg-indigo-500 ndb:dark:bg-indigo-400"
                                                style="left: {{ min(99, max(1, $span['start_percent'])) }}%"
                                            ></span>
                                        @else
                                            <span
                                                class="ndb:absolute ndb:inset-y-0 ndb:min-w-[3px] ndb:rounded-full ndb:bg-indigo-500 ndb:dark:bg-indigo-400"
                                                style="left: {{ $span['start_percent'] }}%; width: {{ max(0.5, $span['duration_percent']) }}%"
                                            ></span>
                                        @endif
                                    </span>
                                    <span class="ndb:text-right ndb:text-[10px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                        {{ $span['kind'] === 'point' ? number_format($span['start_ms'], 2).' ms' : number_format($span['duration_ms'], 2).' ms' }}
                                    </span>
                                </summary>
                                <dl class="ndb:mb-3 ndb:grid ndb:grid-cols-2 ndb:gap-3 ndb:bg-zinc-50/70 ndb:px-3 ndb:py-2.5 ndb:text-[10px] ndb:sm:grid-cols-4 ndb:dark:bg-zinc-900/40">
                                    <div>
                                        <dt class="ndb:text-zinc-400">Start</dt>
                                        <dd class="ndb:mt-0.5 ndb:font-bold ndb:tabular-nums">
                                            {{ number_format($span['start_ms'], 3) }} ms
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="ndb:text-zinc-400">Duration</dt>
                                        <dd class="ndb:mt-0.5 ndb:font-bold ndb:tabular-nums">
                                            {{ number_format($span['duration_ms'], 3) }} ms
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="ndb:text-zinc-400">Evidence</dt>
                                        <dd class="ndb:mt-0.5 ndb:font-bold">
                                            {{ ucfirst($span['confidence'] ?? 'unknown') }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="ndb:text-zinc-400">Source</dt>
                                        <dd class="ndb:mt-0.5 ndb:font-bold">
                                            {{ str_replace('_', ' ', $span['source'] ?? 'unknown') }}
                                        </dd>
                                    </div>
                                </dl>
                            </details>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    @endforeach
</div>
