<div data-ndb-livewire-events>
    <div>
        <h4 class="ndb:text-xs ndb:font-bold">Livewire events</h4>
        <p class="ndb:mt-0.5 ndb:text-[10px] ndb:leading-4 ndb:text-zinc-400">
            Declared targets and observed recipients are kept separate. A target does not prove that a component received the event.
        </p>
    </div>

    <div class="ndb:mt-3 ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800">
        @forelse ($livewire['events'] ?? [] as $event)
            <article data-ndb-livewire-event="{{ $event['id'] }}" class="ndb:border-b ndb:border-zinc-200 ndb:py-4 ndb:dark:border-zinc-800">
                <div class="ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:items-center ndb:gap-x-3 ndb:gap-y-1">
                    <h5 class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-sm ndb:font-bold">{{ $event['name'] }}</h5>
                    <span class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">{{ $event['mode_label'] }}</span>
                </div>
                <dl class="ndb:mt-3 ndb:grid ndb:grid-cols-1 ndb:gap-3 ndb:text-[10px] ndb:sm:grid-cols-3">
                    <div>
                        <dt class="ndb:text-zinc-400">Source</dt>
                        <dd class="ndb:mt-0.5 ndb:font-bold">{{ $event['source_name'] ?? 'Unknown source' }}</dd>
                    </div>
                    <div>
                        <dt class="ndb:text-zinc-400">Declared target</dt>
                        <dd class="ndb:mt-0.5 ndb:font-bold">{{ ($event['declared_target'] ?? null) === null ? 'None declared' : $event['declared_target_label'] }}</dd>
                    </div>
                    <div>
                        <dt class="ndb:text-zinc-400">Observed recipients</dt>
                        <dd class="ndb:mt-0.5 ndb:font-bold">
                            @if (($event['recipient_names'] ?? []) !== [])
                                {{ implode(', ', $event['recipient_names']) }}
                            @elseif (($event['recipient_status'] ?? 'unknown') === 'unknown')
                                Unknown
                            @else
                                None observed
                            @endif
                        </dd>
                    </div>
                </dl>
                @if (($event['parameters'] ?? []) !== [])
                    <details class="ndb:mt-3">
                        <summary class="ndb:cursor-pointer ndb:text-[10px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300">Redacted parameters</summary>
                        <pre class="ndb-code ndb-scrollbar ndb:mt-2"><code data-ndb-language="json">{{ json_encode($event['parameters'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                    </details>
                @endif
            </article>
        @empty
            <div class="ndb:py-5">
                <x-newdebugbar::empty-state label="No Livewire events were observed." success />
            </div>
        @endforelse
    </div>
</div>
