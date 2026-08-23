<x-newdebugbar::inspector-detail-header data-ndb-mail-header>
    <x-slot:title>
        <h3
            data-ndb-mail-detail-subject
            class="ndb:break-words ndb:text-base ndb:font-bold ndb:leading-6"
            x-text="selectedMailMessage.subject"
        ></h3>
    </x-slot:title>

    <x-slot:aside>
        <div class="ndb:flex ndb:shrink-0 ndb:items-center ndb:gap-2">
            <span
                data-ndb-mail-status
                :class="selectedMailMessage.status_class"
                class="ndb:inline-flex ndb:rounded-md ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-bold"
                x-text="selectedMailMessage.status_label"
            ></span>
            <span
                x-show.important="selectedMailMessage.lifecycle === 'after_response'"
                class="ndb:rounded-md ndb:bg-indigo-100 ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-semibold ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300"
            >After response</span>
            <x-newdebugbar::mail-actions />
        </div>
    </x-slot:aside>

    <x-slot:identity data-ndb-mail-recipient>
        <dl class="ndb:space-y-2">
            <div class="ndb:grid ndb:grid-cols-[4.75rem_minmax(0,1fr)] ndb:items-baseline ndb:gap-2">
                <dt class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">Recipients</dt>
                <dd
                    :title="formatMailAddresses(selectedMailMessage.to)"
                    class="ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-2"
                >
                    <code
                        class="ndb:truncate ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                        x-text="selectedMailMessage.to[0] || 'No recipient captured'"
                    ></code>
                    <span
                        x-show.important="selectedMailMessage.to.length > 1"
                        class="ndb:shrink-0 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:dark:text-indigo-300"
                        x-text="'+' + (selectedMailMessage.to.length - 1) + ' more'"
                    ></span>
                </dd>
            </div>

            <div class="ndb:grid ndb:grid-cols-[4.75rem_minmax(0,1fr)] ndb:items-baseline ndb:gap-2">
                <dt class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">Sender</dt>
                <dd :title="formatMailAddresses(selectedMailMessage.from)" class="ndb:min-w-0">
                    <code
                        class="ndb:block ndb:truncate ndb:font-mono ndb:text-[11px] ndb:font-medium ndb:text-zinc-600 ndb:dark:text-zinc-300"
                        x-text="selectedMailMessage.from[0] || 'No sender captured'"
                    ></code>
                </dd>
            </div>
        </dl>
    </x-slot:identity>

    <x-slot:metadata data-ndb-mail-metadata>
        <div x-show.important="selectedMailMessage.attachment_count > 0">
            <dt class="ndb:sr-only">Attachments</dt>
            <dd
                data-ndb-mail-attachment-badge
                class="ndb:font-bold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                x-text="
                    selectedMailMessage.attachment_count +
                    (selectedMailMessage.attachment_count === 1 ? ' attachment' : ' attachments')
                "
            ></dd>
        </div>
        <div>
            <dt class="ndb:sr-only">Runtime</dt>
            <dd
                class="ndb:font-semibold ndb:tabular-nums"
                x-text="
                    selectedMailMessage.status === 'sent'
                        ? selectedMailMessage.duration_ms.toFixed(2) + ' ms'
                        : selectedMailMessage.delay_seconds > 0
                          ? selectedMailMessage.delay_seconds + ' s delay'
                          : selectedMailMessage.status_label
                "
            ></dd>
        </div>
        <div class="ndb:min-w-0">
            <dt class="ndb:sr-only">Delivery</dt>
            <dd
                :title="selectedMailMessage.delivery_label"
                class="ndb:truncate ndb:font-semibold"
                x-text="selectedMailMessage.delivery_label"
            ></dd>
        </div>
        <div class="ndb:min-w-0">
            <dt class="ndb:sr-only">Source</dt>
            <dd
                :title="selectedMailMessage.callsite_label"
                class="ndb:truncate ndb:font-mono ndb:font-medium"
                x-text="selectedMailMessage.callsite_short_label"
            ></dd>
        </div>
    </x-slot:metadata>
</x-newdebugbar::inspector-detail-header>
