{{-- Shows the selected request and opens the bounded recent-request picker. --}}
@props ([
    'scope',
    'direction' => 'dynamic',
])

@php
    $directionClass = match ($direction) {
        'dynamic' => '',
        'below' => 'ndb:top-[calc(100%+0.5rem)] ndb:origin-top',
        'above' => 'ndb:bottom-[calc(100%+0.5rem)] ndb:origin-bottom',
        default => throw new InvalidArgumentException("Unsupported request picker direction [{$direction}]."),
    };
@endphp

<div
  data-ndb-request-switcher="{{ $scope }}"
  @click.outside="if (requestPickerScope === @js($scope)) closeRequestPicker(false)"
  {{ $attributes->class('ndb:relative ndb:flex ndb:min-w-0 ndb:self-stretch') }}
>
  <div
    class="ndb:flex ndb:min-w-0 ndb:flex-1 ndb:overflow-visible ndb:rounded-xl ndb:transition-colors"
    :class="requestPickerScope === @js($scope) ? 'ndb:bg-zinc-100 ndb:dark:bg-white/10' : ''"
  >
    <button
      type="button"
      @if ($scope === 'toolbar') data-ndb-toolbar="request" @endif
      @if ($scope === 'header-mobile') data-ndb-header-mobile-request @endif
      @if ($scope === 'header') data-ndb-header-request @endif
      @click="
        closeRequestPicker(false);
        inspectorOpen
          ? selectSection('request')
          : openInspector('request', $el);
      "
      aria-label="Open current request in Requests"
      class="ndb:flex ndb:min-w-0 ndb:flex-1 ndb:items-center ndb:gap-2 ndb:rounded-l-xl ndb:py-1.5 ndb:pl-3 ndb:pr-4 ndb:text-left ndb:transition-colors ndb:hover:bg-zinc-100 ndb:focus-visible:z-10 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:hover:bg-white/10"
    >
      <span
        class="ndb:rounded-md ndb:bg-indigo-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300"
        x-text="summary.method"
      ></span>
      <span class="ndb:min-w-0">
        <span
          @if ($scope === 'toolbar') data-ndb-toolbar-request-path @endif
          @if ($scope === 'header-mobile') data-ndb-header-mobile-request-path @endif
          class="ndb:block ndb:truncate ndb:text-xs ndb:font-semibold"
          :title="summary.path"
          x-text="summary.path"
        ></span>
        <span
          class="ndb:flex ndb:items-center ndb:gap-1.5 ndb:whitespace-nowrap ndb:text-[11px] ndb:font-medium ndb:text-zinc-400"
          ><span
            @if ($scope === 'toolbar') data-ndb-toolbar-status @endif
            @if ($scope === 'header-mobile') data-ndb-header-mobile-status @endif
            @if ($scope === 'header') data-ndb-header-status @endif
            x-text="summary.status"
          ></span
          ><span
            @if ($scope === 'toolbar') data-ndb-toolbar-response-size @endif
            @if ($scope === 'header') data-ndb-header-response-size @endif
            class="ndb:hidden ndb:font-semibold ndb:text-zinc-500 ndb:lg:inline ndb:dark:text-zinc-300"
            x-show="summary.response_size"
            x-text="summary.response_size"
          ></span
        ></span>
      </span>
    </button>

    <button
      type="button"
      data-ndb-request-picker-trigger="{{ $scope }}"
      @click.stop="toggleRequestPicker(@js($scope), $el)"
      :aria-expanded="requestPickerScope === @js($scope)"
      :disabled="!hasOtherRequests"
      aria-controls="newdebugbar-request-list-{{ $scope }}"
      aria-haspopup="listbox"
      :aria-label="requestPickerButtonLabel"
      :title="requestPickerButtonLabel"
      class="ndb:relative ndb:flex ndb:w-12 ndb:shrink-0 ndb:items-center ndb:justify-center ndb:rounded-r-xl ndb:text-zinc-400 ndb:transition-colors ndb:hover:bg-zinc-100 ndb:hover:text-zinc-700 ndb:focus-visible:z-10 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:cursor-default ndb:disabled:text-zinc-300 ndb:disabled:hover:bg-transparent ndb:sm:w-11 ndb:dark:hover:bg-white/10 ndb:dark:hover:text-zinc-200 ndb:dark:disabled:text-zinc-700 ndb:dark:disabled:hover:bg-transparent"
    >
      <x-newdebugbar::icon
        name="chevron-down"
        class="ndb:size-3.5 ndb:transition-transform ndb:motion-reduce:transition-none"
        ::class="requestPickerScope === @js($scope) ? 'ndb:rotate-180' : ''"
      />
      <span
        x-cloak
        x-show.important="newRequestCount > 0"
        x-text="requestBadgeCount"
        data-ndb-request-badge="{{ $scope }}"
        aria-hidden="true"
        class="ndb:absolute ndb:-top-1.5 ndb:-right-1.5 ndb:flex ndb:h-4 ndb:min-w-4 ndb:items-center ndb:justify-center ndb:rounded-full ndb:bg-indigo-600 ndb:px-1 ndb:text-[9px] ndb:font-bold ndb:leading-none ndb:text-white ndb:shadow-[0_0_0_2px_rgba(255,255,255,0.95)] ndb:tabular-nums ndb:dark:bg-indigo-400 ndb:dark:text-zinc-950 ndb:dark:shadow-[0_0_0_2px_rgba(9,9,11,0.95)]"
      ></span>
    </button>
  </div>

  <div
    id="newdebugbar-request-list-{{ $scope }}"
    x-cloak
    x-show.important="requestPickerScope === @js($scope)"
    x-transition:enter="ndb:transition ndb:duration-150 ndb:ease-out ndb:motion-reduce:transition-none"
    x-transition:enter-start="ndb:scale-95 ndb:opacity-0"
    x-transition:enter-end="ndb:scale-100 ndb:opacity-100"
    x-transition:leave="ndb:transition ndb:duration-100 ndb:ease-in ndb:motion-reduce:transition-none"
    x-transition:leave-start="ndb:scale-100 ndb:opacity-100"
    x-transition:leave-end="ndb:scale-95 ndb:opacity-0"
    @if ($direction === 'dynamic')
      :class="toolbarPlacement === 'top'
        ? 'ndb:top-[calc(100%+0.5rem)] ndb:origin-top'
        : 'ndb:bottom-[calc(100%+0.5rem)] ndb:origin-bottom'"
    @endif
    class="ndb:absolute ndb:left-0 ndb:z-50 ndb:w-[calc(100vw-1.5rem)] ndb:max-w-sm {{ $directionClass }}"
  >
    <div
      class="ndb:overflow-hidden ndb:rounded-2xl ndb:border ndb:border-zinc-200/80 ndb:bg-white/95 ndb:shadow-[0_18px_50px_-16px_rgba(24,24,27,0.45)] ndb:backdrop-blur-xl ndb:dark:border-zinc-700/80 ndb:dark:bg-zinc-900/95 ndb:dark:shadow-[0_18px_50px_-16px_rgba(0,0,0,0.85)]"
    >
      <div
        class="ndb:flex ndb:items-center ndb:justify-between ndb:border-b ndb:border-zinc-200/80 ndb:px-3 ndb:py-2.5 ndb:dark:border-zinc-800"
      >
        <span class="ndb:text-xs ndb:font-bold">Requests</span>
        <span
          class="ndb:text-[11px] ndb:font-medium ndb:text-zinc-400 ndb:tabular-nums"
          x-text="
            recentProfiles.length +
            (recentProfiles.length === 1 ? ' request' : ' requests')
          "
        ></span>
      </div>

      <div
        role="listbox"
        aria-label="Recent requests"
        @keydown.escape.stop.prevent="closeRequestPicker()"
        @keydown.arrow-down.prevent="moveRequestPicker(1, $event.currentTarget)"
        @keydown.arrow-up.prevent="moveRequestPicker(-1, $event.currentTarget)"
        @keydown.home.prevent="
          focusRequestPickerEdge('start', $event.currentTarget)
        "
        @keydown.end.prevent="
          focusRequestPickerEdge('end', $event.currentTarget)
        "
        class="ndb-scrollbar ndb:max-h-[min(24rem,60vh)] ndb:overflow-y-auto ndb:p-1.5"
      >
        <template
          x-for="request in recentProfiles"
          :key="@js($scope) + '-' + request.id"
        >
          <button
            type="button"
            role="option"
            data-ndb-request-option
            :data-profile-id="request.id"
            :aria-selected="request.id === summary.id"
            :aria-busy="requestSelectionPending === request.id"
            @click="selectRequest(request.id)"
            class="ndb:flex ndb:w-full ndb:min-w-0 ndb:items-start ndb:gap-2.5 ndb:rounded-xl ndb:px-2.5 ndb:py-2 ndb:text-left ndb:transition-colors ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500"
            :class="request.id === summary.id
              ? 'ndb:bg-indigo-50 ndb:text-indigo-950 ndb:dark:bg-indigo-950/70 ndb:dark:text-indigo-100'
              : requestSelectionPending === request.id
                ? 'ndb:opacity-60'
                : 'ndb:hover:bg-zinc-100 ndb:dark:hover:bg-white/10'"
          >
            <span
              class="ndb:mt-0.5 ndb:shrink-0 ndb:rounded-md ndb:bg-zinc-100 ndb:px-1.5 ndb:py-0.5 ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-600 ndb:dark:bg-zinc-800 ndb:dark:text-zinc-300"
              x-text="request.method"
            ></span>
            <span class="ndb:min-w-0 ndb:flex-1">
              <span class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-2">
                <span
                  class="ndb:min-w-0 ndb:flex-1 ndb:truncate ndb:text-xs ndb:font-semibold"
                  :title="requestTitle(request)"
                  x-text="requestTitle(request)"
                ></span>
                <span
                  class="ndb:shrink-0 ndb:text-[11px] ndb:font-bold ndb:tabular-nums"
                  :class="request.status >= 500
                    ? 'ndb:text-red-600 ndb:dark:text-red-300'
                    : request.status >= 400
                      ? 'ndb:text-amber-600 ndb:dark:text-amber-300'
                      : 'ndb:text-zinc-500 ndb:dark:text-zinc-400'"
                  x-text="request.status"
                ></span>
              </span>
              <span
                class="ndb:mt-0.5 ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-x-2 ndb:gap-y-0.5 ndb:text-[10px] ndb:font-medium ndb:text-zinc-400"
              >
                <span x-text="requestTypeLabel(request.request_type)"></span>
                <span
                  class="ndb:tabular-nums"
                  x-text="request.duration_ms + ' ms'"
                ></span>
                <span
                  class="ndb:tabular-nums"
                  x-text="
                    request.query_count +
                    (request.query_count === 1 ? ' query' : ' queries')
                  "
                ></span>
                <time
                  :datetime="request.recorded_at"
                  x-text="relativeRequestTime(request)"
                ></time>
              </span>
            </span>
            <span
              x-show.important="request.id === summary.id"
              aria-hidden="true"
              class="ndb:mt-1 ndb:flex ndb:size-4 ndb:shrink-0 ndb:items-center ndb:justify-center ndb:text-indigo-600 ndb:dark:text-indigo-300"
              ><x-newdebugbar::icon name="check" class="ndb:size-3.5"
            /></span>
          </button>
        </template>

        <p
          x-show.important="recentProfiles.length === 0"
          class="ndb:px-3 ndb:py-6 ndb:text-center ndb:text-xs ndb:text-zinc-500 ndb:dark:text-zinc-400"
        >No recent requests.</p>
      </div>
    </div>
  </div>
</div>
