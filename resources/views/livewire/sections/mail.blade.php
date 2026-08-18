{{-- Renders captured mail messages and previews. --}}
<dl class="ndb:grid ndb:grid-cols-3 ndb:divide-x ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
    @foreach ([['Messages', $section['summary']['count']], ['Recipients', $section['summary']['recipient_count']], ['Attachments', $section['summary']['attachment_count']]] as [$label, $value])
        <div class="ndb:px-3.5 ndb:py-3">
            <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                {{ $label }}
            </dt>
            <dd class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $value }}</dd>
        </div>
    @endforeach
</dl>
<div class="ndb:space-y-2">
    @forelse ($section['payload']['items'] as $index => $item)
        <article
            wire:key="mail-{{ $index }}"
            class="ndb:min-w-0 ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:bg-white/45 ndb:px-3.5 ndb:py-3 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30"
        >
            <div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3">
                <div class="ndb:min-w-0 ndb:flex-1">
                    <code class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold">{{ $item['source'] ?: 'Mail message' }}</code>
                    <p class="ndb:mt-1 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
                        <span>{{ $item['recipient_count'] }} recipients</span
                        ><span>{{ $item['attachment_count'] }} attachments</span
                        ><span>{{ $item['has_html'] ? 'HTML' : 'No HTML' }}</span
                        ><span>{{ $item['has_text'] ? 'Text' : 'No text' }}</span>
                    </p>
                </div>
                <span class="ndb:shrink-0 ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $item['duration_ms'] }} ms</span>
            </div>
            @if (is_array($item['preview'] ?? null))
                <div class="ndb:mt-3 ndb:space-y-2 ndb:border-t ndb:border-zinc-200 ndb:pt-3 ndb:dark:border-zinc-800">
                    <p class="ndb:text-xs ndb:font-semibold">{{ $item['preview']['subject'] ?: '(No subject)' }}</p>
                    <p class="ndb:break-all ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        To: {{ implode(', ', $item['preview']['to']) ?: '(none)' }}
                    </p>
                    <div class="ndb:flex ndb:flex-wrap ndb:gap-2">
                        @if (is_string($item['preview']['html'] ?? null))
                            <a
                                href="{{ route('newdebugbar.mail-preview', ['profile' => $profileId, 'index' => $index, 'format' => 'html']) }}"
                                target="_blank"
                                rel="noreferrer"
                                class="ndb:rounded-md ndb:border ndb:border-zinc-200 ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-semibold ndb:hover:bg-zinc-100 ndb:dark:border-zinc-700 ndb:dark:hover:bg-zinc-800"
                            >Open HTML preview</a>
                        @endif
                        @if (is_string($item['preview']['text'] ?? null))
                            <a
                                href="{{ route('newdebugbar.mail-preview', ['profile' => $profileId, 'index' => $index, 'format' => 'text']) }}"
                                target="_blank"
                                rel="noreferrer"
                                class="ndb:rounded-md ndb:border ndb:border-zinc-200 ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-semibold ndb:hover:bg-zinc-100 ndb:dark:border-zinc-700 ndb:dark:hover:bg-zinc-800"
                            >Open text preview</a>
                        @endif
                        <a
                            href="{{ route('newdebugbar.mail-preview', ['profile' => $profileId, 'index' => $index, 'format' => 'eml']) }}"
                            class="ndb:rounded-md ndb:border ndb:border-zinc-200 ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-semibold ndb:hover:bg-zinc-100 ndb:dark:border-zinc-700 ndb:dark:hover:bg-zinc-800"
                        >Download .eml</a>
                    </div>
                    @if (($item['preview']['attachments_omitted'] ?? 0) > 0 || ($item['preview']['addresses_omitted'] ?? 0) > 0 || ($item['preview']['truncated'] ?? false))
                        <p class="ndb:text-[11px] ndb:font-semibold ndb:text-amber-700 ndb:dark:text-amber-300">
                            @if (($item['preview']['attachments_omitted'] ?? 0) > 0) {{ $item['preview']['attachments_omitted'] }}attachments omitted.@endif
                            @if (($item['preview']['addresses_omitted'] ?? 0) > 0) {{ $item['preview']['addresses_omitted'] }}addresses omitted.@endif
                            @if ($item['preview']['truncated'] ?? false)
                                Preview content was bounded.
                            @endif
                        </p>
                    @endif
                </div>
            @endif
        </article>
    @empty
        <x-newdebugbar::empty-state label="No mail was sent." />
    @endforelse
</div>
