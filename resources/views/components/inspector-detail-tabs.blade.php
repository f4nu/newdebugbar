@props(['label'])

<div {{ $attributes->class('ndb:border-b ndb:border-zinc-200/90 ndb:px-4 ndb:py-2.5 ndb:dark:border-zinc-800') }}>
    <x-newdebugbar::filter-tabs :label="$label" class="ndb:min-w-0"> {{ $slot }} </x-newdebugbar::filter-tabs>
</div>
