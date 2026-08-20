{{-- Renders validation failures with their messages, rules, and application source. --}}
<div class="ndb:space-y-3">
    @forelse ($section['payload']['items'] as $index => $item)
        @php
            $fieldCount = count($item['fields'] ?? []);
            $failureLabel = $fieldCount.' '.Str::plural('field', $fieldCount).' failed validation';
            $fromPreviousRequest = (bool) ($item['from_previous_request'] ?? false);
            $sessionExplanation = "These messages came from Laravel's session, usually after a redirect. Failed rules and source code are not available on this request.";
            $callsite = is_array($item['callsite'] ?? null) ? $item['callsite'] : null;
            $responseStatus = isset($item['response_status']) ? (int) $item['response_status'] : null;
            $responseLabel = $responseStatus !== null && $responseStatus >= 300 && $responseStatus < 400
                ? 'Redirect '.$responseStatus
                : ($responseStatus === null ? null : 'Response '.$responseStatus);
        @endphp
        <article
            data-ndb-validation-item="{{ $index }}"
            wire:key="validation-{{ $index }}"
            class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-amber-200 ndb:bg-amber-50/35 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/15"
        >
            <header class="ndb:flex ndb:flex-wrap ndb:items-start ndb:gap-3 ndb:p-4">
                <span
                    class="ndb:grid ndb:size-8 ndb:shrink-0 ndb:place-items-center ndb:rounded-lg ndb:bg-amber-100 ndb:text-amber-700 ndb:dark:bg-amber-950 ndb:dark:text-amber-300"
                    ><x-newdebugbar::icon name="warning" class="ndb:size-4"
                /></span>
                <div class="ndb:min-w-0 ndb:flex-1">
                    <h3 class="ndb:text-sm ndb:font-bold">{{ $failureLabel }}</h3>
                    <p class="ndb:mt-0.5 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        {{ $fromPreviousRequest ? 'Carried from the previous request.' : ($item['exception_message'] ?? 'Laravel rejected the submitted data.') }}
                    </p>
                </div>
                <div class="ndb:flex ndb:max-w-full ndb:flex-wrap ndb:gap-1.5 ndb:text-[11px] ndb:font-semibold">
                    @if ($fromPreviousRequest)
                        <span class="ndb:rounded-md ndb:bg-amber-100 ndb:px-2 ndb:py-1 ndb:text-amber-800 ndb:dark:bg-amber-950 ndb:dark:text-amber-300">Previous request</span>
                    @endif
                    <span class="ndb:rounded-md ndb:bg-white/70 ndb:px-2 ndb:py-1 ndb:text-zinc-600 ndb:dark:bg-zinc-900/70 ndb:dark:text-zinc-300">{{ $item['error_bag'] }} bag</span>
                    @if (($item['exception_status'] ?? null) !== null)
                        <span class="ndb:rounded-md ndb:bg-white/70 ndb:px-2 ndb:py-1 ndb:text-zinc-600 ndb:dark:bg-zinc-900/70 ndb:dark:text-zinc-300">Validation {{ $item['exception_status'] }}</span>
                    @endif
                    @if ($responseLabel !== null)
                        <span class="ndb:rounded-md ndb:bg-white/70 ndb:px-2 ndb:py-1 ndb:text-zinc-600 ndb:dark:bg-zinc-900/70 ndb:dark:text-zinc-300">{{ $responseLabel }}</span>
                    @endif
                </div>
            </header>
            @if ($callsite !== null)
                <div class="ndb:border-t ndb:border-amber-200 ndb:px-4 ndb:py-2.5 ndb:dark:border-amber-950">
                    <p class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                        Application source
                    </p>
                    <button
                        type="button"
                        data-ndb-validation-callsite="{{ $index }}"
                        @click="copyText(@js($callsite['file'].':'.$callsite['line']))"
                        class="ndb:mt-1 ndb:block ndb:max-w-full ndb:truncate ndb:text-left ndb:font-mono ndb:text-xs ndb:font-semibold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                        aria-label="Copy validation source"
                    >
                        {{ $callsite['file'] }}:{{ $callsite['line'] }}
                    </button>
                </div>
            @endif
            <ol class="ndb:m-0 ndb:list-none ndb:divide-y ndb:divide-amber-200 ndb:border-t ndb:border-amber-200 ndb:p-0 ndb:dark:divide-amber-950 ndb:dark:border-amber-950">
                @foreach ($item['fields'] as $field)
                    @php
                        $rules = (array) ($item['rules'][$field] ?? []);
                        $messages = (array) ($item['messages'][$field] ?? []);
                    @endphp
                    <li class="ndb:grid ndb:min-w-0 ndb:gap-2 ndb:px-4 ndb:py-3 ndb:sm:grid-cols-[minmax(8rem,0.7fr)_minmax(0,2fr)] ndb:sm:gap-4">
                        <div class="ndb:min-w-0">
                            <code class="ndb:block ndb:break-words ndb:text-xs ndb:font-bold">{{ $field }}</code>
                            @if ($rules !== [])
                                <div
                                    data-ndb-validation-rules="{{ $field }}"
                                    class="ndb:mt-1.5 ndb:flex ndb:flex-wrap ndb:gap-1"
                                >
                                    @foreach ($rules as $rule)
                                        <span class="ndb:rounded-md ndb:bg-amber-100 ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-semibold ndb:text-amber-800 ndb:dark:bg-amber-950 ndb:dark:text-amber-300">{{ $rule }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <ul class="ndb:m-0 ndb:list-none ndb:space-y-1 ndb:p-0">
                            @forelse ($messages as $message)
                                <li
                                    data-ndb-validation-message="{{ $field }}"
                                    class="ndb:text-xs ndb:font-medium ndb:leading-5"
                                >
                                    {{ $message }}
                                </li>
                            @empty
                                <li class="ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                    No validation message was returned.
                                </li>
                            @endforelse
                        </ul>
                    </li>
                @endforeach
            </ol>
            @if ($fromPreviousRequest)
                <p class="ndb:border-t ndb:border-amber-200 ndb:px-4 ndb:py-3 ndb:text-xs ndb:leading-5 ndb:text-zinc-500 ndb:dark:border-amber-950 ndb:dark:text-zinc-400">
                    {{ $sessionExplanation }}
                </p>
            @endif
        </article>
    @empty
        <x-newdebugbar::empty-state label="No validation failures were captured. This does not prove validation ran." />
    @endforelse
</div>
