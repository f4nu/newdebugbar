{{-- Renders validation failures. --}}
<div class="ndb:space-y-3">
    @forelse ($section['payload']['items'] as $index => $item)
        <article
            wire:key="validation-{{ $index }}"
            class="ndb:rounded-xl ndb:border ndb:border-amber-200 ndb:bg-amber-50/35 ndb:p-4 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/15"
        >
            <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-2">
                <span class="ndb:text-xs ndb:font-bold">{{ count($item['fields']) }} invalid fields</span
                ><span
                    class="ndb:ml-auto ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:gap-y-1 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400"
                    ><span>{{ $item['error_bag'] }} bag</span><span>HTTP {{ $item['response_status'] }}</span></span>
            </div>
            <dl class="ndb:mt-3 ndb:space-y-2">
                @foreach ($item['rules'] as $field => $rules)
                    <div class="ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:items-center ndb:gap-2">
                        <dt class="ndb:min-w-24 ndb:font-mono ndb:text-[11px] ndb:font-bold">{{ $field }}</dt>
                        <dd class="ndb:flex ndb:flex-wrap ndb:gap-1">
                            @foreach ($rules as $rule)
                                <span class="ndb:rounded-md ndb:bg-amber-100 ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-semibold ndb:text-amber-800 ndb:dark:bg-amber-950 ndb:dark:text-amber-300">{{ $rule }}</span>
                            @endforeach
                        </dd>
                    </div>
                @endforeach
            </dl>
            @if (($item['messages'] ?? []) !== [])
                <details class="ndb:group ndb:mt-3 ndb:border-t ndb:border-amber-200 ndb:pt-3 ndb:dark:border-amber-950">
                    <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-2 ndb:text-[11px] ndb:font-bold ndb:text-amber-800 ndb:focus-visible:outline-2 ndb:focus-visible:outline-amber-500 ndb:dark:text-amber-300">
                        <span class="ndb:flex-1">Show validation messages</span
                        ><x-newdebugbar::icon
                            name="chevron-down"
                            class="ndb:size-3.5 ndb:transition ndb:group-open:rotate-180"
                        />
                    </summary>
                    <dl class="ndb:mt-2 ndb:space-y-2">
                        @foreach ($item['messages'] as $field => $messages)
                            <div>
                                <dt class="ndb:font-mono ndb:text-[11px] ndb:font-bold">{{ $field }}</dt>
                                <dd class="ndb:mt-0.5 ndb:text-xs">{{ implode(' ', (array) $messages) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </details>
            @endif
        </article>
    @empty
        <x-newdebugbar::empty-state label="No validation failures were captured." success />
    @endforelse
</div>
