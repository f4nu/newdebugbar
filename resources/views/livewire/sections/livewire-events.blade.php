<div data-ndb-livewire-events>
    <div class="ndb:mb-3">
        <h3 class="ndb:text-sm ndb:font-bold">Event trace</h3>
        <p class="ndb:mt-0.5 ndb:max-w-2xl ndb:text-[10px] ndb:leading-4 ndb:text-zinc-500 ndb:dark:text-zinc-400">
            Events are listed in captured order. A declared target is not shown as an observed recipient.
        </p>
    </div>

    @if (($livewire['events'] ?? []) === [])
        <div class="ndb:border-y ndb:border-zinc-200 ndb:py-5 ndb:dark:border-zinc-800">
            <x-newdebugbar::empty-state label="No Livewire events were observed." success />
        </div>
    @else
        <div class="ndb:grid ndb:gap-5 ndb:sm:grid-cols-[13rem_minmax(0,1fr)]">
            <ol
                role="listbox"
                aria-label="Livewire event trace"
                class="ndb:list-none ndb:border-y ndb:border-zinc-200 ndb:sm:max-h-[32rem] ndb:sm:overflow-y-auto ndb:dark:border-zinc-800"
            >
                @foreach ($livewire['events'] as $event)
                    <li>
                        <button
                            type="button"
                            role="option"
                            data-ndb-livewire-event-choice="{{ $event['id'] }}"
                            data-ndb-livewire-choice="{{ $event['id'] }}"
                            @click="selectLivewireItem('event', @js($event['id']))"
                            @keydown="handleLivewireItemKey($event, 'event')"
                            :aria-selected="selectedEventId === @js($event['id'])"
                            class="ndb:flex ndb:w-full ndb:min-w-0 ndb:gap-2.5 ndb:border-b ndb:border-zinc-200 ndb:px-2.5 ndb:py-3 ndb:text-left ndb:last:border-b-0 ndb:hover:bg-zinc-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:aria-selected:bg-indigo-50 ndb:dark:border-zinc-800 ndb:dark:hover:bg-zinc-900/60 ndb:dark:aria-selected:bg-indigo-950/45"
                        >
                            <span class="ndb:shrink-0 ndb:text-[10px] ndb:font-bold ndb:tabular-nums ndb:text-zinc-400">{{ $event['sequence'] }}</span>
                            <span class="ndb:min-w-0">
                                <span class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold">{{ $event['display_name'] }}</span>
                                <span class="ndb:mt-0.5 ndb:block ndb:truncate ndb:text-[10px] ndb:text-zinc-400">{{ $event['source_name'] ?? 'Source not observed' }}</span>
                            </span>
                        </button>
                    </li>
                @endforeach
            </ol>

            <div class="ndb:min-w-0">
                @foreach ($livewire['events'] as $event)
                    <article
                        data-ndb-livewire-event="{{ $event['id'] }}"
                        x-cloak
                        x-show.important="selectedEventId === @js($event['id'])"
                        class="ndb:space-y-5"
                    >
                        <header class="ndb:min-w-0">
                            <p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                Event {{ $event['sequence'] }}
                            </p>
                            <h3 class="ndb:mt-1 ndb:truncate ndb:text-lg ndb:font-bold ndb:tracking-tight">
                                {{ $event['display_name'] }}
                            </h3>
                            <code class="ndb:mt-1 ndb:block ndb:truncate ndb:text-[10px] ndb:text-zinc-400">{{ $event['name'] }}</code>
                        </header>

                        <dl class="ndb:grid ndb:gap-x-4 ndb:gap-y-4 ndb:border-y ndb:border-zinc-200 ndb:py-3 ndb:text-xs ndb:sm:grid-cols-2 ndb:dark:border-zinc-800">
                            <div>
                                <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    Source component
                                </dt>
                                <dd class="ndb:mt-1 ndb:font-bold">{{ $event['source_name'] ?? 'Not observed' }}</dd>
                            </div>
                            <div>
                                <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    Dispatch mode
                                </dt>
                                <dd class="ndb:mt-1 ndb:font-bold">{{ $event['mode_label'] }}</dd>
                            </div>
                            <div>
                                <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    Declared target
                                </dt>
                                <dd class="ndb:mt-1 ndb:font-bold">{{ $event['declared_target_label'] }}</dd>
                            </div>
                            <div>
                                <dt class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    Observed recipients
                                </dt>
                                <dd class="ndb:mt-1 ndb:font-bold">{{ $event['recipient_label'] }}</dd>
                            </div>
                        </dl>

                        <section>
                            <h4 class="ndb:text-xs ndb:font-bold">Safe payload</h4>
                            @if ($event['parameters'] === [])
                                <p class="ndb:mt-2 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                    No payload values were recorded.
                                </p>
                            @else
                                <pre class="ndb-code ndb-scrollbar ndb:mt-2 ndb:max-h-64"><code data-ndb-language="json">{{ $event['parameters_json'] }}</code></pre>
                            @endif
                        </section>
                    </article>
                @endforeach
            </div>
        </div>
    @endif
</div>
