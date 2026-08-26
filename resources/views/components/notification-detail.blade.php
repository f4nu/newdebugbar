<x-newdebugbar::inspector-detail-pane
    detail-open="notificationDetailOpen"
    detail-ref="notificationDetail"
    detail-label="Selected notification details"
    back-label="Notifications"
    close-action="notificationDetailOpen = false"
    data-ndb-notification-detail
>
    <x-slot:back>
        <x-newdebugbar::inspector-detail-back
            data-ndb-notification-detail-back
            @click="notificationDetailOpen = false"
            label="Notifications"
        />
    </x-slot:back>

    <template x-if="selectedNotification">
        <div class="ndb:flex ndb:flex-col">
            <x-newdebugbar::notification-header />

            <x-newdebugbar::inspector-detail-tabs label="Notification detail">
                @foreach (['delivery' => ['Delivery', 'activity'], 'payload' => ['Payload', 'database'], 'source' => ['Source', 'code']] as $tab => [$label, $icon])
                    <x-newdebugbar::filter-tab
                        variant="segmented"
                        data-ndb-notification-detail-tab="{{ $tab }}"
                        @click="setNotificationDetailTab({{ \Illuminate\Support\Js::from($tab) }})"
                        ::aria-pressed="notificationDetailTab === {{ \Illuminate\Support\Js::from($tab) }}"
                        aria-label="{{ $label }}"
                        class="ndb:h-auto"
                    >
                        <x-newdebugbar::icon
                            name="{{ $icon }}"
                            size="3.5"
                            data-ndb-notification-detail-tab-icon="{{ $tab }}"
                            class="ndb:sm:hidden"
                        />
                        <span class="ndb:hidden ndb:sm:inline">{{ $label }}</span>
                    </x-newdebugbar::filter-tab>
                @endforeach

                <x-slot:aside
                    data-ndb-notification-channel-control
                    x-show.important="notificationDetailTab === 'payload' && selectedNotification.delivery_count > 1"
                    class="ndb:shrink-0"
                >
                    <x-newdebugbar::select-field
                        label="Choose notification channel payload"
                        data-ndb-notification-channel
                        x-model="notificationChannel"
                        @change="setNotificationChannel($event.target.value)"
                        class="ndb:max-w-44 ndb:truncate"
                    >
                        <template x-for="delivery in selectedNotification.deliveries" :key="delivery.channel">
                            <option :value="delivery.channel" x-text="delivery.channel_label"></option>
                        </template>
                    </x-newdebugbar::select-field>
                </x-slot:aside>
            </x-newdebugbar::inspector-detail-tabs>

            <x-newdebugbar::notification-delivery-panel />
            <x-newdebugbar::notification-payload-panel />
            <x-newdebugbar::notification-source-panel />
        </div>
    </template>
</x-newdebugbar::inspector-detail-pane>
