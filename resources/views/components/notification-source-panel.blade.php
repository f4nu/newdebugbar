<div
    data-ndb-notification-detail-panel="source"
    x-show.important="notificationDetailTab === 'source'"
    class="ndb:p-4"
>
    <div class="ndb:space-y-4">
        <div>
            <p class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Notification class</p>
            <code
                class="ndb:mt-2 ndb:block ndb:break-all ndb:text-xs ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                x-text="selectedNotification.notification"
            ></code>
        </div>
        <div>
            <p class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Defined at</p>
            <code
                class="ndb:mt-2 ndb:block ndb:break-all ndb:text-xs ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                x-text="
                    selectedNotification.notification_source
                        ? selectedNotification.notification_source.file +
                          ':' +
                          selectedNotification.notification_source.line
                        : 'Source unavailable'
                "
            ></code>
        </div>
        <div>
            <p class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Triggered at</p>
            <code
                class="ndb:mt-2 ndb:block ndb:break-all ndb:text-xs ndb:font-semibold ndb:text-indigo-600 ndb:dark:text-indigo-300"
                x-text="selectedNotification.callsite_label"
            ></code>
        </div>
        <div x-show="selectedNotification.notification_id">
            <p class="ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">Notification ID</p>
            <code
                class="ndb:mt-2 ndb:block ndb:break-all ndb:text-xs ndb:font-semibold ndb:text-zinc-700 ndb:dark:text-zinc-200"
                x-text="selectedNotification.notification_id"
            ></code>
        </div>
    </div>

    <div class="ndb:mt-5 ndb:border-t ndb:border-zinc-200/90 ndb:pt-4 ndb:dark:border-zinc-800">
        <h4 class="ndb:text-xs ndb:font-bold">Application stack</h4>
        <template x-if="selectedNotification.stack.length === 0">
            <p class="ndb:mt-2 ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400">No application stack was captured.</p>
        </template>
        <div class="ndb:mt-2 ndb:divide-y ndb:divide-zinc-200/90 ndb:dark:divide-zinc-800">
            <template x-for="(frame, index) in selectedNotification.stack" :key="index">
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
