{{-- Renders notification channel outcomes and links mail-channel previews to Mail. --}}
@php
    $notificationItems = $section['payload']['items'] ?? [];
    $statusCounts = collect($notificationItems)->countBy(static fn (array $item): string => (string) ($item['status'] ?? 'sent'));
    $pendingCount = collect(['queued', 'delayed', 'processing', 'waiting'])->sum(
        static fn (string $status): int => (int) ($statusCounts[$status] ?? 0),
    );
    $statusLabels = [
        'queued' => 'Queued',
        'delayed' => 'Delayed',
        'processing' => 'Processing',
        'sent' => 'Sent',
        'failed' => 'Failed',
        'waiting' => 'Waiting for worker',
    ];
    $statusClasses = [
        'queued' => 'ndb:bg-sky-100 ndb:text-sky-700 ndb:dark:bg-sky-950 ndb:dark:text-sky-300',
        'delayed' => 'ndb:bg-amber-100 ndb:text-amber-700 ndb:dark:bg-amber-950 ndb:dark:text-amber-300',
        'processing' => 'ndb:bg-indigo-100 ndb:text-indigo-700 ndb:dark:bg-indigo-950 ndb:dark:text-indigo-300',
        'sent' => 'ndb:bg-emerald-100 ndb:text-emerald-700 ndb:dark:bg-emerald-950 ndb:dark:text-emerald-300',
        'failed' => 'ndb:bg-red-100 ndb:text-red-700 ndb:dark:bg-red-950 ndb:dark:text-red-300',
        'waiting' => 'ndb:bg-amber-100 ndb:text-amber-700 ndb:dark:bg-amber-950 ndb:dark:text-amber-300',
    ];
@endphp

<dl class="ndb:grid ndb:grid-cols-3 ndb:divide-x ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
    @foreach ([['Sent', $statusCounts['sent'] ?? 0], ['Pending', $pendingCount], ['Failed', $statusCounts['failed'] ?? 0]] as [$label, $value])
        <div class="ndb:px-3.5 ndb:py-3">
            <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                {{ $label }}
            </dt>
            <dd class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $value }}</dd>
        </div>
    @endforeach
</dl>

<div class="ndb:space-y-2">
    @forelse ($notificationItems as $index => $item)
        @php
            $status = (string) ($item['status'] ?? 'sent');
            $isOrigin = (bool) ($item['is_origin'] ?? false);
            $relatedProfileId = $isOrigin ? ($item['worker_profile_id'] ?? null) : ($item['origin_profile_id'] ?? null);
            $relatedProfileId = is_string($relatedProfileId) && $relatedProfileId !== $profileId ? $relatedProfileId : null;
            $mailChannel = ($item['channel'] ?? null) === 'mail';
            $relatedSection = $isOrigin && $mailChannel && $status === 'sent' ? 'mail' : 'notifications';
            $relatedLabel = $isOrigin && $relatedSection === 'mail'
                ? 'Open mail preview'
                : ($isOrigin ? 'Open worker' : 'Open request');
            $notifiableTypes = array_values((array) ($item['notifiable_types'] ?? []));
            $notifiableType = $item['notifiable_type'] ?? ($notifiableTypes[0] ?? null);
        @endphp
        <article
            wire:key="notification-{{ $item['correlation_key'] ?? $index }}-{{ $index }}"
            data-ndb-notification-item
            data-ndb-notification-status="{{ $status }}"
            class="ndb:min-w-0 ndb:rounded-xl ndb:border ndb:p-3.5 {{ $status === 'failed' ? 'ndb:border-red-200 ndb:bg-red-50/35 ndb:dark:border-red-950 ndb:dark:bg-red-950/15' : 'ndb:border-zinc-200 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30' }}"
        >
            <div class="ndb:flex ndb:min-w-0 ndb:items-start ndb:gap-3">
                <span class="ndb:shrink-0 ndb:rounded-md ndb:px-2 ndb:py-1 ndb:text-[11px] ndb:font-bold {{ $statusClasses[$status] ?? 'ndb:bg-zinc-100 ndb:text-zinc-600 ndb:dark:bg-zinc-900 ndb:dark:text-zinc-300' }}">
                    {{ $statusLabels[$status] ?? ucfirst($status) }}
                </span>
                <div class="ndb:min-w-0 ndb:flex-1">
                    <code title="{{ $item['notification'] }}" class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold">
                        {{ class_basename($item['notification']) }}
                    </code>
                    <p class="ndb:mt-1.5 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:gap-y-1 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
                        <span>{{ $item['channel'] ?: 'Channels resolved by worker' }}</span>
                        @if (is_string($notifiableType) && $notifiableType !== '')
                            <span title="{{ $notifiableType }}">{{ class_basename($notifiableType) }}</span>
                        @endif
                        @if (($item['notifiable_count'] ?? 0) > 0)
                            <span>{{ $item['notifiable_count'] }} {{ \Illuminate\Support\Str::plural('notifiable', $item['notifiable_count']) }}</span>
                        @endif
                        @if (($item['queue'] ?? null) !== null)
                            <span>{{ $item['connection'] }} · {{ $item['queue'] ?: 'default queue' }}</span>
                        @endif
                        @if (($item['delay_seconds'] ?? null) > 0)
                            <span>{{ $item['delay_seconds'] }} s delay</span>
                        @endif
                        @if (($item['lifecycle'] ?? null) === 'after_response')
                            <span class="ndb:text-indigo-500 ndb:dark:text-indigo-300">After response</span>
                        @endif
                    </p>
                </div>
            </div>

            @if ($relatedProfileId !== null)
                <div class="ndb:mt-3 ndb:flex ndb:justify-end ndb:border-t ndb:border-zinc-200/80 ndb:pt-3 ndb:dark:border-zinc-800">
                    <button
                        type="button"
                        data-ndb-notification-profile-link
                        @click="openRelatedProfile({{ \Illuminate\Support\Js::from($relatedProfileId) }}, {{ \Illuminate\Support\Js::from($relatedSection) }})"
                        class="ndb:inline-flex ndb:h-8 ndb:items-center ndb:gap-1.5 ndb:rounded-lg ndb:bg-indigo-50 ndb:px-2.5 ndb:text-[11px] ndb:font-bold ndb:text-indigo-700 ndb:transition ndb:hover:bg-indigo-100 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500 ndb:dark:bg-indigo-950/55 ndb:dark:text-indigo-300 ndb:dark:hover:bg-indigo-950"
                    >
                        {{ $relatedLabel }}
                        <x-newdebugbar::icon name="external-link" size="3" />
                    </button>
                </div>
            @endif
        </article>
    @empty
        <x-newdebugbar::empty-state label="No notifications were sent or queued." />
    @endforelse
</div>
