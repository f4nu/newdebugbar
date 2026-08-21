{{-- Renders the page-session Livewire activity and component inspector. --}}
@php ($livewirePayload = $section['payload'] ?? ['components' => [], 'activity' => []])

<div
  data-ndb-livewire
  x-init="mergeLivewireServer(@js([
        'components' => $livewirePayload['components'] ?? [],
        'activity' => $livewirePayload['activity'] ?? [],
    ]))"
  class="ndb:space-y-4"
>
  <div
    class="ndb:flex ndb:flex-col ndb:gap-3 ndb:border-b ndb:border-zinc-200 ndb:pb-4 ndb:sm:flex-row ndb:sm:items-center ndb:sm:justify-between ndb:dark:border-zinc-800"
  >
    <x-newdebugbar::filter-tabs label="Livewire view" class="ndb:shrink-0">
      <x-newdebugbar::filter-tab
        data-ndb-livewire-tab="activity"
        @click="setLivewireTab('activity')"
        ::aria-pressed="livewireTab === 'activity'"
      >
        <span>Activity</span>
        <span
          class="ndb:text-[11px] ndb:font-bold ndb:tabular-nums ndb:opacity-65"
          x-text="livewireActivity.length"
        ></span>
      </x-newdebugbar::filter-tab>
      <x-newdebugbar::filter-tab
        data-ndb-livewire-tab="components"
        @click="setLivewireTab('components')"
        ::aria-pressed="livewireTab === 'components'"
      >
        <span>Components</span>
        <span
          class="ndb:text-[11px] ndb:font-bold ndb:tabular-nums ndb:opacity-65"
          x-text="livewireComponents.length"
        ></span>
      </x-newdebugbar::filter-tab>
    </x-newdebugbar::filter-tabs>

    <div
      class="ndb:grid ndb:min-w-0 ndb:grid-cols-[minmax(0,1fr)_auto] ndb:gap-2 ndb:sm:w-[28rem]"
    >
      <label class="ndb:relative ndb:min-w-0">
        <span
          class="ndb:sr-only"
          x-text="
            livewireTab === 'activity'
              ? 'Search Livewire activity'
              : 'Search Livewire components'
          "
        ></span>
        <input
          data-ndb-livewire-search
          x-model="livewireSearch"
          type="search"
          :placeholder="livewireTab === 'activity'
            ? 'Search activity or component'
            : 'Search components'"
          class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-9 ndb:pl-3 ndb:text-xs ndb:outline-none ndb:transition ndb:placeholder:text-zinc-400 ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
        />
        <x-newdebugbar::icon
          name="search"
          class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-3 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
        />
      </label>
      <label x-show.important="livewireTab === 'activity'" class="ndb:relative">
        <span class="ndb:sr-only">Filter Livewire activity</span>
        <select
          data-ndb-livewire-type
          x-model="livewireActivityType"
          @change="setLivewireActivityType($event.target.value)"
          class="ndb:h-9 ndb:appearance-none ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:pr-8 ndb:pl-3 ndb:text-xs ndb:font-semibold ndb:outline-none ndb:transition ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70"
        >
          <option value="all">All activity</option>
          <template x-for="type in livewireActivityTypes" :key="type">
            <option
              :value="type"
              x-text="
                type
                  .replaceAll('_', ' ')
                  .replace(/\b\w/g, (letter) => letter.toUpperCase())
              "
            ></option>
          </template>
        </select>
        <x-newdebugbar::icon
          name="chevron-down"
          class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-2.5 ndb:size-3.5 ndb:-translate-y-1/2 ndb:text-zinc-400"
        />
      </label>
      <span
        x-show.important="livewireTab === 'components'"
        aria-hidden="true"
        class="ndb:w-0"
      ></span>
    </div>
  </div>

  <div
    x-show.important="
      (livewireTrace.dropped?.components ?? 0) +
        (livewireTrace.dropped?.activity ?? 0) >
      0
    "
    role="status"
    class="ndb:rounded-lg ndb:border ndb:border-amber-200 ndb:bg-amber-50/60 ndb:px-3 ndb:py-2 ndb:text-xs ndb:font-semibold ndb:text-amber-800 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/25 ndb:dark:text-amber-300"
  >
    Capture limit reached.
    <span x-text="livewireTrace.dropped.activity"></span> activity records and
    <span x-text="livewireTrace.dropped.components"></span> component records
    were omitted.
  </div>

  <div x-show.important="livewireTab === 'activity'">
    @include ('newdebugbar::livewire.livewire.activity')
  </div>

  <div x-show.important="livewireTab === 'components'">
    @include ('newdebugbar::livewire.livewire.components')
  </div>
</div>
