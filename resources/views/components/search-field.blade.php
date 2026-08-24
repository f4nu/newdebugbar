@props([
    'label',
    'placeholder' => 'Search',
    'iconPosition' => 'left',
])

@php
    [$inputClasses, $iconClasses, $iconSize] = match ($iconPosition) {
        'left' => [
            'ndb:pr-3 ndb:pl-8',
            'ndb:left-2.5',
            '4',
        ],
        'right' => [
            'ndb:pr-9 ndb:pl-3',
            'ndb:right-3',
            '3.5',
        ],
        default => throw new \InvalidArgumentException("Unknown search icon position [{$iconPosition}]."),
    };
@endphp

<label class="ndb:relative ndb:block ndb:min-w-0">
    <span class="ndb:sr-only">{{ $label }}</span>
    <input
        type="search"
        placeholder="{{ $placeholder }}"
        {{ $attributes->class("ndb:h-9 ndb:w-full ndb:rounded-lg ndb:border ndb:border-zinc-200 ndb:bg-white/70 ndb:text-xs ndb:outline-none ndb:transition ndb:placeholder:text-zinc-400 ndb:focus:border-indigo-400 ndb:focus:ring-2 ndb:focus:ring-indigo-500/15 ndb:dark:border-zinc-700 ndb:dark:bg-zinc-900/70 {$inputClasses}") }}
    />
    <x-newdebugbar::icon
        name="search"
        :size="$iconSize"
        class="ndb:pointer-events-none ndb:absolute ndb:top-1/2 ndb:-translate-y-1/2 ndb:text-zinc-400 {{ $iconClasses }}"
    />
</label>
