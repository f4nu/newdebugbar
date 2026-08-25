{{-- Presents model activity as a compact model table with persistent details. --}}
@php
    $modelGroups = array_values($section['payload']['model_group_previews'] ?? $section['payload']['model_groups'] ?? []);
    $modelSummary = $section['summary'] ?? [];
    $retrievalCount = (int) ($modelSummary['retrieval_count'] ?? 0);
    $changeCount = (int) ($modelSummary['model_change_count'] ?? 0);
    $repeatCount = (int) ($modelSummary['repeated_load_count'] ?? 0);
@endphp

<div
    data-ndb-models
    x-init="initializeModels({{ count($modelGroups) }}, {{ $retrievalCount }}, {{ $changeCount }}, {{ $repeatCount }})"
    class="ndb:text-zinc-950 ndb:[&_code]:bg-transparent ndb:[&_dd]:bg-transparent ndb:[&_dl]:bg-transparent ndb:[&_dt]:bg-transparent ndb:dark:text-white ndb:lg:flex ndb:lg:min-h-0 ndb:lg:flex-1 ndb:lg:flex-col"
>
    @if ($modelGroups !== [])
        <x-newdebugbar::inspector-workspace
            frame="top"
            data-ndb-model-workspace
            class="ndb:border-l-0 ndb:p-0 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
        >
            <x-newdebugbar::inspector-list-panel detail-open="modelDetailOpen" list-ref="modelList">
                <x-slot:controls>
                    <x-newdebugbar::search-field
                        label="Search models"
                        placeholder="Search models"
                        data-ndb-model-search
                        x-model="modelSearch"
                        @input.debounce.100ms="applyModelView()"
                    />
                </x-slot:controls>

                <x-slot:list
                    data-ndb-model-list
                    class="ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
                >
                    <div
                        data-ndb-model-list-heading
                        aria-hidden="true"
                        class="ndb:sticky ndb:top-0 ndb:z-10 ndb:hidden ndb:grid-cols-[minmax(7rem,1fr)_3.5rem_2.75rem_3rem] ndb:gap-2 ndb:border-l-0 ndb:border-b ndb:border-zinc-200/90 ndb:bg-white/95 ndb:px-3 ndb:py-2 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400 ndb:backdrop-blur-sm ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/95 ndb:sm:grid"
                    >
                        <span>Model</span>
                        <span class="ndb:text-right">Retrieved</span>
                        <span class="ndb:text-right">Writes</span>
                        <span class="ndb:text-right" title="Extra retrievals of identified records">Extra</span>
                    </div>

                    @foreach ($modelGroups as $index => $group)
                        <x-newdebugbar::model-group :group="$group" :index="$index" />
                    @endforeach

                    <div x-show.important="visibleModelCount === 0" class="ndb:p-3">
                        <x-newdebugbar::empty-state label="No models match this search." />
                    </div>

                    <section
                        data-ndb-model-summary
                        aria-label="Model activity totals"
                        x-show.important="visibleModelCount > 0"
                        class="ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
                    >
                        <dl class="ndb:grid ndb:grid-cols-4 ndb:gap-2 ndb:px-3 ndb:py-2.5 ndb:sm:grid-cols-[minmax(7rem,1fr)_3.5rem_2.75rem_3rem]">
                            <div class="ndb:min-w-0">
                                <dt class="ndb:text-xs ndb:font-bold ndb:text-zinc-950 ndb:dark:text-white">Counts</dt>
                                <dd class="ndb:sr-only">Visible model activity totals</dd>
                            </div>

                            @foreach ([
                                ['Retrieved', 'visibleModelRetrievalCount'],
                                ['Writes', 'visibleModelWriteCount'],
                                ['Extra', 'visibleModelExtraCount'],
                            ] as [$label, $value])
                                <div class="ndb:min-w-0 ndb:text-right">
                                    <dt class="ndb:sr-only">{{ $label }}</dt>
                                    <dd
                                        @class([
                                            'ndb:text-xs ndb:font-bold ndb:tabular-nums',
                                            'ndb:text-amber-700 ndb:dark:text-amber-300' => $label === 'Extra',
                                        ])
                                        x-text="{{ $value }}"
                                    ></dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                </x-slot:list>
            </x-newdebugbar::inspector-list-panel>

            <x-newdebugbar::inspector-detail-pane
                detail-open="modelDetailOpen"
                detail-ref="modelDetail"
                detail-label="Selected model details"
                back-label="Models"
                close-action="closeModelDetail()"
                id="newdebugbar-model-detail"
                data-ndb-model-detail-pane
                class="ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
            >
                <x-slot:back>
                    <x-newdebugbar::inspector-detail-back
                        data-ndb-model-detail-back
                        @click="closeModelDetail()"
                        label="Models"
                        class="ndb:bg-transparent"
                    />
                </x-slot:back>

                @foreach ($modelGroups as $index => $group)
                    <template x-if="modelSelected === {{ $index }}">
                        <div wire:key="model-detail-{{ $index }}" class="ndb:flex ndb:flex-col">
                            <x-newdebugbar::model-group-detail :$group />
                        </div>
                    </template>
                @endforeach

                <x-newdebugbar::inspector-detail-empty
                    data-ndb-model-detail-empty
                    label="Select a model to inspect its activity."
                    x-show.important="modelSelected === null"
                    class="ndb:flex-1"
                />
            </x-newdebugbar::inspector-detail-pane>
        </x-newdebugbar::inspector-workspace>
    @else
        <x-newdebugbar::empty-state label="No Eloquent model activity was captured for this request." />
    @endif
</div>
