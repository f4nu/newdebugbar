@props([
    'id',
    'menu',
    'label',
    'direction' => 'dynamic',
])

@php
    $directionClass = match ($direction) {
        'dynamic' => '',
        'below' => 'ndb:top-[calc(100%+0.75rem)] ndb:origin-top',
        'above' => 'ndb:bottom-[calc(100%+0.75rem)] ndb:origin-bottom',
        default => throw new InvalidArgumentException("Unsupported mobile toolbar popover direction [{$direction}]."),
    };

    $arrowDirectionClass = match ($direction) {
        'dynamic' => '',
        'below' => 'ndb:-top-[7px] ndb:rotate-180',
        'above' => 'ndb:-bottom-[7px]',
    };
@endphp

<div
    id="{{ $id }}"
    x-cloak
    x-show.important="mobileToolbarMenu === '{{ $menu }}'"
    x-transition:enter="ndb:transition ndb:duration-150 ndb:ease-out"
    x-transition:enter-start="ndb:scale-95 ndb:opacity-0"
    x-transition:enter-end="ndb:scale-100 ndb:opacity-100"
    x-transition:leave="ndb:transition ndb:duration-100 ndb:ease-in"
    x-transition:leave-start="ndb:scale-100 ndb:opacity-100"
    x-transition:leave-end="ndb:scale-95 ndb:opacity-0"
    role="menu"
    aria-label="{{ $label }}"
    data-ndb-mobile-toolbar-menu="{{ $menu }}"
    @if ($direction === 'dynamic')
        :class="toolbarPlacement === 'top'
            ? 'ndb:top-[calc(100%+0.75rem)] ndb:origin-top'
            : 'ndb:bottom-[calc(100%+0.75rem)] ndb:origin-bottom'"
    @endif
    class="ndb:absolute ndb:right-0 ndb:z-50 ndb:w-64 {{ $directionClass }}"
>
    <span
        aria-hidden="true"
        data-ndb-mobile-toolbar-popover-arrow="{{ $menu }}"
        @if ($direction === 'dynamic')
            :class="toolbarPlacement === 'top' ? 'ndb:-top-[7px] ndb:rotate-180' : 'ndb:-bottom-[7px]'"
        @endif
        class="ndb:pointer-events-none ndb:absolute ndb:right-[14px] ndb:z-20 ndb:h-2 ndb:w-4 {{ $arrowDirectionClass }}"
    >
        <svg viewBox="0 0 16 8" class="ndb:block ndb:h-full ndb:w-full ndb:overflow-visible">
            <path d="M0 0H16L8 8Z" class="ndb:fill-white/95 ndb:dark:fill-zinc-900/95" />
            <path
                d="M0.75 0.5L8 7.75L15.25 0.5"
                fill="none"
                stroke-width="1"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="ndb:stroke-zinc-300/90 ndb:dark:stroke-zinc-700/90"
            />
        </svg>
    </span>

    <div
        data-ndb-mobile-toolbar-popover-surface
        class="ndb:relative ndb:z-10 ndb:overflow-hidden ndb:rounded-2xl ndb:border ndb:border-zinc-200/80 ndb:bg-white/95 ndb:p-1.5 ndb:shadow-[0_18px_50px_-16px_rgba(24,24,27,0.45)] ndb:backdrop-blur-xl ndb:dark:border-zinc-700/80 ndb:dark:bg-zinc-900/95 ndb:dark:shadow-[0_18px_50px_-16px_rgba(0,0,0,0.85)]"
    >
        <div class="ndb:divide-y ndb:divide-zinc-200/80 ndb:dark:divide-zinc-700/80">{{ $slot }}</div>
    </div>
</div>
