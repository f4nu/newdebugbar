{{-- Renders reported exceptions as a shared list-detail inspector. --}}
@php($exceptions = array_values($section['payload']['items'] ?? []))

@if ($exceptions !== [])
    <div
        data-ndb-exceptions
        x-data="{
            selectedException: 0,
            exceptionDetailOpen: false,
            selectException(index) {
                this.selectedException = index;
                this.exceptionDetailOpen = true;
                this.$nextTick(() => this.$refs.exceptionDetail?.focus());
            },
            closeException() {
                const selected = this.selectedException;
                this.exceptionDetailOpen = false;
                this.$nextTick(() => this.$root.querySelector(`[data-ndb-exception-item='${selected}']`)?.focus());
            },
        }"
        class="ndb:flex ndb:min-h-0 ndb:flex-1 ndb:flex-col"
    >
        <x-newdebugbar::inspector-workspace frame="top" data-ndb-exception-workspace>
            <x-newdebugbar::inspector-list-panel detail-open="exceptionDetailOpen" list-ref="exceptionList">
                <x-slot:controls>
                    <x-newdebugbar::inspector-list-controls :show-search="false">
                        <x-slot:leading>
                            <p class="ndb:text-xs ndb:font-semibold ndb:text-zinc-600 ndb:dark:text-zinc-300">
                                {{ number_format(count($exceptions)) }} {{ \Illuminate\Support\Str::plural('exception', count($exceptions)) }}
                            </p>
                        </x-slot:leading>
                    </x-newdebugbar::inspector-list-controls>
                </x-slot:controls>

                <x-slot:list data-ndb-exception-list>
                    @foreach ($exceptions as $index => $exception)
                        <x-newdebugbar::exception-list-item :exception="$exception" :index="$index" />
                    @endforeach
                </x-slot:list>
            </x-newdebugbar::inspector-list-panel>

            <x-newdebugbar::inspector-detail-pane
                detail-open="exceptionDetailOpen"
                detail-ref="exceptionDetail"
                detail-label="Selected exception details"
                back-label="Exceptions"
                close-action="closeException()"
            >
                @foreach ($exceptions as $index => $exception)
                    <template x-if="selectedException === {{ $index }}">
                        <x-newdebugbar::exception-detail :exception="$exception" :index="$index" />
                    </template>
                @endforeach
            </x-newdebugbar::inspector-detail-pane>
        </x-newdebugbar::inspector-workspace>
    </div>
@else
    <x-newdebugbar::empty-state label="No exceptions were reported." success />
@endif
