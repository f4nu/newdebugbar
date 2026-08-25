<div data-ndb-notification-detail-panel="source" x-show.important="notificationDetailTab === 'source'" class="ndb:p-4">
    <dl class="ndb:grid ndb:gap-2 ndb:sm:grid-cols-2">
        <x-newdebugbar::inspector-source-fact label="Notification class" :code="true">
            <x-slot:value x-text="selectedNotification.notification"></x-slot:value>
        </x-newdebugbar::inspector-source-fact>
        <x-newdebugbar::inspector-source-fact label="Defined at">
            <x-slot:value
                x-text="
                    selectedNotification.notification_source
                        ? selectedNotification.notification_source.file +
                          ':' +
                          selectedNotification.notification_source.line
                        : 'Source unavailable'
                "
            ></x-slot:value>
        </x-newdebugbar::inspector-source-fact>
        <x-newdebugbar::inspector-source-fact label="Triggered at">
            <x-slot:value x-text="selectedNotification.callsite_label"></x-slot:value>
        </x-newdebugbar::inspector-source-fact>
        <x-newdebugbar::inspector-source-fact label="Notification ID" x-show="selectedNotification.notification_id">
            <x-slot:value x-text="selectedNotification.notification_id"></x-slot:value>
        </x-newdebugbar::inspector-source-fact>
    </dl>

    <x-newdebugbar::inspector-stack frames="selectedNotification.stack" />
</div>
