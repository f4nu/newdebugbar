@props([
    'id',
    'menu',
    'label',
    'align' => 'center',
    'width' => 'facts',
])

@php
    $positionClass = match ($align) {
        'center' => 'ndb:left-1/2 ndb:-translate-x-1/2',
        'end' => 'ndb:right-0',
        default => throw new InvalidArgumentException("Unsupported mobile toolbar popover alignment [{$align}]."),
    };

    $arrowPositionClass = match ($align) {
        'center' => 'ndb:left-1/2 ndb:-translate-x-1/2',
        'end' => 'ndb:right-[15px]',
    };

    $widthClass = match ($width) {
        'facts' => 'ndb:w-[min(20rem,calc(100vw-32px))]',
        'actions' => 'ndb:w-64',
        default => throw new InvalidArgumentException("Unsupported mobile toolbar popover width [{$width}]."),
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
    :class="toolbarPlacement === 'top'
        ? 'ndb:top-[calc(100%+0.75rem)] ndb:origin-top'
        : 'ndb:bottom-[calc(100%+0.75rem)] ndb:origin-bottom'"
    class="ndb:absolute ndb:z-50 {{ $positionClass }} {{ $widthClass }}"
>
    <span
        aria-hidden="true"
        data-ndb-mobile-toolbar-popover-arrow="{{ $menu }}"
        :class="toolbarPlacement === 'top'
            ? 'ndb:-top-[7px] ndb:border-l ndb:border-t'
            : 'ndb:-bottom-[7px] ndb:border-r ndb:border-b'"
        class="ndb:pointer-events-none ndb:absolute ndb:z-0 ndb:size-3.5 ndb:rotate-45 ndb:border-zinc-300/90 ndb:bg-white/95 {{ $arrowPositionClass }} ndb:dark:border-zinc-700/90 ndb:dark:bg-zinc-900/95"
    ></span>

    <div
        data-ndb-mobile-toolbar-popover-surface
        class="ndb:relative ndb:z-10 ndb:overflow-hidden ndb:rounded-2xl ndb:border ndb:border-zinc-200/80 ndb:bg-white/95 ndb:p-1.5 ndb:shadow-[0_18px_50px_-16px_rgba(24,24,27,0.45)] ndb:backdrop-blur-xl ndb:dark:border-zinc-700/80 ndb:dark:bg-zinc-900/95 ndb:dark:shadow-[0_18px_50px_-16px_rgba(0,0,0,0.85)]"
    >
        <div class="ndb:divide-y ndb:divide-zinc-200/80 ndb:dark:divide-zinc-700/80">{{ $slot }}</div>
    </div>
</div>
