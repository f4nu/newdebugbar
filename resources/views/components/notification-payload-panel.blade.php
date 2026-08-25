<div
    data-ndb-notification-detail-panel="payload"
    x-show.important="notificationDetailTab === 'payload'"
    class="ndb:space-y-5 ndb:p-4"
>
    <section>
        <div class="ndb:flex ndb:items-center ndb:justify-between ndb:gap-3">
            <h4 class="ndb:text-xs ndb:font-bold">Application payload</h4>
            <span
                x-show="selectedNotification.locale"
                class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400"
                x-text="'Locale ' + selectedNotification.locale"
            ></span>
        </div>
        <pre class="ndb-scrollbar ndb:mt-2 ndb:overflow-x-auto ndb:rounded-lg ndb:bg-zinc-100/75 ndb:p-3 ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-zinc-700 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"><code x-text="formatNotificationEvidence(selectedNotification.notification_data)"></code></pre>
    </section>

    <section class="ndb:border-t ndb:border-zinc-200/90 ndb:pt-4 ndb:dark:border-zinc-800">
        <div class="ndb:flex ndb:items-center ndb:justify-between ndb:gap-3">
            <h4 class="ndb:text-xs ndb:font-bold">Channel evidence</h4>
            <span
                class="ndb:text-[11px] ndb:font-bold ndb:text-zinc-500 ndb:dark:text-zinc-400"
                x-text="selectedNotificationDelivery?.channel_label"
            ></span>
        </div>
        <dl class="ndb:mt-2 ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800">
            <template
                x-for="
                    field in
                    [
                        ['Destination', selectedNotificationDelivery?.destination_label, false],
                        ['Status', selectedNotificationDelivery?.status_label, false],
                        ['Response type', selectedNotificationDelivery?.response_type, false],
                        ['Exception', selectedNotificationDelivery?.exception_class, true],
                        [
                            'Failed at',
                            selectedNotificationDelivery?.exception_location
                                ? selectedNotificationDelivery.exception_location.file +
                                  ':' +
                                  selectedNotificationDelivery.exception_location.line
                                : null,
                            false,
                        ],
                        ['Message ID', selectedNotificationDelivery?.mail_message_id, false],
                    ]
                "
                :key="field[0]"
            >
                <div
                    x-show.important="field[1]"
                    class="ndb:grid ndb:gap-1 ndb:py-2 ndb:sm:grid-cols-[8rem_minmax(0,1fr)] ndb:sm:gap-4"
                >
                    <dt class="ndb:text-[11px] ndb:font-bold" x-text="field[0]"></dt>
                    <dd
                        :class="field[2] ? 'ndb:font-mono ndb:text-[11px]' : 'ndb:text-xs'"
                        class="ndb:break-all ndb:text-zinc-600 ndb:dark:text-zinc-300"
                        x-text="field[1]"
                    ></dd>
                </div>
            </template>
        </dl>
        <pre class="ndb-scrollbar ndb:mt-2 ndb:overflow-x-auto ndb:rounded-lg ndb:bg-zinc-100/75 ndb:p-3 ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-zinc-700 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"><code x-text="formatNotificationEvidence(selectedNotificationDelivery?.response, 'No provider response was captured.')"></code></pre>
        <div x-show="selectedNotificationDelivery?.status === 'failed'" class="ndb:mt-3">
            <p
                x-show="selectedNotificationDelivery?.failure_message"
                class="ndb:rounded-lg ndb:bg-red-50 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-semibold ndb:leading-5 ndb:text-red-700 ndb:dark:bg-red-950/30 ndb:dark:text-red-300"
                x-text="selectedNotificationDelivery?.failure_message"
            ></p>
            <pre class="ndb-scrollbar ndb:mt-2 ndb:overflow-x-auto ndb:rounded-lg ndb:bg-zinc-100/75 ndb:p-3 ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-zinc-700 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"><code x-text="formatNotificationEvidence(selectedNotificationDelivery?.failure_data, 'No extra failure data was captured.')"></code></pre>
        </div>
    </section>

    <section
        x-show="Object.keys(selectedNotification.routes).length > 0"
        class="ndb:border-t ndb:border-zinc-200/90 ndb:pt-4 ndb:dark:border-zinc-800"
    >
        <h4 class="ndb:text-xs ndb:font-bold">Anonymous routes</h4>
        <pre class="ndb-scrollbar ndb:mt-2 ndb:overflow-x-auto ndb:rounded-lg ndb:bg-zinc-100/75 ndb:p-3 ndb:font-mono ndb:text-[11px] ndb:leading-5 ndb:text-zinc-700 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300"><code x-text="formatNotificationEvidence(selectedNotification.routes)"></code></pre>
    </section>
</div>
