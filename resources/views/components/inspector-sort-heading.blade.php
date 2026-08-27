@props([
    'label',
    'active',
    'direction',
    'align' => 'left',
])

@php
    if (! in_array($align, ['left', 'right'], true)) {
        throw new \InvalidArgumentException("Unknown inspector sort heading alignment [{$align}].");
    }
@endphp

<button
    type="button"
    x-bind:aria-pressed="{{ $active }}"
    x-bind:class="{{ $active }}
        ? 'ndb:text-indigo-600 ndb:dark:text-indigo-300'
        : 'ndb:text-zinc-400 ndb:hover:text-zinc-600 ndb:dark:hover:text-zinc-300'"
    {{
        $attributes->class([
            'ndb:inline-flex ndb:min-h-5 ndb:items-center ndb:gap-1 ndb:transition-colors ndb:focus-visible:rounded ndb:focus-visible:outline-2 ndb:focus-visible:outline-offset-2 ndb:focus-visible:outline-indigo-500',
            'ndb:justify-start' => $align === 'left',
            'ndb:justify-end' => $align === 'right',
        ])
    }}
>
    <span aria-hidden="true" data-ndb-sort-indicator class="ndb:relative ndb:size-3 ndb:shrink-0">
        <x-newdebugbar::icon
            name="chevron-down"
            size="3"
            x-show.important="{{ $active }}"
            x-bind:class="{{ $direction }} === 'asc' ? 'ndb:rotate-180' : ''"
            class="ndb:absolute ndb:inset-0 ndb:transition-transform"
        />
    </span>
    <span aria-hidden="true">{{ $label }}</span>
    <span
        class="ndb:sr-only"
        x-text="{{ $active }}
            ? {{ \Illuminate\Support\Js::from($label) }} + ', sorted ' + ({{ $direction }} === 'asc' ? 'ascending' : 'descending')
            : {{ \Illuminate\Support\Js::from($label) }} + ', not sorted'"
    ></span>
</button>
