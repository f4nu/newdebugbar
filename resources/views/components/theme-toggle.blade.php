@props(['darkSurface' => false])

<x-newdebugbar::icon-button
    :dark-surface="$darkSurface"
    @click="toggleTheme()"
    {{ $attributes->class('ndb:size-9 ndb:rounded-xl') }}
    ::aria-label="resolvedTheme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'"
    ::title="resolvedTheme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'"
>
    <span
        x-show.important="resolvedTheme !== 'dark'"
        class="ndb:flex ndb:items-center ndb:justify-center ndb:leading-none"
        ><x-newdebugbar::icon name="moon" class="ndb:size-4" /></span
    ><span
        x-show.important="resolvedTheme === 'dark'"
        class="ndb:flex ndb:items-center ndb:justify-center ndb:leading-none"
        ><x-newdebugbar::icon name="sun" class="ndb:size-4" /></span
></x-newdebugbar::icon-button>
