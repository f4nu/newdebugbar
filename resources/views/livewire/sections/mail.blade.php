{{-- Renders captured mail as a selectable message list with an isolated preview and diagnostics. --}}
@php
    $capturedMailItems = $section['payload']['items'] ?? [];
    $mailSummary = $section['summary'];
    $formatMailBytes = static fn (int $bytes): string => match (true) {
        $bytes >= 1024 * 1024 => number_format($bytes / (1024 * 1024), 2).' MB',
        $bytes >= 1024 => number_format($bytes / 1024, 2).' KB',
        default => number_format($bytes).' B',
    };
    $mailItems = collect($capturedMailItems)
        ->values()
        ->map(function (array $item, int $index) use ($formatMailBytes, $profileId): array {
            $preview = is_array($item['preview'] ?? null) ? $item['preview'] : [];
            $attachments = collect(is_array($preview['attachments'] ?? null) ? $preview['attachments'] : [])
                ->values()
                ->map(function (mixed $attachment, int $attachmentIndex) use ($formatMailBytes, $index, $profileId): array {
                    $attachment = is_array($attachment) ? $attachment : [];
                    $sizeBytes = is_numeric($attachment['size_bytes'] ?? null)
                        ? max(0, (int) $attachment['size_bytes'])
                        : null;
                    $downloadable = is_string($attachment['body_base64'] ?? null);

                    return [
                        'name' => is_string($attachment['name'] ?? null) && $attachment['name'] !== ''
                            ? $attachment['name']
                            : 'Attachment '.($attachmentIndex + 1),
                        'content_type' => is_string($attachment['content_type'] ?? null)
                            ? $attachment['content_type']
                            : 'application/octet-stream',
                        'disposition' => is_string($attachment['disposition'] ?? null)
                            ? $attachment['disposition']
                            : 'attachment',
                        'content_id' => is_string($attachment['content_id'] ?? null)
                            ? $attachment['content_id']
                            : null,
                        'size_bytes' => $sizeBytes,
                        'size_label' => $sizeBytes === null ? 'Size unavailable' : $formatMailBytes($sizeBytes),
                        'download_url' => $downloadable
                            ? route('newdebugbar.mail-attachment', [
                                'profile' => $profileId,
                                'index' => $index,
                                'attachment' => $attachmentIndex,
                            ])
                            : null,
                    ];
                })
                ->all();
            $to = array_values(is_array($preview['to'] ?? null) ? $preview['to'] : []);
            $cc = array_values(is_array($preview['cc'] ?? null) ? $preview['cc'] : []);
            $bcc = array_values(is_array($preview['bcc'] ?? null) ? $preview['bcc'] : []);
            $from = array_values(is_array($preview['from'] ?? null) ? $preview['from'] : []);
            $replyTo = array_values(is_array($preview['reply_to'] ?? null) ? $preview['reply_to'] : []);
            $callsite = is_array($item['callsite'] ?? null) ? $item['callsite'] : null;
            $stack = array_values(is_array($item['stack'] ?? null) ? $item['stack'] : []);
            $source = is_string($item['source'] ?? null) ? $item['source'] : null;
            $mailer = is_string($item['mailer'] ?? null) && $item['mailer'] !== '' ? $item['mailer'] : null;
            $transport = is_string($item['transport'] ?? null) && $item['transport'] !== '' ? $item['transport'] : null;
            $status = (string) ($item['status'] ?? 'sent');
            $statusLabel = [
                'queued' => 'Queued',
                'delayed' => 'Delayed',
                'processing' => 'Processing',
                'sent' => 'Sent',
                'failed' => 'Failed',
                'waiting' => 'Waiting for worker',
            ][$status] ?? ucfirst($status);
            $statusTextClass = [
                'queued' => 'ndb:text-sky-600 ndb:dark:text-sky-300',
                'delayed' => 'ndb:text-amber-600 ndb:dark:text-amber-300',
                'processing' => 'ndb:text-indigo-600 ndb:dark:text-indigo-300',
                'sent' => 'ndb:text-emerald-600 ndb:dark:text-emerald-300',
                'failed' => 'ndb:text-red-600 ndb:dark:text-red-300',
                'waiting' => 'ndb:text-amber-600 ndb:dark:text-amber-300',
            ][$status] ?? 'ndb:text-zinc-600 ndb:dark:text-zinc-300';
            $subject = is_string($preview['subject'] ?? null) && $preview['subject'] !== ''
                ? $preview['subject']
                : ($source === null ? '(No subject)' : class_basename($source));
            $hasHtml = is_string($preview['html'] ?? null);
            $hasText = is_string($preview['text'] ?? null);
            $hasPreview = $hasHtml || $hasText;
            $execution = $index + 1;
            $callsiteLabel = $callsite === null ? 'Source unavailable' : $callsite['file'].':'.$callsite['line'];
            $callsiteShortLabel = $callsite === null
                ? 'Unavailable'
                : basename(str_replace('\\', '/', $callsite['file'])).':'.$callsite['line'];
            $deliveryLabel = $mailer ?? $transport ?? 'Unavailable';

            if ($mailer !== null && $transport !== null && $mailer !== $transport) {
                $deliveryLabel = $mailer.' via '.$transport;
            }

            if ($mailer === null && $transport === null && ($item['connection'] ?? null) !== null) {
                $deliveryLabel = $item['connection'].' on '.(($item['queue'] ?? null) ?: 'default queue');
            }

            $isOrigin = (bool) ($item['is_origin'] ?? false);
            $relatedProfileId = $isOrigin ? ($item['worker_profile_id'] ?? null) : ($item['origin_profile_id'] ?? null);
            $relatedProfileId = is_string($relatedProfileId) && $relatedProfileId !== $profileId ? $relatedProfileId : null;
            $relatedSection = $isOrigin && $status === 'sent' ? 'mail' : 'queue';
            $attachmentCount = (int) ($item['attachment_count'] ?? count($attachments));
            $downloadableAttachmentCount = count(array_filter(
                $attachments,
                static fn (array $attachment): bool => is_string($attachment['download_url']),
            ));
            $attachmentSummaryLabel = match (true) {
                $attachmentCount === 0 => 'None',
                $downloadableAttachmentCount === $attachmentCount => $attachmentCount.' available',
                $downloadableAttachmentCount > 0 => $downloadableAttachmentCount.' of '.$attachmentCount.' available',
                default => $attachmentCount.' not retained',
            };

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
                'primary_recipient' => $to[0] ?? $cc[0] ?? $bcc[0]
                    ?? (($item['recipient_count'] ?? 0) > 0
                        ? $item['recipient_count'].' '.\Illuminate\Support\Str::plural('recipient', $item['recipient_count'])
                        : 'Recipient resolved by worker'),
                'status' => $status,
                'status_label' => $statusLabel,
                'status_text_class' => $statusTextClass,
                'duration_ms' => (float) ($item['duration_ms'] ?? 0),
                'mailer' => $mailer,
                'transport' => $transport,
                'delivery_label' => $deliveryLabel,
                'transport_message_id' => $item['transport_message_id'] ?? null,
                'connection' => $item['connection'] ?? null,
                'queue' => $item['queue'] ?? null,
                'job_id' => $item['job_id'] ?? null,
                'delay_seconds' => $item['delay_seconds'] ?? null,
                'lifecycle' => $item['lifecycle'] ?? null,
                'related_profile_id' => $relatedProfileId,
                'related_section' => $relatedSection,
                'related_label' => $isOrigin
                    ? ($relatedSection === 'mail' ? 'Open worker preview' : 'Open failed worker')
                    : 'Open request',
                'source' => $source,
                'source_label' => $source === null ? 'Mail message' : \Illuminate\Support\Str::afterLast($source, '\\'),
                'callsite' => $callsite,
                'callsite_label' => $callsiteLabel,
                'callsite_short_label' => $callsiteShortLabel,
                'stack' => $stack,
                'headers' => is_string($preview['headers'] ?? null) ? $preview['headers'] : '',
                'attachments' => $attachments,
                'attachment_count' => $attachmentCount,
                'downloadable_attachment_count' => $downloadableAttachmentCount,
                'attachment_summary_label' => $attachmentSummaryLabel,
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
                'eml_url' => $hasPreview
                    ? route('newdebugbar.mail-preview', ['profile' => $profileId, 'index' => $index, 'format' => 'eml'])
                    : null,
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

<div
    data-ndb-mail
    x-init="initializeMail({{ \Illuminate\Support\Js::encode($mailItems) }})"
    class="ndb:space-y-4 ndb:lg:flex ndb:lg:min-h-0 ndb:lg:flex-1 ndb:lg:flex-col ndb:lg:space-y-0"
>
    @if ($mailItems !== [])
        <x-newdebugbar::inspector-workspace frame="top" data-ndb-mail-workspace>
            <div
                :class="mailDetailOpen ? 'ndb:hidden ndb:lg:flex' : 'ndb:flex'"
                class="ndb:min-h-0 ndb:flex-col ndb:border-b ndb:border-zinc-200/90 ndb:lg:border-r ndb:lg:border-b-0 ndb:dark:border-zinc-800"
            >
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
                            data-ndb-execution="{{ $message['execution'] }}"
                            data-ndb-attachments="{{ $message['attachment_count'] > 0 ? 'true' : 'false' }}"
                            data-ndb-search="{{ $message['search'] }}"
                            @click="selectMailMessage({{ $message['execution'] }})"
                            :aria-pressed="mailSelected === {{ $message['execution'] }}"
                            :class="mailSelected === {{ $message['execution'] }}
                                ? 'ndb:bg-indigo-50/65 ndb:dark:bg-indigo-950/20'
                                : 'ndb:hover:bg-zinc-50/80 ndb:dark:hover:bg-zinc-900/60'"
                            class="ndb:grid ndb:w-full ndb:grid-cols-[minmax(0,1fr)_auto] ndb:items-baseline ndb:gap-x-3 ndb:gap-y-1 ndb:px-3 ndb:py-3 ndb:text-left ndb:transition-colors ndb:focus-visible:relative ndb:focus-visible:z-10 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
                        >
                            <span
                                data-ndb-mail-list-title
                                class="ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-bold"
                            >{{ $message['subject'] }}</span>
                            <span
                                data-ndb-mail-list-status
                                class="ndb:justify-self-end ndb:text-[11px] ndb:font-bold {{ $message['status_text_class'] }}"
                            >{{ $message['status_label'] }}</span>
                            <span
                                data-ndb-mail-list-recipient
                                class="ndb:col-start-1 ndb:min-w-0 ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                            >
                                To {{ $message['primary_recipient'] }}
                            </span>
                            @if (in_array($message['status'], ['sent', 'failed'], true))
                                <span
                                    data-ndb-mail-list-activity
                                    class="ndb:col-start-2 ndb:justify-self-end ndb:text-right ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                >
                                    {{ number_format($message['duration_ms'], 2) }} ms
                                </span>
                            @elseif (($message['delay_seconds'] ?? null) > 0)
                                <span
                                    data-ndb-mail-list-activity
                                    class="ndb:col-start-2 ndb:justify-self-end ndb:text-right ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400"
                                >
                                    {{ $message['delay_seconds'] }} s delay
                                </span>
                            @endif
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
                :class="mailDetailOpen ? 'ndb:flex' : 'ndb:hidden ndb:lg:flex'"
                class="ndb-scrollbar ndb:min-h-[32rem] ndb:min-w-0 ndb:flex-col ndb:scroll-mt-20 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:lg:min-h-0 ndb:lg:overflow-y-auto"
            >
                <x-newdebugbar::inspector-detail-back
                    data-ndb-mail-detail-back
                    @click="mailDetailOpen = false"
                    label="Messages"
                />

                <template x-if="selectedMailMessage">
                    <div class="ndb:flex ndb:flex-col">
                        <x-newdebugbar::mail-header />

                        <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:justify-between ndb:gap-2 ndb:border-b ndb:border-zinc-200/90 ndb:px-4 ndb:py-2.5 ndb:dark:border-zinc-800">
                            <x-newdebugbar::filter-tabs label="Mail detail" variant="segmented" class="ndb:min-w-0">
                                @foreach (['preview' => ['Preview', 'eye'], 'message' => ['Message', 'mail'], 'source' => ['Source', 'code']] as $tab => [$label, $icon])
                                    <x-newdebugbar::filter-tab
                                        variant="segmented"
                                        data-ndb-mail-detail-tab="{{ $tab }}"
                                        @click="setMailDetailTab({{ \Illuminate\Support\Js::from($tab) }})"
                                        ::aria-pressed="mailDetailTab === {{ \Illuminate\Support\Js::from($tab) }}"
                                        aria-label="{{ $label }}"
                                    >
                                        <x-newdebugbar::icon
                                            name="{{ $icon }}"
                                            size="3.5"
                                            data-ndb-mail-detail-tab-icon="{{ $tab }}"
                                            class="ndb:sm:hidden"
                                        />
                                        <span class="ndb:hidden ndb:sm:inline">{{ $label }}</span>
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
                                    :aria-disabled="mailPreviewFormat === 'text'"
                                    data-ndb-mail-preview-viewport-control
                                    class="ndb:inline-flex ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-zinc-100/80 ndb:p-0.5 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900"
                                >
                                    @foreach (['desktop' => ['Desktop preview', 'monitor'], 'mobile' => ['Mobile preview', 'smartphone']] as $viewport => [$label, $icon])
                                        <button
                                            type="button"
                                            data-ndb-mail-preview-viewport="{{ $viewport }}"
                                            @click="setMailPreviewViewport({{ \Illuminate\Support\Js::from($viewport) }})"
                                            :disabled="mailPreviewFormat === 'text'"
                                            :aria-pressed="mailPreviewViewport === {{ \Illuminate\Support\Js::from($viewport) }}"
                                            :class="mailPreviewViewport === {{ \Illuminate\Support\Js::from($viewport) }}
                        ? 'ndb:bg-white ndb:text-indigo-600 ndb:shadow-sm ndb:dark:bg-zinc-800 ndb:dark:text-indigo-300'
                        : 'ndb:text-zinc-400 ndb:hover:text-zinc-700 ndb:dark:hover:text-zinc-200'"
                                            aria-label="{{ $label }}"
                                            title="{{ $label }}"
                                            class="ndb:inline-flex ndb:size-7 ndb:items-center ndb:justify-center ndb:rounded-md ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:pointer-events-none ndb:disabled:opacity-40"
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
                                        :class="mailPreviewFormat === 'html' && mailPreviewViewport === 'mobile'
                                            ? 'ndb:max-w-[23.4375rem]'
                                            : 'ndb:max-w-none'"
                                        class="ndb:relative ndb:mx-auto ndb:h-80 ndb:w-full ndb:flex-1 ndb:overflow-hidden ndb:transition-[max-width]"
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
                                            class="ndb:absolute ndb:top-0 ndb:left-1/2 ndb:block ndb:h-80 ndb:w-full ndb:max-w-none ndb:origin-top ndb:box-border ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white ndb:shadow-sm"
                                        ></iframe>
                                    </div>
                                </template>
                                <template x-if="! selectedMailMessage.has_html && ! selectedMailMessage.has_text">
                                    <div class="ndb:m-auto ndb:flex ndb:min-h-80 ndb:w-full ndb:flex-col ndb:items-center ndb:justify-center ndb:rounded-lg ndb:border ndb:border-dashed ndb:border-zinc-300 ndb:bg-white/55 ndb:px-6 ndb:py-10 ndb:text-center ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/45">
                                        <span class="ndb:grid ndb:size-9 ndb:place-items-center ndb:rounded-xl ndb:bg-zinc-100 ndb:text-zinc-400 ndb:dark:bg-zinc-800">
                                            <x-newdebugbar::icon name="mail" size="4" />
                                        </span>
                                        <p
                                            class="ndb:mt-3 ndb:text-xs ndb:font-bold"
                                            x-text="
                                                selectedMailMessage.status === 'failed'
                                                    ? 'The worker failed before a preview was created.'
                                                    : 'The preview is created when the worker sends this message.'
                                            "
                                        ></p>
                                        <p
                                            x-show="
                                                ['queued', 'delayed', 'processing', 'waiting'].includes(
                                                    selectedMailMessage.status,
                                                )
                                            "
                                            class="ndb:mt-1 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                                            x-text="selectedMailMessage.status_label"
                                        ></p>
                                        <button
                                            x-show.important="selectedMailMessage.related_profile_id"
                                            type="button"
                                            data-ndb-mail-related-profile
                                            @click="
                                                openRelatedProfile(
                                                    selectedMailMessage.related_profile_id,
                                                    selectedMailMessage.related_section,
                                                )
                                            "
                                            class="ndb:mt-4 ndb:inline-flex ndb:h-9 ndb:items-center ndb:gap-2 ndb:rounded-lg ndb:bg-indigo-600 ndb:px-3 ndb:text-xs ndb:font-bold ndb:text-white ndb:transition ndb:hover:bg-indigo-500 ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500"
                                        >
                                            <span x-text="selectedMailMessage.related_label"></span>
                                            <x-newdebugbar::icon name="external-link" size="3.5" />
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <x-newdebugbar::mail-message-details />
                    </div>
                </template>
            </section>
        </x-newdebugbar::inspector-workspace>
    @else
        <x-newdebugbar::empty-state label="No mail was sent or queued." />
    @endif
</div>
