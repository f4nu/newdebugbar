<div data-ndb-mail-detail-panel="message" x-show.important="mailDetailTab === 'message'" class="ndb:p-4">
    <dl class="ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800">
        <template
            x-for="
                field in
                [
                    ['From', selectedMailMessage.from],
                    ['To', selectedMailMessage.to],
                    ['CC', selectedMailMessage.cc],
                    ['BCC', selectedMailMessage.bcc],
                    ['Reply to', selectedMailMessage.reply_to],
                ]
            "
            :key="field[0]"
        >
            <div
                x-show="field[1].length > 0 || ['From', 'To'].includes(field[0])"
                class="ndb:grid ndb:gap-1 ndb:py-3 ndb:first:pt-0 ndb:sm:grid-cols-[8rem_minmax(0,1fr)] ndb:sm:gap-4"
            >
                <dt class="ndb:text-xs ndb:font-bold" x-text="field[0]"></dt>
                <dd
                    class="ndb:break-all ndb:text-xs ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                    x-text="formatMailAddresses(field[1])"
                ></dd>
            </div>
        </template>
        <template
            x-for="
                field in
                [
                    ['Sender', selectedMailMessage.sender],
                    ['Return path', selectedMailMessage.return_path],
                    ['Date', selectedMailMessage.date],
                    ['Message ID', selectedMailMessage.transport_message_id],
                    ['Default mailer', selectedMailMessage.mailer],
                    ['Transport', selectedMailMessage.transport],
                    ['Connection', selectedMailMessage.connection],
                    ['Queue', selectedMailMessage.queue],
                    ['Job ID', selectedMailMessage.job_id],
                    ['Delay seconds', selectedMailMessage.delay_seconds],
                ]
            "
            :key="field[0]"
        >
            <div
                x-show="field[1]"
                class="ndb:grid ndb:gap-1 ndb:py-3 ndb:sm:grid-cols-[8rem_minmax(0,1fr)] ndb:sm:gap-4"
            >
                <dt class="ndb:text-xs ndb:font-bold" x-text="field[0]"></dt>
                <dd
                    class="ndb:break-all ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                    x-text="field[1]"
                ></dd>
            </div>
        </template>
    </dl>

    <section
        x-show="selectedMailMessage.attachments.length > 0"
        class="ndb:mt-5 ndb:border-t ndb:border-zinc-200/90 ndb:pt-4 ndb:dark:border-zinc-800"
    >
        <h4 class="ndb:text-xs ndb:font-bold">Attachments</h4>
        <div class="ndb:mt-2 ndb:space-y-2">
            <template x-for="(attachment, index) in selectedMailMessage.attachments" :key="index">
                <div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:justify-between ndb:gap-3 ndb:rounded-lg ndb:bg-zinc-100/75 ndb:px-3 ndb:py-2 ndb:dark:bg-zinc-900">
                    <span
                        class="ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-semibold"
                        x-text="attachment.name"
                    ></span>
                    <span
                        class="ndb:shrink-0 ndb:text-[11px] ndb:text-zinc-400"
                        x-text="attachment.content_type"
                    ></span>
                </div>
            </template>
        </div>
        <p class="ndb:mt-2 ndb:text-[11px] ndb:leading-5 ndb:text-zinc-400">
            Attachment bodies are left out of saved previews and the .eml download.
        </p>
    </section>

    <p
        x-show="
            selectedMailMessage.truncated ||
            selectedMailMessage.addresses_omitted > 0 ||
            selectedMailMessage.attachment_metadata_omitted > 0
        "
        class="ndb:mt-4 ndb:rounded-lg ndb:bg-amber-50 ndb:px-3 ndb:py-2 ndb:text-[11px] ndb:font-semibold ndb:leading-5 ndb:text-amber-700 ndb:dark:bg-amber-950/35 ndb:dark:text-amber-300"
    >
        Some message data was bounded to keep this profile responsive.
    </p>

    <details
        data-ndb-mail-headers
        class="ndb:mt-5 ndb:border-t ndb:border-zinc-200/90 ndb:pt-4 ndb:dark:border-zinc-800"
    >
        <summary class="ndb:cursor-pointer ndb:text-xs ndb:font-bold ndb:text-zinc-900 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-zinc-100">
            Raw headers
        </summary>
        <pre class="ndb-scrollbar ndb:mt-3 ndb:overflow-x-auto ndb:rounded-lg ndb:bg-zinc-100/75 ndb:p-3 ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-zinc-700 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"><code x-text="selectedMailMessage.headers || 'No raw headers were captured.'"></code></pre>
    </details>
</div>

<div data-ndb-mail-detail-panel="source" x-show.important="mailDetailTab === 'source'" class="ndb:p-4">
    <div class="ndb:space-y-4">
        <div>
            <p class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                Mailable or notification
            </p>
            <code
                class="ndb:mt-2 ndb:block ndb:break-all ndb:text-xs ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                x-text="selectedMailMessage.source || 'No source class was captured.'"
            ></code>
        </div>
        <div>
            <p class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Triggered at</p>
            <code
                class="ndb:mt-2 ndb:block ndb:break-all ndb:text-xs ndb:font-semibold ndb:text-indigo-600 ndb:dark:text-indigo-300"
                x-text="selectedMailMessage.callsite_label"
            ></code>
        </div>
    </div>

    <div class="ndb:mt-5 ndb:border-t ndb:border-zinc-200/90 ndb:pt-4 ndb:dark:border-zinc-800">
        <h4 class="ndb:text-xs ndb:font-bold">Application stack</h4>
        <template x-if="selectedMailMessage.stack.length === 0">
            <p class="ndb:mt-2 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">
                No application stack was captured.
            </p>
        </template>
        <div class="ndb:mt-2 ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800">
            <template x-for="(frame, index) in selectedMailMessage.stack" :key="index">
                <div class="ndb:py-3 ndb:first:pt-0">
                    <code
                        class="ndb:block ndb:text-[11px] ndb:font-semibold ndb:text-indigo-600 ndb:dark:text-indigo-300"
                        x-text="frame.file + ':' + frame.line"
                    ></code>
                    <p
                        class="ndb:mt-1 ndb:truncate ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                        x-text="frame.function || 'Application call'"
                    ></p>
                </div>
            </template>
        </div>
    </div>
</div>
