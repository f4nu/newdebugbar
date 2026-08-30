@props([
    'label' => null,
    'tone' => 'default',
])

@php
    [$termClasses, $valueClasses] = match ($tone) {
        'default' => [
            'ndb:text-zinc-700 ndb:dark:text-zinc-200',
            'ndb:text-zinc-600 ndb:dark:text-zinc-300',
        ],
        'danger' => [
            'ndb:text-red-700 ndb:dark:text-red-300',
            'ndb:text-red-700 ndb:dark:text-red-300',
        ],
        default => throw new \InvalidArgumentException("Unknown inspector definition tone [{$tone}]."),
    };
@endphp

<div {{ $attributes->class('ndb:grid ndb:gap-1 ndb:py-2.5 ndb:first:pt-0 ndb:sm:grid-cols-[8rem_minmax(0,1fr)] ndb:sm:gap-4 ndb:sm:py-3') }}>
    @isset($term)
        <dt {{ $term->attributes->class("ndb:text-xs ndb:font-bold {$termClasses}") }}>{{ $term }}</dt>
    @else
        <dt class="ndb:text-xs ndb:font-bold {{ $termClasses }}">{{ $label }}</dt>
    @endisset
    @isset($value)
        <dd {{ $value->attributes->class("ndb:text-xs ndb:leading-5 {$valueClasses}") }}>{{ $value }}</dd>
    @else
        <dd class="ndb:text-xs ndb:leading-5 {{ $valueClasses }}">{{ $slot }}</dd>
    @endisset
</div>
