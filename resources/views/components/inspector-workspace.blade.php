@props([
    'mode' => 'split',
    'detailOpen' => 'false',
    'detailId' => null,
    'detailRef' => 'workspaceDetail',
    'detailLabel' => 'Selected item details',
    'backLabel' => 'Items',
    'closeAction' => '',
])

@php
    $workspaceClass = match ($mode) {
        'split' => 'ndb:overflow-hidden ndb:rounded-xl ndb:border ndb:border-zinc-200/90 ndb:bg-white/45 ndb:lg:grid ndb:lg:min-h-0 ndb:lg:flex-1 ndb:lg:grid-cols-[minmax(18rem,0.72fr)_minmax(0,1.68fr)] ndb:dark:border-zinc-800 ndb:dark:bg-zinc-950/35',
        'focus' => 'ndb:min-h-0 ndb:min-w-0 ndb:flex-1',
        default => throw new \InvalidArgumentException("Unknown inspector workspace mode [{$mode}]."),
    };

    if ($mode === 'focus' && (! is_string($detailId) || ! str_starts_with($detailId, 'newdebugbar'))) {
        throw new \InvalidArgumentException('Focused inspector workspaces require a namespaced detail ID.');
    }
@endphp

<div {{ $attributes->class($workspaceClass) }}>
    @if ($mode === 'focus')
        <div
            x-cloak
            x-show.important="! ({{ $detailOpen }})"
            data-ndb-inspector-focus-list
            {{ $list->attributes->class('ndb:min-w-0') }}
        >
            {{ $list }}
        </div>

        <section
            id="{{ $detailId }}"
            x-cloak
            x-show.important="{{ $detailOpen }}"
            x-ref="{{ $detailRef }}"
            data-ndb-inspector-focus-detail
            aria-live="polite"
            aria-label="{{ $detailLabel }}"
            tabindex="-1"
            {{ $detail->attributes->class('ndb:min-w-0 ndb:focus-visible:outline-2 ndb:focus-visible:outline-indigo-500') }}
        >
            <x-newdebugbar::inspector-detail-back
                persistent
                data-ndb-inspector-focus-back
                @click="{{ $closeAction }}"
                :label="$backLabel"
            />

            {{ $detail }}
        </section>
    @else
        {{ $slot }}
    @endif
</div>
