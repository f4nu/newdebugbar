<section
    x-ref="notificationDetail"
    data-ndb-notification-detail
    aria-live="polite"
    aria-label="Selected notification details"
    tabindex="0"
    :class="notificationDetailOpen ? 'ndb:flex' : 'ndb:hidden ndb:lg:flex'"
    class="ndb-scrollbar ndb:min-h-[32rem] ndb:min-w-0 ndb:flex-col ndb:scroll-mt-20 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:lg:min-h-0 ndb:lg:overflow-y-auto"
>
    <button
        type="button"
        data-ndb-notification-detail-back
        @click="notificationDetailOpen = false"
        class="ndb:m-2 ndb:inline-flex ndb:h-auto ndb:w-fit ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:p-2 ndb:text-xs ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:lg:hidden ndb:dark:text-indigo-300"
    >
        <x-newdebugbar::icon name="chevron-down" size="3.5" class="ndb:rotate-90" />
        Notifications
    </button>

    <template x-if="selectedNotification">
        <div class="ndb:flex ndb:flex-col">
            <x-newdebugbar::notification-header />

            <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:justify-between ndb:gap-2 ndb:border-b ndb:border-zinc-200/90 ndb:px-4 ndb:py-2.5 ndb:dark:border-zinc-800">
                <x-newdebugbar::filter-tabs label="Notification detail" class="ndb:min-w-0">
                    @foreach (['delivery' => ['Delivery', 'activity'], 'payload' => ['Payload', 'database'], 'source' => ['Source', 'code']] as $tab => [$label, $icon])
                        <x-newdebugbar::filter-tab
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
                </x-newdebugbar::filter-tabs>

                <label
                    data-ndb-notification-channel-control
                    x-show.important="notificationDetailTab === 'payload' && selectedNotification.delivery_count > 1"
                    class="ndb:relative ndb:ml-auto ndb:shrink-0"
                >
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
            </div>

            <x-newdebugbar::notification-delivery-panel />
            <x-newdebugbar::notification-payload-panel />
            <x-newdebugbar::notification-source-panel />
        </div>
    </template>
</section>
