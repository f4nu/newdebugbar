@props([
    'checkpoint',
    'index',
    'total',
])

@php
    $context = is_array($checkpoint['context'] ?? null) ? $checkpoint['context'] : [];
    $callsite = is_array($checkpoint['callsite'] ?? null) ? $checkpoint['callsite'] : null;
    $source = isset($callsite['file'], $callsite['line'])
        ? $callsite['file'].':'.$callsite['line']
        : null;
    $time = is_numeric($checkpoint['at_ms'] ?? null)
        ? '+'.number_format((float) $checkpoint['at_ms'], 3).' ms'
        : 'Time not captured';
    $hasNextCheckpoint = $index < $total - 1;
@endphp

<li
    wire:key="checkpoint-{{ $index }}"
    data-ndb-checkpoint-item="{{ $index }}"
    {{ $attributes->class('ndb:grid ndb:min-w-0 ndb:grid-cols-[18px_minmax(0,1fr)] ndb:gap-x-3') }}
>
    <span aria-hidden="true" class="ndb:relative ndb:flex ndb:justify-center">
        @if ($hasNextCheckpoint)
            <span
                data-ndb-checkpoint-connector
                class="ndb:absolute ndb:top-4 ndb:-bottom-4 ndb:left-1/2 ndb:w-px ndb:-translate-x-1/2 ndb:bg-zinc-200 ndb:dark:bg-zinc-800"
            ></span>
        @endif
        <span
            data-ndb-checkpoint-dot
            class="ndb:absolute ndb:top-4 ndb:left-1/2 ndb:z-[1] ndb:size-2.5 ndb:-translate-x-1/2 ndb:-translate-y-1/2 ndb:rounded-full ndb:bg-zinc-400 ndb:ring-4 ndb:ring-white ndb:dark:bg-zinc-600 ndb:dark:ring-zinc-950"
        ></span>
    </span>

    <article @class(['ndb:min-w-0', 'ndb:pb-5' => $hasNextCheckpoint])>
        <header class="ndb:grid ndb:min-w-0 ndb:grid-cols-[minmax(0,1fr)_auto] ndb:items-baseline ndb:gap-3">
            <h3 class="ndb:min-w-0 ndb:text-sm ndb:font-semibold ndb:leading-5 ndb:[overflow-wrap:anywhere]">
                {{ $checkpoint['label'] ?? 'Checkpoint' }}
            </h3>
            <span
                data-ndb-checkpoint-time
                class="ndb:text-right ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400"
            >{{ $time }}</span>
        </header>

        @if ($source !== null)
            <div class="ndb:mt-1 ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-2">
                <span class="ndb:shrink-0 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400">Source</span>
                <x-newdebugbar::inspector-source-link
                    :copy="$source"
                    data-ndb-checkpoint-source
                >{{ $source }}</x-newdebugbar::inspector-source-link>
            </div>
        @endif

        @if ($context !== [])
            <section data-ndb-checkpoint-context class="ndb:mt-3">
                <h4 class="ndb:mb-1.5 ndb:text-[11px] ndb:font-bold ndb:text-zinc-600 ndb:dark:text-zinc-300">
                    Context
                </h4>
                <x-newdebugbar::inspector-definition-list data-ndb-checkpoint-context-list>
                    @foreach ($context as $contextKey => $contextValue)
                        @php
                            $structuredContextValue = is_array($contextValue) || is_object($contextValue);
                            $contextPreview = match (true) {
                                $structuredContextValue => '',
                                $contextValue === null => 'null',
                                is_bool($contextValue) => $contextValue ? 'true' : 'false',
                                default => (string) $contextValue,
                            };
                        @endphp

                        <x-newdebugbar::inspector-definition-row :label="(string) $contextKey">
                            <x-slot:value>
                                @if ($structuredContextValue)
                                    <x-newdebugbar::code-block
                                        language="json"
                                        class="ndb:max-w-full"
                                    >{{ json_encode($contextValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}</x-newdebugbar::code-block>
                                @else
                                    <span @class([
                                        'ndb:whitespace-pre-wrap ndb:break-words ndb:[overflow-wrap:anywhere]',
                                        'ndb:tabular-nums' => is_int($contextValue) || is_float($contextValue),
                                    ])>{{ $contextPreview }}</span>
                                @endif
                            </x-slot:value>
                        </x-newdebugbar::inspector-definition-row>
                    @endforeach
                </x-newdebugbar::inspector-definition-list>
            </section>
        @endif
    </article>
</li>
