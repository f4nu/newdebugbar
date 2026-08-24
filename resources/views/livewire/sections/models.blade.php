{{-- Summarizes Eloquent activity before model-specific evidence. --}}
@php
    $modelGroups = array_values($section['payload']['model_groups'] ?? []);
    $modelSummary = $section['summary'] ?? [];
    $modelClassCount = (int) ($modelSummary['model_classes'] ?? 0);
    $modelContextCount = (int) ($modelSummary['model_contexts'] ?? count($modelGroups));
    $retrievalCount = (int) ($modelSummary['retrieval_count'] ?? 0);
    $changeCount = (int) ($modelSummary['model_change_count'] ?? 0);
    $repeatCount = (int) ($modelSummary['repeated_load_count'] ?? 0);
    $lifecycleCount = (int) ($modelSummary['retained_lifecycle_event_count'] ?? 0);
    $intermediateCount = (int) ($modelSummary['intermediate_lifecycle_event_count'] ?? 0);
    $unknownSourceCount = (int) ($modelSummary['unknown_source_activity_count'] ?? 0);
@endphp

<div data-ndb-models class="ndb:space-y-5 ndb:text-zinc-950 ndb:dark:text-white">
  @if ($modelGroups !== [])
    <section
      data-ndb-model-summary
      aria-label="Model activity summary"
      class="ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-zinc-50/55 ndb:text-xs ndb:text-zinc-950 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/45 ndb:dark:text-white"
    >
      <dl class="ndb:grid ndb:grid-cols-2 ndb:sm:grid-cols-4">
        @foreach ([
                    ['Model classes', $modelClassCount, 'Class names involved in this request'],
                    ['Retrieved instances', $retrievalCount, 'Eloquent retrieved events, not database rows'],
                    ['Write operations', $changeCount, 'Completed logical model changes'],
                    ['Extra retrievals', $repeatCount, 'Loads after an identified record was first retrieved'],
                ] as $metricIndex => [$label, $value, $description])
          <div
            @class([
                'ndb:min-w-0 ndb:border-zinc-200/90 ndb:px-3 ndb:py-3 ndb:dark:border-zinc-800',
                'ndb:border-l' => in_array($metricIndex, [1, 3], true),
                'ndb:border-t' => $metricIndex >= 2,
                'ndb:sm:border-t-0 ndb:sm:border-l' => $metricIndex === 2,
            ])
          >
            <dt
              class="ndb:text-[10px] ndb:font-bold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400"
            >
              {{ $label }}
            </dt>
            <dd
              class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:leading-none ndb:tabular-nums ndb:text-zinc-950 ndb:dark:text-white"
            >
              {{ number_format($value) }}
            </dd>
            <span class="ndb:sr-only">{{ $description }}</span>
          </div>
        @endforeach
      </dl>
      <div
        class="ndb:border-t ndb:border-zinc-200/90 ndb:px-3 ndb:py-2.5 ndb:text-[11px] ndb:leading-5 ndb:text-zinc-500 ndb:dark:border-zinc-800 ndb:dark:text-zinc-400"
      >
        <p>Retrieved instances count Eloquent <code class="ndb:font-mono ndb:font-semibold">retrieved</code> events. They are not query counts or database row counts. One query can retrieve many model instances.</p>
        @if ($intermediateCount > 0)
          <p class="ndb:mt-1">
            {{ number_format($lifecycleCount) }} lifecycle callbacks were
            captured. {{ number_format($intermediateCount) }} intermediate {{ \Illuminate\Support\Str::plural('callback', $intermediateCount) }} were
            folded into {{ number_format($changeCount) }} logical write {{ \Illuminate\Support\Str::plural('operation', $changeCount) }}.
          </p>
        @endif
      </div>
    </section>

    @if ($modelContextCount > $modelClassCount)
      <p
        data-ndb-model-context-note
        class="ndb:rounded-lg ndb:border ndb:border-zinc-200/90 ndb:bg-white/60 ndb:px-3 ndb:py-2 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/40 ndb:dark:text-zinc-400"
      >
        {{ number_format($modelClassCount) }} model {{ \Illuminate\Support\Str::plural('class', $modelClassCount) }} appeared
        in {{ number_format($modelContextCount) }} connection or table contexts.
        Contexts stay separate so their counts are not mixed.
      </p>
    @endif

    @if ($unknownSourceCount > 0)
      <p
        data-ndb-model-source-gap
        role="status"
        class="ndb:rounded-lg ndb:border ndb:border-amber-200 ndb:bg-amber-50/60 ndb:px-3 ndb:py-2 ndb:text-[11px] ndb:font-semibold ndb:text-amber-800 ndb:dark:border-amber-950 ndb:dark:bg-amber-950/25 ndb:dark:text-amber-300"
      >The application source was unavailable for {{ number_format($unknownSourceCount) }} model {{ \Illuminate\Support\Str::plural('activity', $unknownSourceCount) }}. Counts remain complete, but that activity cannot be traced to an app file.</p>
    @endif

    <section aria-label="Models involved" class="ndb:space-y-2">
      <div class="ndb:flex ndb:items-end ndb:justify-between ndb:gap-3">
        <div>
          <h3 class="ndb:text-xs ndb:font-bold">Models involved</h3>
          <p class="ndb:mt-0.5 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">Writes appear first, followed by classes with repeated retrievals.</p>
        </div>
        <span
          class="ndb:shrink-0 ndb:text-[11px] ndb:font-semibold ndb:tabular-nums ndb:text-zinc-400"
        >
          {{ number_format($modelContextCount) }} {{ \Illuminate\Support\Str::plural('context', $modelContextCount) }}
        </span>
      </div>

      <div class="ndb:space-y-2">
        @foreach ($modelGroups as $index => $group)
          <x-newdebugbar::model-group :group="$group" :index="$index" />
        @endforeach
      </div>
    </section>
  @else
    <x-newdebugbar::empty-state
      label="No Eloquent model activity was captured for this request."
    />
  @endif
</div>
