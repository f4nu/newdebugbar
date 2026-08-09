@props([
    'darkSurface' => false,
])

<div role="group" aria-label="Window controls" {{ $attributes->class('ndb:flex ndb:items-center') }}>
    <x-newdebugbar::icon-button
        name="expand"
        icon-class="ndb:size-4 ndb:translate-x-2"
        :dark-surface="$darkSurface"
        :color-only="true"
        data-ndb-window-action="expand"
        @click="openInspector()"
        x-bind:disabled="inspectorOpen"
        ::aria-label="inspectorOpen ? 'Expand debug bar (already expanded)' : 'Expand debug bar'"
        ::title="inspectorOpen ? 'Already expanded' : 'Expand debug bar'"
        class="ndb:size-8 ndb:rounded-lg"
    />
    <x-newdebugbar::icon-button
        name="shrink"
        :dark-surface="$darkSurface"
        :color-only="true"
        data-ndb-window-action="shrink"
        @click="closeInspector()"
        x-bind:disabled="! inspectorOpen"
        ::aria-label="inspectorOpen ? 'Shrink debug bar' : 'Shrink debug bar (already compact)'"
        ::title="inspectorOpen ? 'Shrink debug bar' : 'Already compact'"
        class="ndb:size-8 ndb:rounded-lg"
    />
    <x-newdebugbar::icon-button
        name="close"
        icon-class="ndb:size-4 ndb:-translate-x-2"
        :dark-surface="$darkSurface"
        :color-only="true"
        data-ndb-window-action="close"
        @click="dismissBar()"
        class="ndb:size-8 ndb:rounded-lg"
        aria-label="Close debug bar until reload"
        title="Close debug bar until reload"
    />
</div>
