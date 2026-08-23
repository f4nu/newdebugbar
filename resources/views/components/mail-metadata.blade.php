<div
    data-ndb-mail-metadata
    class="ndb:mt-2 ndb:overflow-hidden ndb:rounded-lg ndb:border-0 ndb:bg-zinc-50/85 ndb:ring-1 ndb:ring-inset ndb:ring-zinc-200/70 ndb:dark:bg-zinc-900/65 ndb:dark:ring-zinc-800"
>
    <dl class="ndb:grid ndb:grid-cols-2 ndb:border-b ndb:border-zinc-200/70 ndb:dark:border-zinc-800">
        <div class="ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-1.5 ndb:border-r ndb:border-zinc-200/70 ndb:px-2.5 ndb:py-1.5 ndb:dark:border-zinc-800">
            <dt class="ndb:shrink-0 ndb:text-[11px] ndb:font-bold ndb:leading-4 ndb:text-indigo-600 ndb:dark:text-indigo-300">
                To
            </dt>
            <dd
                :title="formatMailAddresses(selectedMailMessage.to)"
                class="ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:leading-4 ndb:text-zinc-700 ndb:dark:text-zinc-200"
                x-text="formatMailAddresses(selectedMailMessage.to)"
            ></dd>
        </div>
        <div class="ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-1.5 ndb:px-2.5 ndb:py-1.5">
            <dt class="ndb:shrink-0 ndb:text-[11px] ndb:font-medium ndb:leading-4 ndb:text-zinc-400">From</dt>
            <dd
                :title="formatMailAddresses(selectedMailMessage.from)"
                class="ndb:truncate ndb:text-[11px] ndb:font-medium ndb:leading-4 ndb:text-zinc-600 ndb:dark:text-zinc-300"
                x-text="formatMailAddresses(selectedMailMessage.from)"
            ></dd>
        </div>
    </dl>

    <dl class="ndb:grid ndb:grid-cols-3">
        <div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-1.5 ndb:border-r ndb:border-zinc-200/70 ndb:px-1.5 ndb:py-1.5 ndb:dark:border-zinc-800">
            <span class="ndb:flex ndb:size-6 ndb:shrink-0 ndb:items-center ndb:justify-center ndb:rounded-md ndb:bg-indigo-100/80 ndb:text-indigo-600 ndb:dark:bg-indigo-950/70 ndb:dark:text-indigo-300">
                <x-newdebugbar::icon name="clock" size="3" />
            </span>
            <div class="ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-1">
                <dt
                    class="ndb:shrink-0 ndb:text-[11px] ndb:font-medium ndb:leading-4 ndb:text-zinc-400"
                    x-text="selectedMailMessage.status_label"
                ></dt>
                <dd
                    class="ndb:truncate ndb:text-[11px] ndb:font-bold ndb:leading-4 ndb:tabular-nums ndb:text-zinc-700 ndb:dark:text-zinc-200"
                    x-text="
                        selectedMailMessage.status === 'sent'
                            ? selectedMailMessage.duration_ms.toFixed(2) + ' ms'
                            : selectedMailMessage.delay_seconds > 0
                              ? selectedMailMessage.delay_seconds + ' s'
                              : '—'
                    "
                ></dd>
            </div>
        </div>
        <div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-1.5 ndb:border-r ndb:border-zinc-200/70 ndb:px-1.5 ndb:py-1.5 ndb:dark:border-zinc-800">
            <span class="ndb:flex ndb:size-6 ndb:shrink-0 ndb:items-center ndb:justify-center ndb:rounded-md ndb:bg-emerald-100/80 ndb:text-emerald-600 ndb:dark:bg-emerald-950/70 ndb:dark:text-emerald-300">
                <x-newdebugbar::icon name="server" size="3" />
            </span>
            <div class="ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-1">
                <dt class="ndb:text-[11px] ndb:font-medium ndb:leading-4 ndb:text-zinc-400">Delivery</dt>
                <dd
                    :title="selectedMailMessage.delivery_label"
                    class="ndb:truncate ndb:text-[11px] ndb:font-bold ndb:leading-4 ndb:text-zinc-700 ndb:dark:text-zinc-200"
                    x-text="selectedMailMessage.delivery_label"
                ></dd>
            </div>
        </div>
        <div class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-1.5 ndb:px-1.5 ndb:py-1.5">
            <span class="ndb:flex ndb:size-6 ndb:shrink-0 ndb:items-center ndb:justify-center ndb:rounded-md ndb:bg-amber-100/80 ndb:text-amber-700 ndb:dark:bg-amber-950/70 ndb:dark:text-amber-300">
                <x-newdebugbar::icon name="code" size="3" />
            </span>
            <div class="ndb:flex ndb:min-w-0 ndb:items-baseline ndb:gap-1">
                <dt class="ndb:shrink-0 ndb:text-[11px] ndb:font-medium ndb:leading-4 ndb:text-zinc-400">Source</dt>
                <dd class="ndb:min-w-0">
                    <code
                        :title="selectedMailMessage.callsite_label"
                        class="ndb:block ndb:truncate ndb:font-mono ndb:text-[11px] ndb:font-semibold ndb:leading-4 ndb:text-zinc-700 ndb:dark:text-zinc-200"
                        x-text="selectedMailMessage.callsite_short_label"
                    ></code>
                </dd>
            </div>
        </div>
    </dl>
</div>
