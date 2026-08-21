<div
  data-ndb-livewire-activity
  class="ndb:min-h-[31rem] ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/45 ndb:sm:grid ndb:sm:grid-cols-[minmax(17rem,0.85fr)_minmax(0,1.35fr)] ndb:sm:items-start ndb:sm:overflow-visible ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/30"
>
  <div
    :class="livewireDetailOpen ? 'ndb:hidden ndb:sm:block' : 'ndb:block'"
    class="ndb:min-w-0 ndb:border-zinc-200/90 ndb:sm:border-r ndb:dark:border-zinc-800"
  >
    <div
      class="ndb:flex ndb:items-center ndb:justify-between ndb:gap-3 ndb:border-b ndb:border-zinc-200/90 ndb:bg-zinc-50/65 ndb:px-4 ndb:py-3 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/45"
    >
      <div>
        <h3 class="ndb:text-xs ndb:font-bold">Page activity</h3>
        <p class="ndb:mt-0.5 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
          <span x-text="filteredLivewireActivity.length"></span>
          <span
            x-text="
              filteredLivewireActivity.length === 1
                ? 'interaction'
                : 'interactions'
            "
          ></span>
        </p>
      </div>
      <div class="ndb:flex ndb:items-center ndb:gap-2">
        <span
          x-show.important="
            livewireActivity.some((item) => item.status === 'updating')
          "
          class="ndb:rounded-md ndb:bg-indigo-50 ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-bold ndb:text-indigo-700 ndb:dark:bg-indigo-950/60 ndb:dark:text-indigo-300"
          >Live</span
        >
        <label class="ndb:relative">
          <span class="ndb:sr-only">Order Livewire activity</span>
          <select
            data-ndb-livewire-order
            x-model="livewireActivityOrder"
            @change="setLivewireActivityOrder($event.target.value)"
            class="ndb:h-8 ndb:appearance-none ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/80 ndb:pr-7 ndb:pl-2.5 ndb:text-[11px] ndb:font-bold ndb:outline-none ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/80"
          >
            <option value="newest">Newest first</option>
            <option value="oldest">Oldest first</option>
          </select>
          <x-newdebugbar::icon
            name="chevron-down"
            class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:right-2 ndb:size-3 ndb:-translate-y-1/2 ndb:text-zinc-400"
          />
        </label>
      </div>
    </div>

    <ol data-ndb-livewire-activity-list class="ndb:m-0 ndb:list-none ndb:p-2">
      <template
        x-for="(item, index) in filteredLivewireActivity"
        :key="item.id"
      >
        <li
          class="ndb:relative ndb:grid ndb:grid-cols-[18px_minmax(0,1fr)] ndb:gap-x-3"
        >
          <div
            aria-hidden="true"
            class="ndb:relative ndb:flex ndb:translate-x-2 ndb:justify-center ndb:pt-[18px]"
          >
            <span
              x-show.important="index < filteredLivewireActivity.length - 1"
              class="ndb:absolute ndb:top-[27px] ndb:-bottom-[18px] ndb:left-1/2 ndb:w-px ndb:-translate-x-1/2 ndb:bg-zinc-200 ndb:dark:bg-zinc-800"
            ></span>
            <span
              class="ndb:relative ndb:z-[1] ndb:size-2.5 ndb:rounded-full ndb:ring-4 ndb:ring-white ndb:dark:ring-zinc-950"
              :class="item.status === 'failed' ||
              item.status === 'failed_validation'
                ? 'ndb:bg-red-500'
                : item.status === 'updating'
                  ? 'ndb:bg-indigo-500 ndb:animate-pulse'
                  : 'ndb:bg-emerald-500'"
            ></span>
          </div>
          <button
            type="button"
            @click="selectLivewireActivity(item.id)"
            :aria-current="livewireSelectedActivityId === item.id
              ? 'true'
              : null"
            class="ndb:min-w-0 ndb:rounded-lg ndb:border ndb:border-transparent ndb:px-3 ndb:py-2.5 ndb:text-left ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-1 ndb:focus-visible:outline-indigo-500"
            :class="livewireSelectedActivityId === item.id
              ? 'ndb:border-indigo-200 ndb:bg-linear-to-r ndb:from-transparent ndb:to-indigo-50/80 ndb:dark:border-indigo-900 ndb:dark:to-indigo-950/45'
              : 'ndb:hover:bg-zinc-50 ndb:dark:hover:bg-zinc-900/65'"
          >
            <span class="ndb:flex ndb:min-w-0 ndb:items-start ndb:gap-3">
              <span class="ndb:min-w-0 ndb:flex-1">
                <span
                  class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold"
                  x-text="item.title"
                ></span>
                <span
                  class="ndb:mt-0.5 ndb:block ndb:truncate ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400"
                  x-text="item.componentTitle"
                ></span>
              </span>
              <span
                class="ndb:flex ndb:shrink-0 ndb:flex-col ndb:items-end ndb:gap-0.5 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
              >
                <span
                  data-ndb-livewire-activity-age
                  class="ndb:whitespace-nowrap"
                  x-text="livewireActivityAge(item)"
                ></span>
                <span
                  data-ndb-livewire-activity-duration
                  class="ndb:whitespace-nowrap ndb:text-zinc-500 ndb:dark:text-zinc-300"
                  x-text="livewireDuration(item)"
                ></span>
              </span>
            </span>
            <span
              class="ndb:mt-2 ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-1.5"
            >
              <span
                x-show.important="
                  item.status === 'failed' ||
                  item.status === 'failed_validation'
                "
                class="ndb:rounded-md ndb:bg-red-50 ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-red-700 ndb:dark:bg-red-950/60 ndb:dark:text-red-300"
                x-text="item.status.replaceAll('_', ' ')"
              ></span>
              <span
                class="ndb:rounded-md ndb:bg-zinc-100 ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide ndb:text-zinc-500 ndb:dark:bg-zinc-800 ndb:dark:text-zinc-400"
                x-text="item.kind.replaceAll('_', ' ')"
              ></span>
              <span
                x-show.important="livewireActivityFactCount(item) > 1"
                class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400"
                x-text="`${livewireActivityFactCount(item)} grouped facts`"
              ></span>
            </span>
          </button>
        </li>
      </template>
    </ol>

    <div
      x-show.important="filteredLivewireActivity.length === 0"
      class="ndb:p-4"
    >
      <x-newdebugbar::empty-state
        label="No Livewire activity matches this view."
      />
    </div>
  </div>

  <div
    :class="livewireDetailOpen ? 'ndb:block' : 'ndb:hidden ndb:sm:block'"
    class="ndb-scrollbar ndb:min-w-0 ndb:sm:sticky ndb:sm:top-0 ndb:sm:z-10 ndb:sm:max-h-[min(62vh,36rem)] ndb:sm:overflow-x-hidden ndb:sm:overflow-y-auto ndb:sm:bg-white/95 ndb:sm:dark:bg-zinc-950/95"
  >
    <button
      type="button"
      @click="livewireDetailOpen = false"
      class="ndb:m-3 ndb:inline-flex ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:px-2 ndb:py-1.5 ndb:text-xs ndb:font-bold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:sm:hidden ndb:dark:text-indigo-300"
    >
      <x-newdebugbar::icon
        name="chevron-down"
        class="ndb:size-3.5 ndb:rotate-90"
      />
      Activity
    </button>

    <template x-if="selectedLivewireActivity">
      <article class="ndb:min-w-0">
        <header
          class="ndb:border-b ndb:border-zinc-200/90 ndb:px-4 ndb:py-4 ndb:sm:px-5 ndb:dark:border-zinc-800"
        >
          <div class="ndb:flex ndb:min-w-0 ndb:items-start ndb:gap-4">
            <span
              class="ndb:mt-0.5 ndb:grid ndb:size-8 ndb:shrink-0 ndb:place-items-center ndb:rounded-lg"
              :class="selectedLivewireActivity.status === 'failed' ||
              selectedLivewireActivity.status === 'failed_validation'
                ? 'ndb:bg-red-50 ndb:text-red-600 ndb:dark:bg-red-950/60 ndb:dark:text-red-300'
                : 'ndb:bg-indigo-50 ndb:text-indigo-600 ndb:dark:bg-indigo-950/60 ndb:dark:text-indigo-300'"
            >
              <x-newdebugbar::icon name="activity" class="ndb:size-4" />
            </span>
            <div class="ndb:min-w-0 ndb:flex-1">
              <div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-2">
                <h3
                  class="ndb:min-w-0 ndb:text-sm ndb:font-bold"
                  x-text="selectedLivewireActivity.title"
                ></h3>
                <span
                  class="ndb:rounded-md ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wide"
                  :class="selectedLivewireActivity.status === 'failed' ||
                  selectedLivewireActivity.status === 'failed_validation'
                    ? 'ndb:bg-red-50 ndb:text-red-700 ndb:dark:bg-red-950/60 ndb:dark:text-red-300'
                    : selectedLivewireActivity.status === 'updating'
                      ? 'ndb:bg-indigo-50 ndb:text-indigo-700 ndb:dark:bg-indigo-950/60 ndb:dark:text-indigo-300'
                      : 'ndb:bg-emerald-50 ndb:text-emerald-700 ndb:dark:bg-emerald-950/60 ndb:dark:text-emerald-300'"
                  x-text="selectedLivewireActivity.status.replaceAll('_', ' ')"
                ></span>
              </div>
              <button
                type="button"
                @click="inspectLivewireActivityComponent()"
                class="ndb:mt-1 ndb:max-w-full ndb:truncate ndb:text-left ndb:text-xs ndb:font-semibold ndb:text-indigo-600 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
                x-text="selectedLivewireActivity.componentTitle"
              ></button>
            </div>
          </div>

          <dl class="ndb:mt-4 ndb:grid ndb:grid-cols-3 ndb:gap-2">
            <div
              class="ndb:rounded-lg ndb:bg-zinc-50 ndb:px-3 ndb:py-2 ndb:dark:bg-zinc-900/65"
            >
              <dt
                class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
              >
                Duration
              </dt>
              <dd
                class="ndb:mt-1 ndb:text-xs ndb:font-bold ndb:tabular-nums"
                x-text="livewireDuration(selectedLivewireActivity)"
              ></dd>
            </div>
            <div
              class="ndb:rounded-lg ndb:bg-zinc-50 ndb:px-3 ndb:py-2 ndb:dark:bg-zinc-900/65"
            >
              <dt
                class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
              >
                Work
              </dt>
              <dd
                class="ndb:mt-1 ndb:text-xs ndb:font-bold"
                x-text="livewireActivityFactCount(selectedLivewireActivity)"
              ></dd>
            </div>
            <div
              class="ndb:rounded-lg ndb:bg-zinc-50 ndb:px-3 ndb:py-2 ndb:dark:bg-zinc-900/65"
            >
              <dt
                class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
              >
                Requests
              </dt>
              <dd
                class="ndb:mt-1 ndb:text-xs ndb:font-bold"
                x-text="selectedLivewireActivity.profileIds.length"
              ></dd>
            </div>
          </dl>
        </header>

        <div class="ndb:space-y-5 ndb:p-4 ndb:sm:p-5">
          <div
            x-show.important="selectedLivewireActivity.error"
            role="alert"
            class="ndb:rounded-lg ndb:border ndb:border-red-200 ndb:bg-red-50/70 ndb:px-3 ndb:py-2.5 ndb:text-xs ndb:font-semibold ndb:text-red-800 ndb:dark:border-red-950 ndb:dark:bg-red-950/30 ndb:dark:text-red-200"
            x-text="selectedLivewireActivity.error"
          ></div>

          <section
            x-show.important="selectedLivewireActivity.changes.length > 0"
          >
            <h4
              class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
            >
              Property changes
            </h4>
            <div
              class="ndb:mt-2 ndb:overflow-hidden ndb:rounded-lg ndb:border ndb:border-zinc-200/90 ndb:dark:border-zinc-800"
            >
              <template
                x-for="change in selectedLivewireActivity.changes"
                :key="change.path"
              >
                <div
                  class="ndb:grid ndb:grid-cols-[minmax(7rem,0.7fr)_minmax(0,1fr)] ndb:gap-3 ndb:border-b ndb:border-zinc-200/80 ndb:px-3 ndb:py-2.5 ndb:last:border-b-0 ndb:dark:border-zinc-800"
                >
                  <code
                    class="ndb:truncate ndb:text-[11px] ndb:font-semibold"
                    x-text="change.path"
                  ></code>
                  <div class="ndb:min-w-0 ndb:text-[11px]">
                    <p class="ndb:truncate"><span class="ndb:text-zinc-400">Before </span><code x-text="JSON.stringify(
                          change.before,
                        )"></code></p>
                    <p class="ndb:mt-1 ndb:truncate"><span class="ndb:text-zinc-400">Server </span><code x-text="change.serverKnown ? JSON.stringify(change.server) : 'Not confirmed'"></code></p>
                  </div>
                </div>
              </template>
            </div>
          </section>

          <section
            x-show.important="selectedLivewireActivity.actions.length > 0"
          >
            <h4
              class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
            >
              Actions
            </h4>
            <div class="ndb:mt-2 ndb:space-y-2">
              <template
                x-for="(action, index) in selectedLivewireActivity.actions"
                :key="`${action.name}-${index}`"
              >
                <div
                  class="ndb:rounded-lg ndb:bg-zinc-50 ndb:px-3 ndb:py-2.5 ndb:dark:bg-zinc-900/65"
                >
                  <code
                    class="ndb:text-[11px] ndb:font-bold"
                    x-text="action.name"
                  ></code>
                  <pre
                    x-show.important="action.params.length > 0"
                    class="ndb-scrollbar ndb:mt-2 ndb:overflow-x-auto ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                  ><code x-text="JSON.stringify(action.params, null, 2)"></code></pre>
                </div>
              </template>
            </div>
          </section>

          <section
            x-show.important="selectedLivewireActivity.events.length > 0"
          >
            <h4
              class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
            >
              Events
            </h4>
            <div class="ndb:mt-2 ndb:space-y-2">
              <template
                x-for="event in selectedLivewireActivity.events"
                :key="event.name"
              >
                <div
                  class="ndb:rounded-lg ndb:border ndb:border-zinc-200/90 ndb:px-3 ndb:py-3 ndb:dark:border-zinc-800"
                >
                  <div
                    class="ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-2"
                  >
                    <code
                      class="ndb:text-[11px] ndb:font-bold"
                      x-text="event.name"
                    ></code>
                    <span
                      class="ndb:rounded-md ndb:bg-zinc-100 ndb:px-1.5 ndb:py-0.5 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:text-zinc-500 ndb:dark:bg-zinc-800"
                      x-text="event.mode"
                    ></span>
                  </div>
                  <p
                    x-show.important="event.declaredTarget"
                    class="ndb:mt-2 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400"
                  >
                    Declared target <code x-text="event.declaredTarget"></code>
                  </p>
                  <div
                    x-show.important="event.observedRecipientIds.length > 0"
                    class="ndb:mt-2 ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-1.5 ndb:text-[11px]"
                  >
                    <span class="ndb:text-zinc-400">Observed recipients</span>
                    <template
                      x-for="recipient in event.observedRecipientIds"
                      :key="recipient"
                    >
                      <code
                        class="ndb:rounded-md ndb:bg-emerald-50 ndb:px-1.5 ndb:py-0.5 ndb:text-emerald-700 ndb:dark:bg-emerald-950/60 ndb:dark:text-emerald-300"
                        x-text="livewireComponentTitle(recipient)"
                      ></code>
                    </template>
                  </div>
                </div>
              </template>
            </div>
          </section>

          <section>
            <h4
              class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
            >
              Trace
            </h4>
            <ol class="ndb:mt-3 ndb:list-none ndb:space-y-0 ndb:p-0">
              <template
                x-for="(phase, index) in selectedLivewireActivity.phases"
                :key="`${phase.name}-${index}`"
              >
                <li
                  class="ndb:grid ndb:grid-cols-[14px_minmax(0,1fr)_auto] ndb:gap-x-3"
                >
                  <div
                    aria-hidden="true"
                    class="ndb:relative ndb:flex ndb:justify-center ndb:pt-1"
                  >
                    <span
                      x-show.important="
                        index < selectedLivewireActivity.phases.length - 1
                      "
                      class="ndb:absolute ndb:top-3 ndb:-bottom-1 ndb:left-1/2 ndb:w-px ndb:-translate-x-1/2 ndb:bg-zinc-200 ndb:dark:bg-zinc-800"
                    ></span>
                    <span
                      class="ndb:relative ndb:z-[1] ndb:size-2 ndb:rounded-full ndb:bg-indigo-500 ndb:ring-2 ndb:ring-white ndb:dark:bg-indigo-400 ndb:dark:ring-zinc-950"
                    ></span>
                  </div>
                  <span
                    class="ndb:pb-3 ndb:text-xs ndb:font-semibold"
                    x-text="phase.name"
                  ></span>
                  <span
                    class="ndb:pb-3 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
                    x-text="
                      `${Math.max(0, phase.at - selectedLivewireActivity.startedAt).toFixed(1)} ms`
                    "
                  ></span>
                </li>
              </template>
            </ol>
            <p x-show.important="selectedLivewireActivity.phases.length === 0" class="ndb:mt-2 ndb:text-xs ndb:text-zinc-400">Browser phases were not available for this stored request.</p>
          </section>
        </div>
      </article>
    </template>

    <div x-show.important="!selectedLivewireActivity" class="ndb:p-5">
      <x-newdebugbar::empty-state
        label="Select an interaction to inspect it."
      />
    </div>
  </div>
</div>
