{{-- Renders the request overview and runtime details. --}}
@php($overview = app(\NewDebugBar\Presentation\OverviewPresenter::class)->present($profile, $summary['sections'] ?? []))
@php($activitySections = $overview['activity'])

<div class="ndb:flex ndb:min-h-0 ndb:flex-1 ndb:flex-col">
    <x-newdebugbar::inspector-workspace mode="stream" frame="top" data-ndb-overview-workspace>
        <x-slot:body>
            @if ($activitySections !== [])
                <section data-ndb-overview-activity class="ndb:px-4 ndb:pt-3">
                    <div class="ndb:pb-3">
                        <h3 class="ndb:text-xs ndb:font-bold">Open the activity that matters most</h3>
                        <p class="ndb:mt-0.5 ndb:text-[11px] ndb:text-zinc-500 ndb:dark:text-zinc-400">
                            Start with a section marked Review; otherwise, choose the activity you want to inspect.
                        </p>
                    </div>
                    <div class="ndb:border-t ndb:border-zinc-200/90 ndb:dark:border-zinc-800">
                        @foreach ($activitySections as $link)
                            <button
                                type="button"
                                data-ndb-overview-activity-section="{{ $link['key'] }}"
                                @click="navigateToSection(@js($link['key']))"
                                class="ndb:grid ndb:w-full ndb:grid-cols-[minmax(0,1fr)_auto] ndb:items-center ndb:gap-x-3 ndb:border-b ndb:border-zinc-200/90 ndb:py-3 ndb:text-left ndb:transition-colors ndb:hover:text-indigo-700 ndb:focus-visible:outline-2 ndb:focus-visible:outline-inset ndb:focus-visible:outline-indigo-500 ndb:sm:grid-cols-[9rem_minmax(0,1fr)_auto] ndb:dark:border-zinc-800 ndb:dark:hover:text-indigo-300"
                            >
                                <span class="ndb:col-start-1 ndb:row-start-1 ndb:min-w-0 ndb:truncate ndb:text-xs ndb:font-bold">
                                    {{ $link['label'] }}
                                </span>
                                <span class="ndb:col-start-1 ndb:row-start-2 ndb:min-w-0 ndb:text-[11px] ndb:leading-4 ndb:text-zinc-500 ndb:sm:col-start-2 ndb:sm:row-start-1 ndb:dark:text-zinc-400">
                                    {{ $link['description'] }}
                                </span>
                                @if ($link['attention'] ?? false)
                                    <span
                                        data-ndb-overview-activity-review
                                        class="ndb:col-start-2 ndb:row-span-2 ndb:row-start-1 ndb:self-center ndb:text-[11px] ndb:font-bold ndb:text-amber-600 ndb:sm:col-start-3 ndb:sm:row-span-1 ndb:dark:text-amber-400"
                                    >Review</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </section>
            @endif

            <x-newdebugbar::overview-runtime-details :groups="$overview['runtime']" />
        </x-slot:body>
    </x-newdebugbar::inspector-workspace>
</div>
