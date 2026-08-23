<div
    data-ndb-notification-detail-panel="delivery"
    x-show.important="notificationDetailTab === 'delivery'"
    class="ndb:p-4"
>
    <div class="ndb:space-y-2.5">
        <template x-for="delivery in selectedNotification.deliveries" :key="delivery.channel">
            <article
                data-ndb-notification-delivery
                :class="delivery.status === 'failed'
                    ? 'ndb:border-red-200 ndb:bg-red-50/45 ndb:dark:border-red-950 ndb:dark:bg-red-950/20'
                    : 'ndb:border-zinc-200 ndb:bg-white/55 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/35'"
                class="ndb:overflow-hidden ndb:rounded-lg ndb:border"
            >
                <div class="ndb:flex ndb:items-start ndb:justify-between ndb:gap-3 ndb:px-3 ndb:py-2.5">
                    <div class="ndb:flex ndb:min-w-0 ndb:items-start ndb:gap-2.5">
                        <span
                            :class="delivery.status === 'failed'
                                ? 'ndb:bg-red-100 ndb:text-red-600 ndb:dark:bg-red-950 ndb:dark:text-red-300'
                                : 'ndb:bg-emerald-100 ndb:text-emerald-600 ndb:dark:bg-emerald-950 ndb:dark:text-emerald-300'"
                            class="ndb:flex ndb:size-7 ndb:shrink-0 ndb:items-center ndb:justify-center ndb:rounded-lg"
                        >
                            <template x-if="delivery.status === 'failed'">
                                <x-newdebugbar::icon name="warning" size="3.5" />
                            </template>
                            <template x-if="delivery.status === 'sent'">
                                <x-newdebugbar::icon name="check" size="3.5" />
                            </template>
                        </span>
                        <div class="ndb:min-w-0">
                            <h4 class="ndb:truncate ndb:text-xs ndb:font-bold" x-text="delivery.channel_label"></h4>
                            <p
                                :class="delivery.status === 'failed'
                                    ? 'ndb:text-red-600 ndb:dark:text-red-300'
                                    : 'ndb:text-zinc-500 ndb:dark:text-zinc-400'"
                                class="ndb:mt-0.5 ndb:text-[11px] ndb:font-medium"
                                x-text="delivery.status_label"
                            ></p>
                            <template x-if="delivery.destination_labels.length <= 1">
                                <code
                                    :class="delivery.destination_resolved
                                        ? 'ndb:text-zinc-500 ndb:dark:text-zinc-400'
                                        : 'ndb:text-amber-700 ndb:dark:text-amber-300'"
                                    class="ndb:mt-1 ndb:block ndb:break-all ndb:font-mono ndb:text-[11px]"
                                    x-text="delivery.destination_label"
                                ></code>
                            </template>
                            <template x-if="delivery.destination_labels.length > 1">
                                <div class="ndb:mt-1.5 ndb:flex ndb:flex-wrap ndb:gap-1">
                                    <template x-for="destination in delivery.destination_labels" :key="destination">
                                        <code
                                            class="ndb:max-w-full ndb:break-all ndb:rounded-md ndb:bg-zinc-100 ndb:px-1.5 ndb:py-0.5 ndb:font-mono ndb:text-[11px] ndb:text-zinc-600 ndb:dark:bg-zinc-800 ndb:dark:text-zinc-300"
                                            x-text="destination"
                                        ></code>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="ndb:flex ndb:shrink-0 ndb:flex-col ndb:items-end ndb:gap-2">
                        <span
                            class="ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
                            x-text="delivery.duration_ms.toFixed(2) + ' ms'"
                        ></span>
                        <template x-if="delivery.mail_available">
                            <button
                                type="button"
                                data-ndb-notification-view-mail
                                @click="openNotificationMail(delivery.mail_message_id)"
                                class="ndb:inline-flex ndb:h-7 ndb:items-center ndb:gap-1.5 ndb:rounded-md ndb:border ndb:border-zinc-200 ndb:bg-white/80 ndb:px-2 ndb:text-[11px] ndb:font-bold ndb:text-zinc-700 ndb:transition ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-200 ndb:dark:hover:bg-zinc-800"
                            >
                                <x-newdebugbar::icon name="mail" size="3.5" />
                                View email
                            </button>
                        </template>
                    </div>
                </div>

                <div
                    x-show="delivery.failure_message"
                    class="ndb:border-t ndb:border-red-200/80 ndb:px-3 ndb:py-2.5 ndb:dark:border-red-950"
                >
                    <p
                        class="ndb:text-xs ndb:font-semibold ndb:leading-5 ndb:text-red-700 ndb:dark:text-red-300"
                        x-text="delivery.failure_message"
                    ></p>
                    <code
                        x-show.important="delivery.exception_class"
                        class="ndb:mt-1 ndb:block ndb:break-all ndb:text-[11px] ndb:text-red-500 ndb:dark:text-red-400"
                        x-text="delivery.exception_class"
                    ></code>
                    <code
                        x-show.important="delivery.exception_location"
                        class="ndb:mt-1 ndb:block ndb:break-all ndb:text-[11px] ndb:text-red-500 ndb:dark:text-red-400"
                        x-text="
                            delivery.exception_location
                                ? delivery.exception_location.file + ':' + delivery.exception_location.line
                                : ''
                        "
                    ></code>
                </div>

                <div
                    x-show="delivery.evidence_summary"
                    class="ndb:border-t ndb:border-zinc-200/80 ndb:px-3 ndb:py-2 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:border-zinc-800 ndb:dark:text-zinc-400"
                    x-text="delivery.evidence_summary"
                ></div>
            </article>
        </template>
    </div>
</div>
