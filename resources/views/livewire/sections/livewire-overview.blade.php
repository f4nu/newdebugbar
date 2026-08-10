<div class="ndb:grid ndb:gap-6 ndb:lg:grid-cols-2">
    <section data-ndb-livewire-overview-state>
        <div>
            <h4 class="ndb:text-xs ndb:font-bold">State changes</h4>
            <p class="ndb:mt-0.5 ndb:text-[10px] ndb:text-zinc-400">Before and server state after the exchange</p>
        </div>
        <div class="ndb:mt-3 ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800">
            @forelse (array_slice($livewire['state_changes'] ?? [], 0, 6) as $change)
                <div
                    data-ndb-livewire-state-change="{{ $change['id'] }}"
                    class="ndb:border-b ndb:border-zinc-200 ndb:py-3 ndb:dark:border-zinc-800"
                >
                    <div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3">
                        <code class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-bold">{{ $change['path'] }}</code>
                        <span class="ndb:text-[10px] ndb:text-zinc-400">{{ $change['component_name'] }}</span>
                    </div>
                    <div class="ndb:mt-1.5 ndb:grid ndb:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] ndb:items-center ndb:gap-2 ndb:text-xs">
                        <span
                            class="ndb:truncate ndb:text-zinc-500 ndb:dark:text-zinc-400"
                            title="{{ $change['before_display'] }}"
                        >{{ $change['before_display'] }}</span>
                        <span aria-label="changed to" class="ndb:text-zinc-300 ndb:dark:text-zinc-700">→</span>
                        <span
                            class="ndb:truncate ndb:font-semibold"
                            title="{{ $change['server_display'] }}"
                        >{{ $change['server_display'] }}</span>
                    </div>
                    @if ($change['redacted'])
                        <p class="ndb:mt-1.5 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400">
                            Secret value stayed hidden.
                        </p>
                    @elseif ($change['submitted_material'] || $change['browser_status'] === 'observed')
                        <details class="ndb:mt-2">
                            <summary class="ndb:cursor-pointer ndb:text-[10px] ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300">
                                Other observed layers
                            </summary>
                            <dl class="ndb:mt-2 ndb:grid ndb:grid-cols-[7rem_minmax(0,1fr)] ndb:gap-x-3 ndb:gap-y-1 ndb:text-[10px]">
                                @if ($change['submitted_material'])
                                    <dt class="ndb:text-zinc-400">Submitted</dt>
                                    <dd class="ndb:truncate">{{ $change['submitted_display'] }}</dd>
                                @endif
                                @if ($change['browser_status'] === 'observed')
                                    <dt class="ndb:text-zinc-400">Browser state</dt>
                                    <dd>
                                        {{ $change['browser_matches_server'] === true ? 'Matched server' : ($change['browser_matches_server'] === false ? 'Did not match server' : 'Comparison unknown') }}{{ $change['browser_type'] ? ' ('.$change['browser_type'].')' : '' }}
                                    </dd>
                                @endif
                            </dl>
                        </details>
                    @endif
                </div>
            @empty
                <div class="ndb:py-4">
                    <x-newdebugbar::empty-state label="No state changes were observed." success />
                </div>
            @endforelse
        </div>
    </section>

    <div class="ndb:space-y-6">
        <section data-ndb-livewire-overview-messages>
            <div>
                <h4 class="ndb:text-xs ndb:font-bold">Messages and effects</h4>
                <p class="ndb:mt-0.5 ndb:text-[10px] ndb:text-zinc-400">
                    Observed results returned by each component message
                </p>
            </div>
            <div class="ndb:mt-3 ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800">
                @foreach (array_slice($livewire['messages'] ?? [], 0, 6) as $message)
                    <div
                        data-ndb-livewire-message="{{ $message['id'] }}"
                        class="ndb:border-b ndb:border-zinc-200 ndb:py-3 ndb:dark:border-zinc-800"
                    >
                        <div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3">
                            <p class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-bold">
                                {{ $message['component_name'] }}
                            </p>
                            <span class="ndb:text-[10px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ $message['result_label'] }}</span>
                        </div>
                        @if ($message['validation_fields'] !== [])
                            <p class="ndb:mt-1.5 ndb:text-[10px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                Validation fields: {{ implode(', ', $message['validation_fields']) }}
                            </p>
                        @endif
                        @if ($message['redirect'])
                            <p class="ndb:mt-1.5 ndb:truncate ndb:text-[10px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                Redirect: {{ $message['redirect'] }}
                            </p>
                        @endif
                        @if ($message['download'])
                            <p class="ndb:mt-1.5 ndb:text-[10px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                Download: {{ $message['download']['name'] }}
                                @if ($message['download']['size_bytes'] !== null) {{ number_format($message['download']['size_bytes']) }}bytes @endif
                            </p>
                        @endif
                    </div>
                @endforeach
                @if (count($livewire['messages'] ?? []) > 6)
                    <p class="ndb:border-b ndb:border-zinc-200 ndb:py-3 ndb:text-[10px] ndb:font-semibold ndb:text-zinc-400 ndb:dark:border-zinc-800">
                        {{ count($livewire['messages']) - 6 }} more messages are listed in the profile data.
                    </p>
                @endif
            </div>
        </section>

        <section data-ndb-livewire-overview-components>
            <div>
                <h4 class="ndb:text-xs ndb:font-bold">Rendered components</h4>
                <p class="ndb:mt-0.5 ndb:text-[10px] ndb:text-zinc-400">Only instances affected by this exchange</p>
            </div>
            <div class="ndb:mt-3 ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800">
                @forelse (array_filter($livewire['components'] ?? [], fn (array $component): bool => $component['rendered'] === 'yes') as $component)
                    <div class="ndb:flex ndb:min-w-0 ndb:items-start ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:py-3 ndb:dark:border-zinc-800">
                        <div class="ndb:min-w-0 ndb:flex-1">
                            <p class="ndb:truncate ndb:text-xs ndb:font-bold">{{ $component['name'] }}</p>
                            <p class="ndb:mt-0.5 ndb:text-[10px] ndb:text-zinc-400">
                                {{ $component['render_reason_label'] }}
                                <span>{{ $component['render_reason_confidence'] }}</span>
                            </p>
                        </div>
                        <code class="ndb:text-[10px] ndb:text-zinc-400">{{ $component['short_id'] }}</code>
                    </div>
                @empty
                    <div class="ndb:py-4">
                        <x-newdebugbar::empty-state label="No rendered component was observed." />
                    </div>
                @endforelse
            </div>
        </section>

        <section data-ndb-livewire-overview-work>
            <div>
                <h4 class="ndb:text-xs ndb:font-bold">Important server work</h4>
                <p class="ndb:mt-0.5 ndb:text-[10px] ndb:text-zinc-400">Longest observed Livewire server phases</p>
            </div>
            <div class="ndb:mt-3 ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800">
                @forelse ($livewire['server_work'] ?? [] as $span)
                    <div class="ndb:flex ndb:items-center ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:py-2.5 ndb:text-xs ndb:dark:border-zinc-800">
                        <span class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:font-semibold">{{ $span['label'] }}</span>
                        <span class="ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ number_format($span['duration_ms'], $span['duration_ms'] < 10 ? 2 : 1) }} ms</span>
                    </div>
                @empty
                    <div class="ndb:py-4">
                        <x-newdebugbar::empty-state label="Server phase timing is unavailable." />
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
