{{-- Renders captured mail as a selectable message list with an isolated preview and diagnostics. --}}
@php
    $capturedMailItems = $section['payload']['items'] ?? [];
    $mailSummary = $section['summary'];
    $mailItems = collect($capturedMailItems)
        ->values()
        ->map(function (array $item, int $index) use ($profileId): array {
            $preview = is_array($item['preview'] ?? null) ? $item['preview'] : [];
            $attachments = array_values(is_array($preview['attachments'] ?? null) ? $preview['attachments'] : []);
            $to = array_values(is_array($preview['to'] ?? null) ? $preview['to'] : []);
            $cc = array_values(is_array($preview['cc'] ?? null) ? $preview['cc'] : []);
            $bcc = array_values(is_array($preview['bcc'] ?? null) ? $preview['bcc'] : []);
            $from = array_values(is_array($preview['from'] ?? null) ? $preview['from'] : []);
            $replyTo = array_values(is_array($preview['reply_to'] ?? null) ? $preview['reply_to'] : []);
            $callsite = is_array($item['callsite'] ?? null) ? $item['callsite'] : null;
            $stack = array_values(is_array($item['stack'] ?? null) ? $item['stack'] : []);
            $source = is_string($item['source'] ?? null) ? $item['source'] : null;
            $subject = is_string($preview['subject'] ?? null) && $preview['subject'] !== ''
                ? $preview['subject']
                : '(No subject)';
            $hasHtml = is_string($preview['html'] ?? null);
            $hasText = is_string($preview['text'] ?? null);
            $execution = $index + 1;

            return [
                'execution' => $execution,
                'subject' => $subject,
                'from' => $from,
                'to' => $to,
                'cc' => $cc,
                'bcc' => $bcc,
                'reply_to' => $replyTo,
                'sender' => is_string($preview['sender'] ?? null) ? $preview['sender'] : null,
                'return_path' => is_string($preview['return_path'] ?? null) ? $preview['return_path'] : null,
                'date' => is_string($preview['date'] ?? null) ? $preview['date'] : null,
                'priority' => (int) ($preview['priority'] ?? 3),
                'primary_recipient' => $to[0] ?? $cc[0] ?? $bcc[0] ?? 'No recipient captured',
                'status' => $item['status'] ?? 'sent',
                'duration_ms' => (float) ($item['duration_ms'] ?? 0),
                'mailer' => $item['mailer'] ?? null,
                'transport' => $item['transport'] ?? null,
                'transport_message_id' => $item['transport_message_id'] ?? null,
                'source' => $source,
                'source_label' => $source === null ? 'Mail message' : \Illuminate\Support\Str::afterLast($source, '\\'),
                'callsite' => $callsite,
                'callsite_label' => $callsite === null ? 'Source unavailable' : $callsite['file'].':'.$callsite['line'],
                'stack' => $stack,
                'headers' => is_string($preview['headers'] ?? null) ? $preview['headers'] : '',
                'attachments' => $attachments,
                'attachment_count' => (int) ($item['attachment_count'] ?? count($attachments)),
                'attachment_bodies_omitted' => (int) ($preview['attachments_omitted'] ?? 0),
                'attachment_metadata_omitted' => (int) ($preview['attachment_metadata_omitted'] ?? 0),
                'addresses_omitted' => (int) ($preview['addresses_omitted'] ?? 0),
                'truncated' => (bool) ($preview['truncated'] ?? false),
                'has_html' => $hasHtml,
                'has_text' => $hasText,
                'html_url' => $hasHtml
                    ? route('newdebugbar.mail-preview', ['profile' => $profileId, 'index' => $index, 'format' => 'html'])
                    : null,
                'text_url' => $hasText
                    ? route('newdebugbar.mail-preview', ['profile' => $profileId, 'index' => $index, 'format' => 'text'])
                    : null,
                'eml_url' => route('newdebugbar.mail-preview', ['profile' => $profileId, 'index' => $index, 'format' => 'eml']),
                'search' => mb_strtolower(implode(' ', array_filter([
                    $subject,
                    ...$from,
                    ...$to,
                    ...$cc,
                    ...$bcc,
                    $source,
                    $callsite === null ? null : $callsite['file'],
                    ...array_map(static fn (array $attachment): string => (string) ($attachment['name'] ?? ''), $attachments),
                ]))),
            ];
        })
        ->all();
    $mailFilters = [
        'all' => ['All', count($mailItems)],
        'attachments' => ['Attachments', count(array_filter($mailItems, static fn (array $item): bool => $item['attachment_count'] > 0))],
    ];
@endphp

<div data-ndb-mail x-init="initializeMail({{ \Illuminate\Support\Js::encode($mailItems) }})" class="ndb:space-y-4">
    @if ($mailItems !== [])
        <div
            data-ndb-mail-workspace
            class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/45 ndb:lg:grid ndb:lg:h-[36rem] ndb:lg:grid-cols-[minmax(18rem,0.72fr)_minmax(0,1.68fr)] ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/35"
        >
            <div class="ndb:flex ndb:min-h-0 ndb:flex-col ndb:border-b ndb:border-zinc-200/90 ndb:lg:border-r ndb:lg:border-b-0 ndb:dark:border-zinc-800">
                <div class="ndb:space-y-3 ndb:border-b ndb:border-zinc-200/90 ndb:p-3 ndb:dark:border-zinc-800">
                    <div class="ndb:flex ndb:items-start ndb:justify-between ndb:gap-3">
                        <p
                            data-ndb-mail-summary
                            class="ndb:min-w-0 ndb:text-xs ndb:font-semibold ndb:text-zinc-600 ndb:dark:text-zinc-300"
                        >
                            <span data-ndb-mail-summary-count class="ndb:block">
                                {{ number_format((int) ($mailSummary['retained_count'] ?? count($mailItems))) }} {{ \Illuminate\Support\Str::plural('message', (int) ($mailSummary['retained_count'] ?? count($mailItems))) }}
                            </span>
                            <span
                                data-ndb-mail-summary-runtime
                                class="ndb:mt-0.5 ndb:block ndb:text-[11px] ndb:font-medium ndb:tabular-nums ndb:text-zinc-400"
                            >
                                {{ number_format((float) ($mailSummary['duration_ms'] ?? 0), 2) }} ms total
                            </span>
                            @if (($mailSummary['dropped_count'] ?? 0) > 0)
                                <span class="ndb:mt-0.5 ndb:block ndb:text-[11px] ndb:text-amber-600 ndb:dark:text-amber-300">
                                    {{ number_format((int) $mailSummary['dropped_count']) }} not retained
                                </span>
                            @endif
                        </p>

                        <label class="ndb:relative ndb:shrink-0">
                            <span class="ndb:sr-only">Filter captured mail</span>
                            <select
                                data-ndb-mail-filter
                                x-model="mailFilter"
                                @change="setMailFilter($event.target.value)"
                                class="ndb:h-8 ndb:appearance-none ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/75 ndb:pr-8 ndb:pl-2.5 ndb:text-[11px] ndb:font-semibold ndb:outline-none ndb:transition ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900"
                            >
                                @foreach ($mailFilters as $filter => [$label, $count])
                                    <option value="{{ $filter }}">{{ $label }} ({{ $count }})</option>
                                @endforeach
                            </select>
                            <x-newdebugbar::icon
                                name="chevron-down"
                                class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-2.5 ndb:size-3 ndb:-translate-y-1/2 ndb:text-zinc-400"
                            />
                        </label>
                    </div>

                    @if (count($mailItems) > 5)
                        <label class="ndb:relative ndb:block">
                            <span class="ndb:sr-only">Search captured mail</span>
                            <input
                                data-ndb-mail-search
                                x-model="mailSearch"
                                @input.debounce.100ms="applyMailView()"
                                type="search"
                                placeholder="Search subject or recipient"
                                class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-9 ndb:pl-3 ndb:text-xs ndb:outline-none ndb:transition ndb:placeholder:text-zinc-400 ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
                            />
                            <x-newdebugbar::icon
                                name="search"
                                class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-3 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
                            />
                        </label>
                    @endif
                </div>

                <div
                    x-ref="mailList"
                    class="ndb-scrollbar ndb:min-h-0 ndb:flex-1 ndb:divide-y ndb:divide-zinc-200/80 ndb:overflow-y-auto ndb:dark:divide-zinc-800"
                >
                    @foreach ($mailItems as $message)
                        <button
                            type="button"
                            data-ndb-mail-item="{{ $message['execution'] }}"
                            data-execution="{{ $message['execution'] }}"
                            data-attachments="{{ $message['attachment_count'] > 0 ? 'true' : 'false' }}"
                            data-search="{{ $message['search'] }}"
                            @click="selectMailMessage({{ $message['execution'] }}, true)"
                            :aria-pressed="mailSelected === {{ $message['execution'] }}"
                            :class="mailSelected === {{ $message['execution'] }}
                                ? 'ndb:bg-indigo-50/65 ndb:dark:bg-indigo-950/20'
                                : 'ndb:hover:bg-zinc-50/80 ndb:dark:hover:bg-zinc-900/60'"
                            class="ndb:grid ndb:w-full ndb:grid-cols-[minmax(0,1fr)_auto] ndb:items-start ndb:gap-3 ndb:px-3 ndb:py-3 ndb:text-left ndb:transition-colors ndb:focus-visible:relative ndb:focus-visible:z-10 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
                        >
                            <span class="ndb:min-w-0">
                                <span class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold">{{ $message['subject'] }}</span>
                                <span class="ndb:mt-1 ndb:block ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                    To {{ $message['primary_recipient'] }}
                                </span>
                                <span class="ndb:mt-1 ndb:block ndb:truncate ndb:text-[11px] ndb:text-zinc-400">
                                    {{ $message['source_label'] }}
                                </span>
                            </span>
                            <span class="ndb:text-right">
                                <span class="ndb:block ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                    {{ number_format($message['duration_ms'], 2) }} ms
                                </span>
                                @if ($message['attachment_count'] > 0)
                                    <span class="ndb:mt-1 ndb:block ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
                                        {{ $message['attachment_count'] }} {{ \Illuminate\Support\Str::plural('file', $message['attachment_count']) }}
                                    </span>
                                @endif
                            </span>
                        </button>
                    @endforeach
                </div>

                <div x-show.important="visibleMailCount === 0" class="ndb:p-3">
                    <x-newdebugbar::empty-state label="No mail matches these filters." />
                </div>
            </div>

            <section
                x-ref="mailDetail"
                data-ndb-mail-detail
                aria-live="polite"
                aria-label="Selected mail details"
                tabindex="0"
                class="ndb-scrollbar ndb:flex ndb:min-h-[32rem] ndb:min-w-0 ndb:flex-col ndb:scroll-mt-20 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:lg:min-h-0 ndb:lg:overflow-y-auto"
            >
                <template x-if="selectedMailMessage">
                    <div class="ndb:flex ndb:flex-col">
                        <header class="ndb:border-b ndb:border-zinc-200/90 ndb:p-4 ndb:dark:border-zinc-800">
                            <div class="ndb:flex ndb:min-w-0 ndb:items-start ndb:justify-between ndb:gap-3">
                                <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-2">
                                    <span class="ndb:rounded-md ndb:bg-emerald-100 ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-bold ndb:text-emerald-700 ndb:dark:bg-emerald-950 ndb:dark:text-emerald-300">
                                        Sent
                                    </span>
                                    <span
                                        data-ndb-mail-attachment-badge
                                        x-show="selectedMailMessage.attachment_count > 0"
                                        class="ndb:rounded-md ndb:bg-zinc-100 ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-400"
                                        x-text="
                                            selectedMailMessage.attachment_count +
                                            (selectedMailMessage.attachment_count === 1
                                                ? ' attachment'
                                                : ' attachments')
                                        "
                                    ></span>
                                </div>
                                <div class="ndb:flex ndb:shrink-0 ndb:flex-wrap ndb:justify-end ndb:gap-2">
                                    <a
                                        data-ndb-mail-open-preview
                                        :href="mailPreviewUrl()"
                                        x-show="selectedMailMessage.has_html || selectedMailMessage.has_text"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="ndb:inline-flex ndb:h-8 ndb:items-center ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:px-2.5 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-600 ndb:hover:bg-zinc-100 ndb:hover:text-zinc-950 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:border-zinc-700 ndb:dark:text-zinc-300 ndb:dark:hover:bg-zinc-800 ndb:dark:hover:text-white"
                                    >Open preview</a>
                                    <a
                                        data-ndb-mail-download
                                        :href="selectedMailMessage.eml_url"
                                        class="ndb:inline-flex ndb:h-8 ndb:items-center ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:px-2.5 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-600 ndb:hover:bg-zinc-100 ndb:hover:text-zinc-950 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:border-zinc-700 ndb:dark:text-zinc-300 ndb:dark:hover:bg-zinc-800 ndb:dark:hover:text-white"
                                    >Download .eml</a>
                                </div>
                            </div>
                            <h3
                                data-ndb-mail-detail-subject
                                class="ndb:mt-3 ndb:text-base ndb:font-bold ndb:leading-6"
                                x-text="selectedMailMessage.subject"
                            ></h3>
                            <p class="ndb:mt-1 ndb:truncate ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                                <span x-text="formatMailAddresses(selectedMailMessage.from)"></span>
                                <span aria-hidden="true"> → </span>
                                <span x-text="formatMailAddresses(selectedMailMessage.to)"></span>
                            </p>
                            <p class="ndb:mt-3 ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-x-2 ndb:gap-y-1 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
                                <span
                                    class="ndb:tabular-nums"
                                    x-text="selectedMailMessage.duration_ms.toFixed(2) + ' ms'"
                                ></span>
                                <span
                                    x-text="
                                        selectedMailMessage.mailer
                                            ? 'Default mailer: ' + selectedMailMessage.mailer
                                            : 'Default mailer unavailable'
                                    "
                                ></span>
                                <code
                                    class="ndb:truncate ndb:font-mono"
                                    x-text="selectedMailMessage.callsite_label"
                                ></code>
                            </p>
                        </header>

                        <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:justify-between ndb:gap-2 ndb:border-b ndb:border-zinc-200/90 ndb:px-4 ndb:py-2.5 ndb:dark:border-zinc-800">
                            <x-newdebugbar::filter-tabs label="Mail detail" class="ndb:min-w-0">
                                @foreach (['preview' => 'Preview', 'message' => 'Message', 'source' => 'Source'] as $tab => $label)
                                    <x-newdebugbar::filter-tab
                                        data-ndb-mail-detail-tab="{{ $tab }}"
                                        @click="setMailDetailTab({{ \Illuminate\Support\Js::from($tab) }})"
                                        ::aria-pressed="mailDetailTab === {{ \Illuminate\Support\Js::from($tab) }}"
                                    >
                                        {{ $label }}
                                    </x-newdebugbar::filter-tab>
                                @endforeach
                            </x-newdebugbar::filter-tabs>
                            <div
                                data-ndb-mail-preview-controls
                                x-show.important="
                                    mailDetailTab === 'preview' &&
                                    (selectedMailMessage.has_html || selectedMailMessage.has_text)
                                "
                                class="ndb:ml-auto ndb:flex ndb:items-center ndb:gap-2"
                            >
                                <div
                                    role="group"
                                    aria-label="Mail preview width"
                                    data-ndb-mail-preview-viewport-control
                                    class="ndb:inline-flex ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-zinc-100/80 ndb:p-0.5 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900"
                                >
                                    @foreach (['desktop' => ['Desktop preview', 'monitor'], 'mobile' => ['Mobile preview', 'smartphone']] as $viewport => [$label, $icon])
                                        <button
                                            type="button"
                                            data-ndb-mail-preview-viewport="{{ $viewport }}"
                                            @click="setMailPreviewViewport({{ \Illuminate\Support\Js::from($viewport) }})"
                                            :aria-pressed="mailPreviewViewport === {{ \Illuminate\Support\Js::from($viewport) }}"
                                            :class="mailPreviewViewport === {{ \Illuminate\Support\Js::from($viewport) }}
                        ? 'ndb:bg-white ndb:text-indigo-600 ndb:shadow-sm ndb:dark:bg-zinc-800 ndb:dark:text-indigo-300'
                        : 'ndb:text-zinc-400 ndb:hover:text-zinc-700 ndb:dark:hover:text-zinc-200'"
                                            aria-label="{{ $label }}"
                                            title="{{ $label }}"
                                            class="ndb:inline-flex ndb:size-7 ndb:items-center ndb:justify-center ndb:rounded-md ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
                                        >
                                            <x-newdebugbar::icon name="{{ $icon }}" size="3" />
                                        </button>
                                    @endforeach
                                </div>
                                <label
                                    x-show="selectedMailMessage.has_html && selectedMailMessage.has_text"
                                    class="ndb:relative"
                                >
                                    <span class="ndb:sr-only">Mail preview format</span>
                                    <select
                                        data-ndb-mail-preview-format
                                        x-model="mailPreviewFormat"
                                        @change="setMailPreviewFormat($event.target.value)"
                                        class="ndb:h-8 ndb:appearance-none ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/75 ndb:pr-8 ndb:pl-2.5 ndb:text-[11px] ndb:font-semibold ndb:outline-none ndb:transition ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900"
                                    >
                                        <option value="html" :disabled="! selectedMailMessage.has_html">HTML</option>
                                        <option value="text" :disabled="! selectedMailMessage.has_text">Text</option>
                                    </select>
                                    <x-newdebugbar::icon
                                        name="chevron-down"
                                        class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-2.5 ndb:size-3 ndb:-translate-y-1/2 ndb:text-zinc-400"
                                    />
                                </label>
                            </div>
                        </div>

                        <div
                            data-ndb-mail-detail-panel="preview"
                            x-show.important="mailDetailTab === 'preview'"
                            class="ndb:flex ndb:flex-col"
                        >
                            <div
                                data-ndb-mail-preview-surface
                                class="ndb:flex ndb:bg-zinc-100/70 ndb:p-3 ndb:dark:bg-zinc-950/65"
                            >
                                <template x-if="selectedMailMessage.has_html || selectedMailMessage.has_text">
                                    <div
                                        data-ndb-mail-preview-canvas
                                        :class="mailPreviewViewport === 'mobile'
                                            ? 'ndb:max-w-[23.4375rem]'
                                            : 'ndb:max-w-none'"
                                        class="ndb:mx-auto ndb:min-h-80 ndb:w-full ndb:flex-1 ndb:transition-[max-width]"
                                    >
                                        <iframe
                                            x-ref="mailPreviewFrame"
                                            data-ndb-mail-preview-frame
                                            :src="mailPreviewUrl()"
                                            :title="'Preview of ' + selectedMailMessage.subject"
                                            x-init="connectMailPreviewFrame($el)"
                                            @load="resizeMailPreviewFrame($event.currentTarget)"
                                            sandbox="allow-scripts"
                                            referrerpolicy="no-referrer"
                                            class="ndb:h-80 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white ndb:shadow-sm"
                                        ></iframe>
                                    </div>
                                </template>
                                <template x-if="! selectedMailMessage.has_html && ! selectedMailMessage.has_text">
                                    <div class="ndb:m-auto ndb:w-full">
                                        <x-newdebugbar::empty-state label="No HTML or text body was captured." />
                                    </div>
                                </template>
                            </div>
                        </div>

                        <x-newdebugbar::mail-message-details />
                    </div>
                </template>
            </section>
        </div>
    @else
        <x-newdebugbar::empty-state label="No mail was sent." />
    @endif
</div>
