@props(['exception', 'index'])

@php
    $applicationFrames = array_values($exception['frames']['application'] ?? []);
    $vendorFrames = array_values($exception['frames']['vendor'] ?? []);
    $sourceText = isset($exception['source']['lines'])
        ? implode("\n", array_map(
            fn (array $line): string => sprintf('%4d%s %s', $line['number'], $line['focus'] ? '>' : ' ', $line['code']),
            $exception['source']['lines'],
        ))
        : null;
@endphp

<article data-ndb-exception-detail="{{ $index }}" x-data="{ exceptionDetailTab: 'source' }">
    <x-newdebugbar::inspector-detail-header>
        <x-slot:title>
            <h3 class="ndb:min-w-0 ndb:text-sm ndb:font-bold">
                <code class="ndb:block ndb:break-words ndb:font-mono">{{ $exception['class'] }}</code>
            </h3>
            <p class="ndb:mt-1 ndb:text-xs ndb:font-semibold ndb:leading-5">
                {{ $exception['message'] ?: 'No exception message was captured.' }}
            </p>
        </x-slot:title>
        <x-slot:aside>
            <x-newdebugbar::inspector-source-link
                data-ndb-copy-exception-callsite="{{ $index }}"
                :copy="$exception['file'].':'.$exception['line']"
                aria-label="Copy exception source"
            >
                {{ $exception['file'] }}:{{ $exception['line'] }}
            </x-newdebugbar::inspector-source-link>
        </x-slot:aside>
    </x-newdebugbar::inspector-detail-header>

    <x-newdebugbar::inspector-detail-tabs label="Exception detail">
        @foreach (['source' => 'Source', 'stack' => 'Stack'] as $tab => $label)
            <x-newdebugbar::filter-tab
                variant="segmented"
                data-ndb-exception-detail-tab="{{ $tab }}"
                @click="exceptionDetailTab = '{{ $tab }}'"
                ::aria-pressed="exceptionDetailTab === '{{ $tab }}'"
            >
                {{ $label }}
            </x-newdebugbar::filter-tab>
        @endforeach
    </x-newdebugbar::inspector-detail-tabs>

    <template x-if="exceptionDetailTab === 'source'">
        <section data-ndb-exception-detail-panel="source">
            @if ($sourceText !== null)
                <x-newdebugbar::code-block
                    language="php"
                    class="ndb:rounded-none ndb:border-0"
                >{{ $sourceText }}</x-newdebugbar::code-block>
            @else
                <x-newdebugbar::empty-state label="No source context was captured for this exception." />
            @endif
        </section>
    </template>

    <template x-if="exceptionDetailTab === 'stack'">
        <section data-ndb-exception-detail-panel="stack" class="ndb:p-4">
            <x-newdebugbar::inspector-stack
                :frames="\Illuminate\Support\Js::from($applicationFrames)"
                empty-label="No application frames were captured."
                class="ndb:mt-0"
            />

            <details class="ndb:group ndb:mt-5 ndb:border-t ndb:border-zinc-200/90 ndb:pt-3 ndb:dark:border-zinc-800">
                <summary class="ndb:flex ndb:cursor-pointer ndb:list-none ndb:items-center ndb:justify-between ndb:gap-3 ndb:text-xs ndb:font-bold ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500">
                    <span>Vendor stack</span>
                    <span class="ndb:text-[11px] ndb:font-medium ndb:tabular-nums ndb:text-zinc-400">
                        {{ number_format(count($vendorFrames)) }} {{ \Illuminate\Support\Str::plural('frame', count($vendorFrames)) }}
                    </span>
                </summary>
                <x-newdebugbar::inspector-stack
                    :frames="\Illuminate\Support\Js::from($vendorFrames)"
                    :show-heading="false"
                    empty-label="No vendor frames were captured."
                    class="ndb:mt-2"
                />
            </details>
        </section>
    </template>
</article>
