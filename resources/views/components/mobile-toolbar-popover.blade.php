@props([
    'id',
    'menu',
    'label',
    'direction' => 'dynamic',
])

<x-newdebugbar::popover-surface
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
    :direction="$direction"
    :mobile-menu="$menu"
>
    <div data-ndb-mobile-toolbar-popover-items class="ndb:flex ndb:flex-col ndb:gap-0.5">{{ $slot }}</div>
</x-newdebugbar::popover-surface>
