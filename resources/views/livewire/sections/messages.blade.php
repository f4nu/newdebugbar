{{-- Renders application checkpoints recorded with New Debug Bar's message API. --}}
@php($checkpoints = array_values($section['payload']['items'] ?? []))

<div class="ndb:flex ndb:min-h-0 ndb:flex-1 ndb:flex-col">
    <x-newdebugbar::inspector-workspace mode="stream" frame="top" data-ndb-checkpoint-workspace>
        <x-slot:header class="ndb:px-4 ndb:py-3">
            <x-newdebugbar::inspector-explanation
                title="What are checkpoints?"
                description="They mark moments in your app and can carry local context."
            />
            <p class="ndb:mt-1 ndb:text-[11px] ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                Add one with <code class="ndb:font-mono">NewDebugBar\Debug::message()</code>. This does not write to
                Laravel's logs. Use
                <a
                    href="https://laravel.com/docs/logging#writing-log-messages"
                    target="_blank"
                    rel="noreferrer noopener"
                    aria-label="Read the Laravel logging documentation in a new tab"
                    data-ndb-checkpoint-logging-link
                    class="ndb:font-semibold ndb:text-zinc-700 ndb:underline ndb:decoration-zinc-300 ndb:underline-offset-2 ndb:hover:text-zinc-950 ndb:hover:decoration-zinc-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-zinc-200 ndb:dark:decoration-zinc-600 ndb:dark:hover:text-white ndb:dark:hover:decoration-zinc-300"
                >Laravel logging</a>
                for severity, channels, or persistent output.
            </p>
        </x-slot:header>

        <x-slot:body>
            @if ($checkpoints !== [])
                <ol
                    data-ndb-checkpoint-list
                    aria-label="Application checkpoint timeline"
                    class="ndb:m-0 ndb:list-none ndb:p-4"
                >
                    @foreach ($checkpoints as $index => $checkpoint)
                        <x-newdebugbar::checkpoint-item
                            :checkpoint="$checkpoint"
                            :index="$index"
                            :total="count($checkpoints)"
                        />
                    @endforeach
                </ol>
            @else
                <div class="ndb:flex ndb:min-h-full ndb:p-3">
                    <x-newdebugbar::empty-state
                        centered
                        label="No checkpoints were added."
                        description="Add one with New Debug Bar's message API when you want to mark a step and attach local context."
                    />
                </div>
            @endif
        </x-slot:body>
    </x-newdebugbar::inspector-workspace>
</div>
