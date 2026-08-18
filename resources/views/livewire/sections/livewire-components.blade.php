<div data-ndb-livewire-components>
    <div class="ndb:mb-3">
        <h3 class="ndb:text-sm ndb:font-bold">Affected components</h3>
        <p class="ndb:mt-0.5 ndb:max-w-2xl ndb:text-[11px] ndb:leading-4 ndb:text-zinc-500 ndb:dark:text-zinc-400">
            @if ($livewire['affected_hierarchy_only'] ?? true)
                Only components observed in this request are listed. This is not a full page inventory.
            @else
                All component instances in this scope were observed.
            @endif
        </p>
    </div>

    @if (($livewire['components'] ?? []) === [])
        <div class="ndb:border-y ndb:border-zinc-200 ndb:py-5 ndb:dark:border-zinc-800">
            <x-newdebugbar::empty-state label="No component instance was observed." />
        </div>
    @else
        <div class="ndb:grid ndb:gap-5 ndb:sm:grid-cols-[13rem_minmax(0,1fr)]">
            <div
                role="listbox"
                aria-label="Affected components"
                class="ndb:border-y ndb:border-zinc-200 ndb:sm:max-h-[32rem] ndb:sm:overflow-y-auto ndb:dark:border-zinc-800"
            >
                @foreach ($livewire['components'] as $component)
                    <button
                        type="button"
                        role="option"
                        data-ndb-livewire-component-choice="{{ $component['id'] }}"
                        data-ndb-livewire-choice="{{ $component['id'] }}"
                        @click="selectLivewireItem('component', @js($component['id']))"
                        @keydown="handleLivewireItemKey($event, 'component')"
                        :aria-selected="selectedComponentId === @js($component['id'])"
                        class="ndb:block ndb:w-full ndb:border-b ndb:border-zinc-200 ndb:px-2.5 ndb:py-3 ndb:text-left ndb:last:border-b-0 ndb:hover:bg-zinc-50 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:aria-selected:bg-indigo-50 ndb:dark:border-zinc-800 ndb:dark:hover:bg-zinc-900/60 ndb:dark:aria-selected:bg-indigo-950/45"
                        style="padding-left: {{ 10 + $component['depth'] * 12 }}px"
                    >
                        <span class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold ndb:aria-selected:text-indigo-700 ndb:dark:aria-selected:text-indigo-300">{{ $component['list_label'] }}</span>
                        <span class="ndb:mt-0.5 ndb:block ndb:truncate ndb:text-[11px] ndb:text-zinc-400">{{ $component['trigger_label'] }}</span>
                    </button>
                @endforeach
            </div>

            <div class="ndb:min-w-0">
                @foreach ($livewire['components'] as $component)
                    <article
                        data-ndb-livewire-component="{{ $component['id'] }}"
                        x-cloak
                        x-show.important="selectedComponentId === @js($component['id'])"
                        class="ndb:space-y-5"
                    >
                        <header class="ndb:min-w-0">
                            <h3 class="ndb:truncate ndb:text-lg ndb:font-bold ndb:tracking-tight">
                                {{ $component['display_name'] }}
                            </h3>
                            <p class="ndb:mt-1 ndb:truncate ndb:text-[11px] ndb:text-zinc-400">
                                {{ $component['raw_name'] }}
                            </p>
                        </header>

                        <dl class="ndb:grid ndb:grid-cols-2 ndb:gap-x-4 ndb:gap-y-3 ndb:border-y ndb:border-zinc-200 ndb:py-3 ndb:text-xs ndb:sm:grid-cols-3 ndb:dark:border-zinc-800">
                            <div>
                                <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    Trigger
                                </dt>
                                <dd class="ndb:mt-1 ndb:font-bold">{{ $component['trigger_label'] }}</dd>
                            </div>
                            <div>
                                <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    Result
                                </dt>
                                <dd class="ndb:mt-1 ndb:font-bold">{{ $component['result_label'] }}</dd>
                            </div>
                            <div>
                                <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    Render
                                </dt>
                                <dd class="ndb:mt-1 ndb:font-bold">{{ $component['rendered_label'] }}</dd>
                            </div>
                        </dl>

                        @if ($component['redirect'] !== null || $component['download'] !== null)
                            <section
                                data-ndb-livewire-response-effect
                                class="ndb:border-l-2 ndb:border-indigo-300 ndb:pl-3 ndb:dark:border-indigo-800"
                            >
                                <h4 class="ndb:text-xs ndb:font-bold">Response</h4>
                                @if ($component['redirect'] !== null)
                                    <p class="ndb:mt-1 ndb:break-all ndb:text-xs ndb:text-zinc-600 ndb:dark:text-zinc-300">
                                        Redirect: {{ $component['redirect'] }}
                                    </p>
                                @endif
                                @if ($component['download'] !== null)
                                    <p class="ndb:mt-1 ndb:break-all ndb:text-xs ndb:text-zinc-600 ndb:dark:text-zinc-300">
                                        Download: {{ $component['download']['name'] }}
                                    </p>
                                @endif
                            </section>
                        @endif

                        @if ($component['state_changes'] !== [])
                            <section>
                                <h4 class="ndb:text-xs ndb:font-bold">Property changes</h4>
                                <div class="ndb:mt-2 ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800">
                                    @foreach ($component['state_changes'] as $change)
                                        <div class="ndb:grid ndb:gap-2 ndb:border-b ndb:border-zinc-200 ndb:py-2.5 ndb:sm:grid-cols-[minmax(7rem,0.5fr)_minmax(0,1.5fr)] ndb:sm:items-center ndb:dark:border-zinc-800">
                                            <code class="ndb:min-w-0 ndb:truncate ndb:text-[11px] ndb:font-semibold">{{ $change['path'] }}</code>
                                            <div class="ndb:grid ndb:min-w-0 ndb:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] ndb:items-center ndb:gap-2 ndb:text-xs">
                                                <span
                                                    class="ndb:truncate ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                                    title="{{ $change['before_display'] }}"
                                                >{{ $change['before_display'] }}</span>
                                                <span
                                                    aria-hidden="true"
                                                    class="ndb:text-zinc-300 ndb:dark:text-zinc-700"
                                                >→</span>
                                                <strong
                                                    class="ndb:truncate"
                                                    title="{{ $change['after_display'] }}"
                                                >{{ $change['after_display'] }}</strong>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        @if ($component['validation_fields'] !== [])
                            <section class="ndb:border-l-2 ndb:border-amber-400 ndb:pl-3">
                                <h4 class="ndb:text-xs ndb:font-bold">Validation</h4>
                                <p class="ndb:mt-1 ndb:text-xs ndb:text-zinc-600 ndb:dark:text-zinc-300">
                                    Fields with errors: {{ implode(', ', $component['validation_fields']) }}
                                </p>
                            </section>
                        @endif

                        @if ($component['emitted_events'] !== [] || $component['received_events'] !== [])
                            <section>
                                <h4 class="ndb:text-xs ndb:font-bold">Events</h4>
                                <div class="ndb:mt-2 ndb:grid ndb:gap-3 ndb:sm:grid-cols-2">
                                    <div>
                                        <p class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                            Emitted
                                        </p>
                                        <p class="ndb:mt-1 ndb:text-xs ndb:leading-5">
                                            {{ $component['emitted_events'] === [] ? 'None observed' : implode(', ', array_column($component['emitted_events'], 'display_name')) }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                            Received
                                        </p>
                                        <p class="ndb:mt-1 ndb:text-xs ndb:leading-5">
                                            {{ $component['received_events'] === [] ? 'None observed' : implode(', ', array_column($component['received_events'], 'display_name')) }}
                                        </p>
                                    </div>
                                </div>
                            </section>
                        @endif

                        @if ($component['server_work'] !== [])
                            <section>
                                <h4 class="ndb:text-xs ndb:font-bold">Important server work</h4>
                                <div class="ndb:mt-2 ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800">
                                    @foreach ($component['server_work'] as $work)
                                        <div class="ndb:flex ndb:items-center ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:py-2.5 ndb:text-xs ndb:dark:border-zinc-800">
                                            <span class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:font-semibold">{{ $work['label'] }}</span>
                                            <span class="ndb:shrink-0 ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400">{{ number_format($work['duration_ms'], $work['duration_ms'] < 10 ? 2 : 1) }} ms</span>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        <section
                            data-ndb-livewire-component-details
                            class="ndb:border-t ndb:border-zinc-200 ndb:pt-4 ndb:dark:border-zinc-800"
                        >
                            <h4 class="ndb:text-xs ndb:font-bold">Source details</h4>
                            <dl class="ndb:mt-3 ndb:grid ndb:gap-3 ndb:text-[11px] ndb:sm:grid-cols-2">
                                @foreach (['Source' => $component['source_label'], 'Class' => $component['class'], 'View' => $component['view_label'], 'Instance' => $component['id']] as $label => $value)
                                    <div class="ndb:min-w-0">
                                        <dt class="ndb:text-zinc-400">{{ $label }}</dt>
                                        <dd class="ndb:mt-0.5 ndb:break-all ndb:font-semibold">
                                            {{ $value ?? 'Not observed' }}
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        </section>
                    </article>
                @endforeach
            </div>
        </div>
    @endif
</div>
