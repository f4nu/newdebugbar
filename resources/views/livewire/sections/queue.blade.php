{{-- Renders queue dispatch and worker outcomes with stable origin links. --}}
@php
    $queueItems = $section['payload']['items'] ?? [];
    $statusLabels = [
        'queued' => 'Queued',
        'delayed' => 'Delayed',
        'processing' => 'Processing',
        'sent' => 'Sent',
        'failed' => 'Failed',
        'waiting' => 'Waiting for worker',
        'completed' => 'Completed',
    ];
    $statusDescriptions = [
        'queued' => 'Waiting for worker',
        'delayed' => 'Waiting until the delay ends',
        'processing' => 'The worker is running this job',
        'sent' => 'The communication was sent',
        'failed' => 'The job failed and no retry remains',
        'waiting' => 'A worker retry is still pending',
        'completed' => 'The job completed',
    ];
    $statusClasses = [
        'queued' => 'ndb:bg-sky-100 ndb:text-sky-700 ndb:dark:bg-sky-950 ndb:dark:text-sky-300',
        'delayed' => 'ndb:bg-amber-100 ndb:text-amber-700 ndb:dark:bg-amber-950 ndb:dark:text-amber-300',
        'processing' => 'ndb:bg-indigo-100 ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300',
        'sent' => 'ndb:bg-emerald-100 ndb:text-emerald-700 ndb:dark:bg-emerald-950 ndb:dark:text-emerald-300',
        'completed' => 'ndb:bg-emerald-100 ndb:text-emerald-700 ndb:dark:bg-emerald-950 ndb:dark:text-emerald-300',
        'failed' => 'ndb:bg-red-100 ndb:text-red-700 ndb:dark:bg-red-950 ndb:dark:text-red-300',
        'waiting' => 'ndb:bg-amber-100 ndb:text-amber-700 ndb:dark:bg-amber-950 ndb:dark:text-amber-300',
    ];
@endphp

<div class="ndb:flex ndb:flex-wrap ndb:items-center ndb:justify-between ndb:gap-3">
    <dl class="ndb:grid ndb:min-w-0 ndb:flex-1 ndb:grid-cols-2 ndb:divide-x ndb:divide-y ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:sm:grid-cols-4 ndb:sm:divide-y-0 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
        @foreach ([['Queued', $section['summary']['queued_count']], ['Executed', $section['summary']['executed_count']], ['Failures', $section['summary']['failed_count']], ['Run time', $section['summary']['duration_ms'].' ms']] as [$label, $value])
            <div class="ndb:px-3.5 ndb:py-3">
                <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                    {{ $label }}
                </dt>
                <dd class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>
    @if (($profile['background_activity']['pending'] ?? false) === true)
        <button
            type="button"
            data-ndb-background-refresh
            @click="refreshBackgroundActivity(true)"
            class="ndb:inline-flex ndb:h-9 ndb:shrink-0 ndb:items-center ndb:gap-2 ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/75 ndb:px-3 ndb:text-xs ndb:font-bold ndb:text-zinc-700 ndb:transition ndb:hover:bg-zinc-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/75 ndb:dark:text-zinc-200 ndb:dark:hover:bg-zinc-800"
        >
            <x-newdebugbar::icon name="activity" size="3.5" />
            Check worker
        </button>
    @endif
</div>

<div class="ndb:space-y-2">
    @forelse ($queueItems as $index => $item)
        @php
            $fallbackStatus = match ($item['kind'] ?? null) {
                'failed' => 'failed',
                'executed' => 'completed',
                default => 'queued',
            };
            $status = (string) ($item['status'] ?? $fallbackStatus);
            $statusLabel = $statusLabels[$status] ?? ucfirst($status);
            $statusDescription = $statusDescriptions[$status] ?? 'Queue activity captured';
            $isOrigin = (bool) ($item['is_origin'] ?? false);
            $relatedProfileId = $isOrigin ? ($item['worker_profile_id'] ?? null) : ($item['origin_profile_id'] ?? null);
            $relatedProfileId = is_string($relatedProfileId) && $relatedProfileId !== $profileId ? $relatedProfileId : null;
            $mailChannel = ($item['communication_type'] ?? null) === 'mail'
                || in_array('mail', (array) ($item['channels'] ?? []), true);
            $relatedSection = $isOrigin && $mailChannel && $status === 'sent' ? 'mail' : 'queue';
            $relatedLabel = $isOrigin
                ? ($relatedSection === 'mail' ? 'Open mail preview' : 'Open worker')
                : 'Open request';
        @endphp
        <article
            wire:key="queue-{{ $item['correlation_key'] ?? $index }}-{{ $index }}"
            data-ndb-queue-item
            data-ndb-queue-status="{{ $status }}"
            class="ndb:min-w-0 ndb:rounded-xl ndb:border ndb:p-3.5 {{ $status === 'failed' ? 'ndb:border-red-200 ndb:bg-red-50/35 ndb:dark:border-red-950 ndb:dark:bg-red-950/15' : 'ndb:border-zinc-200 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30' }}"
        >
            <div class="ndb:flex ndb:min-w-0 ndb:items-start ndb:gap-3">
                <span class="ndb:shrink-0 ndb:rounded-md ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-bold {{ $statusClasses[$status] ?? 'ndb:bg-zinc-100 ndb:text-zinc-600 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300' }}">
                    {{ $statusLabel }}
                </span>
                <div class="ndb:min-w-0 ndb:flex-1">
                    <code title="{{ $item['job'] }}" class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold">
                        {{ class_basename($item['job']) }}
                    </code>
                    <p class="ndb:mt-1 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400">
                        {{ $statusDescription }}
                    </p>
                    <p class="ndb:mt-1.5 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:gap-y-1 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
                        <span>{{ $item['connection'] ?: 'default connection' }}</span>
                        <span>{{ $item['queue'] ?: 'default queue' }}</span>
                        @if (($item['job_id'] ?? null) !== null)
                            <span>Job {{ $item['job_id'] }}</span>
                        @endif
                        @if (($item['attempt'] ?? $item['activity_attempt'] ?? null) !== null)
                            <span>Attempt {{ $item['attempt'] ?? $item['activity_attempt'] }}</span>
                        @endif
                        @if (($item['delay_seconds'] ?? null) > 0)
                            <span>{{ $item['delay_seconds'] }} s delay</span>
                        @endif
                        @if (($item['lifecycle'] ?? null) === 'after_response')
                            <span class="ndb:text-indigo-500 ndb:dark:text-indigo-300">After response</span>
                        @endif
                    </p>
                </div>
                @if (($item['kind'] ?? null) !== 'queued')
                    <span class="ndb:shrink-0 ndb:text-xs ndb:font-bold ndb:tabular-nums">{{ $item['duration_ms'] }} ms</span>
                @endif
            </div>

            @if (($item['communication_type'] ?? null) !== null || $relatedProfileId !== null)
                <div class="ndb:mt-3 ndb:flex ndb:flex-wrap ndb:items-center ndb:gap-2 ndb:border-t ndb:border-zinc-200/80 ndb:pt-3 ndb:dark:border-zinc-800">
                    @if (($item['communication_type'] ?? null) !== null)
                        <span class="ndb:text-[11px] ndb:font-semibold ndb:text-zinc-500 ndb:dark:text-zinc-400">
                            {{ ucfirst($item['communication_type']) }}
                            @if (($item['channels'] ?? []) !== [])
                                · {{ implode(', ', $item['channels']) }}
                            @endif
                            @if (($item['notifiable_count'] ?? 0) > 0)
                                · {{ $item['notifiable_count'] }} {{ \Illuminate\Support\Str::plural('notifiable', $item['notifiable_count']) }}
                            @endif
                        </span>
                    @endif
                    @if ($relatedProfileId !== null)
                        <button
                            type="button"
                            data-ndb-queue-profile-link
                            @click="openRelatedProfile({{ \Illuminate\Support\Js::from($relatedProfileId) }}, {{ \Illuminate\Support\Js::from($relatedSection) }})"
                            class="ndb:ml-auto ndb:inline-flex ndb:h-8 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:bg-indigo-50 ndb:px-2.5 ndb:text-[11px] ndb:font-bold ndb:text-indigo-700 ndb:transition ndb:hover:bg-indigo-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:bg-indigo-950/55 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950"
                        >
                            {{ $relatedLabel }}
                            <x-newdebugbar::icon name="external-link" size="3" />
                        </button>
                    @endif
                </div>
            @endif
        </article>
    @empty
        <x-newdebugbar::empty-state label="No queue activity was captured." />
    @endforelse
</div>
