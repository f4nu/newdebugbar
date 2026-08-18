{{-- Renders captured Laravel notifications. --}}
<dl class="ndb:grid ndb:grid-cols-2 ndb:divide-x ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200 ndb:dark:divide-zinc-800 ndb:dark:border-zinc-800">
    @foreach ([['Sent', $section['summary']['sent_count']], ['Failed', $section['summary']['failed_count']]] as [$label, $value])
        <div class="ndb:px-3.5 ndb:py-3">
            <dt class="ndb:text-[11px] ndb:font-semibold ndb:uppercase ndb:tracking-wider ndb:text-zinc-400">
                {{ $label }}
            </dt>
            <dd class="ndb:mt-1 ndb:text-lg ndb:font-bold ndb:tabular-nums">{{ $value }}</dd>
        </div>
    @endforeach
</dl>
<div class="ndb:space-y-2">
    @forelse ($section['payload']['items'] as $index => $item)
        <article
            wire:key="notification-{{ $index }}"
            class="ndb:flex ndb:min-w-0 ndb:items-center ndb:gap-3 ndb:rounded-xl ndb:border ndb:px-3.5 ndb:py-3 {{ $item['status'] === 'failed' ? 'ndb:border-red-200 ndb:bg-red-50/35 ndb:dark:border-red-950 ndb:dark:bg-red-950/15' : 'ndb:border-zinc-200 ndb:bg-white/45 ndb:dark:border-zinc-800 ndb:dark:bg-zinc-900/30' }}"
        >
            <span class="ndb:w-12 ndb:shrink-0 ndb:text-[11px] ndb:font-bold ndb:uppercase ndb:tracking-wider {{ $item['status'] === 'failed' ? 'ndb:text-red-600 ndb:dark:text-red-300' : 'ndb:text-emerald-600 ndb:dark:text-emerald-300' }}">{{ $item['status'] }}</span>
            <div class="ndb:min-w-0 ndb:flex-1">
                <code
                    title="{{ $item['notification'] }}"
                    class="ndb:block ndb:truncate ndb:text-xs ndb:font-bold"
                >{{ class_basename($item['notification']) }}</code>
                <p class="ndb:mt-1 ndb:flex ndb:flex-wrap ndb:gap-x-3 ndb:text-[11px] ndb:font-semibold ndb:text-zinc-400">
                    <span>{{ $item['channel'] }}</span
                    ><span title="{{ $item['notifiable_type'] }}">{{ class_basename($item['notifiable_type']) }}</span>
                </p>
            </div>
        </article>
    @empty
        <x-newdebugbar::empty-state label="No notifications were sent." />
    @endforelse
</div>
