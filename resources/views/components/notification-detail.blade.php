<section
    x-ref="notificationDetail"
    data-ndb-notification-detail
    aria-live="polite"
    aria-label="Selected notification details"
    tabindex="0"
    :class="notificationDetailOpen ? 'ndb:flex' : 'ndb:hidden ndb:lg:flex'"
    class="ndb-scrollbar ndb:min-h-[32rem] ndb:min-w-0 ndb:flex-col ndb:scroll-mt-20 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:lg:min-h-0 ndb:lg:overflow-y-auto"
>
    <x-newdebugbar::inspector-detail-back
        data-ndb-notification-detail-back
        @click="notificationDetailOpen = false"
        label="Notifications"
    />

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
                    <label class="ndb:relative ndb:block">
                        <span class="ndb:sr-only">Choose notification channel payload</span>
                        <select
                            data-ndb-notification-channel
                            x-model="notificationChannel"
                            @change="setNotificationChannel($event.target.value)"
                            class="ndb:h-8 ndb:max-w-44 ndb:appearance-none ndb:truncate ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/75 ndb:pr-8 ndb:pl-2.5 ndb:text-[11px] ndb:font-semibold ndb:outline-none ndb:transition ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900"
                        >
                            <template x-for="delivery in selectedNotification.deliveries" :key="delivery.channel">
                                <option :value="delivery.channel" x-text="delivery.channel_label"></option>
                            </template>
                        </select>
                        <x-newdebugbar::icon
                            name="chevron-down"
                            class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-2.5 ndb:size-3 ndb:-translate-y-1/2 ndb:text-zinc-400"
                        />
                    </label>
                </x-slot:aside>
            </x-newdebugbar::inspector-detail-tabs>

            <x-newdebugbar::notification-delivery-panel />
            <x-newdebugbar::notification-payload-panel />
            <x-newdebugbar::notification-source-panel />
        </div>
    </template>
</section>
