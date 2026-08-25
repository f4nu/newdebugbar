{{-- Renders reported exceptions and source frames. --}}
@forelse ($section['payload']['items'] as $index => $exception)
    <article
        wire:key="exception-{{ $index }}"
        class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-red-200 ndb:dark:border-red-950"
    >
        <div class="ndb:flex ndb:items-start ndb:gap-3 ndb:bg-red-50 ndb:p-4 ndb:dark:bg-red-950/50">
            <span
                class="ndb:grid ndb:size-8 ndb:shrink-0 ndb:place-items-center ndb:rounded-lg ndb:bg-red-100 ndb:text-red-600 ndb:dark:bg-red-950 ndb:dark:text-red-300"
                ><x-newdebugbar::icon name="warning" class="ndb:size-4"
            /></span>
            <div class="ndb:min-w-0 ndb:flex-1">
                <p class="ndb:truncate ndb:text-xs ndb:font-bold ndb:text-red-700 ndb:dark:text-red-300">
                    {{ $exception['class'] }}
                </p>
                <p class="ndb:mt-1 ndb:text-sm ndb:font-semibold">{{ $exception['message'] ?: 'No message' }}</p>
                <div class="ndb:mt-1 ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3">
                    <button
                        type="button"
                        data-ndb-copy-exception-callsite="{{ $index }}"
                        @click="copyText(@js($exception['file'].':'.$exception['line']))"
                        class="ndb:min-w-0 ndb:truncate ndb:text-left ndb:text-[11px] ndb:text-zinc-500 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
                    >
                        {{ $exception['file'] }}:{{ $exception['line'] }}
                    </button>
                </div>
            </div>
        </div>
        @if ($exception['source'] ?? null)
            @php($sourceText = implode("\n", array_map(fn (array $line): string => sprintf('%4d%s %s', $line['number'], $line['focus'] ? '>' : ' ', $line['code']), $exception['source']['lines'])))
            <x-newdebugbar::code-block
                language="php"
                class="ndb:max-h-72 ndb:rounded-none ndb:border-b ndb:border-zinc-200 ndb:dark:border-zinc-800"
            >{{ $sourceText }}</x-newdebugbar::code-block>
        @endif
        <div class="ndb:p-4">
            <h3 class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                Application frames
            </h3>
            <ol class="ndb:mt-3 ndb:list-none ndb:space-y-2">
                @forelse ($exception['frames']['application'] ?? [] as $frame)
                    <li class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:text-xs">
                        <code class="ndb:min-w-0 ndb:flex-1 ndb:truncate"
                            >{{ $frame['file'] }}:{{ $frame['line'] }}</code
                        ><span class="ndb:max-w-[35%] ndb:truncate ndb:text-zinc-400">{{ $frame['function'] }}</span>
                    </li>
                @empty
                    <li class="ndb:text-xs ndb:text-zinc-400">No application frames were captured.</li>
                @endforelse
            </ol>
            <details class="ndb:group ndb:mt-4 ndb:border-t ndb:border-zinc-200 ndb:pt-3 ndb:dark:border-zinc-800">
                <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:gap-2 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                    <span class="ndb:flex-1">Vendor frames ({{ count($exception['frames']['vendor'] ?? []) }})</span
                    ><x-newdebugbar::icon
                        name="chevron-down"
                        class="ndb:size-3.5 ndb:transition ndb:group-open:rotate-180"
                    />
                </summary>
                <ol class="ndb:mt-3 ndb:list-none ndb:space-y-2">
                    @foreach ($exception['frames']['vendor'] ?? [] as $frame)
                        <li class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:text-xs">
                            <code class="ndb:min-w-0 ndb:flex-1 ndb:truncate"
                                >{{ $frame['file'] }}:{{ $frame['line'] }}</code
                            ><span
                                class="ndb:max-w-[35%] ndb:truncate ndb:text-zinc-400"
                                >{{ $frame['function'] }}</span>
                        </li>
                    @endforeach
                </ol>
            </details>
        </div>
    </article>
@empty
    <x-newdebugbar::empty-state label="No exceptions were reported." success />
@endforelse
