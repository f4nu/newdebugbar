{{-- Renders developer messages recorded during the request. --}}
@php($messages = array_values($section['payload']['items'] ?? []))

@if ($messages !== [])
    <div class="ndb:flex ndb:min-h-0 ndb:flex-1 ndb:flex-col">
        <x-newdebugbar::inspector-workspace mode="stream" frame="top" data-ndb-message-workspace>
            <x-slot:controls>
                <x-newdebugbar::inspector-list-controls :show-search="false">
                    <x-slot:leading>
                        <p class="ndb:text-xs ndb:font-semibold ndb:text-zinc-600 ndb:dark:text-zinc-300">
                            {{ number_format(count($messages)) }} {{ \Illuminate\Support\Str::plural('message', count($messages)) }}
                        </p>
                    </x-slot:leading>
                </x-newdebugbar::inspector-list-controls>
            </x-slot:controls>

            <x-slot:body data-ndb-message-list class="ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800">
                @foreach ($messages as $index => $item)
                    <article wire:key="message-{{ $index }}" data-ndb-message-item="{{ $index }}">
                        <header class="ndb:flex ndb:items-baseline ndb:gap-3 ndb:px-4 ndb:py-3">
                            <h3 class="ndb:min-w-0 ndb:flex-1 ndb:text-xs ndb:font-bold">{{ $item['label'] }}</h3>
                            <span class="ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400">
                                {{ $item['at_ms'] }} ms
                            </span>
                        </header>
                        @if (($item['context'] ?? []) !== [])
                            <x-newdebugbar::code-block
                                language="json"
                                class="ndb:rounded-none ndb:border-t ndb:border-zinc-200 ndb:dark:border-zinc-800"
                            >{{ json_encode($item['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</x-newdebugbar::code-block>
                        @endif
                    </article>
                @endforeach
            </x-slot:body>
        </x-newdebugbar::inspector-workspace>
    </div>
@else
    <x-newdebugbar::empty-state label="No developer messages were recorded." />
@endif
