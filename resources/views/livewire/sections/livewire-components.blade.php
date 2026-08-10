<div data-ndb-livewire-components>
    <div>
        <h4 class="ndb:text-xs ndb:font-bold">Affected component relationships</h4>
        <p class="ndb:mt-0.5 ndb:text-[10px] ndb:leading-4 ndb:text-zinc-400">
            @if ($livewire['affected_hierarchy_only'] ?? true)
                This is not a full page component tree. It contains only instances observed in this exchange.
            @else
                All component instances in this scope were observed.
            @endif
        </p>
    </div>

    <div class="ndb:mt-3 ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800">
        @forelse ($livewire['components'] ?? [] as $component)
            <article
                data-ndb-livewire-component="{{ $component['id'] }}"
                class="ndb:border-b ndb:border-zinc-200 ndb:py-4 ndb:dark:border-zinc-800"
            >
                <div style="padding-left: {{ $component['depth'] * 16 }}px">
                    <div class="ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:items-start ndb:gap-x-3 ndb:gap-y-1">
                        <div class="ndb:min-w-0 ndb:flex-1">
                            <h5 class="ndb:truncate ndb:text-sm ndb:font-bold">{{ $component['name'] }}</h5>
                            <p class="ndb:mt-0.5 ndb:truncate ndb:text-[10px] ndb:text-zinc-400">
                                {{ $component['class'] ?? 'Class unknown' }}
                            </p>
                        </div>
                        <code
                            class="ndb:text-[10px] ndb:text-zinc-400"
                            title="Mount-scoped instance ID"
                        >{{ $component['short_id'] }}</code>
                    </div>

                    <dl class="ndb:mt-3 ndb:grid ndb:grid-cols-2 ndb:gap-3 ndb:text-[10px] ndb:sm:grid-cols-4">
                        <div>
                            <dt class="ndb:text-zinc-400">Rendered</dt>
                            <dd class="ndb:mt-0.5 ndb:font-bold">{{ ucfirst($component['rendered']) }}</dd>
                        </div>
                        <div>
                            <dt class="ndb:text-zinc-400">Render reason</dt>
                            <dd class="ndb:mt-0.5 ndb:font-bold">{{ $component['render_reason_label'] }}</dd>
                            <dd class="ndb:text-zinc-400">{{ ucfirst($component['render_reason_confidence']) }}</dd>
                        </div>
                        <div>
                            <dt class="ndb:text-zinc-400">Message result</dt>
                            <dd class="ndb:mt-0.5 ndb:font-bold">
                                {{ ucfirst(str_replace('_', ' ', $component['message_result'])) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="ndb:text-zinc-400">Parent</dt>
                            <dd class="ndb:mt-0.5 ndb:truncate ndb:font-bold">
                                {{ $component['parent_name'] ?? 'Not observed' }}
                            </dd>
                        </div>
                    </dl>

                    @if ($component['source_label'] || $component['view_label'])
                        <div class="ndb:mt-3 ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:gap-x-4 ndb:gap-y-1 ndb:text-[10px]">
                            @if ($component['source_label'])
                                <button
                                    type="button"
                                    @click="copyText(@js($component['source_label']))"
                                    class="ndb:min-w-0 ndb:truncate ndb:text-left ndb:font-semibold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                                    title="Copy file and line"
                                >
                                    {{ $component['source_label'] }}
                                </button>
                            @endif
                            @if ($component['view_label'])
                                <span class="ndb:min-w-0 ndb:truncate ndb:text-zinc-400">View {{ $component['view_label'] }}</span>
                            @endif
                        </div>
                    @endif

                    @if ($component['actions'] !== [] || $component['state_changes'] !== [])
                        <div class="ndb:mt-3 ndb:grid ndb:gap-3 ndb:sm:grid-cols-2">
                            <div>
                                <p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    Actions
                                </p>
                                <ul class="ndb:mt-1 ndb:list-none ndb:space-y-1">
                                    @forelse ($component['actions'] as $action)
                                        <li class="ndb:text-xs">
                                            <span class="ndb:font-bold">{{ $action['name'] }}</span>
                                            <span class="ndb:text-zinc-400">{{ $action['kind_label'] }}</span>
                                        </li>
                                    @empty
                                        <li class="ndb:text-xs ndb:text-zinc-400">None observed</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div>
                                <p class="ndb:text-[9px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                    Changed state
                                </p>
                                <ul class="ndb:mt-1 ndb:list-none ndb:space-y-1">
                                    @forelse ($component['state_changes'] as $change)
                                        <li class="ndb:flex ndb:gap-2 ndb:text-xs">
                                            <code
                                                class="ndb:min-w-0 ndb:flex-1 ndb:truncate"
                                                >{{ $change['path'] }}</code
                                            ><span
                                                class="ndb:max-w-[55%] ndb:truncate ndb:text-zinc-400"
                                                >{{ $change['server_display'] }}</span>
                                        </li>
                                    @empty
                                        <li class="ndb:text-xs ndb:text-zinc-400">None observed</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="ndb:py-5">
                <x-newdebugbar::empty-state label="No component instance could be identified." />
            </div>
        @endforelse
    </div>
</div>
