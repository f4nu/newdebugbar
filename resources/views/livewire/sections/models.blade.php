{{-- Presents model activity as a compact model table with persistent details. --}}
@php
    $modelGroups = array_values($section['payload']['model_group_previews'] ?? $section['payload']['model_groups'] ?? []);
    $modelSummary = $section['summary'] ?? [];
    $modelClassCount = (int) ($modelSummary['model_classes'] ?? 0);
    $modelContextCount = (int) ($modelSummary['model_contexts'] ?? count($modelGroups));
    $activityCount = (int) ($modelSummary['activity_count'] ?? 0);
    $retrievalCount = (int) ($modelSummary['retrieval_count'] ?? 0);
    $changeCount = (int) ($modelSummary['model_change_count'] ?? 0);
    $repeatCount = (int) ($modelSummary['repeated_load_count'] ?? 0);
    $plural = static fn (string $word, int $count): string => \Illuminate\Support\Str::plural($word, $count);
    $modelScope = number_format($modelClassCount).' model '.$plural('class', $modelClassCount);

    if ($modelContextCount > $modelClassCount) {
        $modelScope .= ' in '.number_format($modelContextCount).' contexts';
    }
@endphp

<div
    data-ndb-models
    x-init="initializeModels({{ count($modelGroups) }})"
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
                    <section
                        data-ndb-model-summary
                        aria-label="Model activity summary"
                        class="ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
                    >
                        <div class="ndb:flex ndb:items-start ndb:justify-between ndb:gap-3">
                            <div class="ndb:min-w-0">
                                <p class="ndb:text-xs ndb:font-bold ndb:tabular-nums">
                                    {{ number_format($activityCount) }} Eloquent {{ $plural('activity', $activityCount) }}
                                </p>
                                <p class="ndb:mt-0.5 ndb:text-[11px] ndb:text-zinc-400">Across {{ $modelScope }}</p>
                            </div>
                        </div>

                        <dl class="ndb:mt-3 ndb:grid ndb:grid-cols-3 ndb:gap-x-3">
                            @foreach ([
                                ['Retrieved', $retrievalCount],
                                ['Writes', $changeCount],
                                ['Extra', $repeatCount],
                            ] as [$label, $value])
                                <div class="ndb:min-w-0">
                                    <dt class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">{{ $label }}</dt>
                                    <dd @class([
                                        'ndb:mt-0.5 ndb:text-xs ndb:font-bold ndb:tabular-nums',
                                        'ndb:text-amber-700 ndb:dark:text-amber-300' => $label === 'Extra' && $value > 0,
                                    ])>
                                        {{ number_format($value) }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>

                        <p class="ndb:mt-3 ndb:text-[11px] ndb:leading-4 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                            Counts describe Eloquent events, not database rows or queries.
                        </p>
                    </section>
                </x-slot:controls>

                <x-slot:list
                    data-ndb-model-list
                    class="ndb:border-l-0 ndb:bg-transparent ndb:p-0 ndb:text-xs ndb:text-zinc-950 ndb:dark:text-white"
                >
                    <div
                        data-ndb-model-list-heading
                        aria-hidden="true"
                        class="ndb:sticky ndb:top-0 ndb:z-10 ndb:hidden ndb:grid-cols-[minmax(8rem,1fr)_4rem_3rem_3.75rem] ndb:gap-2 ndb:border-l-0 ndb:border-b ndb:border-zinc-200/90 ndb:bg-white/95 ndb:px-3 ndb:py-2 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400 ndb:backdrop-blur-sm ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/95 ndb:sm:grid"
                    >
                        <span>Model</span>
                        <span class="ndb:text-right">Retrieved</span>
                        <span class="ndb:text-right">Writes</span>
                        <span class="ndb:text-right" title="Extra retrievals of identified records">Extra</span>
                    </div>

                    @foreach ($modelGroups as $index => $group)
                        <x-newdebugbar::model-group :group="$group" :index="$index" />
                    @endforeach
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
                    label="Choose a model to inspect its activity."
                    x-show.important="modelSelected === null"
                />
            </x-newdebugbar::inspector-detail-pane>
        </x-newdebugbar::inspector-workspace>
    @else
        <x-newdebugbar::empty-state label="No Eloquent model activity was captured for this request." />
    @endif
</div>
