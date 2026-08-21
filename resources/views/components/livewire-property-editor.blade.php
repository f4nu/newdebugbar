<div
  x-data="{}"
  x-id="['livewire-edit-trigger', 'livewire-edit-popover']"
  class="ndb:relative ndb:w-full ndb:sm:w-auto ndb:sm:justify-self-end"
  @keydown.escape.stop="
    if (livewireDrafts[livewireDraftKey(row)]) {
      cancelLivewireDraft(row, true);
    }
  "
>
  <button
    x-ref="livewireEditButton"
    x-show.important="row.editable"
    type="button"
    :id="$id('livewire-edit-trigger')"
    :data-ndb-livewire-edit-key="livewireDraftKey(row)"
    :aria-controls="$id('livewire-edit-popover')"
    :aria-expanded="Boolean(
      livewireDrafts[livewireDraftKey(row)] &&
        livewireDrafts[livewireDraftKey(row)]?.status !== 'closing',
    )"
    @click.stop="toggleLivewirePropertyEditor(row)"
    :class="
      livewireDrafts[livewireDraftKey(row)] &&
      livewireDrafts[livewireDraftKey(row)]?.status !== 'closing'
        ? 'ndb:bg-indigo-50 ndb:dark:bg-indigo-950/60'
        : 'ndb:hover:bg-zinc-100 ndb:dark:hover:bg-zinc-800'
    "
    class="ndb:inline-flex ndb:h-7 ndb:items-center ndb:rounded-md ndb:px-2 ndb:text-[11px] ndb:font-bold ndb:text-indigo-600 ndb:transition ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-indigo-300"
  >
    Edit
  </button>

  <template
    x-if="
      livewireDrafts[livewireDraftKey(row)] &&
        livewireDrafts[livewireDraftKey(row)]?.status !== 'closing'
    "
  >
    <template x-teleport="#newdebugbar">
      <x-newdebugbar::popover-surface
        :anchored="true"
        x-anchor.bottom-end.offset.12.fixed="
          document.getElementById($id('livewire-edit-trigger'))
        "
        x-init="
          $nextTick(() => {
            $el.querySelector('[data-ndb-livewire-edit-control]')?.focus();
          });
        "
        @keydown.escape.stop.prevent="
          cancelLivewireDraft(row, true);
        "
        @click.outside="
          if (livewireDrafts[livewireDraftKey(row)]?.status !== 'updating') {
            cancelLivewireDraft(row);
          }
        "
        data-ndb-livewire-property-popover
        ::id="$id('livewire-edit-popover')"
        ::aria-labelledby="$id('livewire-edit-trigger')"
        ::style="{
          visibility:
            $anchor.x !== 0 || $anchor.y !== 0 ? 'visible' : 'hidden',
        }"
        role="dialog"
        direction="below"
        align="left"
        width-class="ndb:w-[min(21rem,calc(100vw-3rem))]"
        surface-class="ndb:p-0"
        arrow-class="ndb:hidden"
        class="ndb:pointer-events-auto"
      >
        <div
          class="ndb:border-b ndb:border-zinc-200/80 ndb:px-4 ndb:py-3 ndb:dark:border-zinc-700/80"
        >
          <p class="ndb:text-xs ndb:font-bold">
            Edit <code x-text="row.path"></code>
          </p>
          <p class="ndb:mt-1 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
            Server value:
            <code class="ndb:font-semibold" x-text="row.serverSummary"></code>
          </p>
        </div>

        <div class="ndb:space-y-3 ndb:px-4 ndb:py-3">
          <p class="ndb:text-[11px] ndb:font-semibold ndb:text-amber-700 ndb:dark:text-amber-300">Applying sends a real Livewire update and may run application code.</p>

          <template x-if="row.value === null">
            <select
              data-ndb-livewire-edit-control
              x-model="livewireDrafts[livewireDraftKey(row)].type"
              :aria-label="`Value type for ${row.path}`"
              class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white ndb:px-3 ndb:text-xs ndb:outline-none ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900"
            >
              <option>String</option>
              <option>Integer</option>
              <option>Float</option>
              <option>Boolean</option>
            </select>
          </template>

          <template
            x-if="livewireDrafts[livewireDraftKey(row)]?.type === 'Boolean'"
          >
            <button
              type="button"
              role="switch"
              data-ndb-livewire-edit-control
              :aria-checked="livewireDrafts[livewireDraftKey(row)].value"
              @click="toggleLivewireBoolean(row)"
              class="ndb:inline-flex ndb:h-9 ndb:w-full ndb:items-center ndb:gap-2 ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white ndb:px-3 ndb:text-xs ndb:font-bold ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900"
            >
              <span
                class="ndb:size-2 ndb:rounded-full"
                :class="livewireDrafts[livewireDraftKey(row)].value
                  ? 'ndb:bg-emerald-500'
                  : 'ndb:bg-zinc-400'"
              ></span>
              <span
                x-text="
                  livewireDrafts[livewireDraftKey(row)].value ? 'True' : 'False'
                "
              ></span>
            </button>
          </template>

          <template
            x-if="livewireDrafts[livewireDraftKey(row)]?.type !== 'Boolean'"
          >
            <input
              data-ndb-livewire-edit-control
              x-model="livewireDrafts[livewireDraftKey(row)].value"
              :type="['Integer', 'Float'].includes(
                livewireDrafts[livewireDraftKey(row)]?.type,
              )
                ? 'number'
                : 'text'"
              :step="livewireDrafts[livewireDraftKey(row)]?.type === 'Float'
                ? 'any'
                : '1'"
              :aria-label="`New value for ${row.path}`"
              class="ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white ndb:px-3 ndb:text-xs ndb:outline-none ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900"
            />
          </template>

          <p
            x-show.important="livewireDrafts[livewireDraftKey(row)]?.error"
            role="alert"
            class="ndb:text-[11px] ndb:font-semibold ndb:text-red-700 ndb:dark:text-red-300"
            x-text="livewireDrafts[livewireDraftKey(row)]?.error"
          ></p>
        </div>

        <div
          class="ndb:flex ndb:justify-end ndb:gap-2 ndb:border-t ndb:border-zinc-200/80 ndb:bg-zinc-50/70 ndb:px-4 ndb:py-3 ndb:dark:border-zinc-700/80 ndb:dark:bg-zinc-950/30"
        >
          <button
            data-ndb-livewire-edit-cancel
            type="button"
            @click="
              cancelLivewireDraft(row, true);
            "
            class="ndb:h-9 ndb:rounded-lg ndb:px-3 ndb:text-xs ndb:font-bold ndb:text-zinc-500 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:text-zinc-400"
          >
            Cancel
          </button>
          <button
            data-ndb-livewire-edit-apply
            type="button"
            @click="
              applyLivewireDraft(
                row,
                document.getElementById($id('livewire-edit-trigger')),
              )
            "
            :disabled="livewireDrafts[livewireDraftKey(row)]?.status ===
            'updating'"
            class="ndb:h-9 ndb:rounded-lg ndb:bg-indigo-600 ndb:px-3 ndb:text-xs ndb:font-bold ndb:text-white ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500 ndb:disabled:opacity-50 ndb:dark:bg-indigo-500"
          >
            <span
              x-text="
                livewireDrafts[livewireDraftKey(row)]?.status === 'updating'
                  ? 'Applying…'
                  : 'Apply'
              "
            ></span>
          </button>
        </div>
      </x-newdebugbar::popover-surface>
    </template>
  </template>
</div>
