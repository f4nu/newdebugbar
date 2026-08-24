{{-- Summarizes Eloquent activity before model-specific evidence. --}}
@php
    $modelGroups = array_values($section['payload']['model_group_previews'] ?? $section['payload']['model_groups'] ?? []);
    $modelSummary = $section['summary'] ?? [];
    $modelClassCount = (int) ($modelSummary['model_classes'] ?? 0);
    $modelContextCount = (int) ($modelSummary['model_contexts'] ?? count($modelGroups));
    $activityCount = (int) ($modelSummary['activity_count'] ?? 0);
    $retrievalCount = (int) ($modelSummary['retrieval_count'] ?? 0);
    $changeCount = (int) ($modelSummary['model_change_count'] ?? 0);
    $repeatCount = (int) ($modelSummary['repeated_load_count'] ?? 0);
    $intermediateCount = (int) ($modelSummary['intermediate_lifecycle_event_count'] ?? 0);
    $unknownSourceCount = (int) ($modelSummary['unknown_source_activity_count'] ?? 0);
    $plural = static fn (string $word, int $count): string => \Illuminate\Support\Str::plural($word, $count);
    $modelScope = number_format($modelClassCount).' model '.$plural('class', $modelClassCount);

    if ($modelContextCount > $modelClassCount) {
        $modelScope .= ' in '.number_format($modelContextCount).' connection or table contexts';
    }

    $methodology = 'Activity means Eloquent retrieved events plus completed logical writes. It does not mean database rows or queries. Callbacks with a shared operation ID are shown once as one write.';

    if ($intermediateCount > 0) {
        $methodology .= ' '.number_format($intermediateCount).' other lifecycle '.$plural('callback', $intermediateCount).' stay outside the activity total.';
    }

    if ($unknownSourceCount > 0) {
        $methodology .= ' Application source was unavailable for '.number_format($unknownSourceCount).' '.$plural('activity', $unknownSourceCount).'.';
    }
@endphp

<div data-ndb-models class="ndb:space-y-4 ndb:text-zinc-950 ndb:dark:text-white">
    @if ($modelGroups !== [])
        <section
            data-ndb-model-summary
            aria-label="Model activity summary"
            class="ndb:border-b ndb:border-zinc-200/90 ndb:pb-4 ndb:dark:border-zinc-800"
        >
            <div class="ndb:flex ndb:flex-col ndb:gap-3 ndb:sm:flex-row ndb:sm:items-end ndb:sm:justify-between">
                <div>
                    <p class="ndb:text-sm ndb:font-bold ndb:tabular-nums">
                        {{ number_format($activityCount) }} Eloquent {{ $plural('activity', $activityCount) }}
                    </p>
                    <p class="ndb:mt-0.5 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        Across {{ $modelScope }}.
                    </p>
                </div>

                <dl class="ndb:grid ndb:grid-cols-3 ndb:gap-x-5 ndb:sm:shrink-0">
                    @foreach ([
                        ['Retrieved', $retrievalCount, false],
                        ['Writes', $changeCount, false],
                        ['Extra retrievals', $repeatCount, $repeatCount > 0],
                    ] as [$label, $value, $attention])
                        <div class="ndb:min-w-0">
                            <dt class="ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                                {{ $label }}
                            </dt>
                            <dd @class([
                                'ndb:mt-0.5 ndb:text-xs ndb:font-bold ndb:tabular-nums',
                                'ndb:text-amber-700 ndb:dark:text-amber-300' => $attention,
                            ])>
                                {{ number_format($value) }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <p class="ndb:mt-3 ndb:max-w-4xl ndb:text-[11px] ndb:leading-5 ndb:text-zinc-500 ndb:dark:text-zinc-400">
                {{ $methodology }}
            </p>
        </section>

        <section aria-label="Models involved">
            <div class="ndb:flex ndb:items-center ndb:justify-between ndb:gap-3 ndb:pb-2">
                <h3 class="ndb:text-xs ndb:font-bold">Models involved</h3>
                <span class="ndb:shrink-0 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400">
                    {{ number_format($modelContextCount) }} {{ $plural('context', $modelContextCount) }}
                </span>
            </div>

            <div
                data-ndb-model-list-heading
                aria-hidden="true"
                class="ndb:hidden ndb:grid-cols-[minmax(10rem,1.35fr)_5.5rem_4.75rem_7rem_minmax(8rem,1fr)_1rem] ndb:gap-3 ndb:border-t ndb:border-zinc-200/90 ndb:px-4 ndb:py-2 ndb:text-[10px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400 ndb:dark:border-zinc-800 ndb:sm:grid"
            >
                <span>Model</span>
                <span class="ndb:text-right">Retrieved</span>
                <span class="ndb:text-right">Writes</span>
                <span class="ndb:text-right">Extra retrievals</span>
                <span>Source</span>
                <span></span>
            </div>

            <div
                data-ndb-model-list
                class="ndb:divide-y ndb:divide-zinc-200/90 ndb:border-y ndb:border-zinc-200/90 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800 ndb:sm:border-t-0"
            >
                @foreach ($modelGroups as $index => $group)
                    <x-newdebugbar::model-group :group="$group" :index="$index" />
                @endforeach
            </div>
        </section>
    @else
        <x-newdebugbar::empty-state label="No Eloquent model activity was captured for this request." />
    @endif
</div>
