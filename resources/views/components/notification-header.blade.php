<x-newdebugbar::inspector-detail-header data-ndb-notification-header>
    <x-slot:title>
        <h3
            data-ndb-notification-detail-title
            class="ndb:break-words ndb:text-base ndb:font-bold ndb:leading-6"
            x-text="selectedNotification.label"
        ></h3>
    </x-slot:title>

    <x-slot:aside>
        <div
            data-ndb-notification-status
            class="ndb:flex ndb:shrink-0 ndb:flex-wrap ndb:items-center ndb:justify-end ndb:gap-2 ndb:bg-transparent ndb:text-[11px]"
        >
            <span
                :class="{
                    'ndb:bg-emerald-100 ndb:text-emerald-700 ndb:dark:bg-emerald-950 ndb:dark:text-emerald-300':
                        selectedNotification.status === 'sent',
                    'ndb:bg-amber-100 ndb:text-amber-700 ndb:dark:bg-amber-950 ndb:dark:text-amber-300': [
                        'partial',
                        'delayed',
                        'waiting',
                    ].includes(selectedNotification.status),
                    'ndb:bg-red-100 ndb:text-red-700 ndb:dark:bg-red-950 ndb:dark:text-red-300':
                        selectedNotification.status === 'failed',
                    'ndb:bg-sky-100 ndb:text-sky-700 ndb:dark:bg-sky-950 ndb:dark:text-sky-300':
                        selectedNotification.status === 'queued',
                    'ndb:bg-indigo-100 ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300':
                        selectedNotification.status === 'processing',
                }"
                class="ndb:inline-flex ndb:shrink-0 ndb:rounded-md ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-bold"
                x-text="selectedNotification.status_label"
            ></span>
            <span
                x-show.important="selectedNotification.lifecycle === 'after_response'"
                class="ndb:rounded-md ndb:bg-indigo-100 ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-semibold ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300"
            >After response</span>
            <button
                type="button"
                data-ndb-notification-profile-link
                x-show.important="selectedNotification.related_profile_id"
                @click="
                    openRelatedProfile(selectedNotification.related_profile_id, selectedNotification.related_section)
                "
                class="ndb:inline-flex ndb:h-8 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:bg-indigo-50 ndb:px-2.5 ndb:text-[11px] ndb:font-bold ndb:text-indigo-700 ndb:transition ndb:hover:bg-indigo-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:bg-indigo-950/55 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950"
            >
                <span x-text="selectedNotification.related_label"></span>
                <x-newdebugbar::icon name="external-link" size="3" />
            </button>
        </div>
    </x-slot:aside>

    <x-slot:identity data-ndb-notification-recipient>
        <dl class="ndb:space-y-2">
            <div class="ndb:grid ndb:grid-cols-[4.75rem_minmax(0,1fr)] ndb:items-baseline ndb:gap-2">
                <dt class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">Recipient</dt>
                <dd class="ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:items-baseline ndb:gap-x-2 ndb:gap-y-0.5">
                    <span
                        class="ndb:truncate ndb:text-xs ndb:font-bold ndb:text-zinc-800 ndb:dark:text-zinc-100"
                        x-text="selectedNotification.recipient_label"
                    ></span>
                    <span
                        x-show.important="selectedNotification.recipient_context_label"
                        :title="selectedNotification.notifiable_type"
                        class="ndb:text-[11px] ndb:font-medium ndb:text-zinc-400"
                        x-text="selectedNotification.recipient_context_label"
                    ></span>
                </dd>
            </div>

            <div class="ndb:grid ndb:grid-cols-[4.75rem_minmax(0,1fr)] ndb:items-start ndb:gap-2">
                <dt class="ndb:pt-1 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">Destinations</dt>
                <dd class="ndb:flex ndb:min-w-0 ndb:flex-wrap ndb:gap-1.5">
                    <template x-for="delivery in selectedNotification.deliveries" :key="delivery.channel">
                        <span
                            :class="delivery.destination_resolved
                                ? 'ndb:bg-white/90 ndb:text-zinc-600 ndb:ring-zinc-200/80 ndb:dark:bg-zinc-950/60 ndb:dark:text-zinc-300 ndb:dark:ring-zinc-700'
                                : 'ndb:bg-amber-50 ndb:text-amber-700 ndb:ring-amber-200/80 ndb:dark:bg-amber-950/30 ndb:dark:text-amber-300 ndb:dark:ring-amber-900'"
                            class="ndb:inline-flex ndb:min-w-0 ndb:max-w-full ndb:items-center ndb:gap-1.5 ndb:rounded-md ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:ring-1 ndb:ring-inset"
                        >
                            <span class="ndb:shrink-0 ndb:font-bold" x-text="delivery.channel_label"></span>
                            <code
                                :title="delivery.destination_label"
                                class="ndb:truncate ndb:font-mono ndb:text-[11px]"
                                x-text="delivery.destination_summary_label"
                            ></code>
                        </span>
                    </template>
                </dd>
            </div>
        </dl>
    </x-slot:identity>

    <x-slot:metadata data-ndb-notification-metadata>
        <div>
            <dt class="ndb:sr-only ndb:text-zinc-400">Channels</dt>
            <dd
                class="ndb:font-bold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                x-text="selectedNotification.channel_count_label"
            ></dd>
        </div>
        <div>
            <dt class="ndb:sr-only ndb:text-zinc-400">Activity</dt>
            <dd
                class="ndb:font-semibold ndb:tabular-nums"
                x-text="
                    ['sent', 'failed', 'partial'].includes(selectedNotification.status) ||
                    selectedNotification.duration_ms > 0
                        ? selectedNotification.duration_ms.toFixed(2) + ' ms'
                        : selectedNotification.delay_seconds > 0
                          ? selectedNotification.delay_seconds + ' s delay'
                          : selectedNotification.status_label
                "
            ></dd>
        </div>
        <div>
            <dt class="ndb:sr-only ndb:text-zinc-400">Execution mode</dt>
            <dd class="ndb:font-semibold" x-text="selectedNotification.execution_mode_label"></dd>
        </div>
        <div class="ndb:min-w-0">
            <dt class="ndb:sr-only ndb:text-zinc-400">Source</dt>
            <dd
                :title="selectedNotification.callsite_label"
                class="ndb:truncate ndb:font-mono ndb:font-medium"
                x-text="selectedNotification.callsite_short_label"
            ></dd>
        </div>
    </x-slot:metadata>
</x-newdebugbar::inspector-detail-header>
