@props([
    'anchored' => false,
    'direction' => 'below',
    'widthClass' => 'ndb:w-64',
    'surfaceClass' => 'ndb:p-1.5',
    'arrowClass' => 'ndb:right-[14px]',
    'mobileMenu' => null,
    'align' => 'right',
])

@php
    $directionClass = $anchored ? '' : match ($direction) {
        'dynamic' => '',
        'below' => 'ndb:top-[calc(100%+0.75rem)] ndb:origin-top',
        'above' => 'ndb:bottom-[calc(100%+0.75rem)] ndb:origin-bottom',
        default => throw new InvalidArgumentException("Unsupported popover direction [{$direction}]."),
    };

    $arrowDirectionClass = $anchored ? '' : match ($direction) {
        'dynamic' => '',
        'below' => 'ndb:-top-[7px] ndb:rotate-180',
        'above' => 'ndb:-bottom-[7px]',
    };

    $alignmentClass = $anchored ? '' : match ($align) {
        'left' => 'ndb:left-0',
        'right' => 'ndb:right-0',
        'dynamic' => '',
        default => throw new InvalidArgumentException("Unsupported popover alignment [{$align}]."),
    };
@endphp

<div
    @if (! $anchored && $direction === 'dynamic' && $align === 'dynamic')
        :class="[
            toolbarIsTop
                ? 'ndb:top-[calc(100%+0.75rem)] ndb:origin-top'
                : 'ndb:bottom-[calc(100%+0.75rem)] ndb:origin-bottom',
            toolbarIsRight ? 'ndb:right-0' : 'ndb:left-0',
        ]"
    @elseif (! $anchored && $direction === 'dynamic')
        :class="toolbarIsTop
            ? 'ndb:top-[calc(100%+0.75rem)] ndb:origin-top'
            : 'ndb:bottom-[calc(100%+0.75rem)] ndb:origin-bottom'"
    @elseif (! $anchored && $align === 'dynamic')
        :class="toolbarIsRight ? 'ndb:right-0' : 'ndb:left-0'"
    @endif
    {{ $attributes->class([
        "ndb:z-50 {$alignmentClass} {$widthClass} {$directionClass}",
        'ndb:absolute' => ! $anchored,
    ]) }}
>
    <span
        aria-hidden="true"
        data-ndb-popover-arrow
        @if ($mobileMenu !== null) data-ndb-mobile-toolbar-popover-arrow="{{ $mobileMenu }}" @endif
        @if (! $anchored && $direction === 'dynamic')
            :class="toolbarIsTop ? 'ndb:-top-[7px] ndb:rotate-180' : 'ndb:-bottom-[7px]'"
        @endif
        class="ndb:pointer-events-none ndb:absolute ndb:z-20 ndb:h-2 ndb:w-4 {{ $arrowClass }} {{ $arrowDirectionClass }}"
    >
        <svg viewBox="0 0 16 8" class="ndb:block ndb:h-full ndb:w-full ndb:overflow-visible">
            <path d="M0 0H16L8 8Z" class="ndb:fill-white/90 ndb:dark:fill-zinc-900/90" />
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
        data-ndb-popover-surface
        @if ($mobileMenu !== null) data-ndb-mobile-toolbar-popover-surface @endif
        class="ndb:relative ndb:z-10 ndb:overflow-hidden ndb:rounded-2xl ndb:border ndb:border-zinc-200/80 ndb:bg-white/90 ndb:shadow-[0_18px_50px_-16px_rgba(24,24,27,0.45)] ndb:backdrop-blur-xl ndb:dark:border-zinc-700/80 ndb:dark:bg-zinc-900/90 ndb:dark:shadow-[0_18px_50px_-16px_rgba(0,0,0,0.85)] {{ $surfaceClass }}"
    >
        {{ $slot }}
    </div>
</div>
